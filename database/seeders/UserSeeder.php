<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 5 teszt felhasználó
        User::create([
            'name' => 'Kiss János',
            'email' => 'janos@example.com',
            'password' => Hash::make('Jelszo_2025'),
            'profile_picture' => 'https://via.placeholder.com/150',
        ]);

        User::create([
            'name' => 'Nagy Anna',
            'email' => 'anna@example.com',
            'password' => Hash::make('Jelszo_2025'),
            'profile_picture' => 'https://via.placeholder.com/150',
        ]);

        User::create([
            'name' => 'Kovács Péter',
            'email' => 'peter@example.com',
            'password' => Hash::make('Jelszo_2025'),
            'profile_picture' => 'https://via.placeholder.com/150',
        ]);

        User::create([
            'name' => 'Szabó Éva',
            'email' => 'eva@example.com',
            'password' => Hash::make('Jelszo_2025'),
            'profile_picture' => 'https://via.placeholder.com/150',
        ]);

        User::create([
            'name' => 'Tóth László',
            'email' => 'laszlo@example.com',
            'password' => Hash::make('Jelszo_2025'),
            'profile_picture' => 'https://via.placeholder.com/150',
        ]);
    }
}
