<?php

namespace App\Http\Controllers\PublicUser\Transaksi;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Symfony\Component\HttpFoundation\Response;
use Validator;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use Yajra\DataTables\DataTables;

use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\PaymentLog;
use App\Models\SSMCompany;

class TransaksiController extends Controller
{
    public function index(){

        $user = Auth::user();

        return view('publicUser.transaksi.index'
        );

    }

    public function checkout(){

        return view('publicUser.transaksi.checkout');
    }

    public function resitPDF($id){

        $data = PaymentLog::leftjoin('MSPaymentStatus','PSCode','PL_PSCode')->where('PLNo',$id)->with('contractor','certApp')->first();


        $template = "RESIT";
        $download = false; //true for download or false for view
        $templateName = "CERTAPP"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF',compact('data','template','templateName'));
        $response = $this->generatePDF($view,$download);

        return $response;
    }

    //{{--Working Code Datatable--}}
    public function TransaksiDatatable(Request $request){

        $user = Auth::user();
        $usercode = $user->USCode;

        $query = PaymentLog::leftjoin('MSPaymentStatus','PSCode','PL_PSCode')->where('PL_CONo', $usercode)->with('contractor','certApp')->orderby('PLID','desc')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PLNo', function($row){

                return $row->PLNo;

            })
            ->editColumn('PLCD', function($row) {

                $carbonDatetime = Carbon::parse($row->COMOFStartDate);
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->addColumn('totalFee', function($row) {

                return number_format($row->certApp->CATotalFee ?? 0, 2, '.', ',');

            })
            ->addColumn('action', function($row) {

                if($row->PL_PLTCode == 'REGISTER'){

                    $route = route('publicUser.register.resitPDF',$row->PLNo);

                }elseif($row->PL_PLTCode == 'CERTAPP'){

                    $route = route('publicUser.transaksi.resitPDF',$row->PLNo);

                }else if($row->PL_PLTCode == 'TENDERAPP'){

                    $route = route('publicUser.application.resitPDF',$row->PLNo);

                }
                $result = '&nbsp<a target="_blank" href="'.$route.'" class="btn btn-sm btn-light-primary"><i class="ki-solid ki-file"></i></a>';
                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','PLCD','totalFee','PLNo','action'])
            ->make(true);

    }



}
