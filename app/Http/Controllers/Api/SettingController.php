<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\WebSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
Use Illuminate\Support\Facades\Storage;
Use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SettingController extends Controller
{
	
	
	public function index(Request $request)
    {
		$setting = WebSetting::First();
		
		$data = array(
				'underMaintenance'			=> $setting->UnderMaintenance,
				'iOsMinVersion'				=> $setting->IOSMinVersion,
				'androidMinVersion'			=> $setting->AndroidMinVersion,
				'compName'					=> $setting->CompName,
				'address'					=> $setting->Address,
				'phoneNumber'				=> $setting->PhoneNumber,
				'email'						=> $setting->Email,
				'website'					=> $setting->Website,
				'termsURL'					=> $setting->TermsURL,
				'privacyURL'				=> $setting->PrivacyURL,
				'aboutURL'					=> $setting->AboutURL,
				'faqURL'					=> $setting->FaqURL,
				'applicationURL'			=> $setting->ApplicationURL,
			);

        return response()->json([
            'status' => 'success',
			'data' => $data
        ]);
    }

	
}