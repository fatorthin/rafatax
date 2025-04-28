<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [
                'name' => 'PT Testing 1',
                'address' => 'Jl. Testing No. 1, Jakarta',
                'email' => 'testing1@example.com',
                'phone' => '081234567890',
                'contact_person' => 'John Doe',
                'npwp' => '12.345.678.9-012.345'
            ],
            [
                'name' => 'PT Testing 2',
                'address' => 'Jl. Testing No. 2, Bandung',
                'email' => 'testing2@example.com',
                'phone' => '082345678901',
                'contact_person' => 'Jane Smith',
                'npwp' => '23.456.789.0-123.456'
            ],
            [
                'name' => 'PT Testing 3',
                'address' => 'Jl. Testing No. 3, Surabaya',
                'email' => 'testing3@example.com',
                'phone' => '083456789012',
                'contact_person' => 'Robert Johnson',
                'npwp' => '34.567.890.1-234.567'
            ]
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
