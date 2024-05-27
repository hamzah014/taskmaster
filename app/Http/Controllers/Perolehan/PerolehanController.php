<?php

namespace App\Http\Controllers\Perolehan;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Yajra\DataTables\DataTables;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\LetterAcceptance;
use App\Models\LetterIntent;
use App\Models\Tender;
use App\Models\Project;
use App\Models\FileType;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class PerolehanController extends Controller
{

    public function index(){

        $user = Auth::user();
        if($user->USLastLogin){
            $timestamp = strtotime($user->USLastLogin);

            // Convert the timestamp to a date object
            $user->USLastLogin  = date('d/m/Y H:i', $timestamp);
        }

        $currentYear = Carbon::now()->year;

        $startDate = $currentYear . '-01-01';
        $endDate = $currentYear . '-12-31';

        $jumlahtender = Tender::whereBetween('TDPublishDate', [$startDate, $endDate])
            ->orWhereBetween('TDClosingDate', [$startDate, $endDate])
            ->count();

        $iklantender = Tender::where('TD_TPCode' , 'PA')->count();
        $awardcontractor = Project::whereBetween('PTStartDate', [$startDate, $endDate])
            ->orWhereBetween('PTEndDate', [$startDate, $endDate])
            ->count();

        // dd($awardcontractor);

        //dd($jumlahtender , $iklantender);

        $bukapeti = Tender::where('TD_TPCode' , 'CA')->count();
        $mesyuaratTender = Tender::where('TD_TPCode' , 'IM')->count();
        $intentWaiting = LetterIntent::where('LIStatus' , 'SUBMIT')->count();
        $intentReject = LetterIntent::where('LIStatus' , 'REJECT')->count();
        $acceptanceWaiting = LetterAcceptance::where('LAStatus' , 'SUBMIT')->count();
        $acceptanceReject = LetterAcceptance::where('LAStatus' , 'REJECT')->count();


        return view('perolehan.index' , compact('user','jumlahtender','iklantender' , 'awardcontractor' ,'intentWaiting' , 'intentReject' , 'acceptanceWaiting'
                            , 'acceptanceReject' , 'bukapeti' , 'mesyuaratTender'));
    }

    public function index_auto(){

        $user = Auth::user();

        if($user->USType == 'CO'){
            Auth::attempt(['USCode' => 'PO1000', 'password' => '123456', 'USActive' => 1]);
        }

//		if (Auth::attempt(['USCode' => 'PO1000', 'password' => '123456', 'USActive' => 1])) {

            $user = Auth::user();
            if($user->USLastLogin){
                $timestamp = strtotime($user->USLastLogin);

                // Convert the timestamp to a date object
                $user->USLastLogin  = date('d/m/Y H:i', $timestamp);
            }

			// The user is active, not suspended, and exists.
			//return view('home');

            $currentYear = Carbon::now()->year;

            $startDate = $currentYear . '-01-01';
            $endDate = $currentYear . '-12-31';

            $jumlahtender = Tender::whereBetween('TDPublishDate', [$startDate, $endDate])
                ->orWhereBetween('TDClosingDate', [$startDate, $endDate])
                ->count();

            $iklantender = Tender::where('TD_TPCode' , 'PA')->count();
            $awardcontractor = Project::whereBetween('PTStartDate', [$startDate, $endDate])
                ->orWhereBetween('PTEndDate', [$startDate, $endDate])
                ->count();

            // dd($awardcontractor);

            //dd($jumlahtender , $iklantender);

            $bukapeti = Tender::where('TD_TPCode' , 'CA')->count();
            $mesyuaratTender = Tender::where('TD_TPCode' , 'IM')->count();
            $intentWaiting = LetterIntent::where('LIStatus' , 'SUBMIT')->count();
            $intentReject = LetterIntent::where('LIStatus' , 'REJECT')->count();
            $acceptanceWaiting = LetterAcceptance::where('LAStatus' , 'SUBMIT')->count();
            $acceptanceReject = LetterAcceptance::where('LAStatus' , 'REJECT')->count();



            //dd($x);

			Session::put('page', 'perolehan');
            return view('perolehan.index' , compact('user','jumlahtender','iklantender' , 'awardcontractor' ,'intentWaiting' , 'intentReject' , 'acceptanceWaiting'
                            , 'acceptanceReject' , 'bukapeti' , 'mesyuaratTender'));

//		}
    }

    public function setting(){

        return view('perolehan.setting');
    }

}
