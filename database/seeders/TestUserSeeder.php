<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserModel;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        UserModel::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'firstname' => 'Test',
                'lastname' => 'User',
                'password' => Hash::make('password123'),
            ]
        );
    }
}
