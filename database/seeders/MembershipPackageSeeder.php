<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\MembershipPackage;
use Illuminate\Database\Seeder;

class MembershipPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Daily Package',
                'description' => 'One-day gym access package.',
                'price' => 5000,
                'duration_type' => 'days',
                'duration_value' => 1,
            ],
            [
                'name' => 'Weekly Package',
                'description' => 'One-week gym access package.',
                'price' => 65000,
                'duration_type' => 'weeks',
                'duration_value' => 1,
            ],
            [
                'name' => 'Monthly Package',
                'description' => 'One-month gym access package.',
                'price' => 150000,
                'duration_type' => 'months',
                'duration_value' => 1,
            ],
        ];

        $branch_ids = Branch::query()->pluck('id');

        foreach ($branch_ids as $branch_id) {
            foreach ($packages as $package) {
                MembershipPackage::query()->updateOrCreate(
                    [
                        'branch_id' => $branch_id,
                        'name' => $package['name'],
                    ],
                    [
                        'description' => $package['description'],
                        'price' => $package['price'],
                        'duration_type' => $package['duration_type'],
                        'duration_value' => $package['duration_value'],
                        'is_renewable' => true,
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
