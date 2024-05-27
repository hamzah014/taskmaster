<?php

namespace App\Http\Controllers\Contractor\EOT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Models\AutoNumber;
use App\Models\CommentLog;
use App\Models\ExtensionOfTime;
use App\Models\FileAttach;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectClaimDet;
use App\Models\ProjectInvoice;
use App\Models\ProjectMilestone;
use App\Models\TemplateClaimFile;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Cassandra\Exception\ExecutionException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class EOTController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        $PTNo = Session::get('project');

        $milestone = ProjectMilestone::where('PM_PTNo', $PTNo)
//            ->where('PMClaimInd', 1)
//            ->whereHas('projectClaim', function ($query) {
//                $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
//            })
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

        return view('contractor.eot.index',
            compact('PTNo', 'milestone')
        );
    }

    public function create($id){
        $eotType = $this->dropdownService->eotType();
        $PTNo = Session::get('project');

        $milestone = ProjectMilestone::where('PM_PTNo', $PTNo)
            // ->whereHas('projectClaim', function ($query) {
            //     $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
            // })
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

        return view('contractor.eot.create',
            compact('id', 'PTNo', 'milestone', 'eotType')
        );
    }

    public function store(Request $request){

        $messages = [
            'idMilestone.required'          => 'Milestone diperlukan.',
            'eotType.required'              => 'Jenis lanjutan masa diperlukan.',
            'keterangan.required'           => 'Keterangan diperlukan.',
            'eotWorkDay.required'           => 'Tempoh lanjutan masa diperlukan.',
            'dokumen.required'              => 'Lampiran Dokumen diperlukan.',
            'dokumen.file'                  => 'Lampiran Dokumen harus berupa file.',
        ];

        $validation = [
            'idMilestone'      => 'required',
            'eotType'          => 'required',
            'keterangan'       => 'required',
            'eotWorkDay'       => 'required',
            'dokumen'          => 'required|file',
        ];

        $request->validate($validation, $messages);

        try {
            $user = Auth::user();

            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $EOTNo = $autoNumber->generateEOTNo();

            $eot = new ExtensionOfTime();
            $eot->EOTNo = $EOTNo;
            $eot->EOTDesc = $request->keterangan;
            $eot->EOType = $request->eotType;
            $eot->EOT_PTNo = $request->PTNo;
            $eot->EOT_PMNo = $request->idMilestone;
            $eot->EOTWorkDay = $request->eotWorkDay;

            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');
                $fileType = 'EOT';
                $refNo = $EOTNo;
                $this->saveFile($file,$fileType,$refNo);
            }

            $status = "CO-SUBMIT";
            $eot->EOTCB = $user->USCode;
            $eot->EOTMB = $user->USCode;
            $eot->EOTByPelaksana = 0;
            $eot->EOTStatus = $status;
            $eot->EOT_EPCode = 'RQ-RQ'; #EOTRQ
            // $eot->EOT_EPCode = 'RQ';
            $eot->save();

            $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
            $approvalController->storeApproval($eot->EOTNo, 'EOT-RQ');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.index'),
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

    public function edit($id){
        $eotType = $this->dropdownService->eotType();
        $meetingStatus = $this->dropdownService->boardMeetingStatus();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();
        $response_type = $this->dropdownService->response_type();
        $statusProcess = $this->dropdownService->statusProcess();

        $eot = ExtensionOfTime::where('EOTNO', $id)->first();
        $PTNo = Session::get('project');

        $milestone = ProjectMilestone::where('PM_PTNo', $PTNo)
            // ->whereHas('projectClaim', function ($query) {
            //     $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
            // })
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

        $commentLog = $eot->commentLog;
        if($eot->commentLog){
            foreach($commentLog as $comment){
                $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');

            }
        }

        $meetingEot = null;

        if($eot->meetingEot){

            $meetingEot = $eot->meetingEot;

            $meetingEot->status = $meetingStatus[$meetingEot->meeting->MStatus];
            $meetingEot->date = Carbon::parse($meetingEot->meeting->MDate)->format('d/m/Y');
            $meetingEot->time = Carbon::parse($meetingEot->meeting->MTime)->format('H:i A');

        }

        // $milestone = [];

        $eot = ExtensionOfTime::where('EOTNo',$id)->first();

        $eot->statusEOT = $statusProcess[$eot->EOTStatus] ?? "-";

        $project = Project::where('PTNo',$eot->EOT_PTNo)->first();

        //JENIS PROJECT
        $jenis_projek = $this->dropdownService->jenis_projek();
        $project_type = "";

        if(!empty($project->tenderProposal->tender->TD_PTCode)){
            $project_type = $jenis_projek[$project->tenderProposal->tender->TD_PTCode];
        }

        $project->projectType = $project_type;

        // TARIKH SST
        $SSTDate = "";
        if($project->tenderProposal->letterAcceptance){

            $letterAccept = $project->tenderProposal->letterAcceptance;


            $carbonDatetime = Carbon::parse($letterAccept->LAConfirmDate);
            $SSTDate = $carbonDatetime->format('d/m/Y');
        }

        $project->projectSSTDate = $SSTDate;

        // TARIKH SAK
        $carbonDatetime = Carbon::parse($project->PTSAKDate);
        $SAKDate = $carbonDatetime->format('d/m/Y');

        $project->projectSAKDate = $SAKDate;

        $commentLog = $eot->commentLog;
        if($eot->commentLog){
            foreach($commentLog as $comment){
                $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');

            }
        }

        return view('contractor.eot.edit',
        compact('eot','eotType', 'PTNo', 'milestone', 'meetingEot', 'milestone', 'eotType','project',
        'unitMeasurement','header_detail','response_type')
        );
    }
    public function update(Request $request){

        $messages = [
            'eotType.required'              => 'Jenis lanjutan masa diperlukan.',
            'keterangan.required'           => 'Keterangan diperlukan.',
            'eotWorkDay.required'           => 'Tempoh lanjutan masa diperlukan.',
        ];

        $validation = [
            'eotType'          => 'required',
            'keterangan'       => 'required',
            'eotWorkDay'       => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            $user = Auth::user();

            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $EOTNo = $request->eotNo;

            $eot = ExtensionOfTime::where('EOTNo',$EOTNo)->first();
            $eot->EOTNo = $EOTNo;
            $eot->EOTDesc = $request->keterangan;
            $eot->EOType = $request->eotType;
            $eot->EOT_PTNo = $request->PTNo;
//            $eot->EOT_PMNo = $request->idMilestone;
            $eot->EOTWorkDay = $request->eotWorkDay;

            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');
                $fileType = 'EOT';
                $refNo = $EOTNo;
                $this->saveFile($file,$fileType,$refNo);
            }

            $updateStatus = $request->updateStatus;

            if($updateStatus == 1){
//                $status = "CO-SUBMIT";
                $eot->EOT_EPCode = 'RQ';
            }
//            else{
//                $status = "NEW";
//
//            }

            $eot->EOTMB = $user->USCode;
            $eot->save();

            DB::commit();

            if($updateStatus == 1) {
                return response()->json([
                    'success' => '1',
                    'redirect' => route('contractor.eot.index'),
                    'message' => 'Maklumat berjaya dikemaskini.'
                ]);
            }
            else{
                return response()->json([
                    'success' => '1',
                    'redirect' => route('contractor.eot.edit', [$EOTNo]),
                    'message' => 'Maklumat berjaya dikemaskini.'
                ]);
            }


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function eotDatatable(Request $request){
        $projectNo = Session::get('project');

        $query = ExtensionOfTime::where('EOT_PTNo', $projectNo)->orderBy('EOTNo', 'DESC')->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('EOTNo', function($row) {

                if($row->EOT_EPCode == 'EOTS'){
                    $route = route('contractor.eot.viewEOT',[$row->EOTNo]);

                }else{
                    if($row->EOTPaid == 1){
                        $route = route('contractor.eot.viewEOT',[$row->EOTNo]);
                    }
                    else{
                        $route = route('contractor.eot.edit',[$row->EOTNo]);
                    }
                }

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->EOTNo.' </a>';

                return $result;

            })
            ->editColumn('EOType', function($row) {
                $result = '';

                if($row->EOType == 'LD'){
                    $result = 'Lanjutan Masa';
                }
                else if($row->EOType == 'ES'){
                    $result = 'Lanjutan Perkhidmatan';
                }

                return $result;
            })
            ->editColumn('EOTStatus', function($row) {
                $eotProcess= $this->dropdownService->eotProcess();
                if($row->EOT_EPCode != null){
                    return $eotProcess[$row->EOT_EPCode] ?? "-";
                }
                else{
                    return '-';
                }
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','EOTStatus', 'indexNo', 'action','EOTNo'])
            ->make(true);
    }


    function sendNotification($eot){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $projectNo = $eot->EOT_PTNo;
            $project = Project::where('PTNo', $projectNo)->first();

            //##NOTIF-036
            $title = "Permohonan Lanjutan Masa - $project->PTNo.";
            $desc = "Perhatian, permohonan lanjutan masa bagi projek, $project->PTNo telah dihantar.";
            $notiType = "EOT";


            //SEND NOTIFICATION TO PIC - PELAKSANA
            $tender = $project->tenderProposal->tender;
            $tenderPIC = $tender->tenderPIC_T;

            if(!empty($tenderPIC)){

                foreach($tenderPIC as $pic){

                    if($pic->TPICType == 'T'){
                        $pelaksanaType = "SO";

                    }

                    $notification = new Notification();
                    $notification->NO_RefCode = $pic->TPIC_USCode;
                    $notification->NOType = $pelaksanaType;
                    $notification->NO_NTCode = $notiType;
                    $notification->NOTitle = $title;
                    $notification->NODescription = $desc;
                    $notification->NORead = 0;
                    $notification->NOSent = 1;
                    $notification->NOActive = 1;
                    $notification->NOCB = $user->USCode;
                    $notification->NOMB = $user->USCode;
                    $notification->save();
                }

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Maklumat notifikasi berjaya dihantar.',
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat notifikasi tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function viewEOT($id){
        $eotType = $this->dropdownService->eotType();
        $meetingStatus = $this->dropdownService->boardMeetingStatus();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();
        $response_type = $this->dropdownService->response_type();
        $statusProcess = $this->dropdownService->statusProcess();
        $statusApply = $this->dropdownService->statusApply();

        $PTNo = Session::get('project');

        $milestone = ProjectMilestone::where('PM_PTNo', $PTNo)
            // ->whereHas('projectClaim', function ($query) {
            //     $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
            // })
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

        $eot = ExtensionOfTime::where('EOTNo',$id)->first();

        $eot->statusEOT = $statusProcess[$eot->EOTStatus] ?? "-";

        $project = Project::where('PTNo',$eot->EOT_PTNo)->first();

        //JENIS PROJECT
        $jenis_projek = $this->dropdownService->jenis_projek();
        $project_type = "";

        if(!empty($project->tenderProposal->tender->TD_PTCode)){
            $project_type = $jenis_projek[$project->tenderProposal->tender->TD_PTCode];
        }

        $project->projectType = $project_type;

        // TARIKH SST
        $SSTDate = "";
        if($project->tenderProposal->letterAcceptance){

            $letterAccept = $project->tenderProposal->letterAcceptance;


            $carbonDatetime = Carbon::parse($letterAccept->LAConfirmDate);
            $SSTDate = $carbonDatetime->format('d/m/Y');
        }

        $project->projectSSTDate = $SSTDate;

        // TARIKH SAK
        $carbonDatetime = Carbon::parse($project->PTSAKDate);
        $SAKDate = $carbonDatetime->format('d/m/Y');

        $project->projectSAKDate = $SAKDate;

        return view('contractor.eot.include.maklumat',
        compact('eot','eotType', 'PTNo', 'milestone', 'eotType','project',
        'unitMeasurement','header_detail','response_type' , 'statusApply')
        );
    }

    public function agreeEOT(Request $request){

        $user = Auth::user();

        try {
            DB::beginTransaction();

            $EOTNo          = $request->EOTNo;
            $disagreeStatus = $request->updateDisagree;
            $acceptStatus   = $request->updateAccept;
            $paymentType    = $request->eotPaid;

            $eot= ExtensionOfTime::where('EOTNo',$EOTNo)->first();

            if($paymentType == 0){
                if($disagreeStatus == 1){
                    $eot->EOTStatus = 'DISAGREE';
                    $eot->EOT_EPCode = 'EOTR';
                    $eot->EOTAccept = 0;
                    $eot->EOTResponseDate = Carbon::now();

                }else{
                    $eot->EOTStatus = 'ACCEPT';
                    // $eot->EOT_EPCode = 'EOTA';
                    $eot->EOT_EPCode = 'EOTA-RQ';
                    $eot->EOTAccept = 1;
                    $eot->EOTResponseDate = Carbon::now();
                    
                    #EOTRP
                    $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
                    $result = $approvalController->storeApproval($eot->EOTNo, 'EOT-RP');
                }
            }
            else{
                if($disagreeStatus == 1){
                    $eot->EOTStatus = 'DISAGREE';
                    $eot->EOT_EPCode = 'EOTR';
                    $eot->EOTAccept = 0;
                    $eot->EOTResponseDate = Carbon::now();
                }else{
                    $eot->EOTStatus = 'AGREE';
                    $eot->EOT_EPCode = 'EOTA';
                    $eot->EOTAccept = 1;
                    $eot->EOTResponseDate = Carbon::now();
                }
            }

            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');
                $fileType = 'EOT';
                $refNo = $EOTNo;

                $fileAttach = FileAttach::where('FARefNo',$refNo)
                    ->where('FAFileType',$fileType)
                    ->first();
                if ($fileAttach != null){
                    $filename   = $fileAttach->FAFileName;
                    $fileExt    = $fileAttach->FAFileExtension ;

                    $returnval = Storage::disk('fileStorage')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);
                    $fileAttach->delete();
                }

                $this->saveFile($file,$fileType,$refNo);
            }


            $eot->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.eot.index'),
                'message' => 'Lanjutan masa berjaya Dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Lanjutan masa tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function rundingSemula(Request $request){
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $EOTNo = $request->EOTNo;

            $eot = ExtensionOfTime::where('EOTNo', $EOTNo)->first();
            $eot->EOT_EPCode = 'EOTV';
            $eot->EOTMB = $user->USCode;
            $eot->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.eot.edit', [$EOTNo]),
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

}
