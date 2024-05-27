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
use App\Models\IntegrationLog;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use App\Services\DropdownService;

class NewsIntegrationController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('osc.integration.news.index');
    }

    public function view($id){

        $integrate = IntegrationLog::where('ILNo',$id)->first();

        $integrate->createDate = Carbon::parse($integrate->ILCD)->format('d/m/Y H:i');

        $responseDecode = json_decode($integrate->ILResponse);

        return view('osc.integration.news.view',
        compact('integrate','responseDecode')
        );
    }

    //{{--Working Code Datatable with indexNo--}}
    public function NewsLogDatatable(Request $request){
        
        $now = Carbon::now()->format('Y-m-d');

        $query = IntegrationLog::where('ILType','NEWS')->whereDate('ILCD',$now)->orderby('ILID','desc')->get();

        $count = 0;

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('ILNo', function($row) {

                $route = route('osc.integration.news.view',[$row->ILNo] );

                $result = '<a href="'.$route.'">'.$row->ILNo.'</a>';

                return $result;
            })
            ->editColumn('ILComplete', function($row) {

                $result = $row->ILComplete == 0 ? 'YES' : 'NO';

                return $result;
            })
            ->editColumn('ILCD', function($row) {
                $carbonDatetime = Carbon::parse($row->ILCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i');

                return $formattedDate;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','ILNo', 'ILCD','ILComplete'])
            ->make(true);
    }


}
