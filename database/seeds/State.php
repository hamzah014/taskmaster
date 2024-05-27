<?php

use Illuminate\Database\Seeder;
use App\Models\ProvinceState;

class State extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
     $state = [
         'Johor',
         'Kedah',
         'Kelantan',
         'Melaka',
         'Negeri Sembilan',
         'Pahang',
         'Perak',
         'Perlis',
         'Pulau Pinang',
         'Sabah',
         'Sarawak',
         'Selangor',
         'Terengganu',
         'W.P. Kuala Lumpur',
         'W.P. Labuan',
         'W.P. Putrajaya',
		];

     foreach($state as $states){
         $lState = new ProvinceState();
         $lState -> ProvinceStateName = $states;
		 $lState -> IsDeleted = 0;
		 $lState -> CreateID = 0;
         $lState -> save();
     }
    }
}