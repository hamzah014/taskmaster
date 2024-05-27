<?php

namespace App\Http\Controllers\Contractor\Auth;

use App\Models\Customer;
use App\Models\EmailLog;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Auth\Passwords\PasswordBroker;
use Validator;
use Mail;

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
         return view('customer.auth.forgotPassword', compact('message') );

    }

	public function email(Request $request){
		$message = '';

        $credentials = request()->validate(['email' => 'required|email']);

        if($request->email != null) {
            $customer= Customer::where('CSEmail',$request->email)->where('CSActive',1)->first();
            if($customer == null){
				$message = 'Email does not exists.';
				return view('customer.auth.forgotPassword', compact('message') );
            }

            $user= User::where('USCode',$customer->CSCode)->first();
            if($user == null){
				$message = 'Email does not exists.';
				return view('customer.auth.forgotPassword', compact('message') );
            }

            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCode;
			$emailLog->ELType 	= 'Reset Password';
			$emailLog->ELSentTo =  $request->email;
            // Send Email

			$tokenResult = $user->createToken('Personal Access Token');
			$token = $tokenResult->token;

            $emailData = array(
                'id' => $user->USID,
                'name'  => $request->name ?? '',
                'email' => $request->email,
                'domain' => config::get('APP_URL'),
                'token' => $token->id,
            );

            try {
                Mail::send(['html' => 'email.resetPassword'], $emailData, function($message) use ($emailData) {
                    //$message->from('parking@example.com', 'noreply');
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Reset Password Notification');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
            } catch (\Exception $e) {
//                $e->getLine ()
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
            }

            $emailLog->save();
        }
		$status ='We have emailed your password reset link!';
		$request->session()->put('status', $status);


        return view('customer.auth.forgotPassword', compact('message') );
    }
}
