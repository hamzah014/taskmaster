<?php

namespace App\Http\Controllers\Auth;

use App\Helper\Custom;
use App\Models\Customer;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TacLog;
use App\Models\WebSetting;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    //use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
   // protected $redirectTo = RouteServiceProvider::HOME;

	
    public function showResetForm(Request $request, $token = null)
    {
        $user = User::where('USEmail', $request->email)->where('USActive', 1)->first();
        return view('auth.resetPassword')->with(
            ['token' => $token, 'email' => $request->email, 'user' => $user]
        );
    }
	
    public function reset(Request $request) {

        $messages = [
            'email.required' 		    => 'Email field is required.',
            'email.email' 			    => 'Email address must be correct.',
            'token.required'     	    => 'Token field is required.',
            'token.string'              => 'Token must be in string.',
            'password.required'         => 'Password field is required.',
            'confirmPassword.required'  => 'Confrim Password field is required.',
            'otpCode.required'          => 'OTP code field is required.',
        ];

        $validation = [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string',
            'confirmPassword' => 'required|string',
            'otpCode' => 'required'
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            if($request->password !== $request->confirmPassword){
                

                return response()->json([
                    'error' => '1',
                    'message' => 'Password do not matched.'
                ], 400);
            }
			
            $user= User::where('USCode',$request->userCode)->first();

            if($user == null){
				return response()->json([
					'error' => '1',
					'message' => 'User does not exists.'
				], 400);
            }

            
            $latestTac = TacLog::where('TACPhone',$user->USPhoneNo)
            ->where('TACEmail',$user->USEmail)
            ->where('TACType', 'US-RP')
            ->latest('TACCD')->first();

            if( isset($latestTac) && $latestTac->TACCode == $request->otpCode ){

                $diffInSeconds = Carbon::now()->diffInSeconds($latestTac->TACCD);

                if ($diffInSeconds >= 300) {
                    return response()->json([
                        'error' => '1',
                        'message' => 'OTP has expired after 5 minute. Please request new OTP.'
                    ], 403);
                }

            }else{

                return response()->json([
                    'error' => '1',
                    'message' => 'OTP code is not valid.'
                ], 400);

            }

			
            $user->USPwd = Hash::make($request->password);
            $user->save();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Password has been unsuccessful changed'.$e->getMessage()
            ], 400);
        }
		
        return response()->json([
            'success' => '1',
            'redirect' => route('resetPassword.success'),
            'message' => 'Password has been successfully changed'
        ]);

    }

    public function resetSuccess(){

        return view('auth.resetSuccess');

    }
   

    public function requestOTPReset(Request $request){

        $webSetting = WebSetting::first();
        $otpTimer = $webSetting->RequestOTPAfter;

        $userCode = $request->userCode;

        $user = User::where('USCode', $userCode)
        ->where('USActive',1)
        ->first();

        $checkPhone = false;
        $checkEmail = false;

        if($user){

            $otp = mt_rand(100000, 999999);
            
            $check_tac_log = TacLog::where('TACEmail', $user->USEmail)
            ->latest('TACCD')->first();
                            
            if($check_tac_log){
                
                $diffInMinutes = Carbon::now()->diffInMinutes($check_tac_log->TACCD);

                if ($diffInMinutes <= $otpTimer) {
                    return response()->json([
                        'error' => '1',
                        'message' => 'Sila tunggu '.$otpTimer.' minit sebelum memohon OTP lagi.'
                    ], 400);
                }
            }

            $create_tac_log = new TacLog();
            $create_tac_log->TACCode = $otp;
            $create_tac_log->TACType = 'US-RP';
            $create_tac_log->TACPhone = $user->USPhoneNo ?? "";
            $create_tac_log->TACEmail = $user->USEmail ?? "";
            $create_tac_log->save();

            if($user->USPhoneNo){
                $checkPhone = $this->sendOTPWSLogin($user->USPhoneNo,$otp);
            }else{
                $checkPhone = true;
            }

            if($user->USEmail){
                $checkEmail = $this->sendMailOTPLogin($user->USEmail, $otp);
            }else{
                $checkEmail = true;
            }

            if($checkPhone == false){

                return response()->json([
                    'error' => '1',
                    'message' => 'Your phone number is not valid!'
                ], 400);

            }

            if($checkEmail == false){

                return response()->json([
                    'error' => '1',
                    'message' => 'Your email is not valid!'
                ], 400);

            }
            
            if($checkEmail == true && $checkPhone == true){

                return response()->json([
                    'success' => '1',
                    'userCode' => $user->USCode,
                    'message' => 'OTP has been successfully send to your Email and phone number'
                ]);

            }

        }else{

			return response()->json([
                'error' => '1',
                'message' => 'User not exist!'
            ], 400);

        }

    }
    
    public function sendOTPWSLogin($phone, $code){

	    $helper = new Custom();
        $responseData= $helper->sendWhatsappOTP($phone, $code);

		if(isset($responseData->message_id)){

            return 1;
		}else{
            Log::error(json_encode($responseData));
            
            return 0;
		}

    }

    public function sendMailOTPLogin($email, $code){

        if($email != null) {

            $emailData = array(
                'email' => $email,
                'otp' => $code,
                'domain' => config('app.url'),
            );

            try {
                
                Mail::send(['html' => 'email.resetPasswordOTP'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['email'])->subject('Reset Password OTP Request');
                });

            } catch (\Exception $e) {
                
                return response()->json([
                    'error' => '1',
                    'message' => 'Failed send email.'.$e->getMessage()
                ], 400);
            }
            
			return 1;

        }
    }
}
