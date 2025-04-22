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
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
} 