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
            ['email' => 'admin@scndienne.sn'],
            [
                'name'     => 'Administrateur SCN',
                'email'    => 'admin@scndienne.sn',
                'password' => Hash::make('password'),
                'role_id'  => $adminRole?->id,
            ]
        );

        $this->command->info('✓ Compte administrateur créé (admin@scndienne.sn / password).');
    }
}
