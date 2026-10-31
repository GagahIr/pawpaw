<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
        ]);

        User::updateOrCreate(
            [
                'email' => 'superadmin@pawpaw.com',
            ],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password123'),
                'phone_number' => '',
                'role_id' => $role->id,
            ]
        );
    }
}
