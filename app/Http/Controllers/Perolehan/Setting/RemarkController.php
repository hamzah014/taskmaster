<?php

namespace App\Http\Controllers\Perolehan\Setting;

use App\Http\Controllers\Controller;
use App\Models\RemarkType;
use App\Models\UnitMeasurement;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Yajra\DataTables\DataTables;
use App\Models\AutoNumber;
use Illuminate\Support\Facades\Storage;
use App\Models\FileAttach;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\FileType;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Services\DropdownService;
use Session;

class RemarkController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('perolehan.setting.remark.index');
    }

    public function remarkDataTable(){

        $query = RemarkType::orderBy('RTID', 'DESC')->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('RTCode', function($row) {
                $route = route('perolehan.setting.remark.edit',[$row->RTID]);
                $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light">'.$row->RTCode.'</a>';
                return $result;
            })
            ->editColumn('RTActive', function($row) {
                return $row->RTActive == 1 ? 'Aktif' : 'Tidak Aktif';
            })
            ->addColumn('action', function($row) {
                $routeDelete = route('perolehan.setting.remark.delete',[$row->RTID]);
                $result = "";

                if($row->RTActive == 0 && $row->RTCode != 'OTHERS'){

                    $result = '<button onclick="deleteRecord(\' '.$row->RTID.' \')" class="btn btn-light-primary"><i class="material-icons">delete</i></button>';

                }
                return $result;
            })
            ->rawColumns(['indexNo', 'RTCode', 'RTActive', 'action'])
            ->make(true);
    }

    public function create(){
        $statusActive = $this->dropdownService->statusActive();

        return view('perolehan.setting.remark.create',
            compact('statusActive')
        );
    }

    public function store(Request $request){

        $messages = [
            'RTCode.required' 	        => "Kod Catatan diperlukan.",
            'RTCode.unique' 	        => "Kod Catatan telah wujud.",
            'RTDesc.required'  	        => "Keterangan diperlukan.",
            'RTActive.required'         => "Status diperlukan.",
        ];

        $validation = [
            'RTCode' 	        => 'required|unique:MSRemarkType,RTCode',
            'RTDesc' 	        => 'required',
            'RTActive' 	        => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();
            $user = Auth::user();

            $remarkType = new RemarkType();
            $remarkType->RTCode    = $request->RTCode;
            $remarkType->RTDesc    = $request->RTDesc;
            $remarkType->RTActive  = $request->RTActive;
            $remarkType->RTCB      = $user->USCode;
            $remarkType->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.remark.index'),
                'message' => 'Maklumat berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function edit($id){
        $statusActive = $this->dropdownService->statusActive();

        $remarkType = RemarkType::where('RTID', $id)->first();

        return view('perolehan.setting.remark.edit',
            compact('statusActive', 'remarkType')
        );
    }

    public function update(Request $request){

        $messages = [
            'RTCode.required' 	        => "Kod Catatan diperlukan.",
            'RTCode.unique' 	        => "Kod Catatan telah wujud.",
            'RTDesc.required'  	        => "Keterangan diperlukan.",
            'RTActive.required'         => "Status diperlukan.",
        ];

        $validation = [
            'RTCode' 	        => 'required|unique:MSRemarkType,RTCode,'.$request->RTID.',RTID',
            'RTDesc' 	        => 'required',
            'RTActive' 	        => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();
            $user = Auth::user();

            $remarkType = RemarkType::where('RTID', $request->RTID)->first();
            $remarkType->RTCode    = $request->RTCode;
            $remarkType->RTDesc    = $request->RTDesc;
            $remarkType->RTActive  = $request->RTActive;
            $remarkType->RTMB      = $user->USCode;
            $remarkType->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.remark.index'),
                'message' => 'Maklumat berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function delete(Request $request)
    {
        try {
            $remarkType = RemarkType::where('RTID', $request->deleteID)->first();
            $remarkType->delete();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.remark.index'),
                'message' => 'Maklumat berjaya dipadam.'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dipadam: ' . $e->getMessage(),
            ], 500);
        }
    }

}
