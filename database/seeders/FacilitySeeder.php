<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $facility = [
            ['name' => 'Toilet'],
            ['name' => 'Kantin'],
            ['name' => 'Kamar Mandi'],
            ['name' => 'Parkir Gratis'],
        ];

        DB::table('facilities')->insert($facility);
    }
}
