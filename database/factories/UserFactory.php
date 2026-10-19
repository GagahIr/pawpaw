<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(), // Ditambahkan agar tidak error jika wajib
            'email' => fake()->unique()->safeEmail(),

            // 'email_verified_at' => now(), // <-- BARIS INI KITA HAPUS/KOMENTAR

            'password' => static::$password ??= Hash::make('password'),
            'phone_number' => fake()->phoneNumber(), // Ditambahkan agar tidak error jika wajib
            'remember_token' => Str::random(10),

            // Otomatis membuat Role 'client' sebagai default jika belum ada
            'role_id' => Role::firstOrCreate(['name' => 'client'])->id,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            // Kosongkan saja karena tidak ada kolom email_verified_at
        ]);
    }
}
