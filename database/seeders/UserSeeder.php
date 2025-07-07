<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'fathin.mubarak@gmail.com')->first();
        $adminRole = Role::where('name', 'admin')->first();

        if (!$user) {
            $user = User::create([
                'name' => 'Fathin',
                'email' => 'fathin.mubarak@gmail.com',
                'password' => Hash::make('fathinif2012'),
                'email_verified_at' => now(),
                'is_verified' => true,
            ]);
        }

        // Assign admin role to user if not already assigned
        if ($adminRole && !$user->hasRole('admin')) {
            $user->roles()->attach($adminRole->id);
        }
    }
}
