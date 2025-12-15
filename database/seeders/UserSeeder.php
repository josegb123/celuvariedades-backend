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
        // Crea el usuario administrador por defecto
        User::updateOrCreate(
            ['email' => 'admin@admin.co'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        User::factory()->count(5)->create();
    }
}
