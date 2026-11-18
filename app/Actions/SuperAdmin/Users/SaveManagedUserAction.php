<?php

namespace App\Actions\SuperAdmin\Users;

use App\Enums\StaffPosition;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use App\Models\VendorStaff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SaveManagedUserAction
{
    /**
     * @param  array{name: string, username: string|null, email: string, phone_number: string, password?: string, account_type: string, role_id: int, vendor_id?: int|null, position?: string|null}  $data
     */
    public function execute(?User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $attributes = [
                'name' => $data['name'],
                'username' => $data['username'] ?: null,
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'role_id' => $data['role_id'],
            ];

            if (! empty($data['password'])) {
                $attributes['password'] = Hash::make($data['password']);
            }

            $user ??= new User;
            $user->fill($attributes);
            $user->save();

            if ($data['account_type'] === 'client') {
                VendorStaff::query()->where('user_id', $user->id)->delete();
                UserClient::query()->firstOrCreate(['user_id' => $user->id]);

                return $user->fresh(['role', 'userClient']);
            }

            UserClient::query()->where('user_id', $user->id)->delete();
            VendorStaff::query()->where('user_id', $user->id)->delete();
            VendorStaff::query()->create([
                'user_id' => $user->id,
                'vendor_id' => $data['vendor_id'],
                'position' => StaffPosition::from($data['position']),
            ]);

            return $user->fresh(['role', 'vendorStaff.vendor']);
        });
    }

    public function clientRoleId(): int
    {
        return Role::query()->firstOrCreate(['name' => 'client'])->id;
    }
}
