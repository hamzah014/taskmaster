<?php

namespace App\Http\Controllers\Perolehan\Setting;

use App\Http\Controllers\Controller;
use App\Models\TemplateFile;
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

class TemplateFileController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        $jenis_docA = $this->dropdownService->jenis_docA();
        $jenis_tender = $this->dropdownService->jenis_tender();
        $statusActive = $this->dropdownService->statusActive();

        return view('perolehan.setting.templateFile.index',
            compact('jenis_docA', 'jenis_tender', 'statusActive')
        );
    }

    public function store(Request $request){
        $messages = [
            'jenisTemplate.required'    => 'Jenis Template Fail diperlukan.',
            'nama.required'             => 'Nama Template Fail diperlukan.',
            'jenisTender.required'      => 'Jenis Tender diperlukan.',
            'status.required'           => 'Status diperlukan.',
        ];

        $validation = [
            'jenisTemplate'     => 'required',
            'nama'              => 'required',
            'jenisTender'       => 'required',
            'status'            => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $TFNo = $autoNumber->generateTemplateFileNo();

            $templateFile = new TemplateFile();
            $templateFile->TFNo = $TFNo;
            $templateFile->TFTitle = $request->nama;
            $templateFile->TF_MTCode = $request->jenisTemplate;
            $templateFile->TF_TTCode = $request->jenisTender;
            $templateFile->TFActive = $request->status;
            $templateFile->TFCB = Auth::user()->USCode;
            $templateFile->save();

            $dok_FAUSCode		    = $TFNo;
            $dok_FARefNo		    = $TFNo;
            $dok_FAFileType		    = $request->jenisTemplate;


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

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateFile.index'),
                'message' => 'Templat Fail berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function deleteFile(Request $request)
    {
        try {
            $fileType = TemplateFile::where('TFNo',$request->fileID)->first();

            if (!$fileType) {
                return response()->json([
                    'error' => '1',
                    'message' => 'File Type not found.',
                ], 404);
            }

            $fileType->delete();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateFile.index'),
                'message' => 'Dokumen berjaya dipadam.'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => '1',
                'message' => 'Failed to delete File Type: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function templateFileDataTable(Request $request)
    {
        $query = TemplateFile::orderBy('TFNo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function ($row) use (&$count) {
                $count++;
                return $count;
            })
            ->editColumn('TF_MTCode', function ($row) {
                $jenis_docA = $this->dropdownService->jenis_docA();

                return $jenis_docA[$row->TF_MTCode];
            })
            ->editColumn('TFTitle', function ($row) {
                $result = '';

                $result .= '<a class="text-decoration-underline new modal-trigger" data-bs-toggle="modal" data-bs-target="#editModal" onclick="editTemplateFile(\'' . $row->TFNo . '\')">'.$row->TFTitle.'</a>';

                return $result;
            })
            ->editColumn('TF_TTCode', function ($row) {
                $jenis_tender = $this->dropdownService->jenis_tender();

                return $jenis_tender[$row->TF_TTCode];
            })
            ->addColumn('actions', function ($row) use (&$count) {
                $result = '';
                $route = "openUploadModal('" . $row->TFNo . "','" . $row->TFNo . "','" . $row->TF_MTCode . "')";

                if ($row->TF_MTCode === 'BF' || $row->TF_MTCode === 'DF') {
                    $result .= '<a data-bs-toggle="modal" data-bs-target="#uploadModal" onclick="' . $route . '" class="new modal-trigger waves-effect waves-light btn btn-sm btn-light-primary">
                                <i class="ki-solid ki-folder fs-2"></i>
                            </a>';
                }

                if ($row->FileAttachTF) {
                    $route2 = route('file.view', [$row->FileAttachTF->FAGuidID]);

                    $result .= '<a target="_blank" class="new modal-trigger btn btn-sm btn-light-primary" href="' . $route2 . '"><i class="ki-solid ki-eye fs-2"></i>Papar</a>';

                }
//                if(!in_array($row->FTCode, ['PT-AF', 'PT-BAF', 'TDFE', 'TFD', 'TFE'])){
                    $result .= '<button onclick="deleteRecord(\' '.$row->TFNo.' \')" class="btn btn-sm btn-danger"><i class="ki-solid ki-trash fs-2 "></i></button>';

//                }

                return $result;
            })
            ->rawColumns(['indexNo', 'TF_MTCode', 'TFTitle', 'TF_TTCode', 'actions'])
            ->make(true);

    }

    //AJAX FUNCTION
    public function getTemplateFile(Request $request){

        $templateFile = TemplateFile::where('TFNo', $request->tfno)->first();

        return $templateFile;
    }

    public function update(Request $request){

        $messages = [
            'ejenisTemplate.required'    => 'Jenis Template Fail diperlukan.',
            'enama.required'             => 'Nama Template Fail diperlukan.',
            'ejenisTender.required'      => 'Jenis Tender diperlukan.',
            'estatus.required'           => 'Status diperlukan.',
        ];

        $validation = [
            'ejenisTemplate'     => 'required',
            'enama'              => 'required',
            'ejenisTender'       => 'required',
            'estatus'            => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $templateFile = TemplateFile::where('TFNo', $request->etfno)->first();
            $templateFile->TFTitle = $request->enama;
            $templateFile->TF_MTCode = $request->ejenisTemplate;
            $templateFile->TF_TTCode = $request->ejenisTender;
            $templateFile->TFActive = $request->estatus;
            $templateFile->TFMB = Auth::user()->eUSCode;
            $templateFile->save();

            $dok_FAUSCode		    = $request->etfno;
            $dok_FARefNo		    = $request->etfno;
            $dok_FAFileType		    = $request->ejenisTemplate;


            //FILE UPLOAD
            if ($request->hasFile('edok_upload')) {

                $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

                $file = $request->file('edok_upload');

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

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateFile.index'),
                'message' => 'Templat Fail berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }
}
