<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Helper\Custom;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\TacLog;
use App\Models\WebSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
    protected $username;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');

        $this->username = $this->findUsername();
    }

    public function findUsername()
    {
        $login = request()->input('email');

        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'Email' : 'USCode';

        request()->merge([$fieldType => $login]);

        return $fieldType;
    }

    /**
     * Get username property.
     *
     * @return string
     */
    public function username()
    {
        return $this->username;
    }

    public function index(){

        return view('admin.auth.login');

    }

    public function logout(){

		$user = Auth::user();

        Auth::guard()->logout();

        return redirect()->route('login.index');
    }

    protected function authenticated(Request $request, $user)
    {
        if($user instanceof User){
            $user->LastLogin = Carbon::now();
            $user->save();
        }

    }

	public function login(Request $request){

		$messages = [
            'email.required' 	=> "Email Required",
            'password.required' => trans('message.password.required'),
		];

		$validation = [
			'email' 	=> 'required|email',
			'password' 	=> 'required|string',
		];

        $request->validate($validation, $messages);

        $user = User::where('USEmail', $request->email)->where('USType','AD')->first();

		if ($user == null){
			return response()->json([
                'error' => '1',
                'message' => 'Email or password is not valid!'
            ], 400);
		}

        if($user->USActive == 0){

			return response()->json([
                'error' => '1',
                'message' => 'Your account has been blocked. Please contact administrator system.'
            ], 400);
        }

		if (Auth::attempt(['USCode' => $user->USCode, 'password' => $request->password, 'USActive' => 1])) {

            $user->USLastLogin = Carbon::now();
            $user->save();

			return response()->json([
				'success' => '1',
				'redirect' => route('admin.dashboard'),
				'message' => 'Successfully Login. Welcome to TaskMaster.'
			]);

		}else{
			return response()->json([
                'error' => '1',
                'message' => 'Email or password invalid!'
            ], 400);
		}

    }

    public function checkLogin(Request $request){

		$messages = [
            'email.required' 	=> "Email Required",
            'password.required' => trans('message.password.required'),
		];

		$validation = [
			'email' 	=> 'required|email',
			'password' 	=> 'required|string',
		];

        $request->validate($validation, $messages);

        $user = User::where('USEmail', $request->email)->first();

		if ($user == null){
			return response()->json([
                'error' => '1',
                'message' => 'Email or password is not valid!'
            ], 400);
		}

        if($user->USActive == 0){

			return response()->json([
                'error' => '1',
                'message' => 'Your account has been blocked. Please contact administrator system.'
            ], 400);
        }

        if($user->USCode == 'SA'){

            if (Auth::attempt(['USCode' => $user->USCode, 'password' => $request->password, 'USActive' => 1])) {

                return response()->json([
                    'success' => '1',
                    'userCode' => $user->USCode,
                    'redirect' => route('home'),
                    'message' => 'Successfully Login. Welcome to DCMS.'
                ]);


            }else{

                return response()->json([
                    'error' => '1',
                    'message' => 'Email or password invalid!'
                ], 400);

            }

        }
        else{

            if ($user && $user->USActive == 1 && password_verify($request->password, $user->USPwd)) {

                return response()->json([
                    'success' => '1',
                    'userCode' => $user->USCode,
                    'redirect' => route('home'),
                    'message' => 'Success authorize'
                ]);
            }else{

                return response()->json([
                    'error' => '1',
                    'message' => 'Email or password invalid!'
                ], 400);

            }

        }

    }

    public function requestOTPLogin(Request $request){

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
            $create_tac_log->TACType = 'US-LGW';
            $create_tac_log->TACPhone = $user->USPhoneNo ?? "";
            $create_tac_log->TACEmail = $user->USEmail ?? "";
            $create_tac_log->save();

            if($user->USPhoneNo){
                $checkPhone = $this->sendOTPWSLogin($user->USPhoneNo,$otp);
            }else{
                $checkPhone = true;
            }
            $checkPhone = true;

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

            // Send Email
            $emailData = array(
                'email' => $email,
                'otp' => $code,
                'domain' => config('app.url'),
            );

            try {

                Mail::send(['html' => 'email.registerUserOTP'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['email'])->subject('Login OTP Request');
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

	public function loginByOTP(Request $request){

		$messages = [
            'otpCode.required' => 'Kod OTP diperlukan',
		];

		$validation = [
			'otpCode' 	=> 'required|string',
		];

        $request->validate($validation, $messages);

        $user = User::where('USEmail', $request->email)->first();

		if ($user == null){
			return response()->json([
                'error' => '1',
                'message' => 'Email or password is not valid!'
            ], 400);
		}

        if($user->USActive == 0){

			return response()->json([
                'error' => '1',
                'message' => 'Your account has been blocked. Please contact administrator system.'
            ], 400);
        }

        $latestTac = TacLog::where('TACPhone',$user->USPhoneNo)
                        ->where('TACEmail',$user->USEmail)
                        ->where('TACType', 'US-LGW')
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

		if (Auth::attempt(['USCode' => $user->USCode, 'password' => $request->password, 'USActive' => 1])) {


            if ($request->has('rememberMe')) {
                // Save email and password to cache
                Cache::put('login.email', $request->input('email'), null);
                Cache::put('login.password', $request->input('password'), null);
            }
            else{
                Cache::forget('login.email');
                Cache::forget('login.password');
            }

            $user->USLastLogin = Carbon::now();
            $user->save();

			return response()->json([
				'success' => '1',
				'redirect' => route('home'),
				'message' => 'Successfully Login. Welcome to DCMS.'
			]);

		}else{
			return response()->json([
                'error' => '1',
                'message' => 'Email or password invalid!'
            ], 400);
		}

    }

}
