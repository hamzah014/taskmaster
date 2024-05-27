<?php

namespace App\Http\Controllers\Pelaksana\Auth;

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
            'phoneNo.required' 		=> 'Phone no field is required.',
            'password.required'     => 'Password field is required.',
            'password.string'       => 'Password must be in string.',
            'password.confirmed'    => 'Password and Confirm Password are not the same.'
        ];

        $validation = [
            'name' 	=> 'required|string',
            'email' 	=> 'required|email',
           // 'phoneNo' 	=> 'required|string',
            'password' 	=> 'required|string|confirmed'
        ];
        $request->validate($validation, $messages);

        $customer = Customer::where('CSEmail',$request->email)->first();
        if ($customer != null){
            return response()->json([
                'error' => '1',
                'message' => 'Email has been registered'
            ], 400);
        }

        try {
            DB::beginTransaction();

			$autoNumber = new AutoNumber();
			$customerCode = $autoNumber->generateCustomerCode();

            $customer = new Customer();
            $customer->CSCode              	= $customerCode;
            $customer->CSName              	= $request->name ?? '';
            $customer->CSPhoneNo           	= $request->phoneNo ?? '';
            $customer->CSEmail             	= $request->email ?? '';
            //$customer->CSDocType         	= $request->docType;
            //$customer->CSDocNo           	= $request->docNo;
            //$customer->EEDOB             	= $request->dateOfBirth ?? '';
            //$customer->CSMStreet1        	= $request->address1 ?? '';
            //$customer->CSMStreet2        	= $request->address2 ?? '';
            //$customer->CSMPostcode       	= $request->postcode ?? '';
            //$customer->CSMCity           	= $request->city ?? '';
            //$customer->CSM_StateCode     	= $request->state;
			$customer->CSActivationCode	 	= $this->getActivationCode();
            $customer->CSRegister          	= 0;
            $customer->CSActive            	= 1;
            $customer->CSCB                	= $customerCode;
            $customer->CSMB                	= $customerCode;
            $customer->save();

            $user = new User();
            $user->USCode       = $customerCode;
            $user->USPwd        = Hash::make($request->password);
            $user->USType       = 'CS';
            $user->USResetPwd   = 0;
            $user->USActive     = 1;
            $user->USCB         = $customerCode;
            $user->save();

			$this->sendMail($request,$user,$customer);

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
            'redirect' => route('customer.login.index'),
            'message' => 'Please check your email to activate your account.'
        ]);
    }

	private function getActivationCode(){

	  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	  $randomString = '';

	  for ($i = 0; $i < 20; $i++) {
		$index = rand(0, strlen($characters) - 1);
		$randomString .= $characters[$index];
	  }

	  return $randomString;
	}

	public function activate(Request $request, $activationCode = null){

		$message = 'The account has been activated successfully!';

        $customer = Customer::where('CSActivationCode',$activationCode)->where('CSActive',1)->orderby('CSID','desc')->first();
        if ($customer == null){
			$message = 'Invalid activation link!';
			return view('auth.login', compact('message'));
        }

		if ($customer->CSRegister == 1){
			$message = 'This account has been activated!';
			return view('auth.login', compact('message'));
        }

        try {
            DB::beginTransaction();

            $customer->CSRegister 		= 1;
            $customer->CSRegisterDate	= carbon::now();
            $customer->save();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();
			$message = $e;
        }

		return view('customer.auth.login', compact('message'));
    }


    private function sendMail(Request $request,$user,$customer){

		if($request->email != null) {
            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCB;
            $emailLog->ELType 	= 'Register';

            // Send Email
            $emailData = array(
                'id' => $user->USID,
                'name'  => $request->name ?? '',
                'email' => $request->email,
                'activationCode' => $customer->CSActivationCode,
                'domain' => config('app.url'),
            );

            try {
                Mail::send(['html' => 'email.newUser'], $emailData, function($message) use ($emailData) {
                   // $message->from('parking@example.com', 'noreply');
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Thank you for your registration');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
				$emailLog->save();
            } catch (\Exception $e) {
//                $e->getLine ()
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
				$emailLog->save();
				return response()->json([
					'error' => '1',
					'message' => 'Sent email is failed!'.$e->getMessage()
				], 400);
            }

        }
    }

}
