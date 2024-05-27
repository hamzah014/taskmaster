<?php

namespace App\Http\Controllers\Perolehan\Setting;

use App\Http\Controllers\Controller;
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

class UOMController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('perolehan.setting.uom.index');
    }

    public function uomDataTable(){

        $query = UnitMeasurement::orderBy('UMID', 'DESC')->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('UMCode', function($row) {
                $route = route('perolehan.setting.uom.edit',[$row->UMID]);
                $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light">'.$row->UMCode.'</a>';
                return $result;
            })
            ->editColumn('UMActive', function($row) {
                return $row->UMActive == 1 ? 'Aktif' : 'Tidak Aktif';
            })
            ->addColumn('action', function($row) {
                $routeDelete = route('perolehan.setting.uom.delete',[$row->UMID]);
                $result = "";

                if($row->UMActive == 0){

                    $result = '<button onclick="deleteRecord(\' '.$row->UMID.' \')" class="btn btn-sm btn-danger"><i class="ki-solid ki-trash fs-2"></i></button>';

                }
                return $result;
            })
            ->rawColumns(['indexNo', 'UMCode', 'action'])
            ->make(true);
    }

    public function create(){
        $statusActive = $this->dropdownService->statusActive();

        return view('perolehan.setting.uom.create',
            compact('statusActive')
        );
    }

    public function store(Request $request){

        $messages = [
            'UMCode.required' 	        => "Kod Unit Ukuran diperlukan.",
            'UMCode.unique' 	        => "Kod Unit Ukuran telah wujud.",
            'UMDesc.required'  	        => "Keterangan diperlukan.",
            'UMQty.required'            => "Kuantiti diperlukan.",
            'UMActive.required'         => "Status diperlukan.",
        ];

        $validation = [
            'UMCode' 	        => 'required|unique:MSUnitMeasurement,UMCode',
            'UMDesc' 	        => 'required',
            'UMQty' 	        => 'required',
            'UMActive' 	        => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();
            $user = Auth::user();

            $uom = new UnitMeasurement();
            $uom->UMCode    = $request->UMCode;
            $uom->UMDesc    = $request->UMDesc;
            $uom->UMQty     = $request->UMQty;
            $uom->UMActive  = $request->UMActive;
            $uom->UMCB      = $user->USCode;
            $uom->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.uom.index'),
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

        $uom = UnitMeasurement::where('UMID', $id)->first();

        return view('perolehan.setting.uom.edit',
            compact('statusActive', 'uom')
        );
    }

    public function update(Request $request){

        $messages = [
            'UMCode.required' 	        => "Kod Unit Ukuran diperlukan.",
            'UMCode.unique' 	        => "Kod Unit Ukuran telah wujud.",
            'UMDesc.required'  	        => "Keterangan diperlukan.",
            'UMQty.required'            => "Kuantiti diperlukan.",
            'UMActive.required'         => "Status diperlukan.",
        ];

        $validation = [
            'UMCode' 	        => 'required|unique:MSUnitMeasurement,UMCode,'.$request->UMID.',UMID',
            'UMDesc' 	        => 'required',
            'UMQty' 	        => 'required',
            'UMActive' 	        => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();
            $user = Auth::user();

            $uom = UnitMeasurement::where('UMID', $request->UMID)->first();
            $uom->UMCode    = $request->UMCode;
            $uom->UMDesc    = $request->UMDesc;
            $uom->UMQty     = $request->UMQty;
            $uom->UMActive  = $request->UMActive;
            $uom->UMMB      = $user->USCode;
            $uom->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.uom.index'),
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

            $uom = UnitMeasurement::where('UMID', $request->deleteID)->first();
            $uom->delete();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.uom.index'),
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
