<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $seeds = [
            [
                'email' => 'admin@example.com',
                'name' => 'Admin User',
                'password' => 'admin123',
                'role' => 'admin',
            ],
            [
                'email' => 'recruiter@example.com',
                'name' => 'Test Recruiter',
                'password' => 'password',
                'role' => 'recruiter',
            ],
            [
                'email' => 'hr@example.com',
                'name' => 'Test HR Reviewer',
                'password' => 'password',
                'role' => 'hr',
            ],
        ];

        foreach ($seeds as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                ]
            );

            $label = $user->wasRecentlyCreated ? 'Created' : 'Already exists';
            $this->command->info("{$label}: {$data['email']} ({$data['role']})");
        }
    }
}
