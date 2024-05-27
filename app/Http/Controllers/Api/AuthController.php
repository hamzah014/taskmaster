<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Helper\Custom;
use App\User;
use App\Models\AutoNumber;
use App\Models\Contractor;
use App\Models\EmailLog;
use App\Models\FaceRegister;
use App\Models\FileAttach;
use App\Models\Notification;
use App\Models\PushNotification;
use App\Models\WebSetting;
use App\Models\ContractorAuth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
Use Illuminate\Support\Facades\Storage;
Use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
Use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mail;


class AuthController extends Controller
{
    protected $personList, $nationality, $gender, $name, $dob, $icNo, $addr, $city, $state, $postcode, $passportNo, $passportExp;

	public function userlogin(Request $request){

		///log::debug($request);

		$messages = [
			'userCode.required'	 		=> 'User Code is required.',
			'password.required' 		=> 'Password is required.',
		];

		$validation = [
			'userCode' 			=> 'required|string',
			'password' 			=> 'required|string',
			'pushID' 			=> 'nullable|string',
			'deviceInfo' 		=> 'nullable|string',
			'platform' 			=> 'nullable|string',
		];
		//test
        $validator = Validator::make($request->all(), $validation, $messages);

		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}


        $user = User::where('USCode', $request->userCode)->where('USActive',1)->first();

		if ($user == null){
            return response()->json([
                'status'  => 'failed',
				'message' => 'User code or password is not valid!'
            ]);
		}

		if (! Hash::check($request->password,$user->USPwd)){
            return response()->json([
                'status'  => 'failed',
				'message' => 'User code or password is not valid!'
            ]);
		}

