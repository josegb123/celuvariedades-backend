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
        User::updateOrCreate(
            ['email' => 'alberto@gmail.com'],
            [
                'name' => 'Alberto',
                'password' => Hash::make('asd'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        User::factory()->count(5)->create();
    }
}
