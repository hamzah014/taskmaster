<?php

namespace App\Http\Controllers\Dashboard;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Project;
use App\Models\ProjectMiletstone;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Auth;
use Yajra\DataTables\DataTables;

class StaffController extends Controller{

    private $projectCode;

    public function __construct() {

        $project = Project::where('PJDesc', 'LIKE', '%' . 'STAFF' . '%')->first();
        $this->projectCode = $project->PJCode;
    }

    public function index(){

		$user = Auth::user();

        $now = Carbon::now();

        $nowDate = Carbon::today();

        $projectCode = $this->projectCode;

        $totalcertificate = Certificate::where('CE_PJCode', $projectCode)->get()->count();
        $totalActiveCert = Certificate::where('CE_PJCode', $projectCode)->where('CEEndDate', '>=' , $now )->where('CERevokeInd', 0)->get()->count();
        $totalExpiredCert = Certificate::where('CE_PJCode', $projectCode)->where('CEEndDate', '<' , $now )->where('CERevokeInd', 0)->get()->count();
        $totalRevokeCert = Certificate::where('CE_PJCode', $projectCode)->where('CERevokeInd', 1 )->get()->count();
        
        $totalNewReq = Certificate::whereDate('CECD', $nowDate )->where('CE_PJCode', $projectCode)->where('CE_CSCode', 'ISSUE' )->get()->count();
        $totalRevokeReq = Certificate::whereDate('CECD', $nowDate )->where('CE_PJCode', $projectCode)->where('CE_CSCode', 'REVOKE' )->get()->count();
        $totalRenewReq = Certificate::whereDate('CECD', $nowDate )->where('CE_PJCode', $projectCode)->where('CE_CSCode', 'RENEW' )->get()->count();

        $dataTotal = array(
            'totalcertificate' => $totalcertificate,
            'totalActiveCert' => $totalActiveCert,
            'totalExpiredCert' => $totalExpiredCert,
            'totalRevokeCert' => $totalRevokeCert,
            'totalNewReq' => $totalNewReq,
            'totalRevokeReq' => $totalRevokeReq,
            'totalRenewReq' => $totalRenewReq,
        );

        $dataRequest = ['New Request', 'Renew Request', 'Revoke Request'];
        $dataAmtRequest = [$totalNewReq, $totalRenewReq, $totalRevokeReq];

        $totalReq = $totalNewReq + $totalRenewReq + $totalRevokeReq;

        $expiry7dayDate = Carbon::now()->copy()->addDays(7);
        $totalExp7day = Certificate::where('CE_PJCode', $projectCode)->where('CEEndDate', '<=', $expiry7dayDate)
                                ->where('CERevokeInd', 0)
                                ->count();

        $expiry30dayDate = Carbon::now()->copy()->addDays(30);
        $totalExp30day = Certificate::where('CE_PJCode', $projectCode)->where('CEEndDate', '<=', $expiry30dayDate)
                                ->where('CERevokeInd', 0)
                                ->count();

        $expiry90dayDate = Carbon::now()->copy()->addDays(30);
        $totalExp90day = Certificate::where('CE_PJCode', $projectCode)->where('CEEndDate', '<=', $expiry90dayDate)
                                ->where('CERevokeInd', 0)
                                ->count();

        $dataExpired = array(
            'totalExp7day' => $totalExp7day,
            'totalExp30day' => $totalExp30day,
            'totalExp90day' => $totalExp90day,
        );

        return view('dashboard.staff.index',compact('user', 'dataTotal', 'dataRequest', 'dataAmtRequest', 'totalReq' ,'dataExpired'));

    }

    public function certList(){


		$user = Auth::user();

        $now = Carbon::now();

        $dateArray = array();
        $totalArray = array();

        $certificates = Certificate::select('CECD as date')
        ->selectRaw('COUNT(*) as total')
        ->where('CE_PJCode', $this->projectCode)
        ->groupBy('CECD')
        ->get();

        foreach($certificates as $index => $certificate){

            array_push($dateArray, Carbon::parse($certificate->date)->format('Y-m-d'));
            array_push($totalArray, $certificate->total);

        }

        return view('dashboard.staff.totalCert',
        compact('dateArray', 'totalArray')
        );

    }

    public function certActive(){


		$user = Auth::user();

        $now = Carbon::now();

        $dateArray = array();
        $totalArray = array();

        $certificates = Certificate::select('CECD as date')
        ->selectRaw('COUNT(*) as total')
        ->where('CE_PJCode', $this->projectCode)
        ->where('CEEndDate', '>=' , $now )
        ->where('CERevokeInd', 0)
        ->groupBy('CECD')
        ->get();

        foreach($certificates as $index => $certificate){

            array_push($dateArray, Carbon::parse($certificate->date)->format('Y-m-d'));
            array_push($totalArray, $certificate->total);

        }

        return view('dashboard.staff.activeCert',
        compact('dateArray', 'totalArray')
        );

    }

