<?php

namespace App\Http\Controllers\Perolehan\Setting;

use App\Http\Controllers\Controller;
use App\Models\Location;
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

class LocationController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('perolehan.setting.location.index');
    }

    public function locationDataTable(){

        $query = Location::orderBy('LCID', 'DESC')->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('LCCode', function($row) {
                $route = route('perolehan.setting.location.edit',[$row->LCID]);
                $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light">'.$row->LCCode.'</a>';
                return $result;
            })
            ->editColumn('LCActive', function($row) {
                return $row->LCActive == 1 ? 'Aktif' : 'Tidak Aktif';
            })
            ->addColumn('action', function($row) {
                $routeDelete = route('perolehan.setting.location.delete',[$row->LCID]);
                $result = "";

                if($row->LCActive == 0){

                    $result = '<button onclick="deleteRecord(\' '.$row->LCID.' \')" class="btn btn-sm btn-danger"><i class="ki-solid ki-trash fs-2"></i></button>';

                }
                return $result;
            })
            ->rawColumns(['indexNo', 'LCCode', 'LCActive', 'action'])
            ->make(true);
    }

    public function create(){
        $statusActive = $this->dropdownService->statusActive();

        return view('perolehan.setting.location.create',
            compact('statusActive')
        );
    }

    public function store(Request $request){

        $messages = [
            'LCCode.required' 	        => "Kod Lokasi Mesyuarat diperlukan.",
            'LCCode.unique' 	        => "Kod Lokasi Mesyuarat telah wujud.",
            'LCDesc.required'  	        => "Keterangan diperlukan.",
            'LCActive.required'         => "Status diperlukan.",
        ];

        $validation = [
            'LCCode' 	        => 'required|unique:MSLocation,LCCode',
            'LCDesc' 	        => 'required',
            'LCActive' 	        => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();
            $user = Auth::user();

            $location = new Location();
            $location->LCCode    = $request->LCCode;
            $location->LCDesc    = $request->LCDesc;
            $location->LCActive  = $request->LCActive;
            $location->LCCB      = $user->USCode;
            $location->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.location.index'),
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

        $location = Location::where('LCID', $id)->first();

        return view('perolehan.setting.location.edit',
            compact('statusActive', 'location')
        );
    }

    public function update(Request $request){
        $messages = [
            'LCCode.required' 	        => "Kod Lokasi Mesyuarat diperlukan.",
            'LCCode.unique' 	        => "Kod Lokasi Mesyuarat telah wujud.",
            'LCDesc.required'  	        => "Keterangan diperlukan.",
            'LCActive.required'         => "Status diperlukan.",
        ];

        $validation = [
            'LCCode' 	        => 'required|unique:MSLocation,LCCode,'.$request->LCID.',LCID',
            'LCDesc' 	        => 'required',
            'LCActive' 	        => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();
            $user = Auth::user();

            $location = Location::where('LCID', $request->LCID)->first();
            $location->LCCode    = $request->LCCode;
            $location->LCDesc    = $request->LCDesc;
            $location->LCActive  = $request->LCActive;
            $location->LCMB      = $user->USCode;
            $location->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.location.index'),
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
            $location = Location::where('LCID', $request->deleteID)->first();
            $location->delete();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.location.index'),
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
