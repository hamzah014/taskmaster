<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\User;
use Carbon\Carbon;
use Auth;
use Illuminate\Http\Request;
use Session;

class HomeController extends Controller
{
//    protected $user;
//
//    public function __construct(Request $request,$user)
//    {
//        $this->user = $user;
//    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function index()
    {

		$user = Auth::user();

        if(Auth::user()->hasModule('Dashboard')){

            return redirect()->route('dashboard.overall.index'); 

        }
        else{
            
            return view('unauthorization');

        }


    }


    public function unauthorization()
    {
        return view('unauthorization');
    }


}