    public function certRevoke(){


		$user = Auth::user();

        $now = Carbon::now();

        $dateArray = array();
        $totalArray = array();

        $certificates = Certificate::select('CECD as date')
        ->selectRaw('COUNT(*) as total')
        ->where('CE_PJCode', $this->projectCode)
        ->where('CERevokeInd', 1 )
        ->groupBy('CECD')
        ->get();

        foreach($certificates as $index => $certificate){

            array_push($dateArray, Carbon::parse($certificate->date)->format('Y-m-d'));
            array_push($totalArray, $certificate->total);

        }

        return view('dashboard.staff.revokeCert',
        compact('dateArray', 'totalArray')
        );

    }

    public function certExpired(){


		$user = Auth::user();

        $now = Carbon::now();

        $dateArray = array();
        $totalArray = array();

        $certificates = Certificate::select('CECD as date')
        ->selectRaw('COUNT(*) as total')
        ->where('CE_PJCode', $this->projectCode)
        ->where('CEEndDate', '<' , $now )
        ->where('CERevokeInd', 0)
        ->groupBy('CECD')
        ->get();

        foreach($certificates as $index => $certificate){

            array_push($dateArray, Carbon::parse($certificate->date)->format('Y-m-d'));
            array_push($totalArray, $certificate->total);

        }

        return view('dashboard.staff.expiredCert',
        compact('dateArray', 'totalArray')
        );

    }

    public function certRequest(){

        $now = Carbon::today();
        
        $totalNewReq = Certificate::where('CE_PJCode', $this->projectCode)->whereDate('CECD', $now )->where('CE_CSCode', 'ISSUE' )->get()->count();
        $totalRevokeReq = Certificate::where('CE_PJCode', $this->projectCode)->whereDate('CECD', $now )->where('CE_CSCode', 'REVOKE' )->get()->count();
        $totalRenewReq = Certificate::where('CE_PJCode', $this->projectCode)->whereDate('CECD', $now )->where('CE_CSCode', 'RENEW' )->get()->count();

        $dataAmtRequest = array(
            'totalNewReq' => $totalNewReq,
            'totalRenewReq' => $totalRenewReq,
            'totalRevokeReq' => $totalRevokeReq,
        );

        return view('dashboard.staff.requestCert',
        compact('dataAmtRequest')
        );

    }

    public function newRequestDatatable(Request $request){

        $now = Carbon::today();

        $query = Certificate::where('CE_PJCode', $this->projectCode)->whereDate('CECD', $now )->where('CE_CSCode', 'ISSUE' )->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('project', function($row){

                $result = $row->project ? $row->project->PJDesc : "-";

                return $result;

            })
        ->rawColumns(['indexNo', 'project'])
        ->make(true);

    }

    public function renewRequestDatatable(Request $request){

        $now = Carbon::today();

        $query = Certificate::where('CE_PJCode', $this->projectCode)->whereDate('CECD', $now )->where('CE_CSCode', 'RENEW' )->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('project', function($row){

                $result = $row->project ? $row->project->PJDesc : "-";

                return $result;

            })
        ->rawColumns(['indexNo', 'project'])
        ->make(true);

    }

    public function revokeRequestDatatable(Request $request){

        $now = Carbon::today();

        $query = Certificate::whereDate('CECD', $now )->where('CE_PJCode', $this->projectCode)->where('CE_CSCode', 'RENEW' )->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('project', function($row){

                $result = $row->project ? $row->project->PJDesc : "-";

                return $result;

            })
        ->rawColumns(['indexNo', 'project'])
        ->make(true);

    }
    
    public function expiredListCert(){

        $now = Carbon::now();

        return view('dashboard.staff.expiredListCert'
        );

    }

    public function expiredCertDatatable(Request $request){

        $query = Certificate::where('CERevokeInd', 0)->where('CE_PJCode', $this->projectCode)->get();

        return $this->getExpiredDatatable($query);

    }

    public function searchExpiredUpcoming(Request $request){
            
        $certificate = Certificate::query();

        if ($request->filled('search_day')) {

            $days = $request->search_day;

            $expiryDay = Carbon::now()->copy()->addDays($days);

            $certificate->where('CEEndDate', '<=', $expiryDay);
        }

        $query = $certificate->where('CERevokeInd', 0)
                    ->where('CE_PJCode', $this->projectCode)
                    ->get();

        return $this->getExpiredDatatable($query);

    }

    public function searchExpiredCert(Request $request){
            
        $certificate = Certificate::query();

        if ($request->filled('dateFrom')) {
            $certificate->whereDate('CEEndDate', '>=', $request->input('dateFrom'));
            
        }

        if ($request->filled('dateTo')) {
            $certificate->whereDate('CEEndDate', '<=', $request->input('dateTo'));
            
        }
        $query = $certificate->where('CERevokeInd', 0)
                    ->where('CE_PJCode', $this->projectCode)
                    ->get();

        return $this->getExpiredDatatable($query);

    }

    public function getExpiredDatatable($query){
        

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('project', function($row){

                $result = $row->project ? $row->project->PJDesc : "-";

                return $result;

            })
            ->editColumn('CEEndDate', function($row){

                $result = $row->CEEndDate ? Carbon::parse($row->CEEndDate)->format('d/m/Y') : "-";

                return $result;

            })
        ->rawColumns(['indexNo', 'project','CEEndDate'])
        ->make(true);

    }


}
