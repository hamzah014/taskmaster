<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\Department;
use App\Models\FileAttach;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Storage;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use Yajra\DataTables\DataTables;

class DepartmentController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('setting.department.index'
        );
    }

    public function departmentDatatable(Request $request){

        $user = Auth::user();

        $query = Department::orderBy('DPTDesc')->get();

        return DataTables::of($query)
            ->editColumn('DPTDesc', function($row) {

                $route = route('setting.department.edit',[$row->DPTID]);
                $result = '<a href="'.$route.'">'.$row->DPTDesc.'</a>';

                return $result;
            })
            ->editColumn('DPTActive', function($row) {

                $statusActive = $this->dropdownService->statusActive();

                $result = $statusActive[$row->DPTActive];

                return $result;
            })
            ->rawColumns(['DPTActive', 'DPTDesc'])
            ->make(true);
    }

    public function edit($id){
        $user = $this->dropdownService->user();
        $statusActive = $this->dropdownService->statusActive();

        $department = Department::where('DPTID', $id)->first();

        if(!$department->user->fileAttachHOD_USFP){
            if($department->user->fileAttachFRNo_USFP){

                $US_FRNo_FileAttach = FileAttach::where('FARefNo' , $department->user->fileAttachFRNo_USFP->US_FRNo)
                    ->where('FAFileType', 'US-FP')
                    ->first();

                if($US_FRNo_FileAttach){
                    $folderPath	 = $US_FRNo_FileAttach->FAFilePath;

                    $filePath = $folderPath;

                    $US_FRNo_FileAttach_Contents = Storage::disk('fileStorage')->get($filePath);

                    $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

                    $folderPath = Carbon::now()->format('ymd');
                    $newFileName = strval($generateRandomSHA256);

                    Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName , $US_FRNo_FileAttach_Contents );

                    $newFileAttachHOD_USFP = new FileAttach();
                    $newFileAttachHOD_USFP->FA_USCode       = $department->user->USCode;
                    $newFileAttachHOD_USFP->FARefNo         = $department->user->USCode;
                    $newFileAttachHOD_USFP->FAFileType      = 'US-FP';
                    $newFileAttachHOD_USFP->FAFilePath	    = $folderPath.'\\'.$newFileName;
                    $newFileAttachHOD_USFP->FAFileName      = $US_FRNo_FileAttach->FAFileName;
                    $newFileAttachHOD_USFP->FAOriginalName  = $US_FRNo_FileAttach->FAOriginalName;
                    $newFileAttachHOD_USFP->FAFileExtension = $US_FRNo_FileAttach->FAFileExtension;
                    $newFileAttachHOD_USFP->FAActive        = $US_FRNo_FileAttach->FAActive;
                    $newFileAttachHOD_USFP->FAMB 		    = Auth::user()->USCode;
                    $newFileAttachHOD_USFP->save();
                }
            }
        }

        return view('setting.department.edit',
            compact('department','user', 'statusActive')
        );
    }

    public function update(Request $request){

        $user = Auth::user();

        $messages = [
            'DPTCode.required' 	        => "Kod Jabatan diperlukan.",
            'DPTCode.unique' 	        => "Kod Jabatan telah wujud.",
            'DPTDesc.required'  	    => "Nama Jabatan diperlukan.",
            'DPTEmail.required'         => "Emel Jabatan diperlukan.",
            'DPTHead_USCode.required'   => "Ketua Jabatan diperlukan.",
            'DPTActive.required'        => "Status diperlukan.",
        ];

        $validation = [
            'DPTCode' 	        => 'required|unique:MSDepartment,DPTCode,'.$request->DPTID.',DPTID',
            'DPTDesc' 	        => 'required',
            'DPTEmail' 	        => 'required',
            'DPTHead_USCode' 	=> 'required',
            'DPTActive' 	    => 'required',
        ];


        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $update_department = Department::where('DPTID', $request->DPTID)->first();
            $update_department->DPTCode        = $request->DPTCode;
            $update_department->DPTDesc        = $request->DPTDesc;
            $update_department->DPTHead_USCode = $request->DPTHead_USCode;
            $update_department->DPTEmail       = $request->DPTEmail;
            $update_department->DPTActive      = $request->DPTActive;
            $update_department->DPTMB          = $user->USCode;
            $update_department->save();

            $department = Department::where('DPTID', $request->DPTID)->first();

            if(!$department->user->fileAttachHOD_USFP){
                if($department->user->fileAttachFRNo_USFP){

                    $US_FRNo_FileAttach = FileAttach::where('FARefNo' , $department->user->fileAttachFRNo_USFP->US_FRNo)
                        ->where('FAFileType', 'US-FP')
                        ->first();

                    if($US_FRNo_FileAttach){
                        $folderPath	 = $US_FRNo_FileAttach->FAFilePath;

                        $filePath = $folderPath;

                        $US_FRNo_FileAttach_Contents = Storage::disk('fileStorage')->get($filePath);

                        $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

                        $folderPath = Carbon::now()->format('ymd');
                        $newFileName = strval($generateRandomSHA256);

                        Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName , $US_FRNo_FileAttach_Contents );

                        $newFileAttachHOD_USFP = new FileAttach();
                        $newFileAttachHOD_USFP->FA_USCode       = $department->user->USCode;
                        $newFileAttachHOD_USFP->FARefNo         = $department->user->USCode;
                        $newFileAttachHOD_USFP->FAFileType      = 'US-FP';
                        $newFileAttachHOD_USFP->FAFilePath	    = $folderPath.'\\'.$newFileName;
                        $newFileAttachHOD_USFP->FAFileName      = $US_FRNo_FileAttach->FAFileName;
                        $newFileAttachHOD_USFP->FAOriginalName  = $US_FRNo_FileAttach->FAOriginalName;
                        $newFileAttachHOD_USFP->FAFileExtension = $US_FRNo_FileAttach->FAFileExtension;
                        $newFileAttachHOD_USFP->FAActive        = $US_FRNo_FileAttach->FAActive;
                        $newFileAttachHOD_USFP->FAMB 		    = Auth::user()->USCode;
                        $newFileAttachHOD_USFP->save();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('setting.department.index'),
                'message' => 'Maklumat Berjaya Dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat Tidak Berjaya Dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }
}
