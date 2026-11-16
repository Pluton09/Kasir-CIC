<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun kasir contoh hanya untuk development lokal.
        if (! app()->environment('local')) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'kasir@kasir.test'],
            [
                'name' => 'Kasir',
                'password' => Hash::make('password'),
            ],
        );
    }
}
