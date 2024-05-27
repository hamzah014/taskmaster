<?php

namespace App\Http\Controllers\PublicUser\Auth;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
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
use App\Models\Announcement;
use App\Models\Tender;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use App\Services\DropdownService;

class LoginController extends Controller
{

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
    public function __construct(DropdownService $dropdownService)
    {
        $this->middleware('guest')->except('logout');
        $this->dropdownService = $dropdownService;

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

    public function index(Request $request){
        // if ($request->ajax()) {

        //     $query = Contractor::select('CONo', 'COName')->get();
        //     return DataTables::of($query)
        //         ->addColumn('tarikh', function($row) {
        //             return $row->CSCode;
        //         })
        //         ->addColumn('tajuk', function($row) {
        //             return $row->CSName;
        //         })
        //         ->rawColumns(['tarikh','tajuk'])
        //         ->make(true);
        // }

        return view('publicUser.auth.login');
    }

    public function logout(){

        //$userUpdate = User::find(Auth::user()->id);


        // $log = new ActivityLog();
        // $log -> user_id = Auth::user()->id;
        // $log -> start_time = $userUpdate ->login_time;
        // $log -> end_time = Carbon::now()->format('Y-m-d H:i:s');
        // $log->save();

		$user = Auth::user();

        Auth::guard()->logout();

        if (Session::get('page') == 'contractor'){
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
            'loginID.required' 	=> trans('message.loginID.required'),
            'password.required' => trans('message.password.required'),
		];

		$validation = [
			'loginID' 	=> 'required|string',
			'password' 	=> 'required|string',
		];


        $request->validate($validation, $messages);


        $contractor = Contractor::where('COEmail', $request->loginID)
						->where('COActive',1)
						->first();


		if ($contractor == null){
			return response()->json([
                'error' => '1',
                'message' => 'ID log masuk atau kata laluan tidak sah!'
            ], 400);
		}

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
				'message' => 'Daftar masuk berjaya.'
			]);

		}else{
			return response()->json([
                'error' => '1',
                'message' => 'ID log masuk atau kata laluan tidak sah!'
            ], 400);
		}

    }

    // {{--Working Code Datatable--}}
    public function beritaDatatable(){

        $query = Announcement::where('ACActive',0)->orderBy('ACDate', 'DESC')->get();

        return DataTables::of($query)
            ->editColumn('ACDate', function($row) {
                $carbonDatetime = Carbon::parse($row->ACDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })

            ->editColumn('ACTitle', function($row) {
                $result = '<a href="#" data-bs-toggle="modal" data-bs-target="#modalBerita"  onclick="openBeritaModal(\''.$row->ACID.'\')">' . $row->ACTitle . '</a>';
                return $result;
            })
            ->rawColumns(['ACDate','ACTitle'])
            ->make(true);
    }

    // {{--Working Code Datatable--}}
    public function iklanDatatable(){

        $query = Tender::where('TD_TPCode', 'PA')
            ->whereDate('TDClosingDate','>',carbon::now())
            ->orderby('TDPublishDate','DESC')
            ->orderby('TDProposalValidDate','DESC')->get();

        return DataTables::of($query)
            ->editColumn('TD_TCCode', function($row) {

                $code = $row->TD_TCCode;

                $tender_sebutharga = $this->dropdownService->tender_sebutharga();
                $result = $tender_sebutharga[$code];

                return $result;
            })
            ->editColumn('TDTitle', function($row) {

                $result = '<a href="#" data-bs-toggle="modal" data-bs-target="#modalIklan"  onclick="openIklanModal(\''.$row->TDNo.'\')">' . $row->TDTitle . '</a>';
                return $result;
            })
            ->editColumn('TDPublishDate', function($row) {

                $carbonDatetime = Carbon::parse($row->TDPublishDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y h:ia');

                return $formattedDate;
            })
            ->editColumn('TDClosingDate', function($row) {

                $carbonDatetime = Carbon::parse($row->TDClosingDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y h:ia');

                return $formattedDate;
            })
            ->editColumn('TDSiteBrief', function($row) {

                $code = $row->TDSiteBrief;

                $yn = $this->dropdownService->yn();
                $result = $yn[$code];

                return $result;
            })
            ->editColumn('TDDocAmt', function($row) {

                return 'RM'.$row->TDDocAmt;
            })
            ->rawColumns(['TD_TCCode','TDClosingDate','TDSiteBrief','TDTitle'])
            ->make(true);
    }

    public function beritaModal($id){

        $announcement = Announcement::where('ACID', $id)->first();

        return view('publicUser.auth.beritaModal',
            compact('announcement')
        );


    }

    public function iklanModal($id){

        $tender = Tender::where('TDNo', $id)->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $yn = $this->dropdownService->yn();

        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod = $tender->TDPublishPeriod;

        $tenderDocuments = [];
        $tenderDetails = $tender->tenderDetail;

        // if($tender->tenderDetail){
        //     foreach ($tender->tenderDetail as $tenderFormHeaderPublic){
        //         $tenderFormDetails = TenderFormDetail::where('TDFD_TDNo', $tenderFormHeaderPublic->TDFH_TDNo)
        //             ->where('TDFD_TDFHCode', $tenderFormHeaderPublic->TDFHCode)
        //             ->where('TDFDType', 'FILE')
        //             ->where('TDFDDownload_FileID', 1)
        //             ->get();

        //         foreach($tenderFormDetails as $tenderFormDetail){
        //             $tenderDocuments = [];

        //             $tenderDocuments = [
        //                 'TDFDDesc' => $tenderFormDetail->TDFDDesc,
        //                 'TDFDCode' => $tenderFormDetail->TDFDCode,
        //             ];

        //             array_push($data, $tenderDocuments);
        //         }
        //     }
        // }

        return view('publicUser.auth.iklanModal',
            compact('tender', 'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms', 'tenderDocuments', 'tenderDetails')
        );


    }

    // {{--Working Code Datatable--}}
    public function cartaDatatable(){

        $query = Tender::orderby('TDClosingDate','DESC')->get();

        return DataTables::of($query)
            ->editColumn('TD_TCCode', function($row) {

                $code = $row->TD_TCCode;

                $tender_sebutharga = $this->dropdownService->tender_sebutharga();
                $result = $tender_sebutharga[$code];

                return $result;
            })
            ->editColumn('TDTitle', function($row) {
                $result = '<a href="#" data-bs-toggle="modal" data-bs-target="#modalCarta"  onclick="openCartaModal(\''.$row->TDNo.'\')">' . $row->TDTitle . '</a>';
                return $result;
            })
            ->editColumn('TDClosingDate', function($row) {

                $carbonDatetime = Carbon::parse($row->TDClosingDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->editColumn('TDSiteBrief', function($row) {

                $code = $row->TDSiteBrief;

                $yn = $this->dropdownService->yn();
                $result = $yn[$code];

                return $result;
            })
            ->rawColumns(['TD_TCCode','TDClosingDate','TDSiteBrief','TDTitle'])
            ->make(true);
    }

    public function cartaModal($id){

        $tender = Tender::where('TDNo', $id)->with('tenderProposalNotDraf')->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $yn = $this->dropdownService->yn();

        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod = $tender->TDPublishPeriod;


        return view('publicUser.auth.cartaModal',
            compact('tender', 'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms')
        );


    }


}
