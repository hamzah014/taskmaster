<?php

namespace App\Http\Controllers\PublicUser\Tender;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\SSMCompany;
use App\Models\Tender;
use App\Models\TenderSpec;
use App\Models\TenderApplication;
use App\Models\TenderFormDetail;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\TenderDetail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;

class TenderController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){
        return view('publicUser.tender.index');
    }

    public function view($id){
        $tender = Tender::where('TDNo', $id)->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $yn = $this->dropdownService->yn();

        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod = $tender->TDPublishPeriod;

        $tenderDocuments = [];
        $tenderDetails = $tender->tenderDetailD;

//         if($tender->tenderDetailD){
//             foreach ($tender->tenderDetail as $tenderFormHeaderPublic){
//                 $tenderFormDetails = TenderFormDetail::where('TDFD_TDNo', $tenderFormHeaderPublic->TDFH_TDNo)
//                     ->where('TDFD_TDFHCode', $tenderFormHeaderPublic->TDFHCode)
//                     ->where('TDFDType', 'FILE')
//                     ->where('TDFDDownload_FileID', 1)
//                     ->get();
//
//                 foreach($tenderFormDetails as $tenderFormDetail){
//                     $tenderDocuments = [];
//
//                     $tenderDocuments = [
//                         'TDFDDesc' => $tenderFormDetail->TDFDDesc,
//                         'TDFDCode' => $tenderFormDetail->TDFDCode,
//                     ];
//
//                     array_push($data, $tenderDocuments);
//                 }
//             }
//         }

        return view('publicUser.tender.view',
            compact('tender', 'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms',
                'tenderDocuments', 'tenderDetails')
        );
    }

    // public function viewTemplate($id, $templateName) {
    //     $tender = Tender::where('TDNo', $id)->first();
    //     $tenderSpecs = TenderSpec::where('TDS_TDNo', $id)->get();
    //     $tenderBQFs = TenderSpec::where('TDS_TDNo', $id)->where('TDStockInd', '1')->get();
        
    //     $template = "DOKUMEN";
    //     $download = false; // true for download or false for view
    //     $view = View::make('general.templatePDF',
    //         compact('template', 'templateName', 'tender', 'tenderSpecs', 'tenderBQFs')
    //     );
        
    //     $response = $this->generatePDF($view, $download);
        
    //     return $response;
    // }

    public function viewSpec($id){
        $tender = Tender::where('TDNo', $id)->first();

        $tenderDetail = TenderDetail::where('TDD_TDNo', $tender->TDNo)->where('TDD_MTCode' , 'SPF')->first();

        $name = $tenderDetail->TDDTitle;

        $tenderSpecs = TenderSpec::where('TDS_TDNo' , $id)->get();

        $tenderBQFs = TenderSpec::where('TDS_TDNo' , $id)->where('TDStockInd' , '1')->get();

        $template = "DOKUMEN";
        $download = true; //true for download or false for view
        $templateName = "SPF"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF',
        compact('template' , 'templateName' ,'tender','tenderSpecs', 'tenderBQFs')
        );
        $response = $this->generatePDF($view,$download,$name);

        return $response;

        // return view('publicUser.tender.templateSPF',
        //     compact('tender','tenderSpecs', 'tenderBQFs')
        // );
    }

    public function viewCF($id){
        $tender = Tender::where('TDNo', $id)->first();

        $tenderDetail = TenderDetail::where('TDD_TDNo', $tender->TDNo)->where('TDD_MTCode' , 'CF')->first();

        $name = $tenderDetail->TDDTitle;

        $tenderSpecs = TenderSpec::where('TDS_TDNo' , $id)->get();

        $tenderBQFs = TenderSpec::where('TDS_TDNo' , $id)->where('TDStockInd' , '1')->get();

        $template = "DOKUMEN";
        $download = true; //true for download or false for view
        $templateName = "CF"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF',
        compact('template' , 'templateName' ,'tender','tenderSpecs', 'tenderBQFs')
        );
        $response = $this->generatePDF($view,$download,$name);

        return $response;

        // return view('publicUser.tender.templateCF',
        //     compact('tender','tenderSpecs', 'tenderBQFs')
        // );
    }

    public function viewBQF($id){
        $tender = Tender::where('TDNo', $id)->first();

        $tenderDetail = TenderDetail::where('TDD_TDNo', $tender->TDNo)->where('TDD_MTCode' , 'BQF')->first();

        $name = $tenderDetail->TDDTitle;

        $tenderSpecs = TenderSpec::where('TDS_TDNo' , $id)->get();

        $tenderBQFs = TenderSpec::where('TDS_TDNo' , $id)->where('TDStockInd' , '1')->get();

        $template = "DOKUMEN";
        $download = true; //true for download or false for view
        $templateName = "BQF"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF',
        compact('template' , 'templateName' ,'tender','tenderSpecs', 'tenderBQFs')
        );
        $response = $this->generatePDF($view,$download, $name);

        return $response;

        // return view('publicUser.tender.templateBQF',
        //     compact('tender','tenderSpecs', 'tenderBQFs')
        // );
    }

    public function listBuyDoc(){
        return view('publicUser.tender.listBuyDoc');
    }

    public function viewBuyDoc($id){
        $tender = Tender::where('TDNo', $id)->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $yn = $this->dropdownService->yn();
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod =$tender->TDPublishPeriod;

        $tenderDocuments = [];
        $tenderDetails = $tender->tenderDetail;


//        if($tender->tenderFormHeaderPublic){
//            foreach ($tender->tenderFormHeaderPublic as $tenderFormHeaderPublic){
//                $tenderFormDetails = TenderFormDetail::where('TDFD_TDNo', $tenderFormHeaderPublic->TDFH_TDNo)
//                    ->where('TDFD_TDFHCode', $tenderFormHeaderPublic->TDFHCode)
//                    ->where('TDFDType', 'FILE')
//                    ->where('TDFDDownload_FileID', 1)
//                    ->get();
//
//                foreach($tenderFormDetails as $tenderFormDetail){
//                    $tenderDocuments = [];
//
//                    $tenderDocuments = [
//                        'TDFDDesc' => $tenderFormDetail->TDFDDesc,
//                        'TDFDCode' => $tenderFormDetail->TDFDCode,
//                    ];
//
//                    array_push($data, $tenderDocuments);
//                }
//            }
//        }

        return view('publicUser.tender.viewBuyDoc',
            compact('tender', 'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms', 'tenderDocuments', 'tenderDetails')
        );
    }

    public function tenderDatatable(Request $request){
        $query = Tender::with('tenderAdv')
        ->where('TD_TPCode', 'PA')
        ->get()
        ->sortByDesc(function ($tender) {
            return $tender->tenderAdv ? $tender->tenderAdv->TDAPublishDate : null;
        });

        return datatables()->of($query)
            ->editColumn('TD_TCCode', function($row) {
                $tender_sebutharga = $this->dropdownService->tender_sebutharga();
                return $tender_sebutharga[$row->TD_TCCode];
            })
            ->editColumn('TDTitle', function($row) {
                $route = route('publicUser.tender.view', [$row->TDNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->TDTitle.' </a>';

                return $result;
            })
            ->editColumn('TDPublishDate', function($row) {

                if($row->tenderAdv){
                    $pubDate = $row->tenderAdv->TDAPublishDate;

                    $carbonDatetime = Carbon::parse($pubDate);
    
                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }else{
                    $formattedDate = "-";

                }

                return $formattedDate;
            })
            ->editColumn('TDClosingDate', function($row) {

                if($row->tenderAdv){
                    $closingDate = $row->tenderAdv->TDAClosingDate;

                    $carbonDatetime = Carbon::parse($closingDate);
    
                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }else{
                    $formattedDate = "-";

                }

                return $formattedDate;
            })
            ->editColumn('TDSiteBrief', function($row) {
                $yn = $this->dropdownService->yn();
                return $yn[$row->TDSiteBrief];
            })
            ->editColumn('TDDocAmt', function($row) {

                if($row->tenderAdv){
                    $price = "RM " . number_format($row->tenderAdv->TDADocAmt,2,'.',',') ?? "RM 0";

                }else{
                    $price = "RM 0";

                }

                return $price;
            })
            ->addColumn('Action', function($row) {

                $route = route('publicUser.application.addCart', [$row->TDNo]);

                $result = '<a data-id="'.$row->TDNo.'" class="add-to-cart new modal-trigger waves-effect waves-light btn btn-primary">Pilih Dokumen</a>';

                return $result;
            })
            ->rawColumns(['TD_TCCode','TDPublishDate', 'TDClosingDate', 'TDSiteBrief', 'TDDocAmt', 'Action', 'TDTitle'])
            ->make(true);
    }

    public function tenderApplicationDatatable(Request $request){
        $user = Auth::user();
        $query = TenderApplication::where('TA_CONo', $user->USCode)
            ->where('TAActive', 1)
            //->where('TAStatus', 'DRAFT')
            ->get();

        return datatables()->of($query)
            ->editColumn('TDNo', function($row) {

                return $row->tender->TDNo;
            })
            ->editColumn('TDTitle', function($row) {
                $route = route('publicUser.tender.viewBuyDoc', [$row->tender->TDNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->tender->TDTitle.' </a>';

                return $result;
            })
            ->editColumn('TDClosingDate', function($row) {
                $carbonDatetime = Carbon::parse($row->tender->TDClosingDate);

                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->editColumn('TDDocAmt', function($row) {

                return 'RM'.number_format($row->tender->TDDocAmt ?? 0,2, '.', '');
            })
            ->addColumn('Action', function($row) {

                $route = route('publicUser.application.create', [$row->TANo]);

                $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light btn btn-primary">Edit</a>';

                return $result;
            })
            ->rawColumns(['TDNo', 'TDTitle', 'TDClosingDate', 'TDDocAmt', 'Action'])
            ->make(true);
    }
}
