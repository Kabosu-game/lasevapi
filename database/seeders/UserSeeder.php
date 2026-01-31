<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur admin
        User::updateOrCreate(
            ['email' => 'admin@lasev.com'],
            [
                'name' => 'Administrateur',
                'email' => 'admin@lasev.com',
                'password' => Hash::make('password'),
                'date_of_birth' => '1990-01-01',
                'gender' => 'other',
                'role' => 'admin',
                'device_id' => 'admin-device-' . uniqid(),
                'is_premium' => true,
                'premium_expires_at' => now()->addYears(10),
                'email_verified_at' => now(),
            ]
        );

        // Créer des utilisateurs de test
        $users = [
            [
                'name' => 'Jean Dupont',
                'email' => 'jean.dupont@example.com',
                'password' => Hash::make('password'),
                'date_of_birth' => '1985-05-15',
                'gender' => 'male',
                'role' => 'user',
                'device_id' => 'device-' . uniqid(),
                'is_premium' => false,
            ],
            [
                'name' => 'Marie Martin',
                'email' => 'marie.martin@example.com',
                'password' => Hash::make('password'),
                'date_of_birth' => '1992-08-20',
                'gender' => 'female',
                'role' => 'user',
                'device_id' => 'device-' . uniqid(),
                'is_premium' => true,
                'premium_expires_at' => now()->addMonths(6),
            ],
            [
                'name' => 'Pierre Dubois',
                'email' => 'pierre.dubois@example.com',
                'password' => Hash::make('password'),
                'date_of_birth' => '1988-12-10',
                'gender' => 'male',
                'role' => 'user',
                'device_id' => 'device-' . uniqid(),
                'is_premium' => false,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}

