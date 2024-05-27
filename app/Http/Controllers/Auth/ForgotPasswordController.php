<?php

namespace App\Http\Controllers\Auth;

use App\Models\Customer;
use App\Models\EmailLog;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Auth\Passwords\PasswordBroker;
use Validator;
use Mail;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    //use SendsPasswordResetEmails;
	
	
	public function index(Request $request){
	
		$request->session()->put('status', '');
		 $message = '';
         return view('auth.forgotPassword', compact('message') );
    
    }
	
    public function sendLink(Request $request){

		$messages = [
            'email.required' 	=> "Email required",
		];

		$validation = [
			'email' 	=> 'required',
		];


        $request->validate($validation, $messages);

        try{

            $user = User::where('USEmail', $request->email)->where('USActive',1)->first();

            if($user){

                $tokenResult = $user->createToken('Personal Access Token');
                $token = $tokenResult->token->id;
        
                $route = route('resetPassword.reset',[$token, 'email'=>$user->USEmail]);
        
                // Send Email
                $emailData = array(
                    'email' => $user->USEmail,
                    'routeReset' => $route,
                    'domain' => config('app.url'),
                    'name'  => $user->USName ?? '',
                    'token' => $token,
                );
                
                try {
                    Mail::send(['html' => 'email.resetPasswordUser'], $emailData, function($message) use ($emailData) {
                        $message->to($emailData['email'] ,$emailData['email'])->subject('Reset Password');
                    });
        
                } catch (\Exception $e) {
                    
                    return response()->json([
                        'error' => '1',
                        'message' => 'Failed to send email.'.$e->getMessage()
                    ], 400);
                }
                    
                return response()->json([
                    'success' => '1',
                    'redirect' => route('login.index'),
                    'message' => 'Link has been sent to your email. Thank you.',
                ]);

                
            }else{
                
                return response()->json([
                    'error' => '1',
                    'message' => 'Akaun anda tidak dijumpai.'
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'error' => '1',
                'message' => 'Ralat ditemukan.'.$e->getMessage()
            ], 400);
        }

    }
}
