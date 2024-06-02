<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use App\Models\Customer;
use App\Models\AutoNumber;
use App\Models\EmailLog;
use App\User;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Mail;

class RegisterController extends Controller
{

	public function index(Request $request){

         return view('auth.register');

    }

    public function create(Request $request){

        $messages = [
            'name.required' 		=> 'Name field is required.',
            'email.required' 		=> 'Email field is required.',
            'email.email' 			=> 'Email address must be correct.',
            'password.required'     => 'Password field is required.',
            'password.string'       => 'Password must be in string.',
            'password.confirmed'    => 'Password and Confirm Password are not the same.'
        ];

        $validation = [
            'name' 	=> 'required|string',
            'email' 	=> 'required|email',
            'password' 	=> 'required|string|confirmed',
        ];
        $request->validate($validation, $messages);

        $user = User::where('USEmail', $request->email)
        ->where('USActive', 1)->first();

        if ($user != null){
            return response()->json([
                'error' => '1',
                'message' => 'Email has been registered'
            ], 400);
        }

        try {
            DB::beginTransaction();

			$autoNumber = new AutoNumber();
			$userCode = $autoNumber->generateUserCode();
			$token = $autoNumber->generateUserToken();

            $getActivationCode = $this->getActivationCode();

            $user = new User();
            $user->USCode       = $userCode;
            $user->USEmail      = $request->email;
            $user->USName       = $request->name;
            $user->USPwd        = Hash::make($request->password);
            $user->USType       = 'US';
            $user->USResetPwd   = 0;
            $user->USActive     = 0;
            $user->USRegister   = 1;
            $user->US_RLCode    = 1;
            $user->USCB         = $userCode;
            $user->USToken      = $token;
            $user->USActivationCode = $getActivationCode;
            $user->save();

            $route = route('user.activate',['token' => $getActivationCode, 'email'=>$user->USEmail]);

            // Send Email
            $emailData = array(
                'email' => $user->USEmail,
                'routeActivate' => $route,
                'domain' => config('app.url'),
                'name'  => $user->USName ?? '',
                'token' => $token,
            );

            try {
                Mail::send(['html' => 'email.activateAccount'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['email'])->subject('Reset Password');
                });

            } catch (\Exception $e) {

                return response()->json([
                    'error' => '1',
                    'message' => 'Failed to send email.'.$e->getMessage()
                ], 400);
            }

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Register account was failed!'.$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('login.index'),
            'message' => 'Your account has been successfully registered. Link for account activation has been sent to your registered email.'
        ]);
    }

	public function activateUser(Request $request){

        $result = 0;
        $message = "";
        $token = $request->token ?? 0;
        $email = $request->email ?? 0;

        $user = User::where('USActivationCode', $token)->where('USEmail', $email)->first();

        if($user){

            $user->USActive = 1;
            $user->save();

            $result = 1;

        }
        else{

            $result = 0;

        }

		return view('auth.activeAccount', compact('result'));
    }


}
