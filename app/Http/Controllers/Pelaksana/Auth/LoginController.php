<?php

namespace App\Http\Controllers\Pelaksana\Auth;

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
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        //$this->username = $this->findUsername();
    }

    public function findUsername()
    {
        $login = request()->input('email');

        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'Email' : 'UserCode';

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
        return view('customer.auth.login');
    }

    public function logout(){

        //$userUpdate = User::find(Auth::user()->id);

/*
        $log = new ActivityLog();
        $log -> user_id = Auth::user()->id;
        $log -> start_time = $userUpdate ->login_time;
        $log -> end_time = Carbon::now()->format('Y-m-d H:i:s');
        $log->save();
*/
		$user = Auth::user();

        Auth::guard()->logout();

		if (Session::get('page') == 'customer'){
			return redirect()->route('customer.login.index');
		}else{
			return redirect()->route('login.index');
		}

		/*if ($user->USType == 'AB'){
		}elseif ($user->USType == 'HR'){
			return redirect()->route('mohr.login.index');
		}else{
			return redirect()->route('login.index');
		}*/
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
            'loginID.required' 	=> trans('message.loginID.required'),
            'password.required' => trans('message.password.required'),
		];

		$validation = [
			'loginID' 	=> 'required|string',
			'password' 	=> 'required|string',
		];


        $request->validate($validation, $messages);


        $customer = Customer::where('CSEmail', $request->loginID)
						->where('CSActive',1)
						->first();

		if ($customer == null){
			return response()->json([
                'error' => '1',
                'message' => 'Login ID or password is not valid!'
            ], 400);
		}

		if (Auth::attempt(['USCode' => $customer->CSCode, 'password' => $request->password, 'USActive' => 1])) {
			// The user is active, not suspended, and exists.
			//return view('home');

			Session::put('page', 'customer');

			return response()->json([
				'success' => '1',
				'redirect' => route('customer.trans.serviceApplication.index'),
				'message' => 'Login Successfully'
			]);

		}else{
			return response()->json([
                'error' => '1',
                'message' => 'Login ID or password is not valid!'
            ], 400);
		}

    }




}
