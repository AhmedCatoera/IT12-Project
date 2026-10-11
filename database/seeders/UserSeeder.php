<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Two roles only: Owner and Staff. Default password for both: password */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'owner@sweetnest.com'], [
            'first_name' => 'Maria',
            'middle_name' => null,
            'last_name' => 'Dizon',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'staff@sweetnest.com'], [
            'first_name' => 'Anna',
            'middle_name' => 'Marie',
            'last_name' => 'Reyes',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);
    }
}
