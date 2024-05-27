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
use Yajra\DataTables\DataTables;

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

class PaymentIntegrationController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('osc.integration.payment.index');
    }

    public function view($id){
        $bg = $this->dropdownService->statusbayaran();

        $integratePayment = PaymentLog::leftjoin('MSPaymentStatus','PSCode','PL_PSCode')->where('PLNo',$id)->first();

        $integratePayment->createDate = Carbon::parse($integratePayment->PLCD)->format('d/m/Y H:i');

        return view('osc.integration.payment.view',
        compact('integratePayment' , 'bg')
        );
    }

    public function update(Request $request, $id)
    {
        $integratePayment = PaymentLog::where('PLNo',$id)->first();
        try {
            DB::beginTransaction();

            if ($integratePayment) {
                $integratePayment->PLCheckoutID = $request->input('reference_no');
                $integratePayment->PL_PSCode = $request->input('status');
                // $integratePayment->PL_PSCode = '02';
                $integratePayment->save();

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('osc.integration.payment.index'),
                    'message' => 'Maklumat berjaya disimpan.'
                ]);
            }

        } catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!' . $e->getMessage()
            ], 400);
        }
    }

    //{{--Working Code Datatable with indexNo--}}
    public function PaymentLogDatatable(Request $request){

        // $user = Auth::user();
        // $usercode = $user->USCode;
        $today = Carbon::now()->format('Y-m-d');

        $query = PaymentLog::leftjoin('MSPaymentStatus','PSCode','PL_PSCode')
                ->whereDate('PLCD', $today)
                ->orderBy('PLNo' , 'desc')->orderby('PLID','desc')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PLNo', function($row){

                $route = route('osc.integration.payment.view',[$row->PLNo] );

                $result = '<a href="'.$route.'">'.$row->PLNo.'</a>';

                return $result;

            })
            ->editColumn('PLCD', function ($row) {
                $carbonDatetime = Carbon::parse($row->PLCD);
                $formattedDate = $carbonDatetime->format('d/m/Y H:i');

                return $formattedDate;
            })
            ->addColumn('totalFee', function($row) {

                return number_format($row->certApp->CATotalFee ?? 0, 2, '.', ',');

            })
            ->addColumn('action', function($row) {

                $route = '#';

                if($row->PL_PSCode == '00'){

                     $route = route('osc.integration.payment.view',$row->PLNo);

                }elseif($row->PL_PSCode == '01'){

                }else if($row->PL_PSCode == '02'){

                }
                $result = '&nbsp<a target="_blank" href="'.$route.'" class="new modal-trigger waves-effect waves-light btn btn-light-primary"><i class="material-icons">receipt</i></a>';
                return $result;
            })
            ->with(['count' => 0])
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','PLCD','totalFee','PLNo','action'])
            ->make(true);

    }

}
