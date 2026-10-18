<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->reorder(['id', 'name', 'username']);
    }

    public function down(): void
    {
        $columns = array_values(array_diff(Schema::getColumnListing('users'), ['username']));
        $this->reorder([...$columns, 'username']);
    }

    /** @param list<string> $firstColumns */
    private function reorder(array $firstColumns): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                $after = array_search('username', $firstColumns, true);
                Schema::table('users', function (Blueprint $table) use ($firstColumns, $after): void {
                    $table->string('username', 50)->nullable()->after($firstColumns[$after - 1])->change();
                });
            }

            return;
        }

        DB::transaction(function () use ($firstColumns): void {
            DB::statement('LOCK TABLE users IN ACCESS EXCLUSIVE MODE');
            $metadata = DB::selectOne("SELECT pg_get_userbyid(relowner) AS owner, obj_description(oid) AS comment, CASE WHEN relrowsecurity OR relforcerowsecurity THEN 1 ELSE 0 END AS has_rls FROM pg_class WHERE oid = 'users'::regclass");

            if ((int) $metadata->has_rls !== 0 || DB::selectOne("SELECT to_regclass('_users_reordered') AS relation")->relation !== null) {
                throw new RuntimeException('Pengurutan users dibatalkan: RLS aktif atau tabel sementara sudah ada.');
            }

            $columns = DB::select("SELECT a.attname AS name, format_type(a.atttypid, a.atttypmod) AS type, a.attnotnull AS not_null, a.attidentity AS identity, a.attgenerated AS generated, pg_get_expr(d.adbin, d.adrelid) AS default_value, col_description(a.attrelid, a.attnum) AS comment FROM pg_attribute a LEFT JOIN pg_attrdef d ON d.adrelid = a.attrelid AND d.adnum = a.attnum WHERE a.attrelid = 'users'::regclass AND a.attnum > 0 AND NOT a.attisdropped ORDER BY a.attnum");
            $byName = collect($columns)->keyBy('name');
            $orderedNames = array_values(array_unique([...$firstColumns, ...array_keys($byName->all())]));
            $definitions = [];

            foreach ($orderedNames as $name) {
                $column = $byName[$name];
                if ($column->identity !== '' || $column->generated !== '') {
                    throw new RuntimeException('Pengurutan users dibatalkan: kolom identity/generated memerlukan penanganan khusus.');
                }

                $definitions[] = $this->quoteIdentifier($name).' '.$column->type
                    .($column->not_null ? ' NOT NULL' : '')
                    .($column->default_value !== null ? ' DEFAULT '.$column->default_value : '');
            }

            $constraints = DB::select("SELECT conname AS name, contype AS type, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conrelid = 'users'::regclass");
            $incoming = DB::select("SELECT format('%I.%I', n.nspname, c.relname) AS table_name, k.conname AS name, pg_get_constraintdef(k.oid) AS definition FROM pg_constraint k JOIN pg_class c ON c.oid = k.conrelid JOIN pg_namespace n ON n.oid = c.relnamespace WHERE k.confrelid = 'users'::regclass AND k.conrelid <> 'users'::regclass");
            $indexes = DB::select("SELECT pg_get_indexdef(i.indexrelid) AS definition FROM pg_index i WHERE i.indrelid = 'users'::regclass AND NOT EXISTS (SELECT 1 FROM pg_constraint k WHERE k.conindid = i.indexrelid)");
            $triggers = DB::select("SELECT pg_get_triggerdef(oid) AS definition FROM pg_trigger WHERE tgrelid = 'users'::regclass AND NOT tgisinternal");
            $grants = DB::select("SELECT CASE WHEN x.grantee = 0 THEN 'PUBLIC' ELSE pg_get_userbyid(x.grantee) END AS grantee, x.privilege_type, x.is_grantable FROM pg_class c CROSS JOIN LATERAL aclexplode(COALESCE(c.relacl, acldefault('r', c.relowner))) x WHERE c.oid = 'users'::regclass");
            $sequence = DB::selectOne("SELECT pg_get_serial_sequence('users', 'id') AS name")->name;

            DB::statement('CREATE TABLE _users_reordered ('.implode(', ', $definitions).')');
            $columnList = implode(', ', array_map($this->quoteIdentifier(...), $orderedNames));
            DB::statement('INSERT INTO _users_reordered ('.$columnList.') SELECT '.$columnList.' FROM users');
            $difference = DB::selectOne('SELECT CASE WHEN EXISTS ((SELECT '.$columnList.' FROM users EXCEPT ALL SELECT '.$columnList.' FROM _users_reordered) UNION ALL (SELECT '.$columnList.' FROM _users_reordered EXCEPT ALL SELECT '.$columnList.' FROM users)) THEN 1 ELSE 0 END AS different');

            if ((int) $difference->different !== 0) {
                throw new RuntimeException('Pengurutan users dibatalkan: data salinan tidak cocok.');
            }

            foreach ($incoming as $foreignKey) {
                DB::statement('ALTER TABLE '.$foreignKey->table_name.' DROP CONSTRAINT '.$this->quoteIdentifier($foreignKey->name));
            }

            if ($sequence !== null) {
                DB::statement('ALTER SEQUENCE '.$sequence.' OWNED BY _users_reordered.id');
            }

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE _users_reordered RENAME TO users');

            foreach ($constraints as $constraint) {
                if ($constraint->type === 'n') {
                    $current = DB::selectOne("SELECT conname AS name FROM pg_constraint WHERE conrelid = 'users'::regclass AND contype = 'n' AND pg_get_constraintdef(oid) = ?", [$constraint->definition]);
                    DB::statement('ALTER TABLE users RENAME CONSTRAINT '.$this->quoteIdentifier($current->name).' TO '.$this->quoteIdentifier($constraint->name));

                    continue;
                }

                DB::statement('ALTER TABLE users ADD CONSTRAINT '.$this->quoteIdentifier($constraint->name).' '.$constraint->definition);
            }
            foreach ($indexes as $index) {
                DB::statement($index->definition);
            }
            foreach ($incoming as $foreignKey) {
                DB::statement('ALTER TABLE '.$foreignKey->table_name.' ADD CONSTRAINT '.$this->quoteIdentifier($foreignKey->name).' '.$foreignKey->definition);
            }
            foreach ($triggers as $trigger) {
                DB::statement($trigger->definition);
            }

            DB::statement('ALTER TABLE users OWNER TO '.$this->quoteIdentifier($metadata->owner));
            foreach ($grants as $grant) {
                $grantee = $grant->grantee === 'PUBLIC' ? 'PUBLIC' : $this->quoteIdentifier($grant->grantee);
                DB::statement('GRANT '.$grant->privilege_type.' ON TABLE users TO '.$grantee.($grant->is_grantable ? ' WITH GRANT OPTION' : ''));
            }

            if ($metadata->comment !== null) {
                DB::statement('COMMENT ON TABLE users IS '.DB::getPdo()->quote($metadata->comment));
            }
            foreach ($columns as $column) {
                if ($column->comment !== null) {
                    DB::statement('COMMENT ON COLUMN users.'.$this->quoteIdentifier($column->name).' IS '.DB::getPdo()->quote($column->comment));
                }
            }
        });
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
};
