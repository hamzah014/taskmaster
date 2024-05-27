<?php

namespace App\Http\Controllers\Perolehan\Setting;

use App\Http\Controllers\Controller;
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
use App\Models\LetterAcceptance;
use App\Models\LetterIntent;
use App\Models\Tender;
use App\Models\Project;
use App\Models\FileType;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Services\DropdownService;
use Session;

class DocumentController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('perolehan.setting.document.index');
    }

    public function store(Request $request){
        $validatedData = $request->validate([
            'FTCode' => 'required',
            'FTDesc' => 'required',
        ]);

        $exist = FileType::where('FTCode', $validatedData['FTCode'])->first();

        if ($exist) {
            return response()->json([
                'error' => '1',
                'message' => 'File Type already exists.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $fileType = new FileType();
            $fileType->FTCode = $validatedData['FTCode'];
            $fileType->FTDesc = $validatedData['FTDesc'];
            $fileType->FTSetting = '1';

            $dok_FAUSCode		    = Auth::user()->USCode;
			$dok_FARefNo		    = Auth::user()->USCode;
			$dok_FAFileType		    = $validatedData['FTCode'];


            //FILE UPLOAD
            if ($request->hasFile('dok_upload')) {

                $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

                $file = $request->file('dok_upload');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = FileAttach::where('FA_USCode',$dok_FAUSCode)
                                        ->where('FARefNo',$dok_FARefNo)
                                        ->where('FAFileType',$fileCode)
                                        ->first();
                if ($fileAttach == null){
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB 		= Auth::user()->USCode;
                    $fileAttach->FAFileType 	= $fileCode;
                }else{

                    $filename   = $fileAttach->FAFileName;
                    $fileExt    = $fileAttach->FAFileExtension ;

                    $returnval = Storage::disk('fileStorage')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);

                }
                $fileAttach->FARefNo     	    = $dok_FARefNo;
                $fileAttach->FA_USCode     	    = $dok_FAUSCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = Auth::user()->USCode;
                $fileAttach->save();

            }

            $fileType->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.document.index'),
                'message' => 'Dokumen berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function deletedoc(Request $request)
    {
        try {
            $fileType = FileType::where('FTID',$request->fileID)->first();


            if (!$fileType) {
                return response()->json([
                    'error' => '1',
                    'message' => 'File Type not found.',
                ], 404);
            }

            $fileType->delete();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.document.index'),
                'message' => 'Dokumen berjaya dipadam.'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => '1',
                'message' => 'Failed to delete File Type: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function fileTypeDataTable(Request $request)
    {
        $query = FileType::where('FTSetting', '1')->orderBy('FTID', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
        ->addColumn('actions', function ($row) use (&$count) {
            $result = '';
            $route = "openUploadModal('" . Auth::user()->USCode . "','" . Auth::user()->USCode . "','" . $row->FTCode . "')";
            $result .= '<a class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal" onclick="' . $route . '">
                            <i class="ki-solid ki-folder-up fs-2"></i>
                        </a>
                        ';

            if ($row->FileAttachFT) {
                $route2 = route('file.view', [$row->FileAttachFT->FAGuidID]);

                $result .= '<a target="_blank" class="btn btn-sm btn-light-primary" href="' . $route2 . '"><i class="ki-solid ki-eye fs-2"></i>Papar</a>';

            }
            return $result;
        })
        ->addColumn('indexNo', function ($row) use (&$count) {
            $count++;
            return $count;
        })
        ->addColumn('file_code', function ($row) {
            return $row->FTCode;
        })
        ->addColumn('document', function ($row) {
            return $row->FTDesc;
        })
        ->rawColumns(['actions'])
        ->make(true);

    }
}
