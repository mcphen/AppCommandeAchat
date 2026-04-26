<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrateur', 'slug' => 'admin'],
            ['name' => 'Demandeur',      'slug' => 'demandeur'],
            ['name' => 'Validateur',     'slug' => 'validateur'],
            ['name' => 'Caissier',       'slug' => 'caissier'],
            ['name' => 'Agent',          'slug' => 'agent'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
