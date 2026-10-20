<?php

namespace App\Actions\Client;

use App\DTOs\RegisterClientData;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterClientAction
{
    public function execute(RegisterClientData $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $role = Role::where('name', 'client')->firstOrFail();

            $user = User::create([
                'name' => $data->name,
                'username' => $data->username,
                'phone_number' => $data->phoneNumber,
                'email' => $data->email,
                'password' => $data->password,
                'role_id' => $role->id,
            ]);

            UserClient::create(['user_id' => $user->id]);

            if ($data->googleId !== null) {
                $user->forceFill(['google_id' => $data->googleId])->save();
            }

            return $user;
        });

        Auth::guard('client')->login($user);
        session()->regenerate();

        return $user;
    }
}
