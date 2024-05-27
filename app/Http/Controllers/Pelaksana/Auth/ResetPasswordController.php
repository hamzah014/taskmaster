<?php

namespace App\Http\Controllers\Pelaksana\Auth;

use App\Models\Customer;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        return view('customer.auth.resetPassword')->with(
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

            $customer= Customer::where('CSEmail',$request->email)->where('CSActive',1)->first();
            if($customer == null){
				return response()->json([
					'error' => '1',
					'message' => 'Email does not exists.'
				], 400);
            }

            $user= User::where('USCode',$customer->CSCode)->first();
            if($user == null){
				return response()->json([
					'error' => '1',
					'message' => 'Email does not exists.'
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
                'message' => 'Password has been unsuccessful changed'.$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('customer.login.index'),
            'message' => 'Password has been successfully changed'
        ]);

//        return response()->json(["msg" => "Password has been successfully changed"]);
    }


}
