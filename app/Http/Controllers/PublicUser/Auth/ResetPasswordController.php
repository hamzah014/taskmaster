<?php

namespace App\Http\Controllers\PublicUser\Auth;

use App\Helper\Custom;
use App\Models\Contractor;
use App\Models\Customer;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Session;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Config;
use Mail;

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
        return view('publicUser.auth.resetPassword')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    public function showSetPasswordForm(Request $request, $token = null)
    {
        return view('publicUser.auth.setPassword')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    public function reset(Request $request) {

        $messages = [
            'email.required' 		=> 'Email field is required.',
            'email.email' 			=> 'Email address must be correct.',
            'token.required'     	=> 'Token field is required.',
            'token.string'          => 'Token must be in string.',
            'password.required'     => 'Password field is required.',
            'password.string'       => 'Password must be in string.',
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

            $contractor= Contractor::where('COEmail',$request->email)->where('COActive',1)->first();
            if($contractor == null){
				return response()->json([
					'error' => '1',
					'message' => 'Emel tidak wujud.'
				], 400);
            }

            $user= User::where('USCode',$contractor->CONo)->first();
            if($user == null){
				return response()->json([
					'error' => '1',
					'message' => 'Emel tidak wujud.'
				], 400);
            }

            $user->USPwd = Hash::make($request->password);
            $user->save();

            //SEND EMAIL FOR SUCCESS SET PASSWORD
            $tokenResult = $contractor->createToken('Personal Access Token');
            $token = $tokenResult->token;

            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $contractor->CONo;
            $emailLog->ELType 	= 'Success Set Password';
            $emailLog->ELSentTo =  $contractor->COEmail;

            $emailData = array(
                'id' => $contractor->COID,
                'name'  => $contractor->COName ?? '',
                'email' => $contractor->COEmail,
                'domain' => config::get('app.url'),
                'token' => $token->id,
                'now' => Carbon::now()->format('j F Y'),
            );

            try {
                Mail::send(['html' => 'email.setPasswordSuccessPublic'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Status Penetapan Kata Laluan');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
            } catch (\Exception $e) {
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
            }

            $emailLog->save();

            $phoneNo = $contractor->COPhone;
            $title = "";
            $refNo = "";

//            $custom = new Custom();
//            $sendWSResetPasw = $custom->sendWhatsappLetter('change_password_notice',$phoneNo,$title,$refNo); //RESETPASWWASAP

            DB::commit();

            if (Auth::attempt(['USCode' => $contractor->CONo, 'password' => $request->password, 'USActive' => 1])) {
                // The user is active, not suspended, and exists.
                //return view('home');

                Session::put('page', 'publicUser');
                Session::put('userLogin', $contractor->CONo) ;
                Session::put('userPassword', $request->password) ;

                $user = User::where('USCode', $contractor->CONo)->first();
                $user->USLastLogin = Carbon::now();
                $user->save();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('publicUser.index'),
                    'message' => 'Penetapan kata laluan berjaya.'
                ]);

            }else{
                return response()->json([
                    'error' => '1',
                    'message' => 'Penetapan kata laluan tidak berjaya!'
                ], 400);
            }

            //return Redirect::back()->with('status','Password has been successfully changed');

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Kata laluan tidak berjaya dikemaskini:'.$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('publicUser.login.index'),
            'message' => 'Kata laluan telah berjaya dikemaskini'
        ]);

        // return response()->json(["msg" => "Password has been successfully changed"]);
    }

    public function setPassword(Request $request) {

        $messages = [
            'email.required' 		=> 'Email field is required.',
            'email.email' 			=> 'Email address must be correct.',
            'token.required'     	=> 'Token field is required.',
            'token.string'          => 'Token must be in string.',
            'password.required'     => 'Password field is required.',
            'password.string'       => 'Password must be in string.',
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

            $contractor= Contractor::where('COEmail',$request->email)->where('COActive',1)->first();
            if($contractor == null){
				return response()->json([
					'error' => '1',
					'message' => 'Emel tidak wujud.'
				], 400);
            }

            $user= User::where('USCode',$contractor->CONo)->first();
            if($user == null){
				return response()->json([
					'error' => '1',
					'message' => 'Emel tidak wujud.'
				], 400);
            }

            $user->USPwd = Hash::make($request->password);
            $user->save();

            DB::commit();

            //return Redirect::back()->with('status','Password has been successfully changed');

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Penetapan kata laluan tidak berjaya!:'.$e->getMessage()
            ], 400);
        }

        // return response()->json([
        //     'success' => '1',
        //     'redirect' => route('publicUser.login.index'),
        //     'message' => 'Kata laluan telah berjaya dikemaskini'
        // ]);

        //REDIRECT USER TO HOMEPAGE PUBLIC USER

		if (Auth::attempt(['USCode' => $contractor->CONo, 'password' => $request->password, 'USActive' => 1])) {
			// The user is active, not suspended, and exists.
			//return view('home');

			Session::put('page', 'publicUser');
            Session::put('userLogin', $contractor->CONo) ;
            Session::put('userPassword', $request->password) ;

            $user = User::where('USCode', $contractor->CONo)->first();
            $user->USLastLogin = Carbon::now();
            $user->save();

			return response()->json([
				'success' => '1',
				'redirect' => route('publicUser.index'),
				'message' => 'Penetapan kata laluan berjaya.'
			]);

		}else{
			return response()->json([
                'error' => '1',
                'message' => 'Penetapan kata laluan tidak berjaya!'
            ], 400);
		}

    }


}
