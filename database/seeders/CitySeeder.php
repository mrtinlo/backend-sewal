<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $city = [
            ['name' => 'Jakarta'],
            ['name' => 'Tangerang'],
            ['name' => 'Bandung'],
            ['name' => 'Tanjung Pinang'],
            ['name' => 'Pontianak'],
            ['name' => 'Pekanbaru'],
        ];

        DB::table('cities')->insert($city);
    }
}
