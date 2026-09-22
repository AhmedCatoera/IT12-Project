<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Owner Account
        User::updateOrCreate(
            ['email' => 'owner@sweetnest.com'],
            [
                'first_name' => 'Maria',
                'middle_name' => null,
                'last_name' => 'SweetNest',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        // 2. Staff Account
        User::updateOrCreate(
            ['email' => 'staff@sweetnest.com'],
            [
                'first_name' => 'Anna',
                'middle_name' => 'Marie',
                'last_name' => 'Reyes',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'is_active' => true,
            ]
        );

        // 3. Baker Account
        User::updateOrCreate(
            ['email' => 'baker@sweetnest.com'],
            [
                'first_name' => 'Carlos',
                'middle_name' => null,
                'last_name' => 'Mendoza',
                'password' => Hash::make('password'),
                'role' => 'baker',
                'is_active' => true,
            ]
        );

        // 4. Delivery Staff Account
        User::updateOrCreate(
            ['email' => 'delivery@sweetnest.com'],
            [
                'first_name' => 'Rico',
                'middle_name' => null,
                'last_name' => 'Dela Cruz',
                'password' => Hash::make('password'),
                'role' => 'delivery',
                'is_active' => true,
            ]
        );
    }
}
