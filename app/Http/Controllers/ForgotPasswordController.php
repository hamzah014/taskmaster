<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Mail;
use Auth;
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

//    use SendsPasswordResetEmails;

    public function forgot(Request $request) {
        $credentials = request()->validate(['email' => 'required|email']);

        if($request->email != null) {
            $customer= Customer::where('CSmail',$request->email)->where('CSActive',1)->first();
            if($customer == null){
                return response()->json([
                    'status'  => 'failed',
                    'message' => 'Email does not exists.'
                ]);
            }

            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $customer->CSCode;
			$emailLog->ELType 	= 'Reset Password';
			$emailLog->ELSentTo =  $request->email;
            // Send Email

            $emailData = array(
                'id' => $user->id,
                'name'  => $request->name ?? '',
                'email' => $request->email,
                'domain' => env('APP_URL'),
                'token' => app(PasswordBroker::class)->createToken($user),
            );

            try {
                Mail::send(['html' => 'email.resetPassword'], $emailData, function($message) use ($emailData) {
                    //$message->from('parking@example.com', 'noreply');
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Emofa Reset Password');
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

        return response()->json([
            'status' => 'success',
            "msg" => 'Reset password link already sent to your email address.'
        ]);
    }

    public function reset(Request $request) {

        $messages = [
            'email.required' => 'Email field is required.',
            'email.email' => 'Email address must be correct.',
            'token.required'     => 'Token field is required.',
            'token.string'          => 'Token must be in string.',
            'password.required'     => 'Password field is required.',
            'password.string'          => 'Password must be in string.',
            'password.confirmed'    => 'Password and Confirm Password are not the same.'
        ];

        $validation = [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|confirmed'
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();
			
            $customer= Customer::where('CSmail',$request->email)->where('CSActive',1)->first();
            $user = User::where('USCode',$customer->CSCode)->first();
            $user->USPwd = Hash::make($request->password);
            $user->save();

            DB::commit();

            return Redirect::back()->with('status','Password has been successfully changed');

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Password has been unsuccessful changed'.$e->getMessage()
            ], 400);
        }

//        return response()->json(["msg" => "Password has been successfully changed"]);
    }

    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.api.resetPassword')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }


}