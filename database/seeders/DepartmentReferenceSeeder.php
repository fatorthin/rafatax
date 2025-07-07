<?php

namespace Database\Seeders;

use App\Models\DepartmentReference;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Administrasi',
                'description' => '-',
            ],
            [
                'name' => 'Tax Audit & Review',
                'description' => '-',
            ],
        ];

        foreach ($departments as $department) {
            DepartmentReference::create($department);
        }
    }
}
