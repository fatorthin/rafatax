<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Administrator with full access',
            ],
            [
                'name' => 'manager',
                'description' => 'Manager with limited access',
            ],
            [
                'name' => 'staff',
                'description' => 'Staff with basic access',
            ],
            [
                'name' => 'leader',
                'description' => 'Leader with limited access',
            ],
            [
                'name' => 'coordinator',
                'description' => 'Coordinator with limited access',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
} 