<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::where('name', 'Admin')->first();

        $red = rand(0, 255);
        $green = rand(0, 255);
        $blue = rand(0, 255);

        $complementaryRed = 255 - $red;
        $complementaryGreen = 255 - $green;
        $complementaryBlue = 255 - $blue;

        $complementaryColor = sprintf("#%02x%02x%02x", $complementaryRed, $complementaryGreen, $complementaryBlue);

        $db_user = User::where('color', $complementaryColor)->get();

        if(count($db_user) > 0){

            while(count($db_user) > 0){
                $red = rand(0, 255);
                $green = rand(0, 255);
                $blue = rand(0, 255);

                $complementaryRed = 255 - $red;
                $complementaryGreen = 255 - $green;
                $complementaryBlue = 255 - $blue;

                $complementaryColor = sprintf("#%02x%02x%02x", $complementaryRed, $complementaryGreen, $complementaryBlue);

                $db_user = User::where('color', $complementaryColor)->get();
            }

        }
        $partner = User::Create([
            'name' => 'Admin Bcourt',
            'email' => 'admin@bcourt.id',
            'color' => $complementaryColor,
            'password' => Hash::make('bcourt$ukses1314'),
            'phone' => '08412333243'
        ]);

        $partner->assignRole($role);
    }
}