		//GENERETATE NEW TOKEN
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addMonths(1);
        }

        $token->save();

		Auth::setUser($user); // need to set for auto detect at app\Resolvers\UserResolver.php
		Auth::guard('api')->setUser($user);

		$user->USLastLoginIP = $request->ip;
		$user->USLastLogin = carbon::now();
		$user->Save();


		if (strlen($request->pushID) == 36){
			$pushNotification = PushNotification::where('PN_USCode', $user->USCode)->First();
			if ($pushNotification == null) {
				$pushNotification = new PushNotification();
                $pushNotification->PN_USCode= $user->USCode;
				$pushNotification->PNCB     = $user->USCode;
			}
			$pushNotification->PNPushID 	= $request->pushID;
			$pushNotification->PNDeviceInfo	= $request->deviceInfo;
			$pushNotification->PNPlatform	= $request->platform;
			$pushNotification->PNMB 	    = $user->USCode;
			$pushNotification->save();

			$this->testSendPushNotification($request->pushID);
		}


		$data = array(
				'access_token' 	=> $tokenResult->accessToken,
				'token_type' 	=> 'Bearer',
				'expires_at' 	=> Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
				'userCode' 		=> $user->USCode,
				'userName' 		=> $user->USName,
			);

        return response()->json([
            'status' => 'success',
			'message' => 'Login successfully！',
			'data' => $data
        ]);
    }


	public function authUserlogin(Request $request){

		///log::debug($request);

		$messages = [
			'email.required'    => 'Email is required.',
			'email.email'	    => 'Email is invalid.',
			'password.required' => 'Password is required.',
		];

		$validation = [
			'email' 			=> 'required|email|string',
			'password' 			=> 'required|string',
			'pushID' 			=> 'nullable|string',
			'deviceInfo' 		=> 'nullable|string',
			'platform' 			=> 'nullable|string',
		];
		//test
        $validator = Validator::make($request->all(), $validation, $messages);

		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}


        $user = User::where('USType','AU')->where('USEmail', $request->email)->where('USActive',1)->first();

		if ($user == null){
            return response()->json([
                'status'  => 'failed',
				'message' => 'Email or password is not valid!'
            ]);
		}

		if (! Hash::check($request->password,$user->USPwd)){
            return response()->json([
                'status'  => 'failed',
				'message' => 'Email or password is not valid!'
            ]);
		}

        $contractorData = Contractor::join('MSContractorAuthUser','CAU_CONo', 'CONo')
                                        ->join('MSUser','CAU_USCode', 'USCode')
                                        ->where('CAU_USCode',$user->USCode)
                                        ->where('CAUStatus','ACTIVE')
                                        ->get();

        $contractorList = [];

        if(isset($contractorData) && count($contractorData)>0) {
           foreach ($contractorData as $x => $contractor) {
               array_push($contractorList, [
                   'contractorNo' => $contractor->CONo ,
                   'contractorName' => $contractor->COName ,
                   'contractorBusinessNo'=> $contractor->COBusinessNo ,
              ]);
           }
        }

		//GENERETATE NEW TOKEN
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addMonths(1);
        }

        $token->save();

		Auth::setUser($user); // need to set for auto detect at app\Resolvers\UserResolver.php
		Auth::guard('api')->setUser($user);

		$user->USLastLoginIP = $request->ip;
		$user->USLastLogin = carbon::now();
		$user->Save();


		if (strlen($request->pushID) == 36){
			$pushNotification = PushNotification::where('PN_USCode', $user->USCode)->First();
			if ($pushNotification == null) {
				$pushNotification = new PushNotification();
                $pushNotification->PN_USCode= $user->USCode;
				$pushNotification->PNCB     = $user->USCode;
			}
			$pushNotification->PNPushID 	= $request->pushID;
			$pushNotification->PNDeviceInfo	= $request->deviceInfo;
			$pushNotification->PNPlatform	= $request->platform;
			$pushNotification->PNMB 	    = $user->USCode;
			$pushNotification->save();

			$this->testSendPushNotification($request->pushID);
		}


		$data = array(
				'access_token' 	=> $tokenResult->accessToken,
				'token_type' 	=> 'Bearer',
				'expires_at' 	=> Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
				'userCode' 		=> $user->USCode,
				'userName' 		=> $user->USName,
				'contractorList'=> $contractorList,
			);

        return response()->json([
            'status' => 'success',
			'message' => 'Login successfully！',
			'data' => $data
        ]);
    }

    public function testSendPushNotification($pushID){

        $setting = WebSetting::first();

		$pushArray =[];
		$pushArray[] = $pushID;

		$headings = array(
			"en" => 'Welcome'
		);

		$content = array(
			"en" => 'Welcome to SPEED App'
		);

		$fields = array(
			'app_id' => $setting->OneSignalAppID,
			'include_player_ids' => $pushArray,
			'data' => array(
				"foo" => "bar"
			),
			'headings' => $headings,
			'contents' => $content,
			//'large_icon' => config('app.url').'/announcements/'. $announcements->banner
		);

		$fields = json_encode($fields);
		//print("\nJSON sent:\n");
		//print($fields);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json; charset=utf-8',
			'Authorization: Basic '.$setting->OneSignalApiKey,
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		$responseData = curl_exec($ch);
		curl_close($ch);

		//return $response;
		//$data = json_decode($fields, true);
		$data = json_decode($responseData, true);
		//log::info($data);
    }

    public function logout(Request $request){

        $request->user()->token()->revoke();

        return response()->json([
            'status' => 'success',
			'message' => 'Logout successfully!',
			'data' => null
        ]);
    }


	public function authorizedAccess(Request $request){

		//log::debug($request);

		$messages = [
			'companyID.required' 	=> 'Company ID Code field is required.',
			'companyID.numeric'			=> 'Company ID field is allowed numeric.',
			'userCode.required'	 	=> 'User Code field is required.',
			'password.required' 	=> 'Password field is required.',
		];

		$validation = [
			'companyID' => 'required|numeric',
			'userCode' 	=> 'required|string',
			'password' 	=> 'required|string',
		];

        $validator = Validator::make($request->all(), $validation, $messages);

		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}

        $user = User::select('Users.*')
					->join('RefUserCompany', 'RefUserCompany.UserID', 'Users.UserID')
					->join('RefCompany', 'RefCompany.CompanyID', 'RefUserCompany.CompanyID')
					->where('Users.UserCode', $request->userCode)
					->where('RefUserCompany.CompanyID', $request->companyID)
					->where('Users.IsActive',1)
					->where('Users.IsDeleted',0)
					->where('Users.UserType','S')
					->where('RefCompany.IsActive',1)
					->where('RefCompany.IsDeleted',0)
					->first();

		if ($user == null){
            return response()->json([
                'status'  => 'failed',
				'message' => 'Invalid authoriszed access!'
            ]);
		}

		if (! Hash::check($request->password,$user->password)){
            return response()->json([
                'status'  => 'failed',
				'message' => 'Invalid authoriszed access!'
            ]);
		}

		$data = array(
				'supervisorID'	=> (float) $user->UserID,
				'userCode' 		=> $user->UserCode,
				'userName' 		=> $user->UserName,
				'userType'		=> $user->UserType,
			);

        return response()->json([
            'status' => 'success',
			'message' => 'Authoriszed access successfully！',
			'data' => $data
        ]);
    }

    public function createAuthUser(Request $request){
        $messages = [
			'name.required'        			=> 'Name is required.',
			'natCode.required'              => 'Nationality is required.',
			'idNo.required'        		    => 'Identity Document No is required.',
			'email.required'        		=> 'Email is required.',
            'email.email'           		=> 'Email address must be correct.',
            'phoneNo.required' 				=> 'Phone Number is required!',
			'addr.required'        			=> 'Address is required.',
			'postcode.required'        		=> 'Postcode is required.',
			'city.required'        			=> 'City is required.',
			'stateCode.required'        	=> 'State is required.',
			'countryCode.required'        	=> 'Country is required.',
			'verificationCode.required' 	=> 'Verification Code is required!',
			'password.required' 			=> 'Password is required!',
            'password.min' 					=> 'Password cannot less than 6 characters.',
            'password.same' 				=> 'Password and confirm password does not match.',
			'confirmPassword.required' 		=> 'Confirm Password is required!',
            'confirmPassword.min' 			=> 'Confirm Password cannot less than 6 characters.',
			'fileIdentityDocPhoto.required' => 'Identoty Document Photo is required.',
			'fileApplicantPhoto.required'   => 'Applicant Photo is required.',
        ];

        $validation = [
            'name' 				=> 'required',
			'natCode' 	        => 'required|string',
			'idNo' 			    => 'required|string',
            'email' 			=> 'required|email',
			'phoneNo' 			=> 'required|numeric',
            'addr' 				=> 'required',
            'postcode' 			=> 'required',
            'city' 				=> 'required',
            'stateCode' 		=> 'required',
            'countryCode' 		=> 'required',
			'verificationCode' 	=> 'required|numeric',
            'password' 			=> 'required|min:6|same:confirmPassword',
            'confirmPassword' 	=> 'required|string|min:6',
			'fileIdentityDocPhoto'	=> 'required',
			'fileApplicantPhoto'	=> 'required'
        ];

        $validator = Validator::make($request->all(), $validation, $messages);

		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}

		/*
		//VALIDATE PASSWORD
		$uppercase = preg_match('@[A-Z]@', $request->password);
		$lowercase = preg_match('@[a-z]@', $request->password);
		$number    = preg_match('@[0-9]@', $request->password);

		if(!$uppercase || !$lowercase || !$number || strlen($request->password) < 8 || strlen($request->password) > 20) {
			return response()->json([
				'status'  => 'failed',
				'message' => trans('message.auth.fail.invalidPwdFormat'),
			]);
		}
		*/

        $imgIdentityDocBase64 = $request->fileIdentityDocPhoto;
        $imgApplicantBase64 = $request->fileIdentityDocPhoto;

		$exists = User::where('USType','AU')->where('USIDNo',$request->email)->first();
		if($exists != null){
			return response()->json([
				'status'  => 'failed',
				'message' =>  'User already exists!'
			]);
		}

		$exists = User::where('USType','AU')->where('USEmail',$request->email)->first();
		if($exists != null){
			return response()->json([
				'status'  => 'failed',
				'message' =>  'Email already exists!'
			]);
		}

		$contractorAuth = ContractorAuth::where('CAUEmail',$request->email)
                                        ->where('CAUVerificationCode',$request->verificationCode)
                                        ->where('CAUStatus','PENDING')
                                        ->first();
        if($contractorAuth == null){
            return response()->json([
                'status'  => 'failed',
				'message' => 'Invalid verification code!'
            ]);
		}
        $matchID = true;
        $matchFace = true;
        $errorMessage = '';

        if($request->natCode != 'MYS'){
            $this->readPassportMRZ($imgIdentityDocBase64);
            if ($contractorAuth->CAUIDNo != $this->passportNo){
                $matchID = false;
                $errorMessage = 'Passport No not match';
            }
        }else{
            $this->readMykadMRZ($imgIdentityDocBase64);
            if ($contractorAuth->CAUIDNo != $this->icNo){
                $matchID = false;
                $errorMessage = 'IC No not match';
            }
        }

        $autoNumber = new AutoNumber();
        $frNo = $autoNumber->generateFaceRegisterNo();

        $faceReg = new FaceRegister();
        $faceReg->FRNo     	        = $frNo;
        $faceReg->FR_CAUNo     	    = $contractorAuth->CAUNo;
        $faceReg->FRName 	        = $this->name;
        $faceReg->FRIDNo 	        = ($request->natCode != 'MYS') ? $this->passportNo : $this->icNo;
        $faceReg->FRAddress         = $this->addr;
        $faceReg->FRGender 	        = $this->gender;
        $faceReg->FRNationality     = $this->nationality;
        $faceReg->FRGender 	        = $this->gender;
        $faceReg->FRDOB 	        = $this->dob;
        $faceReg->FRPassportExpDate = $this->passportExp;
        $faceReg->FRMatchID 	    = $matchID;
        $faceReg->FRCB 		        = 'SYSTEM';
        $faceReg->FRMB 			    = 'SYSTEM';
        $faceReg->FRUnmatchDesc 	= $errorMessage;
        $faceReg->save();

        $contractorAuth->CAURegisterCount = $contractorAuth->CAURegisterCount + 1;
        $contractorAuth->save();

        //*** SAVE FILE TO STORAGE ***
        $folderPath = Carbon::now()->format('ymd');
        $timeStamp = Carbon::now()->format('Ymd').'T'.Carbon::now()->format('Hms');

        if ($imgIdentityDocBase64 != null) {
            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
            $newFileName = strval($generateRandomSHA256);
            $fileIdentityDocPhotoData  = base64_decode($imgIdentityDocBase64);
            $fileIdentityDocPhotoName  = 'IdentityDoc-'.$timeStamp.'.jpg';
            Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileIdentityDocPhotoData);
            $this->addAttachment($frNo, $frNo, 'FR-ID', $folderPath, $newFileName, 'jpg', $fileIdentityDocPhotoName);
        }

        if ($imgApplicantBase64 != null) {
            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
            $newFileName = strval($generateRandomSHA256);
            $imgApplicantBase64 = $request->fileApplicantPhoto;  // your base64 encoded
            $fileApplicantPhotoData  = base64_decode($imgApplicantBase64);
            $fileApplicantPhotoName  = 'Applicant-'.$timeStamp.'.jpg';
            Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileApplicantPhotoData);
            $this->addAttachment($frNo, $frNo, 'FR-AP', $folderPath, $newFileName, 'jpg', $fileApplicantPhotoName);
        }

        if ($matchID == false){
            return response()->json([
                'status'  => 'failed',
				'message' => 'Identity Card not match!'
            ]);
        }

        if ($imgIdentityDocBase64 != '' && $imgApplicantBase64 != ''  ){
            $helper = new Custom();
            $faceResult = $helper->faceCompareAI($imgIdentityDocBase64,$imgApplicantBase64);
            $faceScore = $faceResult['faceScore'];

            if ($faceScore < 80){
                $matchFace = false;
                $errorMessage = 'Face not match!';
            }
            $faceReg->FRMatchFace = $matchFace;
            $faceReg->FRFaceScore = $faceScore;
            $faceReg->save();
        }

        if ($matchFace == false){
            return response()->json([
                'status'  => 'failed',
				'message' => 'Face Verficaiton not match!'
            ]);
        }

        try {

            DB::beginTransaction();

			$userCode = $autoNumber->generateApprovalUserCode();

            $user = new User();
            $user->USCode               = $userCode;
            $user->USPwd                = Hash::make($request->password);
            $user->USNationality_CTCode = $request->natCode ?? '';
            $user->USIDNo              	= $request->idNo ?? '';
            $user->USName              	= $request->name ?? '';
            $user->USPhoneNo           	= $request->phoneNo ?? '';
            $user->USEmail             	= $request->email ?? '';
            $user->USAddr        	    = $request->addr ?? '';
            $user->USPostcode       	= $request->postcode ?? '';
            $user->USCity           	= $request->city ?? '';
            $user->US_StateCode     	= $request->stateCode;
            $user->US_CTCode	     	= $request->countryCode;
            $user->USType               = 'AU';
            $user->USResetPwd           = 0;
            $user->USActive             = 1;
            $user->USCB                 = $userCode;
            $user->save();

            $contractorAuth->CAU_USCode = $userCode;
            $contractorAuth->CAUStatus  = 'ACTIVE';
            $contractorAuth->save();

            $notification = new Notification();
            $notification->NO_RefCode       = $userCode;
            $notification->NOTitle       	= 'Selamat Datang';
            $notification->NODescription    = 'Selamat Datang ke Sistem Pengesahan SPEED';
            $notification->NORead   		= 0;
            $notification->NOSent     		= 0;
            $notification->NOCB     		= $userCode;
            $notification->save();

			DB::commit();

		} catch (\Throwable $e) {
			DB::rollback();
			throw $e;
		}

		$this->sendNotificationEmail($user);

		$data = array(
			'userCode'  => $user->USCode,
			'email'	    => $user->USEmail,
		);

		return response()->json([
			'status' => 'success',
			'message' => 'Your account is registered successfully!',
			'data' => $data
		]);
    }

	private function sendNotificationEmail( $user){

        $emailFile = 'email.accountRegistered';
        $title = 'Thank you for your registration';

		//*** Send Email ****
		$emailLog = new EmailLog();
		$emailLog->ELTransNo	= $user->USCode;
		$emailLog->ELSentTo		= $user->USEmail;
		$emailLog->ELType		= 'REGISTER';

		$emailData = array(
			'fullName'	=> $user->CSName,
			'email' 	=> $user->CSEmail,
		);

		try {
			Mail::send(['html'=>$emailFile], $emailData, function ($message) use($emailData, $title) {
				$message->to($emailData['email']);
				$message->subject($title);
			});

			$emailLog->ELMessage = 'Success';
			$emailLog->ELSentStatus = 1;
		} catch (\Exception $e) {
			$emailLog->ELMessage = $e->getMessage();
			$emailLog->ELSentStatus = 2;
		}
		$emailLog->save();

		return $emailLog->ELSentStatus;

	}

	private function addAttachment($userCode, $refNo, $refType, $folderPath, $newFileName, $newFileExt, $originalName){

		$fileAttach = FileAttach::where('FARefNo',$refNo)
                                ->where('FAFileType',$refType)
                                ->where('FAActive', 1)
                                ->first();

		if ($fileAttach != null){
            $fileAttach->FAActive = 0;
            $fileAttach->save();
		}

        $fileAttach = new FileAttach();
        $fileAttach->FARefNo     	    = $refNo;
        $fileAttach->FAFileType 	    = $refType;
        $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
        $fileAttach->FAFileName 	    = $newFileName;
        $fileAttach->FAOriginalName 	= $originalName;
        $fileAttach->FAFileExtension    = strtolower($newFileExt);
        $fileAttach->FACB 		        = $userCode;
        $fileAttach->FAMB 			    = $userCode;
        $fileAttach->save();

	}

    private function readPassportMRZ($image) {

		$helper = new Custom();
		$passportOCR = null;
		$resultOCR =  null;
		$passportOCR = $helper->passportOCR($image);
		$resultOCR = $passportOCR->result ?? null;

		if ($resultOCR == null){
			return 0;
		}else{
			if ((strlen($resultOCR->machine_code) != 44) || (strlen($resultOCR->machine_code2) != 44)){
				return 0;
			}

			$this->nationality 	= $resultOCR->country_code;
			$this->gender 		= $resultOCR->sex;
			$this->name 		= trim($resultOCR->surname .' ' .$resultOCR->given_name);
			$this->passportNo 	= $resultOCR->passport_number;

			if ($resultOCR->confidence->date_of_birth == 0){
				$this->dob = null;
			}else{
				$this->dob = $resultOCR->date_of_birth;
			}

			if ($resultOCR->confidence->date_of_expiry == 0){
				$this->passportExp = null;
			}else{
				$this->passportExp = $resultOCR->date_of_expiry;
			}
			return 1;
		}
    }

    private function readMykadMRZ($image) {

		$helper = new Custom();
		$myKadOCR = null;
		$resultOCR =  null;
		$myKadOCR = $helper->mykadOCR($image);
		//$resultOCR = $passportOCR->result ?? null;

		if ($myKadOCR == null){
			return 0;
		}else{
			$this->icNo 	= str_replace('-','',$myKadOCR->ID);
			$this->gender 	= $myKadOCR->Sex;
			$this->name 	= $myKadOCR->Name;
			$this->image 	= $myKadOCR->Image;
			$this->type 	= $myKadOCR->Type;
			if ($myKadOCR->Birthday != null){
				$arr = explode("/", $myKadOCR->Birthday);
				$birthday = $arr[2].'-'.$arr[1].'-'.$arr[0];
			}
			$this->dob 		= $birthday ?? '';
			$this->addr 	= $myKadOCR->Address;
			return 1;
		}
    }


}
