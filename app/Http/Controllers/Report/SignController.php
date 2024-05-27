<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Jobs\CertificateReport;
use App\Jobs\SignReport;
use App\Models\AutoNumber;
use App\Models\Certificate;
use App\Models\GenerateReport;
use App\Models\Permission;
use App\Models\SignDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Auth;
use App\Services\DropdownService;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class SignController extends Controller
{
    
    public function index(){

        $dropdownService = new DropdownService();

        $project = $dropdownService->projekCert();
        $status = $dropdownService->statusActive();

        return view('report.sign.index', compact('project', 'status'));

    }
    
    public function reportSignDatatable(Request $request){
        
        $query = SignDocument::get();

        return $this->getSignDatatable($query);

    }

    public function getSignDatatable($query){
        

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('SDCD', function($row){

                return Carbon::parse($row->SDCD)->format('d/m/Y');
            })
            ->addColumn('CENo', function($row){

                return $row->certificate->CENo;
            })
            ->addColumn('CEIDNo', function($row){

                return $row->certificate->CEIDNo;
            })

        ->rawColumns(['indexNo', 'SDCD', 'CENo', 'CEIDNo'])
        ->make(true);
    
    }
    
    public function searchReportSign(Request $request){

        try {

            $dropdownService = new DropdownService();
            
            $signDocument = SignDocument::query();

            if ($request->filled('search_signNo')) {
                $signDocument->where('SDNo', 'LIKE', '%' . $request->input('search_signNo') . '%');
            }

            if ($request->filled('search_certno')) {
                $signDocument->whereHas('certificate', function($query) use(&$request) {
                    $query->where('CENo', 'LIKE', '%' . $request->input('search_certno') . '%');
                });
            }

            if ($request->filled('search_id')) {
                $signDocument->whereHas('certificate', function($query) use(&$request) {
                    $query->where('CEIDNo', 'LIKE', '%' . $request->input('search_id') . '%');
                });
            }

            if ($request->filled('search_dateFrom')) {
                $signDocument->whereDate('SDCD', '>=', $request->input('search_dateFrom'));
                
            }

            if ($request->filled('search_dateTo')) {
                $signDocument->whereDate('SDCD', '<=', $request->input('search_dateTo'));
                
            }

            $query = $signDocument->get();
            
            $dataTable = $this->getSignDatatable($query);

            $parameter = json_encode($request->except('_token'));
            $redirectJana = url('/report/signDocument/generateReportSign/' . $parameter);

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

    public function generateReportSign($searchParam){

        $request1 = json_decode($searchParam, true);
        $request = new Request($request1);

        try {

            $user = Auth::user();

            $parameters = $request->only([
                
                'search_signNo',
                'search_certno',
                'search_id',
                'search_dateFrom',
                'search_dateTo',
                
            ]);

            $param = json_encode($parameters);

            $autoNumber = new AutoNumber();

            $generateCode = $autoNumber->generateReportCode();

            $generateGR = new GenerateReport();
            $generateGR->GRNo = 'RS' . $generateCode;
            $generateGR->GR_GRTCode = 'SIGN';
            $generateGR->GRParameter = $param;
            $generateGR->GRStatus = 0;
            $generateGR->GRCB = $user->USCode;
            $generateGR->save();


            $report = (new SignReport($parameters , $generateGR->GRNo))->onQueue('report');
    
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
