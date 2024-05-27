<?php

namespace App\Http\Controllers\Perolehan\Setting;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\SSMCompany;
use App\Models\TemplateClaimFile;
use App\Models\TemplateClaimFileDet;
use App\Models\TemplateSpecDetail;
use App\Models\TemplateSpecHeader;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
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
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;

class TemplateChecklistController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        return view('perolehan.setting.templateChecklist.index');
    }

    public function create(){
        $departmentAll = $this->dropdownService->departmentAll();
        $statusActive = $this->dropdownService->statusActive();
        $jenis_projek = $this->dropdownService->jenis_projek();
        $mandatory = $this->dropdownService->mandatory();
        $jenis_checklist = $this->dropdownService->jenis_checklist();

        return view('perolehan.setting.templateChecklist.create',
            compact('departmentAll', 'statusActive', 'jenis_projek', 'mandatory', 'jenis_checklist')
        );
    }

    public function store(Request $request){

        $messages = [
            'title.required'        => 'Tajuk Templat Senarai Semak diperlukan.',
            'jabatan.required'      => 'Jenis Projek diperlukan.',
            'jenis_projek.required' => 'Jenis Projek diperlukan.',
            'jenis_checklist.required' => 'Jenis Projek diperlukan.',
            'status.required'       => 'Status berita diperlukan.',
        ];

        $validation = [
            'title'         => 'required',
            'jabatan'       => 'required',
            'jenis_projek'  => 'required',
            'jenis_checklist'  => 'required',
            'status'        => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();
            if(isset($request->TCFDID)){
                $TCFDIDs = $request->TCFDID;
                $TCFDDescs = $request->TCFDDesc;
                $TCFDUploadTypes = $request->TCFDUploadType;
            }
            else{
                $TCFDIDs = [];
            }

            $autoNumber = new AutoNumber();
            $TCFCode = $autoNumber->generateTemplateClaimFileNo();

            $templateClaimFile = new TemplateClaimFile();
            $templateClaimFile->TCFCode     = $TCFCode;
            $templateClaimFile->TCFDesc     = $request->title;
            $templateClaimFile->TCF_DPTCode = $request->jabatan;
            $templateClaimFile->TCF_PTCode  = $request->jenis_projek;
            $templateClaimFile->TCFType     = $request->jenis_checklist;
            $templateClaimFile->TCFActive   = $request->status;
            $templateClaimFile->TCFCB       = $user->USCode;
            $templateClaimFile->TCFMB       = $user->USCode;
            $templateClaimFile->save();

            if(count($TCFDIDs) > 0){
                foreach ($TCFDIDs as $key => $TCFDID){

                    $templateClaimFileDet = new TemplateClaimFileDet();
                    $templateClaimFileDet->TCFD_TCFCode     = $TCFCode;
                    $templateClaimFileDet->TCFDSeq          = $key + 1;
                    $templateClaimFileDet->TCFDDesc         = $TCFDDescs[$key];
                    $templateClaimFileDet->TCFDUploadType   = $TCFDUploadTypes[$key];
                    $templateClaimFileDet->TCFDCB           = $user->USCode;
                    $templateClaimFileDet->TCFDMB           = $user->USCode;
                    $templateClaimFileDet->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateChecklist.edit', [$TCFCode]),
                'message' => 'Maklumat berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }

    public function edit($id){

        $departmentAll = $this->dropdownService->departmentAll();
        $statusActive = $this->dropdownService->statusActive();
        $jenis_projek = $this->dropdownService->jenis_projek();
        $mandatory = $this->dropdownService->mandatory();
        $jenis_checklist = $this->dropdownService->jenis_checklist();

        $templateclaimfile = TemplateClaimFile::where('TCFCode',$id)->first();

        return view('perolehan.setting.templateChecklist.edit',
            compact('templateclaimfile','departmentAll', 'statusActive', 'jenis_projek', 'mandatory', 'jenis_checklist')
        );

    }

    public function update(Request $request,$id){

        $messages = [
            'title.required'        => 'Tajuk Templat Senarai Semak diperlukan.',
            'jabatan.required'      => 'Jenis Projek diperlukan.',
            'jenis_projek.required' => 'Jenis Projek diperlukan.',
            'jenis_checklist.required' => 'Jenis Projek diperlukan.',
            'status.required'       => 'Status berita diperlukan.',
        ];

        $validation = [
            'title'         => 'required',
            'jabatan'       => 'required',
            'jenis_projek'  => 'required',
            'jenis_checklist'  => 'required',
            'status'        => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            $user = Auth::user();
            if(isset($request->TCFDID)){
                $TCFDIDs = $request->TCFDID;
                $TCFDDescs = $request->TCFDDesc;
                $TCFDUploadTypes = $request->TCFDUploadType;
            }
            else{
                $TCFDIDs = [];
            }

            $templateClaimFile = TemplateClaimFile::where('TCFCode', $request->TCFCode)->first();

            $templateClaimFile->TCFDesc     = $request->title;
            $templateClaimFile->TCF_DPTCode = $request->jabatan;
            $templateClaimFile->TCF_PTCode  = $request->jenis_projek;
            $templateClaimFile->TCFType     = $request->jenis_checklist;
            $templateClaimFile->TCFActive   = $request->status;
            $templateClaimFile->TCFMB       = $user->USCode;
            $templateClaimFile->save();

            $old_templateClaimFileDet = TemplateClaimFileDet::where('TCFD_TCFCode', $request->TCFCode)->get();

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_templateClaimFileDet) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_templateClaimFileDet as $otemplateClaimFileDet){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($TCFDIDs as $TCFDID){
                        if($otemplateClaimFileDet->TCFDID == $TCFDID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $otemplateClaimFileDet->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($TCFDIDs as $key => $TCFDID){
                    $new_templateClaimFileDet = TemplateClaimFileDet::where('TCFD_TCFCode', $request->TCFCode)
                        ->where('TCFDID', $TCFDID)->first();

                    if(!$new_templateClaimFileDet){
                        $new_templateClaimFileDet = new TemplateClaimFileDet();
                        $new_templateClaimFileDet->TCFD_TCFCode     = $request->TCFCode;
                        $new_templateClaimFileDet->TCFDCB            = $user->USCode;
                    }
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                    $new_templateClaimFileDet->TCFDSeq          = $key + 1;
                    $new_templateClaimFileDet->TCFDDesc         = $TCFDDescs[$key];
                    $new_templateClaimFileDet->TCFDUploadType   = $TCFDUploadTypes[$key];
                    $new_templateClaimFileDet->TCFDMB           = $user->USCode;
                    $new_templateClaimFileDet->save();
                }
            }
            else{
                if(count($TCFDIDs) > 0){
                    foreach ($TCFDIDs as $key => $TCFDID){

                        $templateClaimFileDet = new TemplateClaimFileDet();
                        $templateClaimFileDet->TCFD_TCFCode     = $request->TCFCode;
                        $templateClaimFileDet->TCFDSeq          = $key + 1;
                        $templateClaimFileDet->TCFDDesc         = $TCFDDescs[$key];
                        $templateClaimFileDet->TCFDUploadType   = $TCFDUploadTypes[$key];
                        $templateClaimFileDet->TCFDCB           = $user->USCode;
                        $templateClaimFileDet->TCFDMB           = $user->USCode;
                        $templateClaimFileDet->save();
                    }
                }
            }
//END HERE


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateChecklist.edit',[$request->TCFCode]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function delete(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $templateClaimFile = TemplateClaimFile::where('TCFCode',$request->idNo)->first();

            if(count($templateClaimFile->claimFileDet) > 0){
                foreach ( $templateClaimFile->claimFileDet as $item => $claimFileDet) {
                    $claimFileDet->delete();
                }
            }

            $templateClaimFile->delete();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateChecklist.index'),
                'message' => 'Maklumat berjaya dipadam.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dipadam!'.$e->getMessage()
            ], 400);
        }

    }

    // {{--Working Code Datatable--}}
    public function templateChecklistDataTable(){

        $query = TemplateClaimFile::orderBy('TCFCode', 'DESC')->get();

        return datatables()->of($query)
            ->editColumn('TCFDesc', function($row) {
                $route = route('perolehan.setting.templateChecklist.edit',[$row->TCFCode]);
                $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light">'.$row->TCFDesc.'</a>';
                return $result;
            })
            ->editColumn('TCF_DPTCode', function($row) {
                $departmentAll = $this->dropdownService->departmentAll();

                return $departmentAll[$row->TCF_DPTCode];
            })
            ->editColumn('TCF_PTCode', function($row) {
                $jenis_projek = $this->dropdownService->jenis_projek();

                return $jenis_projek[$row->TCF_PTCode];
            })
            ->editColumn('TCFType', function($row) {
                $jenis_checklist = $this->dropdownService->jenis_checklist();

                return $jenis_checklist[$row->TCFType];
            })
            ->editColumn('TCFActive', function($row) {
                return $row->TCFActive == 1 ? 'Aktif' : 'Tidak Aktif';
            })
            ->editColumn('TCFCD', function($row) {


                // Create a Carbon instance from the MySQL datetime value
                $carbonDatetime = Carbon::parse($row->TCFCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i');

                return $formattedDate;

            })
            ->addColumn('action', function($row) {
                $routeDelete = route('perolehan.setting.templateSpec.delete',[$row->TCFCode]);
                $result = "";

                if($row->TCFActive == 0){

                    $result = '<button onclick="deleteRecord(\' '.$row->TCFCode.' \')" class="btn btn-light-primary"><i class="material-icons">delete</i></button>';

                }
                return $result;
            })
            ->rawColumns(['TCFDesc','TCF_DPTCode','TCF_DPTCode', 'TCFType','TCFActive', 'TCFCD','action'])
            ->make(true);
    }

}
