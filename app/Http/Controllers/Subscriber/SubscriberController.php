<?php

namespace App\Http\Controllers\Subscriber;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\Certificate;
use App\Models\Register;
use App\Models\Subscriber as Subs;
use App\Models\Subscriber;
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
use Session;
use Yajra\DataTables\DataTables;

class SubscriberController extends Controller
{
    
    public function index(){

        return view('subscriber.index');

    }

    public function subsDatatable(){
        $query = Register::orderBy('RGCD', 'DESC')->get();

        return $this->getSubsDatatable($query);

        
    }
    
    public function searchSubs(Request $request){ 

        try {
            
            $subs = Register::query();

            if ($request->filled('search_no')) {
                $subs->where('RGIDNo', 'LIKE', '%' . $request->input('search_no') . '%');
            }

            if ($request->filled('search_name')) {
                $subs->where('RGName', 'LIKE', '%' . $request->input('search_name') . '%');
            }

            if ($request->filled('search_email')) {
                $subs->where('RGEmail', 'LIKE', '%' . $request->input('search_email') . '%');
            }

            $query = $subs->orderBy('RGCD', 'DESC')->get();

            return $this->getSubsDatatable($query);

    
        }catch (\Throwable $e) {

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Permohonan tidak berjaya!'.$e->getMessage()
            ], 400);
        }


    }
    
    public function getSubsDatatable($query){

        $dropdownService = new DropdownService();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('action', function($row){

                $route = route('subscriber.view',$row->RGNo);
                
                $result = '<a class="btn btn-secondary" href="'.$route.'" ><i class="text-dark fas fs-7 fa-eye"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo', 'USActive','USRole','action'])
            ->make(true);

    }
    
    public function view($id){

        $subscriber = Register::where('RGNo', $id)->first();

        return view('subscriber.view',compact('subscriber'));

    }

    public function loadHistoryCert(Request $request){

        $dropdownService = new DropdownService();

        $query = Certificate::where('CEIDNo', $request->registerID)->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('CEStartDate', function($row){

                return $row->CEStartDate ? Carbon::parse($row->CEStartDate)->format('d/m/Y') : "-";

            })
            ->editColumn('CE_CSCode', function($row) use(&$dropdownService) {

                $certStatus = $dropdownService->certStatus();

                return $certStatus[$row->CE_CSCode] ?? "-";

            })
            ->rawColumns(['indexNo','CE_CSCode'])
            ->make(true);

    }

}
