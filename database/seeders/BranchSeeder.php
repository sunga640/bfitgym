<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Main Branch - Dar es Salaam',
                'code' => 'DSM-MAIN',
                'address' => 'Masaki, Haile Selassie Road',
                'city' => 'Dar es Salaam',
                'country' => 'Tanzania',
                'phone' => '+255 22 260 1234',
                'email' => 'main@gymmanagement.co.tz',
                'status' => 'active',
            ],
            [
                'name' => 'Mikocheni Branch',
                'code' => 'DSM-MIK',
                'address' => 'Mikocheni B, Old Bagamoyo Road',
                'city' => 'Dar es Salaam',
                'country' => 'Tanzania',
                'phone' => '+255 22 270 5678',
                'email' => 'mikocheni@gymmanagement.co.tz',
                'status' => 'active',
            ],
            [
                'name' => 'Arusha Branch',
                'code' => 'ARU-MAIN',
                'address' => 'Njiro, Arusha-Moshi Road',
                'city' => 'Arusha',
                'country' => 'Tanzania',
                'phone' => '+255 27 250 9012',
                'email' => 'arusha@gymmanagement.co.tz',
                'status' => 'active',
            ],
        ];

        foreach ($branches as $branch_data) {
            Branch::firstOrCreate(
                ['code' => $branch_data['code']],
                $branch_data
            );
        }
    }
}

