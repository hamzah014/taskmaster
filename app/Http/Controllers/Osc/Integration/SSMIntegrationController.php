<?php

namespace App\Http\Controllers\Osc\Integration;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SchedulerController;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\AutoNumber;
use App\Models\CertApp;
use App\Models\Contractor;
use App\Models\ContractorComp;
use App\Models\ContractorCompOfficer;
use App\Models\ContractorCompShareholder;
use App\Models\PaymentLog;
use App\Models\Role;
use App\Models\Customer;
use App\Models\FileAttach;
use App\Models\IntegrateSSMLog;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use App\Services\DropdownService;

class SSMIntegrationController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('osc.integration.ssm.index');
    }

    public function view($id){

        $integrateSSM = IntegrateSSMLog::where('ISSMLogNo',$id)->first();

        $integrateSSM->createDate = Carbon::parse($integrateSSM->ISSMLogCD)->format('d/m/Y H:i');

        $payloadOrderDecode = json_decode($integrateSSM->ISSMLogPayloadOrder);
        $payloadDataDecode = json_decode($integrateSSM->ISSMLogPayloadData);

        return view('osc.integration.ssm.view',
        compact('integrateSSM','payloadOrderDecode','payloadDataDecode')
        );
    }

    //{{--Working Code Datatable with indexNo--}}
    public function SSMLogDatatable(Request $request){

        $now = Carbon::now()->format('Y-m-d');
        $query = IntegrateSSMLog::whereDate('ISSMLogCD',$now)->orderby('ISSMLogID','desc')->get();

        $count = 0;

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('ISSMLogNo', function($row) {

                $route = route('osc.integration.ssm.view',[$row->ISSMLogNo] );

                $result = '<a href="'.$route.'">'.$row->ISSMLogNo.'</a>';

                return $result;
            })
            ->editColumn('ISSMLogCD', function($row) {
                $carbonDatetime = Carbon::parse($row->ISSMLogCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i');

                return $formattedDate;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','ISSMLogNo', 'ISSMLogCD'])
            ->make(true);
    }


}
