<?php

namespace App\Http\Controllers\Auth;

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
        return view('auth.login');
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

        $user = User::where('USEmail', $request->email)->where('USType','US')->first();

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
				'redirect' => route('dashboard.index'),
				'message' => 'Successfully Login. Welcome to TaskMaster.'
			]);

		}else{
			return response()->json([
                'error' => '1',
                'message' => 'Email or password invalid!'
            ], 400);
		}

    }

}
