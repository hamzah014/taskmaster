<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{

	public function listCountry(Request $request)
    {
		
		$countryData = Country::where('CTActive',1)
								->orderBy('CTDesc', 'asc')
								->get();

        $data = [];

        if(isset($countryData) && count($countryData)>0) {
            foreach ($countryData as $x => $country) {
				array_push($data, [
                    'countryCode' 	=> $country->CTCode ?? '',
                    'countryDesc' 	=> $country->CTDesc ?? '',
					]
				);
			}
        }
        return response()->json([
            'status' => 'success',
			'data' 	 => $data
        ]);
    }

}