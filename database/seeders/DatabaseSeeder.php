<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            CashReferenceSeeder::class,
            GroupCoaSeeder::class,
            CoaSeeder::class,
            CategoryMouSeeder::class,
            PositionReferenceSeeder::class,
            DepartmentReferenceSeeder::class,
            JournalBookSeeder::class,
        ]);
    }
}
