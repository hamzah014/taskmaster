<?php

namespace App\Http\Controllers\Contractor\Auth;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;

use App\Http\Requests;
use App\Models\Role;
use App\Models\Contractor;
use App\User;
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
        return view('contractor.auth.login');
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

        if (Session::get('page') == 'publicUser'){
            return redirect()->route('publicUser.login.index');
        }else if (Session::get('page') == 'contractor'){
            return redirect()->route('contractor.login.index');
        }else{
            return redirect()->route('login.index');
        }
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
            'projectCode.required' 	=> trans('message.loginID.required'),
            'password.required' => trans('message.password.required'),
		];

		$validation = [
			'projectCode' 	=> 'required|string',
			'password' 	=> 'required|string',
		];


        $request->validate($validation, $messages);

        $project = Project::where('PTCode', $request->projectCode)->where('PTActive',1)->first();

        $contractor = Contractor::where('CONo', $project->PT_CONo ?? '')->where('COActive',1)->first();

		if ($contractor == null){
			return response()->json([
                'error' => '1',
                'message' => 'ID log masuk atau kata laluan tidak sah!'
            ], 400);
		}


		if (! Hash::check($request->password,$project->PTPwd)){
			return response()->json([
                'error' => '1',
                'message' => 'ID log masuk atau kata laluan tidak sah!'
            ], 400);
		}

        $user = User::where('USCode', $contractor->CONo)->first();

        // To login specific user using eloquent model
        Auth::guard('web')->login($user);

        // For getting logged in user
        Auth::guard('web')->user();

        // To check if user is logged in
        if (Auth::guard('web')->check()) {
            // Logged in
                Session::put('page', 'contactor');
                Session::put('project', $request->projectCode);

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

    }
}
