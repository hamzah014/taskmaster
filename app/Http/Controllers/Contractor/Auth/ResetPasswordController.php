<?php

namespace App\Http\Controllers\Contractor\Auth;

use App\Helper\Custom;
use App\Models\Customer;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\EmailLog;
use App\Models\Project;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Auth;
use Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use TencentCloud\Domain\V20180808\Models\ContactInfo;

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


    public function resetPaswActiveCode($id){
        $activationCode = $id;

        try {

            $project = Project::where('PTActivationCode',$activationCode)->first();

            if($project == null){

                return response()->json([
                    'error' => '1',
                    'message' => 'Maaf, kod pengesahan anda tidak berdaftar.'
                ], 400);


            }else{

                return view('contractor.auth.resetPasswActivateCode',
                        compact('project','activationCode')
                );

            }

            //return Redirect::back()->with('status','Password has been successfully changed');

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Password has been unsuccessful changed'.$e->getMessage()
            ], 400);
        }

    }

    public function updatePaswProject(Request $request) {

        $messages = [
            'password.required'     => 'Password field is required.',
            'password.string'       => 'Password must be in string.',
            'password.confirmed'    => 'Password and Confirm Password are not the same.'
        ];

        $validation = [
            'password' => 'required|string|confirmed'
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $activateCode   = $request->activateCode;
            $projectNo      = $request->projectNo;
            $password       = $request->password;

            $project = Project::where('PTNo',$projectNo)->first();
            $project->PTPwd = Hash::make($password);
            $project->save();

            $contractor = Contractor::where('CONo' , $project->PT_CONo)->first();

            $this->sendMailResetPasswordStatus($contractor);

            $phoneNo = $contractor->COPhone;
            $title = $project->tenderProposal->tender->TDTitle;
            $refNo = $project->tenderProposal->tender->TDNo;

//            $custom = new Custom();
//            $sendWSResetPasw = $custom->sendWhatsappLetter('change_password_notice',$phoneNo,$title,$refNo); //RESETPASWWASAP

            DB::commit();

            //REDIRECT THEM TO CONTRACTOR PAGE
            $project = Project::where('PTNo', $projectNo)->where('PTActive',1)->first();

            $contractor = Contractor::where('CONo', $project->PT_CONo ?? '')->where('COActive',1)->first();

            $user = User::where('USCode', $contractor->CONo)->first();

            // To login specific user using eloquent model
            Auth::guard('web')->login($user);

            // For getting logged in user
            Auth::guard('web')->user();

            // To check if user is logged in
            if (Auth::guard('web')->check()) {
                // Logged in
                    Session::put('page', 'contactor');
                    Session::put('project', $projectNo);

                    $user = User::where('USCode', $contractor->CONo)->first();
                    $user->USLastLogin = Carbon::now();
                    $user->save();

                    return response()->json([
                        'success' => '1',
                        'redirect' => route('contractor.index'),
                        'message' => 'Daftar masuk berjaya.'
                    ]);

            }else{
                return response()->json([
                    'error' => '1',
                    'message' => 'ID log masuk atau kata laluan tidak sah!'
                ], 400);
            }

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Kata laluan tidak berjaya ditukar'.$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('contractor.login.index'),
            'message' => 'Kata laluan berjaya ditukar.'
        ]);

    }

    private function sendMailResetPasswordStatus($contractor){

        // $paymentLog = PaymentLog::where('PLNo',$paymentLogNo)->first();


        $emailLog = new EmailLog();
        $emailLog->ELCB 	= $contractor->CONo;
        $emailLog->ELType 	= 'Set Password';
        $emailLog->ELSentTo =  $contractor->COEmail;
        // Send Email

        $tokenResult = $contractor->createToken('Personal Access Token');
        $token = $tokenResult->token;

        $emailData = array(
            'id' => $contractor->COID,
            'name'  => $contractor->COName ?? '',
            'email' => $contractor->COEmail,
            'domain' => config::get('app.url'),
            'token' => $token->id,
            'now' => Carbon::now()->format('j F Y'),
            'contractor' => $contractor,
        );

        try {
            Mail::send(['html' => 'email.setPasswordSuccessContractor'], $emailData, function($message) use ($emailData) {
                $message->to($emailData['email'] ,$emailData['name'])->subject('Tetapan Kata Laluan');
            });

            $emailLog->ELMessage = 'Success';
            $emailLog->ELSentStatus = 1;
        } catch (\Exception $e) {
            $emailLog->ELMessage = $e->getMessage();
            $emailLog->ELSentStatus = 2;
        }

        $emailLog->save();

    }


}
