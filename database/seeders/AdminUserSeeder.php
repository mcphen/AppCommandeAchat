<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();

        User::firstOrCreate(
            ['email' => 'admin@construcsen.com'],
            [
                'name'     => 'Administrateur Construcsen',
                'email'    => 'admin@construcsen.com',
                'password' => Hash::make('password'),
                'role_id'  => $adminRole?->id,
            ]
        );

        $this->command->info('✓ Compte administrateur créé (admin@construcsen.com / password).');
    }
}
