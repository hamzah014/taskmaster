<?php

namespace App\Http\Controllers\Osc;

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

class OSCController extends Controller
{

    public function index(){


        return view('osc.index'
        );
    }
    public function index_auto(){

		if (Auth::attempt(['USCode' => 'OSC1000', 'password' => '123456', 'USActive' => 1])) {
			// The user is active, not suspended, and exists.
			//return view('home');

			Session::put('page', 'osc');
            return view('osc.index');

		}
    }
}
