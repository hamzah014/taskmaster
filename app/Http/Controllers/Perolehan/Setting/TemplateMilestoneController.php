<?php

namespace App\Http\Controllers\Perolehan\Setting;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\SSMCompany;
use App\Models\TemplateClaimFile;
use App\Models\TemplateClaimFileDet;
use App\Models\TemplateMilestone;
use App\Models\TemplateMilestoneDet;
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

class TemplateMilestoneController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        return view('perolehan.setting.templateMilestone.index');
    }

    public function create(){
        $departmentAll = $this->dropdownService->departmentAll();
        $statusActive = $this->dropdownService->statusActive();
        $jenis_projek = $this->dropdownService->jenis_projek();

        return view('perolehan.setting.templateMilestone.create',
            compact('departmentAll', 'statusActive', 'jenis_projek')
        );
    }

    public function store(Request $request){


        $messages = [
            'title.required'        => 'Tajuk Templat Senarai Semak diperlukan.',
            'jenis_projek.required' => 'Jenis Projek diperlukan.',
            'status.required'       => 'Status berita diperlukan.',
        ];

        $validation = [
            'title'         => 'required',
            'jenis_projek'  => 'required',
            'status'        => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();
            if(isset($request->TMDID)){
                $TMDIDs = $request->TMDID;
                $TMDDescs = $request->TMDDesc;
                $TMDPercents = $request->TMDPercent;
            }
            else{
                $TMDIDs = [];
            }

            $autoNumber = new AutoNumber();
            $TMCode = $autoNumber->generateTemplateMilestoneNo();

            $TemplateMilestone = new TemplateMilestone();
            $TemplateMilestone->TMCode     = $TMCode;
            $TemplateMilestone->TMDesc     = $request->title;
            $TemplateMilestone->TM_DPTCode = $request->jabatan;
            $TemplateMilestone->TM_PTCode  = $request->jenis_projek;
            $TemplateMilestone->TMActive   = $request->status;
            $TemplateMilestone->TMCB       = $user->USCode;
            $TemplateMilestone->TMMB       = $user->USCode;
            $TemplateMilestone->save();

            if(count($TMDIDs) > 0){
                foreach ($TMDIDs as $key => $TMDID){

                    $templateClaimFileDet = new TemplateMilestoneDet();
                    $templateClaimFileDet->TMD_TMCode   = $TMCode;
                    $templateClaimFileDet->TMDSeq       = $key + 1;
                    $templateClaimFileDet->TMDDesc      = $TMDDescs[$key];
                    $templateClaimFileDet->TMDPercent   = $TMDPercents[$key];
                    $templateClaimFileDet->TMDCB        = $user->USCode;
                    $templateClaimFileDet->TMDMB        = $user->USCode;
                    $templateClaimFileDet->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateMilestone.edit', [$TMCode]),
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

        $templateMilestone = TemplateMilestone::where('TMCode',$id)->first();

        return view('perolehan.setting.templateMilestone.edit',
            compact('templateMilestone','departmentAll', 'statusActive', 'jenis_projek')
        );

    }

    public function update(Request $request,$id){

        $messages = [
            'title.required'        => 'Tajuk Templat Senarai Semak diperlukan.',
            'jenis_projek.required' => 'Jenis Projek diperlukan.',
            'status.required'       => 'Status berita diperlukan.',
        ];

        $validation = [
            'title'         => 'required',
            'jenis_projek'  => 'required',
            'status'        => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            $user = Auth::user();
            if(isset($request->TMDID)){
                $TMDIDs = $request->TMDID;
                $TMDDescs = $request->TMDDesc;
                $TMDPercents = $request->TMDPercent;
            }
            else{
                $TMDIDs = [];
            }

            $templateMilestone = TemplateMilestone::where('TMCode', $request->TMCode)->first();

            $templateMilestone->TMDesc     = $request->title;
            $templateMilestone->TM_DPTCode = $request->jabatan;
            $templateMilestone->TM_PTCode  = $request->jenis_projek;
            $templateMilestone->TMActive   = $request->status;
            $templateMilestone->TMMB       = $user->USCode;
            $templateMilestone->save();

            $old_templateMilestoneDet = TemplateMilestoneDet::where('TMD_TMCode', $request->TMCode)->get();

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_templateMilestoneDet) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_templateMilestoneDet as $otemplateMilestoneDet){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($TMDIDs as $TMDID){
                        if($otemplateMilestoneDet->TMDID == $TMDID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $otemplateMilestoneDet->delete();
                    }
                }
                //ADD NEW 4,5,6
                foreach($TMDIDs as $key => $TMDID){
                    $new_templateMilestoneDet = TemplateMilestoneDet::where('TMD_TMCode', $request->TMCode)
                        ->where('TMDID', $TMDID)->first();

                    if(!$new_templateMilestoneDet){
                        $new_templateMilestoneDet = new TemplateMilestoneDet();
                        $new_templateMilestoneDet->TMD_TMCode   = $request->TMCode;
                        $new_templateMilestoneDet->TMDCB        = $user->USCode;
                    }
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                    $new_templateMilestoneDet->TMDSeq       = $key + 1;
                    $new_templateMilestoneDet->TMDDesc      = $TMDDescs[$key];
                    $new_templateMilestoneDet->TMDPercent   = $TMDPercents[$key];
                    $new_templateMilestoneDet->TMDMB        = $user->USCode;
                    $new_templateMilestoneDet->save();
                }
            }
            else{
                if(count($TMDIDs) > 0){
                    foreach ($TMDIDs as $key => $TMDID){

                        $templateMilestoneDet = new TemplateMilestoneDet();
                        $templateMilestoneDet->TMD_TMCode   = $request->TMCode;
                        $templateMilestoneDet->TMDSeq       = $key + 1;
                        $templateMilestoneDet->TMDDesc      = $TMDDescs[$key];
                        $templateMilestoneDet->TMDPercent   = $TMDPercents[$key];
                        $templateMilestoneDet->TMDCB        = $user->USCode;
                        $templateMilestoneDet->TMDMB        = $user->USCode;
                        $templateMilestoneDet->save();
                    }
                }
            }
//END HERE

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateMilestone.edit',[$request->TMCode]),
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

            $templateMilestone = TemplateMilestone::where('TMCode',$request->idNo)->first();

            if(count($templateMilestone->templateMilestoneDet) > 0){
                foreach ( $templateMilestone->templateMilestoneDet as $item => $templateMilestoneDet) {
                    $templateMilestoneDet->delete();
                }
            }

            $templateMilestone->delete();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateMilestone.index'),
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
    public function templateMilestoneDataTable(){

        $query = TemplateMilestone::orderBy('TMCode', 'DESC')->get();

        return datatables()->of($query)
            ->editColumn('TMDesc', function($row) {
                $route = route('perolehan.setting.templateMilestone.edit',[$row->TMCode]);
                $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light">'.$row->TMDesc.'</a>';
                return $result;
            })
            ->editColumn('TM_DPTCode', function($row) {
                $departmentAll = $this->dropdownService->departmentAll();

                if($row->TM_DPTCode == null){
                    return 'Semua Jabatan';
                }
                else{
                    return $departmentAll[$row->TM_DPTCode];
                }
            })
            ->editColumn('TM_PTCode', function($row) {
                $jenis_projek = $this->dropdownService->jenis_projek();

                return $jenis_projek[$row->TM_PTCode];
            })
            ->editColumn('TMActive', function($row) {
                return $row->TMActive == 1 ? 'Aktif' : 'Tidak Aktif';
            })
            ->editColumn('TMCD', function($row) {


                // Create a Carbon instance from the MySQL datetime value
                $carbonDatetime = Carbon::parse($row->TMCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i');

                return $formattedDate;

            })
            ->addColumn('action', function($row) {
                $routeDelete = route('perolehan.setting.templateMilestone.delete',[$row->TMCode]);
                $result = "";

                if($row->TMActive == 0){

                    $result = '<button onclick="deleteRecord(\' '.$row->TMCode.' \')" class="btn btn-light-primary"><i class="material-icons">delete</i></button>';

                }
                return $result;
            })
            ->rawColumns(['TMDesc','TM_DPTCode','TM_PTCode','TMActive', 'TMCD','action'])
            ->make(true);
    }

}
