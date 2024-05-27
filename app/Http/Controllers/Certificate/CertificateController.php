<?php

namespace App\Http\Controllers\Certificate;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\Customer;
use App\Models\FileAttach;
use App\Models\ProjectIssue;
use App\Models\ProjectIssueDet;
use App\Models\Tender;
use App\Models\TenderProposal;
use App\Models\WebSetting;
use App\Models\Audit;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use auditDatatable;


use App\Http\Requests;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use App\Services\DropdownService;
use App\Jobs\Report\ReportBackgroundVerification;
use App\Models\Certificate;
use App\Models\CertStatus;

class CertificateController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        
    }

    public function index(){

        $statusActive = $this->dropdownService->statusActive();
        $projekCert = $this->dropdownService->projekCert();
         
        return view('certificate.index', compact('statusActive', 'projekCert'));
       
    }

    public function view($id){

        $certificate = Certificate::where('CEID', $id)->first();
        // $cerStatus = CertStatus::where('CSID',$id)->first();
     

        return view('certificate.view', compact('certificate'));
    }


    public function certDatatable(Request $request){
        $query = Certificate::orderBy('CECD', 'DESC')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('PJDesc', function($row){

                return $row->project->PJDesc;
            })
            ->addColumn('status', function ($certificate) {

               if ($certificate->CERevokeInd == 1){
                    $status = '<span class="badge badge-outline badge-danger">Inactive</span>';
               } elseif ($certificate->CEEndDate < $certificate->CEStartDate) {
                    $status = '<span class="badge badge-outline badge-danger">Inactive</span>';
               } else {
                    $status = '<span class="badge badge-outline badge-success">Active</span>';
               }
               
                return $status; 
                
            })
            ->addColumn('action', function($row){

                $route = route('certificate.view',$row->CEID);
                
                $result = '<a class="btn btn-secondary" href="'.$route.'" ><i class="text-dark fas fa-regular fa-eye"></i></a>';
                return $result;
            })

            ->rawColumns(['indexNo', 'PJDesc', 'status', 'action'])
            ->make(true);
    }

    public function searchFilter(Request $request){
        try {
            $certificate = Certificate::query();

            if ($request->filled('search_project')) {
                $certificate->where('CE_PJCode', 'LIKE', '%' . $request->search_project . '%');
            }
            if ($request->search_certno != null) {
                $certificate->where('CENo', 'LIKE', '%' . $request->search_certno . '%');
            }
    
            if ($request->filled('search_status')) {
                if ($request->search_status == 1) {
                    $certificate->whereDate('CEEndDate', '>=', now())->where('CERevokeInd', 0);
                } else {
                    $certificate->where(function ($query) {
                        $query->whereDate('CEEndDate', '<', now())
                              ->orWhere('CERevokeInd', 1);
                    });
                }
            }
            if ($request->filled('search_ic')) {
                $certificate->where('CEIDNo', 'LIKE', '%' . $request->search_ic . '%');
            }
            if ($request->filled('search_name')) {
                $certificate->where('CEName', 'LIKE', '%' . $request->search_name . '%');
            }
            
            $query = $certificate->orderBy('CECD', 'DESC')->get();

            return datatables()->of($query)
                ->addColumn('indexNo', function($row) use(&$count) {
                    $count++;

                    return $count;
                })
                ->addColumn('PJDesc', function($row){
    
                    return $row->project->PJDesc;
                })
                ->addColumn('status', function ($certificate) {

                    if ($certificate->CERevokeInd == 1){
                         $status = '<span class="badge badge-outline badge-danger">Inactive</span>';
                    } elseif ($certificate->CEEndDate < $certificate->CEStartDate) {
                         $status = '<span class="badge badge-outline badge-danger">Inactive</span>';
                    } else {
                         $status = '<span class="badge badge-outline badge-success">Active</span>';
                    }
                     return $status; 
                     
                 })
                ->addColumn('action', function($row){

                    $route = route('certificate.view',$row->CEID);
                    
                    $result = '<a class="btn btn-secondary" href="'.$route.'" ><i class="text-dark fas fa-regular fa-eye"></i></a>';
                    return $result;
                })
    
                ->rawColumns(['indexNo', 'PJDesc', 'status', 'action'])
                ->make(true);

        }catch (\Throwable $e) {

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Permohonan tidak berjaya!'.$e->getMessage()
            ], 400);
        }
    }


}
