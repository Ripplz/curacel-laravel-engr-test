<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $insurers = [
            [
                'name' => 'Insurer A',
                'code' => 'INS-A',
                'base_cost' => 100.00,
                'time_cost_min' => 0.2,
                'time_cost_max' => 0.5,
                'specialty_efficiency' => json_encode(['cardiology' => 0.8, 'orthopedics' => 1.2, 'general' => 1.0]),
                'priority_cost_multiplier' => 1.5,
                'value_cost_multiplier' => 0.01,
                'daily_capacity' => 100,
                'min_batch_size' => 1,
                'max_batch_size' => 50,
                'date_preference' => 'encounter',
                'email' => 'contact@insurera.com',
            ],
            [
                'name' => 'Insurer B',
                'code' => 'INS-B',
                'base_cost' => 120.00,
                'time_cost_min' => 0.15,
                'time_cost_max' => 0.4,
                'specialty_efficiency' => json_encode(['cardiology' => 1.1, 'orthopedics' => 0.9, 'general' => 1.0]),
                'priority_cost_multiplier' => 1.3,
                'value_cost_multiplier' => 0.008,
                'daily_capacity' => 80,
                'min_batch_size' => 5,
                'max_batch_size' => 40,
                'date_preference' => 'submission',
                'email' => 'contact@insurerb.com',
            ],
            [
                'name' => 'Insurer C',
                'code' => 'INS-C',
                'base_cost' => 90.00,
                'time_cost_min' => 0.25,
                'time_cost_max' => 0.6,
                'specialty_efficiency' => json_encode(['cardiology' => 0.7, 'orthopedics' => 1.3, 'general' => 1.0]),
                'priority_cost_multiplier' => 1.7,
                'value_cost_multiplier' => 0.012,
                'daily_capacity' => 120,
                'min_batch_size' => 1,
                'max_batch_size' => 60,
                'date_preference' => 'encounter',
                'email' => 'contact@insurerc.com',
            ],
            [
                'name' => 'Insurer D',
                'code' => 'INS-D',
                'base_cost' => 110.00,
                'time_cost_min' => 0.18,
                'time_cost_max' => 0.45,
                'specialty_efficiency' => json_encode(['cardiology' => 1.0, 'orthopedics' => 1.0, 'general' => 1.0]),
                'priority_cost_multiplier' => 1.4,
                'value_cost_multiplier' => 0.009,
                'daily_capacity' => 90,
                'min_batch_size' => 3,
                'max_batch_size' => 45,
                'date_preference' => 'submission',
                'email' => 'contact@insurerd.com',
            ],
        ];

        DB::table('insurers')->insert($insurers);
    }
}
