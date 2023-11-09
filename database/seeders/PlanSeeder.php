<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->insert([
            [
                'name' => 'Monthly  Subscription',
                'slug' => 'monthly',
                'price' => 12.00,
                'interval' => 'month',
                'interval_count' => 3,
                'duration_in_days' => 30
            ],
            [
                'name' => 'Yearly  Subscription',
                'slug' => 'yearly',
                'price' => 99.99,
                'interval' => 'year',
                'interval_count' => 1,
                'duration_in_days' => 365
            ],
        ]);
    }
}
