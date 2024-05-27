<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\SchedulerController;
use App\Http\Controllers\WebcamController;
use App\Models\AutoNumber;
use App\Models\ContractorAuthUser;
use App\Models\Department;
use App\Models\FaceCompareLog;
use App\Models\FaceRegister;
use App\Services\DropdownService;
use Carbon\Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Country;
use App\Models\FileAttach;
use App\User;
use Illuminate\Http\Request;
Use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Validator;
use Auth;
use Yajra\DataTables\DataTables;


class UserController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        return view('setting.user.index');
    }

    public function userMUDatatable(Request $request){

        $user = Auth::user();

        $query = User::where('USType', 'MU')->orderBy('USName')->get();

        return DataTables::of($query)
            ->editColumn('USName', function($row) {

                $route = route('setting.user.edit',[$row->USCode]);
                $result = '<a href="'.$route.'">'.$row->USName.'</a>';

                return $result;
            })
            ->editColumn('USActive', function($row) {

                $statusActive = $this->dropdownService->statusActive();

                $result = $statusActive[$row->USActive];

                return $result;
            })
            ->rawColumns(['USActive', 'USName'])
            ->make(true);
    }

    public function edit($id){
        $statusActive = $this->dropdownService->statusActive();
        $roles = Role::whereNotIn('RLCode',['ADMIN'])->get()->pluck('RLName','RLCode');

        $user = User::where('USCode', $id)->first();

        return view('setting.user.edit',
            compact('user', 'roles', 'statusActive')
        );
    }

    public function update(Request $request){

        $user = Auth::user();

        $messages = [
            'USName.required' 	    => "Nama Pengguna diperlukan.",
            'USPhoneNo.required'    => "No. Tel. Pengguna diperlukan.",
            'USEmail.required'      => "Emel Pengguna diperlukan.",
            'USActive.required'     => "Status diperlukan.",
        ];

        $validation = [
            'USName'    => 'required',
            'USEmail'   => 'required',
            'USPhoneNo' => 'required',
            'USActive'  => 'required',
        ];


        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $update_user = User::where('USCode', $request->USCode)->first();
            $update_user->USName        = $request->USName;
            $update_user->USPhoneNo     = $request->USPhoneNo;
            $update_user->USEmail       = $request->USEmail;
            $update_user->USActive      = $request->USActive;
            $update_user->USMB          = $user->USCode;
            $update_user->save();

            $update_user = User::where('USCode', $request->USCode)->first();

            if(!$update_user->fileAttachHOD_USFP){
                if($update_user->fileAttachFRNo_USFP){

                    $US_FRNo_FileAttach = FileAttach::where('FARefNo' , $update_user->fileAttachFRNo_USFP->US_FRNo)
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
                        $newFileAttachHOD_USFP->FA_USCode       = $update_user->USCode;
                        $newFileAttachHOD_USFP->FARefNo         = $update_user->USCode;
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
                'redirect' => route('setting.user.edit', [$request->USCode]),
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

    public function updateGambar(Request $request){

        $user = Auth::user();

        try {
            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $update_user = User::where('USCode', $request->userCode)->first();

            if($request->hasFile('dok_ic') && $request->hasFile('dok_picture')){

                $FRNo = $autoNumber->generateFaceRegisterNo();

                $faceRegister = new FaceRegister();
                $faceRegister->FRNo         = $FRNo;
                $faceRegister->FR_USCode    = $request->userCode;
                $faceRegister->FRMatchID    = '1';
                $faceRegister->FRMatchFace  = '1';
                $faceRegister->FRCB         = $user->USCode;
                $faceRegister->FRMB         = $user->USCode;
                $faceRegister->save();

                //save ic
                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

                $dok_FAFileType		    = "US-ID";

                $file = $request->file('dok_ic');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = new FileAttach();
                $fileAttach->FACB 		        = $user->USCode;
                $fileAttach->FAFileType 	    = $fileCode;
                $fileAttach->FARefNo     	    = $FRNo;
                $fileAttach->FA_USCode     	    = $request->userCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = $user->USCode;
                $fileAttach->save();

                $FA_ID = $fileAttach->FAID;

                //save selfie
                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

                $dok_FAFileType		    = "US-FP";

                $file = $request->file('dok_picture');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = new FileAttach();
                $fileAttach->FACB 		        = $user->USCode;
                $fileAttach->FAFileType 	    = $fileCode;
                $fileAttach->FARefNo     	    = $FRNo;
                $fileAttach->FA_USCode     	    = $request->userCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = $user->USCode;
                $fileAttach->save();

                $FA_US_FD = $fileAttach->FAID;

                $webController = new WebcamController(new DropdownService(), new AutoNumber());

                $result = $webController->faceRegisterChecking($FRNo);

                $update_faceRegister = FaceRegister::where('FRNo', $FRNo)->first();
                $update_faceRegister->FRFaceScore       = $result['faceScore'];
                $update_faceRegister->FRMatchComplete   = $result['facePass'];
                $update_faceRegister->FRMatchDate       = Carbon::now();
                $update_faceRegister->FRMB              = $user->USCode;
                $update_faceRegister->save();

                if($result['facePass'] == true){
                    $US_FRNo_FileAttach = FileAttach::where('FARefNo' , $FRNo)
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
                        $newFileAttachHOD_USFP->FA_USCode       = $update_user->USCode;
                        $newFileAttachHOD_USFP->FARefNo         = $update_user->USCode;
                        $newFileAttachHOD_USFP->FAFileType      = 'US-FP';
                        $newFileAttachHOD_USFP->FAFilePath	    = $folderPath.'\\'.$newFileName;
                        $newFileAttachHOD_USFP->FAFileName      = $US_FRNo_FileAttach->FAFileName;
                        $newFileAttachHOD_USFP->FAOriginalName  = $US_FRNo_FileAttach->FAOriginalName;
                        $newFileAttachHOD_USFP->FAFileExtension = $US_FRNo_FileAttach->FAFileExtension;
                        $newFileAttachHOD_USFP->FAActive        = $US_FRNo_FileAttach->FAActive;
                        $newFileAttachHOD_USFP->FAMB 		    = Auth::user()->USCode;
                        $newFileAttachHOD_USFP->save();

                        $update_user->US_FRNo = $FRNo;
                        $update_user->save();
                    }
                }

                // $fclNo = $autoNumber->generateFCLNo();

                // $faceCompareLog = new FaceCompareLog();
                // $faceCompareLog->FCLNo = $fclNo;
                // $faceCompareLog->FCLRefNo = $FRNo;
                // $faceCompareLog->FCLRefType = 'FR';
                // $faceCompareLog->FCL_FAID1 = $FA_ID;
                // $faceCompareLog->FCL_FAID2 = $FA_US_FD;
                // $faceCompareLog->FCLFaceScore = $result['faceScore'];
                // $faceCompareLog->FCLResult = json_encode($result['respondResult']);
                // $faceCompareLog->FCLActive = 1;
                // $faceCompareLog->FCLCB = Auth::user()->USCode;
                // $faceCompareLog->save();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('setting.user.edit', [$request->userCode, 'flag'=>1]),
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

    public function faceDatatable(Request $request){

        $user = Auth::user();

        $query = FaceRegister::
            where('FR_USCode', $request->userCode)
            ->orderBy('FRCD', 'DESC')
            ->get();

        return DataTables::of($query)
            ->editColumn('FRNo', function($row) {

                $route = route('setting.user.face.edit',[$row->FRNo]);
                $result = '<a href="'.$route.'">'.$row->FRNo.'</a>';

                return $result;
            })
            ->editColumn('FRFaceScore', function($row) {
                if($row->FRFaceScore){
                    return number_format($row->FRFaceScore ?? 0 , 2, '.', ',');
                }
                else{
                    return '';
                }
            })
            ->editColumn('FRMatchComplete', function($row) {

                $statusMatch = $this->dropdownService->statusMatch();

                return $statusMatch[$row->FRMatchComplete] ?? '';
            })
            ->editColumn('FRMatchDate', function($row) {

                if($row->FRMatchDate){
                    $FRMatchDate = \Carbon\Carbon::parse($row->FRMatchDate)->format('d/m/Y H:i:s');
                }
                else{
                    $FRMatchDate = '';
                }

                return $FRMatchDate;
            })
            ->rawColumns(['FRNo', 'FRFaceScore', 'FRMatchComplete', 'FRMatchDate'])
            ->make(true);
    }

    public function create(){
        $statusActive = $this->dropdownService->statusActive();
        $roles = Role::whereNotIn('RLCode',['ADMIN'])->get()->pluck('RLName','RLCode');

        return view('setting.user.create',
            compact('roles', 'statusActive')
        );
    }

    public function store(Request $request){

        $user = Auth::user();

        $messages = [
            'USName.required' 	    => "Nama Pengguna diperlukan.",
            'USPhoneNo.required'    => "No. Tel. Pengguna diperlukan.",
            'USEmail.required'      => "Emel Pengguna diperlukan.",
            'USActive.required'     => "Status diperlukan.",
        ];

        $validation = [
            'USName'    => 'required',
            'USEmail'   => 'required',
            'USPhoneNo' => 'required',
            'USActive'  => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $generateManagementUserCode = $this->autoNumber->generateManagementUserCode();

            $user = new User();
            $user->USCode       = $generateManagementUserCode;
            $user->USName       = $request->USName ?? '';
            $user->USPwd        = Hash::make('123456');
            $user->USType       = 'MU';
            $user->USEmail      = $request->USEmail ?? '';
            $user->USPhoneNo    = $request->USPhoneNo ?? '';
            $user->USResetPwd   = 0;
            $user->USActive     = $request->USActive;
            $user->USCB         = $user->USCode;
            $user->USMB         = $user->USCode;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('setting.user.edit', [$generateManagementUserCode]),
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

    public function gambarEdit($id, $type){
        $statusActive = $this->dropdownService->statusActive();
        $roles = Role::whereNotIn('RLCode',['ADMIN'])->get()->pluck('RLName','RLCode');

        $user = User::where('USCode', $id)->first();

        return view('setting.user.gambar.edit',
            compact('id', 'type', 'user', 'roles', 'statusActive')
        );
    }

    public function gambarUpdate(Request $request){
//        dd($request->hasFile('dok_ic'), $request->picSelfie, $x);

        $user = Auth::user();

        try {
            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $update_user = User::where('USCode', $request->userCode)->first();

            if($request->picSelfie == null){
                return response()->json([
                    'error' => '1',
                    'message' => 'Sila ambil gambar selfie!'
                ], 400);
            }

            if($request->hasFile('dok_ic') && $request->picSelfie != null){

                $FRNo = $autoNumber->generateFaceRegisterNo();

                $faceRegister = new FaceRegister();
                $faceRegister->FRNo         = $FRNo;
                $faceRegister->FR_USCode    = $request->userCode;
                $faceRegister->FRMatchID    = '1';
                $faceRegister->FRMatchFace  = '1';
                $faceRegister->FRCB         = $user->USCode;
                $faceRegister->FRMB         = $user->USCode;
                $faceRegister->save();

                //save ic
                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

                $dok_FAFileType		    = "US-ID";

                $file = $request->file('dok_ic');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = new FileAttach();
                $fileAttach->FACB 		        = $user->USCode;
                $fileAttach->FAFileType 	    = $fileCode;
                $fileAttach->FARefNo     	    = $FRNo;
                $fileAttach->FA_USCode     	    = $request->userCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = $user->USCode;
                $fileAttach->save();

                $FA_ID = $fileAttach->FAID;

                //save selfie

                $request->picSelfie = preg_replace('#^data:image/\w+;base64,#i', '', $request->picSelfie);
                $decodedFile =base64_decode($request->picSelfie);
                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

                $dok_FAFileType		    = "US-FP";

//                $file = $request->file('dok_picture');

                $folderPath = Carbon::now()->format('ymd');
                $originalName = $request->userCode . Carbon::now()->timestamp .'.png';
                $newFileExt = 'png';
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

//                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $decodedFile);

                $fileAttach = new FileAttach();
                $fileAttach->FACB 		        = $user->USCode;
                $fileAttach->FAFileType 	    = $fileCode;
                $fileAttach->FARefNo     	    = $FRNo;
                $fileAttach->FA_USCode     	    = $request->userCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = $user->USCode;
                $fileAttach->save();

                $FA_US_FD = $fileAttach->FAID;

                $webController = new WebcamController(new DropdownService(), new AutoNumber());

                $result = $webController->faceRegisterChecking($FRNo);

                $update_faceRegister = FaceRegister::where('FRNo', $FRNo)->first();
                $update_faceRegister->FRFaceScore       = $result['faceScore'];
                $update_faceRegister->FRMatchComplete   = $result['facePass'];
                $update_faceRegister->FRMatchDate       = Carbon::now();
                $update_faceRegister->FRMB              = $user->USCode;
                $update_faceRegister->save();

                if($result['facePass'] == true){
                    $US_FRNo_FileAttach = FileAttach::where('FARefNo' , $FRNo)
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
                        $newFileAttachHOD_USFP->FA_USCode       = $update_user->USCode;
                        $newFileAttachHOD_USFP->FARefNo         = $update_user->USCode;
                        $newFileAttachHOD_USFP->FAFileType      = 'US-FP';
                        $newFileAttachHOD_USFP->FAFilePath	    = $folderPath.'\\'.$newFileName;
                        $newFileAttachHOD_USFP->FAFileName      = $US_FRNo_FileAttach->FAFileName;
                        $newFileAttachHOD_USFP->FAOriginalName  = $US_FRNo_FileAttach->FAOriginalName;
                        $newFileAttachHOD_USFP->FAFileExtension = $US_FRNo_FileAttach->FAFileExtension;
                        $newFileAttachHOD_USFP->FAActive        = $US_FRNo_FileAttach->FAActive;
                        $newFileAttachHOD_USFP->FAMB 		    = Auth::user()->USCode;
                        $newFileAttachHOD_USFP->save();

                        $update_user->US_FRNo = $FRNo;
                        $update_user->save();
                    }
                }

                // $fclNo = $autoNumber->generateFCLNo();

                // $faceCompareLog = new FaceCompareLog();
                // $faceCompareLog->FCLNo = $fclNo;
                // $faceCompareLog->FCLRefNo = $FRNo;
                // $faceCompareLog->FCLRefType = 'FR';
                // $faceCompareLog->FCL_FAID1 = $FA_ID;
                // $faceCompareLog->FCL_FAID2 = $FA_US_FD;
                // $faceCompareLog->FCLFaceScore = $result['faceScore'];
                // $faceCompareLog->FCLResult = json_encode($result['respondResult']);
                // $faceCompareLog->FCLActive = 1;
                // $faceCompareLog->FCLCB = Auth::user()->USCode;
                // $faceCompareLog->save();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('setting.gambar.edit', [$request->userCode, $request->type]),
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

    public function createDirector(){
        $statusActive = $this->dropdownService->statusActive();
        $roles = Role::whereNotIn('RLCode',['ADMIN'])->get()->pluck('RLName','RLCode');

        return view('setting.user.director.create',
            compact('roles', 'statusActive')
        );
    }

    public function storeDirector(Request $request){
        try {
            DB::beginTransaction();

            $contractorAU = ContractorAuthUser::where('CAUNo', $request->CAUNo)->first();

            if($contractorAU){
                $user_exist = User::where('USEmail', $contractorAU->CAUEmail)
                    ->where('USType', 'AU')
                    ->first();

                if($user_exist){
                    $CAU_USCode = $user_exist->USCode;
                }
                else{
                    $generateApprovalUserCode = $this->autoNumber->generateApprovalUserCode();

                    $user = new User();
                    $user->USCode       = $generateApprovalUserCode;
                    $user->USName       = $contractorAU->CAUName ?? '';
                    $user->USPwd        = Hash::make('123456');
                    $user->USType       = 'AU';
                    $user->USEmail      = $contractorAU->CAUEmail ?? '';
                    $user->USPhoneNo    = $contractorAU->CAUPhoneNo ?? '';
                    $user->USResetPwd   = 0;
                    $user->USActive     = '1';
                    $user->USCB         = $user->USCode;
                    $user->USMB         = $user->USCode;
                    $user->save();

                    $CAU_USCode = $generateApprovalUserCode;
                }
                $contractorAU->CAUStatus = 'ACTIVE';
                $contractorAU->CAU_USCode = $CAU_USCode;
                $contractorAU->save();

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('setting.user.edit', [$CAU_USCode]),
                    'message' => 'Maklumat Berjaya Dikemaskini.'
                ]);
            }
            else{
                return response()->json([
                    'error' => '1',
                    'message' => 'Maklumat Tidak Berjaya Dikemaskini!'
                ], 400);
            }




        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat Tidak Berjaya Dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

//    public function delete(Request $request){
//        try {
//            DB::beginTransaction();
//			$user = User::find($request->id);
//			FileAttach::where('FA_USCode',$user->USCode)->delete();
//			$user->delete();
//            DB::commit();
//
//        }catch (\Throwable $e) {
//            DB::rollback();
//
//            return response()->json([
//                'error' => '1',
//				'message' => trans('message.user.fail').$e->getMessage()
//            ], 400);
//        }
//
//        return response()->json([
//			'success' => '1',
//			'redirect' => route('setting.user.index'),
//			'message' => trans('message.user.delete')
//		]);
//    }

}
