<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SageSystemUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'sage100@system.local'],
            [
                'name'     => 'Sage100 (import automatique)',
                'email'    => 'sage100@system.local',
                'password' => Hash::make(Str::random(40)),
                'role_id'  => null,
            ]
        );

        $this->command->info('✓ Utilisateur système Sage100 créé (sage100@system.local).');
    }
}
