<?php

namespace App\Http\Controllers\Report;

use App\Exports\ReportCertExport;
use App\Http\Controllers\Controller;
use App\Jobs\CertificateReport;
use App\Models\AutoNumber;
use App\Models\Certificate;
use App\Models\GenerateReport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\WebSetting;
use App\Services\DropdownService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Session;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class CertController extends Controller
{
    
    public function index(){

        $dropdownService = new DropdownService();

        $project = $dropdownService->projekCert();
        $status = $dropdownService->statusActive();

        return view('report.certificate.index', compact('project', 'status'));

    }
    
    public function reportCertDatatable(Request $request){
        
        $query = Certificate::get();

        return $this->getCertDatatable($query);

    }

    public function getCertDatatable($query){
        

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('CE_PJCode', function($row){

                return $row->project->PJDesc;
            })
            ->editColumn('CECD', function($row){

                return Carbon::parse($row->CECD)->format('d/m/Y');
            })
            ->editColumn('CEEndDate', function($row){

                return Carbon::parse($row->CEEndDate)->format('d/m/Y');
            })
            ->addColumn('validateDay', function($row){
                
                $date1 = Carbon::now();
                $date2 = Carbon::parse($row->CEEndDate);

                $diffInDays = $date1->diffInDays($date2);
            
                $validDay = $diffInDays;

                if($row->CERevokeInd == 1){
                    $validDay = 0;
                }
                else{

                    if( !Carbon::now()->lessThanOrEqualTo($row->CEEndDate)){
                        $validDay = 0;
                    }

                }

                return $validDay;
            })
            ->editColumn('CERevokeDate', function($row){

                if($row->CERevokeDate){
                
                    return Carbon::parse($row->CERevokeDate)->format('d/m/Y');

                }else{
                    return "-";
                }
            })
            ->addColumn('status', function ($row) {
                

                if($row->CERevokeInd == 1){
                    $status = '<span class="badge badge-outline badge-danger">Inactive</span>';
                }
                else{
                    if( Carbon::now()->lessThanOrEqualTo($row->CEEndDate)){
                        $status = '<span class="badge badge-outline badge-success">Active</span>';
                    }
                    else{
                        $status = '<span class="badge badge-outline badge-danger">Inactive</span>';
                    }

                }
                return $status; 
            })

        ->rawColumns(['indexNo', 'CE_PJCode', 'CECD', 'CEEndDate', 'validateDay', 'CERevokeDate', 'status'])
        ->make(true);
    
    }
    
    public function searchReportCert(Request $request){
        try {

            $dropdownService = new DropdownService();
            
            $certificate = Certificate::query();
            
            if ($request->filled('search_project')) {
                $certificate->where('CE_PJCode', $request->input('search_project') );
            }

            if ($request->filled('search_certno')) {
                $certificate->where('CENo', 'LIKE', '%' . $request->input('search_certno') . '%');
            }

            if ($request->filled('search_status')) {

                if($request->search_status== 1){
                    $certificate->whereDate('CEEndDate', '>=', now());
                }
                else{
                    $certificate->whereDate('CEEndDate', '<', now());
                    $certificate->orWhere('CERevokeInd', 1);
                }
                
            }

            if ($request->filled('search_id')) {
                $certificate->where('CEIDNo', 'LIKE', '%' . $request->input('search_id') . '%');
            }

            if ($request->filled('search_isuDateFrom')) {
                $certificate->whereDate('CECD', '>=', $request->input('search_isuDateFrom'));
                
            }

            if ($request->filled('search_isuDateTo')) {
                $certificate->whereDate('CECD', '<=', $request->input('search_isuDateTo'));
                
            }

            if ($request->filled('search_validDay')) {

                $validityDays = $request->input('search_validDay');
                $endDate = Carbon::now()->addDays($validityDays)->endOfDay();
                $certificate->where('CEEndDate', '<=', $endDate);
                
            }

            if ($request->filled('search_revokeDateFrom')) {
                $certificate->whereDate('CERevokeDate', '>=', $request->input('search_revokeDateFrom'));
                
            }

            if ($request->filled('search_revokeDateTo')) {
                $certificate->whereDate('CERevokeDate', '<=', $request->input('search_revokeDateTo'));
                
            }

            $query = $certificate->get();
            
            $dataTable = $this->getCertDatatable($query);

            $parameter = json_encode($request->except('_token'));
            $redirectJana = url('/report/certificate/generateReportCert/' . $parameter);

            return response()->json([
                'success' => true,
                'message' => 'Success',
                'dataTable' => $dataTable,
                'redirectGenerate' => $redirectJana,
            ]);
            

        }catch (\Throwable $e) {

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Permohonan tidak berjaya!'.$e->getMessage()
            ], 400);
        }

    }

    public function generateReportCert($searchParam){

        $request1 = json_decode($searchParam, true);
        $request = new Request($request1);

        try {

            $user = Auth::user();

            $parameters = $request->only([
                "search_project",
                "search_certNo",
                "search_status",
                "search_id",
                "search_isuDateFrom",
                "search_isuDateTo",
                "search_validDay",
                "search_revokeDateFrom",
                "search_revokeDateTo",
            ]);

            $param = json_encode($parameters);

            $autoNumber = new AutoNumber();

            $generateCode = $autoNumber->generateReportCode();

            $generateGR = new GenerateReport();
            $generateGR->GRNo = 'RC' . $generateCode;
            $generateGR->GR_GRTCode = 'CERT';
            $generateGR->GRParameter = $param;
            $generateGR->GRStatus = 0;
            $generateGR->GRCB = $user->USCode;
            $generateGR->save();


            $report = (new CertificateReport($parameters , $generateGR->GRNo))->onQueue('report');
    
            $result = $this->dispatch($report);
    
            return response()->json([
                'success' => true,
                'message' => 'Report will be generated. Please refer to General Report list to see the report.'
            ], 200);

        }catch (\Throwable $e) {

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error occured! '.$e->getMessage()
            ], 400);
        }


    }


}
