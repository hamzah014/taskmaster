<?php

namespace App\Http\Controllers\PublicUser;

use App\Http\Controllers\Controller;
use App\Models\CertApp;
use App\Models\Contractor;
use App\Models\TenderApplication;
use App\Models\TenderProposal;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Symfony\Component\HttpFoundation\Response;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use Dompdf\Dompdf;
use App\Services\DropdownService;
use App\Models\AutoNumber;

class PublicUserController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        Session::put('page', 'publicUser') ;

        $user = Auth::user();
        if($user->USLastLogin){
            $timestamp = strtotime($user->USLastLogin);

            // Convert the timestamp to a date object
            $user->USLastLogin  = date('d/m/Y H:i', $timestamp);
        }

        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::leftjoin('MSCertAppPRocess','CAPCode','CA_CAPCode')
                            ->where('CA_CONo', $contractor->CONo)
                            ->orderby('CAID','desc')
                            ->first();

        $validDate = true;

        if($certApp) {
            if ($certApp->CACertEndDate != null){
                $timestamp = strtotime($certApp->CACertEndDate);
                $certApp->CACertEndDate = date('d/m/Y H:i A', $timestamp);

                if (Carbon::now() > $certApp->CACertEndDate) {
                    $validDate = false;
                }
            }
        }

        // $certApp = null;

        if($contractor->COCompleteEntry == 0){
            Session::put('certAppNo', $certApp->CANo) ;
            return view('publicUser.index',compact('user', 'contractor', 'certApp', 'validDate'));
        }
        else{
           $tenderProposal = TenderProposal::where('TP_CONo', $user->USCode)->first();


            $menungguBayaran = TenderApplication::where('TA_CONo', $user->USCode)->wherenull('TA_PLNo')->count();
            $tenderBelumLengkap = TenderProposal::where('TP_CONo', $user->USCode)->where('TP_TPPCode','DF')->count();
            $belumTarikhLuput = 0;
            $tenderBerjaya = TenderProposal::where('TP_CONo', $user->USCode)->where('TP_TPPCode','SB')->count();
            $tenderDiluluskan = 0;
            $lawatanTapak = TenderProposal::join('TRTender','TDNo','TP_TDNo')->where('TDSiteBrief',1)->where('TP_CONo', $user->USCode)->count();


            return view('publicUser.index2',
                compact('user', 'contractor', 'certApp', 'validDate', 'menungguBayaran', 'tenderBelumLengkap', 'belumTarikhLuput',
                    'tenderBerjaya', 'tenderDiluluskan', 'lawatanTapak', 'tenderProposal')
            );
        }


    }


    public function index_auto(){


        $userLogin = Session::get('userLogin');
        $userPassword = Session::get('userPassword');

        if ($userLogin == null){
            $userLogin = "CO00000030";
            $userPassword = "111111";
            Session::put('userLogin', $userLogin);
            Session::put('userPassword', $userPassword);
        }

		if (Auth::attempt(['USCode' => $userLogin, 'password' => $userPassword, 'USActive' => 1])) {
			// The user is active, not suspended, and exists.
			//return view('home');
		}
        else{
            return response()->json([
                'error' => '1',
                'message' => 'Unable login to public user',
            ], 400);
        }

        Session::put('page', 'publicUser') ;

        $user = Auth::user();
        if($user->USLastLogin){
            $timestamp = strtotime($user->USLastLogin);

            // Convert the timestamp to a date object
            $user->USLastLogin  = date('d/m/Y H:i', $timestamp);
        }

        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::leftjoin('MSCertAppPRocess','CAPCode','CA_CAPCode')
                            ->where('CA_CONo', $contractor->CONo)
                            ->orderby('CAID','desc')
                            ->first();

        $validDate = true;

        if($certApp) {
            if ($certApp->CACertEndDate != null){
                $timestamp = strtotime($certApp->CACertEndDate);
                $certApp->CACertEndDate = date('d/m/Y H:i', $timestamp);

                if (Carbon::now() > $certApp->CACertEndDate) {
                    $validDate = false;
                }
            }
        }

        // $certApp = null;

        if($contractor->COCompleteEntry == 0){
            return view('publicUser.index',
                compact('user', 'contractor', 'certApp', 'validDate')
            );
        }
        else{
           $tenderProposal = TenderProposal::where('TP_CONo', $user->USCode)->first();


            $menungguBayaran = TenderApplication::where('TA_CONo', $user->USCode)->wherenull('TA_PLNo')->count();
            $tenderBelumLengkap = TenderProposal::where('TP_CONo', $user->USCode)->where('TP_TPPCode','DF')->count();
            $belumTarikhLuput = 0;
            $tenderBerjaya = TenderProposal::where('TP_CONo', $user->USCode)->where('TP_TPPCode','SB')->count();
            $tenderDiluluskan = 0;
            $lawatanTapak = TenderProposal::join('TRTender','TDNo','TP_TDNo')->where('TDSiteBrief',1)->where('TP_CONo', $user->USCode)->count();


            return view('publicUser.index2',
                compact('user', 'contractor', 'certApp', 'validDate', 'menungguBayaran', 'tenderBelumLengkap', 'belumTarikhLuput',
                    'tenderBerjaya', 'tenderDiluluskan', 'lawatanTapak', 'tenderProposal')
            );
        }


    }

    public function beritaDatatable(){
        $query = Contractor::select('CONo', 'COName')->where('CONo', 'CO00000001')->get();

        return datatables()->of($query)
            ->addColumn('tarikh', function($row) {
                return $row->CONo;
            })
            ->addColumn('tajuk', function($row) {
                return $row->COName;
            })
            ->rawColumns(['tarikh','tajuk'])
            ->make(true);
    }

    public function viewCert($certId){

        $certApp = CertApp::where('CACertNo',$certId)->with('contractor')->first();

        $bidangs = $this->dropdownService->kod_bidang();

        $contractor = $certApp->contractor->where('CONo',$certApp->contractor->CONo)
                     ->with('state','contractorMOF','contractorMOF.MOFActivity','contractorMOF.MOFActivity.mainCategory','contractorMOF.MOFActivity.subCategory')
                     ->first();

        $kod_bidang = $contractor->contractorMOF;

        $startDateString = $certApp->CACertStartDate;
        $endDateString = $certApp->CACertEndDate;

        // Convert the date strings to DateTime objects
        $startDate = Carbon::parse($startDateString);
        $endDate = Carbon::parse($endDateString);

        // Calculate the difference between the two dates
        $tempohSah = $startDate->diffInDays($endDate);

        $download = false; //true for download or false for view
        $filename = "Sijil Akuan Pendaftaran";
        $template = "SIJIL";
        $templateName = "SIJIL"; // Specific template name to check in generalPDF
        // return view('general.templatePDF',
        $view = View::make('general.templatePDF',
        compact(
            'certApp','tempohSah','contractor','kod_bidang','template','templateName','bidangs'
        ));
        $response = $this->generatePDF($view,$download,$filename);

        return $response;

    }
}
