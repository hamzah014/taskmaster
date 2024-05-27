<?php

namespace App\Http\Controllers\Pelaksana\EOT;

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
use App\Models\ExtensionOfTimeDet;
use App\Models\ExtensionOfTimeSpec;
use App\Models\Notification;
use App\Models\VariantOrder;
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
        $yt = $this->dropdownService->yt();

        $project = Project::whereNotNull('PTPwd')
            ->with('tenderProposal.tender')
            ->orderBy('PTNo', 'desc')
            ->get()
            ->pluck('tenderProposal.tender.TDTitle', 'PTNo');

        // $tender = Tender::whereHas('tenderProposal', function($query){
        //     $query->where('TP_TPPCode','SB');
        // })
        //     ->get()->pluck('TDTitle','TDNo')->map(function ($item, $key) {
        //         $code = Tender::where('TDNo', $key)->value('TDNo');
        //         return  $code . " - " . $item;
        //     });

        $milestone = [];

        return view('pelaksana.eot.index',
            compact('project', 'milestone' , 'yt')
        );
    }

    public function createPaidService($id){ //get project id

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $project = Project::where('PTNo',$id)->first();

            $EOTNo = $this->autoNumber->generateEOTNo();

            $eot = new ExtensionOfTime();
            $eot->EOTNo = $EOTNo;
            $eot->EOTDesc = "";
            $eot->EOType = "ES";
            $eot->EOT_PTNo = $project->PTNo;
            $eot->EOTPayment = 1;
            $eot->EOTStatus = 'NEW';
            $eot->EOTCB = $user->USCode;
            $eot->EOTMB = $user->USCode;
            $eot->EOTPaid = 1;
            $eot->EOT_EPCode = 'EOTN';
            $eot->save();

            DB::commit();

			return redirect()->route('pelaksana.eot.editPaidService', [$EOTNo]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }



    }

    public function editPaidService($id){ //get eot id
        $eotType = $this->dropdownService->eotType();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();
        $response_type = $this->dropdownService->response_type();
        $statusProcess = $this->dropdownService->statusProcess();

        $milestone = [];

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

        return view('pelaksana.eot.editPaidService',
            compact('id','eot', 'milestone', 'eotType','project',
            'unitMeasurement','header_detail','response_type'
            )
        );

    }


    public function updateDocService(Request $request,$id){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $EOTNo            = $id;

            $eot_detail       = $request->detailNo;
            $eot_lampiran     = $request->eot_lampiran;
            // $vo_file         = $request->vo_file;

            //INSERT VO DETAILS
            if(count($eot_detail) > 0 || !empty($eot_detail)){

                $oldDatas = ExtensionOfTimeDet::where('EOTD_EOTNo',$EOTNo)->get();
                $count = 0;
                foreach($eot_detail as $index => $detail){

                    $count++;

                    $exists = $oldDatas->contains('EOTDNo', $detail);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $EOTDNo = $this->autoNumber->generateEOTDetNo();
                        $eotDetail = new ExtensionOfTimeDet();
                        $eotDetail->EOTDNo = $EOTDNo;
                        $eotDetail->EOTD_EOTNo = $EOTNo;
                        $eotDetail->EOTDSeq = $count;
                        $eotDetail->EOTDDesc = $eot_lampiran[$index];
                        $eotDetail->EOTDComplete = 0;
                        $eotDetail->EOTDCB = $user->USCode;
                        $eotDetail->EOTDMB = $user->USCode;
                        $eotDetail->save();

                        if ($request->hasFile('eot_file.' . $index)) {

                            $file = $request->file('eot_file')[$index];
                            $fileType = 'EOTD';
                            $refNo = $EOTDNo;
                            $this->saveFile($file,$fileType,$refNo);
                        }


                    }else{
                        //UPDATE CURRENT DATA
                        $eotDetail = ExtensionOfTimeDet::where('EOTDNo',$detail)->first();
                        $eotDetail->EOTDSeq = $count;
                        $eotDetail->EOTDDesc = $eot_lampiran[$index];
                        $eotDetail->EOTDComplete = 0;
                        $eotDetail->EOTDCB = $user->USCode;
                        $eotDetail->EOTDMB = $user->USCode;
                        $eotDetail->save();

                        if ($request->hasFile('vo_file.' . $index)) {

                            $file = $request->file('vo_file')[$index];
                            $fileType = 'VOD';
                            $refNo = $eotDetail->EOTDNo;
                            $this->saveFile($file,$fileType,$refNo);
                        }

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->EOTDNo, $eot_detail)) {

                        // DELETE
                        $oldData->delete();

                    }

                }


            }

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => route('pelaksana.eot.editPaidService',[$id]),
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Fail tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }

    }



    public function addServiceSpec(Request $request){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $EOTSpec_last = ExtensionOfTimeSpec::where('EOTS_EOTNo', $request->EOTNo)->first();

            $autoNumber = new AutoNumber();
            $EOTSNo = $autoNumber->generateEOTSpecNo();

            if(isset($request->EOTSID)){
                $EOTSIDs = $request->EOTSID;
                $EOTSDescs = $request->EOTSDesc;
                $EOTSStockInds = $request->EOTSStockInd;
                $EOTS_UMCodes = $request->EOTS_UMCode;
                $EOTSQtys = $request->EOTSQty;
                $EOTSProposedAmts = $request->EOTSProposedAmt;

            }
            else{
                $EOTSIDs = [];
            }


            $old_EOTSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $request->EOTNo)->get();

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_EOTSpec) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_EOTSpec as $oEOTSpec){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($EOTSIDs as $EOTSID){
                        if($oEOTSpec->EOTSID == $EOTSID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $oEOTSpec->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($EOTSIDs as $key => $EOTSID){

                    $new_EOTSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $request->EOTNo)->where('EOTSID', $EOTSID)->first();

                    if(!$new_EOTSpec){
                        $autoNumber = new AutoNumber();
                        $EOTSNo = $autoNumber->generateEOTSpecNo();

                        $new_EOTSpec = new ExtensionOfTimeSpec();
                        $new_EOTSpec->EOTSNo            = $EOTSNo;
                        $new_EOTSpec->EOTS_EOTNo        = $request->EOTNo;
                        $new_EOTSpec->EOTSCB             = $user->USCode;
                    }
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                    $new_EOTSpec->EOTSSeq             = $key + 1;
                    $new_EOTSpec->EOTSStockInd        = $EOTSStockInds[$key];
                    $new_EOTSpec->EOTSDesc            = $EOTSDescs[$key];

                    if($EOTSStockInds[$key] == 1){

                        $totalprice = $EOTSProposedAmts[$key] * $EOTSQtys[$key];

                        $new_EOTSpec->EOTSProposeAmt = $EOTSProposedAmts[$key];
                        $new_EOTSpec->EOTSTotalProposeAmt = $totalprice;
                        $new_EOTSpec->EOTSQty = $EOTSQtys[$key];
                        $new_EOTSpec->EOTS_UMCode = $EOTS_UMCodes[$key];
                    }

                    $new_EOTSpec->EOTSMB              = $user->USCode;
                    $new_EOTSpec->save();

                    //d($new_EOTSpec);
                    }
                }
                else{
                    if(count($EOTSIDs) > 0){
                        foreach ($EOTSIDs as $key => $EOTSID){
                            $autoNumber = new AutoNumber();
                            $EOTSNo = $autoNumber->generateEOTSpecNo();

                            $EOTSpec = new ExtensionOfTimeSpec();
                            $EOTSpec->EOTSNo            = $EOTSNo;
                            $EOTSpec->EOTS_EOTNo        = $request->EOTNo;
                            $EOTSpec->EOTSStockInd      = $EOTSStockInds[$key];
                            $EOTSpec->EOTSSeq           = $key + 1;
                            $EOTSpec->EOTSDesc          = $EOTSDescs[$key];

                            if($EOTSStockInds[$key] == 1){

                                $totalprice = $EOTSProposedAmts[$key] * $EOTSQtys[$key];

                                $EOTSpec->EOTSProposeAmt = $EOTSProposedAmts[$key];
                                $EOTSpec->EOTSTotalProposeAmt = $totalprice;
                                $EOTSpec->EOTSQty = $EOTSQtys[$key];
                                $EOTSpec->EOTS_UMCode = $EOTS_UMCodes[$key];
                            }

                            $EOTSpec->EOTSCB = $user->USCode;
                            $EOTSpec->EOTSMB = $user->USCode;
                            $EOTSpec->save();

                        }
                    }
                }


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.eot.editPaidService',[$request->EOTNo, 'flag'=>2]),
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

    public function updateServiceMilestone(Request $request){

        $messages = [
            'keterangan.required'           => 'Keterangan diperlukan.',
            'eotWorkDay.required'           => 'Tempoh lanjutan masa diperlukan.',
        ];

        $validation = [
            'keterangan'       => 'required',
            'eotWorkDay'       => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();
            $user = Auth::user();

            $eot = ExtensionOfTime::where('EOTNo',$request->EOTNo)->first();
            $eot->EOTNo = $request->EOTNo;
            $eot->EOTDesc = $request->keterangan;
            $eot->EOTWorkDay = $request->eotWorkDay;

            if($request->updateStatus == 1){

                $status = "SUBMIT";
                $eot->EOTStatus = $status;
                $eot->EOT_EPCode = 'EOTA';
                $eot->EOTMB = $user->USCode;
            }

            $eot->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.eot.editPaidService',[$request->EOTNo, 'flag'=>3]),
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

    public function view($id){
        $eotType = $this->dropdownService->eotType();

        $statusApply = $this->dropdownService->statusApply();

        $eot = ExtensionOfTime::where('EOTNO', $id)->first();

        $commentLog = $eot->commentLog;
        if($eot->commentLog){
            foreach($commentLog as $comment){
                $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');

            }
        }

        return view('pelaksana.eot.view',
        compact('eot','eotType','statusApply')
        );
    }

    public function eotDatatable(Request $request){

        $query = ExtensionOfTime::orderBy('EOTNo' , 'desc')->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('EOTNo', function($row) {

                $route = route('pelaksana.eot.view',[$row->EOTNo]);

                if($row->EOType == 'LD' && $row->EOTPaid == 1){
                    $route = route('pelaksana.eot.create.eotBerbayar',[$row->EOTNo]);
                }

                elseif($row->EOType == 'LD' && $row->EOTPaid == 0){
                    $route = route('pelaksana.eot.editNoPaidLD',[$row->EOTNo]);
                }

                elseif($row->EOType == 'ES' && $row->EOTPaid == 1){
                    $route = route('pelaksana.eot.editPaidService',[$row->EOTNo]);
                }

                elseif($row->EOType == 'ES' && $row->EOTPaid == 0){
                    $route = route('pelaksana.eot.editNoPaidLD',[$row->EOTNo]);
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
                $eotProcessDesc= $this->dropdownService->eotProcessDesc();
                if($row->EOT_EPCode != null){
                    return $eotProcessDesc[$row->EOT_EPCode] ?? "-";
                }
                else{
                    return '-';
                }
            })
            ->addColumn('projectName', function($row) {
                $result= $row->project->tenderProposal->tender->TDTitle;

                return $result;
            })
            ->addColumn('action', function($row) {
                $result = '';

                // $route = route('contractor.claim.create', [$row->PCNo]);

                // if($row->PC_PCPCode == 'DF' || $row->PC_PCPCode == 'RV'){
                //     $result = '<a class="btn btn-light-primary" href="'.$route.'"><i class="material-icons">edit</i></a>';

                // }

                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['EOTStatus', 'indexNo', 'action','EOTNo', 'projectName'])
            ->make(true);
    }

    public function update(Request $request){

        $messages = [
            'statusApply.required'              => 'Status permohonan diperlukan.',
        ];

        $validation = [
            'statusApply' => $request->byPelaksana == 0 ? 'required' : '',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();
            $EOTNo = $request->eotNo;

            $eot = ExtensionOfTime::where('EOTNo',$EOTNo)->first();
            $eot->EOTDesc = $request->keterangan;
            // $eot->EOT_PMNo = $request->idMilestone;
            $eot->EOTWorkDay = $request->eotWorkDay;

            $status = '';

            if($request->byPelaksana == 0){
                $status = $request->statusApply;

            }else{
                $status = 'ACCEPT';
            }

            $eot->EOTStatus = $status;

            if($status == 'ACCEPT'){
                $this->sendNotification($EOTNo);
                $epcode = 'RQA-RQ';

                $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());

                $approvalController->storeApproval($EOTNo, 'EOT-RQA');
            }
            else{
                $epcode = 'RQR';
            }

            $eot->EOT_EPCode = $epcode;
            $eot->save();
            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.eot.index'),
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

    function sendNotification($eotNo){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $eot = ExtensionOfTime::where('EOTNo',$eotNo)->first();
            $projectNo = $eot->EOT_PTNo;
            $project = Project::where('PTNo', $projectNo)->first();

            //##NOTIF-036
            $title = "Permohonan Lanjutan Masa - $eot->EOTNo.";
            $desc = "Perhatian, permohonan lanjutan masa ($eot->EOTNo) bagi projek $project->PTNo telah diterima.";
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

    function getMilestone($projectNo){

        $milestone = ProjectMilestone::where('PM_PTNo', $projectNo)->get()->pluck('PMDesc', 'PMNo');

        return response()->json($milestone);
    }

    public function create($id){
        $eotType = $this->dropdownService->eotType();

        $milestone = ProjectMilestone::where('PMNo', $id)
            // ->whereHas('projectClaim', function ($query) {
            //     $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
            // })
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

        return view('pelaksana.eot.create',
            compact('id', 'milestone', 'eotType')
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

            DB::beginTransaction();

            $user = Auth::user();
            $autoNumber = new AutoNumber();
            $EOTNo = $autoNumber->generateEOTNo();

            $milestone = ProjectMilestone::where('PMNo', $request->idMilestone)->first();

            $eot = new ExtensionOfTime();
            $eot->EOTNo = $EOTNo;
            $eot->EOTDesc = $request->keterangan;
            $eot->EOType = $request->eotType;
            $eot->EOT_PTNo = $milestone->PM_PTNo;
            $eot->EOT_PMNo = $request->idMilestone;
            $eot->EOTWorkDay = $request->eotWorkDay;

            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');
                $fileType = 'EOT';
                $refNo = $EOTNo;
                $this->saveFile($file,$fileType,$refNo);
            }

            $status = "ACCEPT";
            $eot->EOTStatus = $status;
            $eot->EOT_EPCode = 'EOTA';
            $eot->EOTByPelaksana = 1;
            $eot->EOTCB = $user->USCode;
            $eot->EOTMB = $user->USCode;
            $eot->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.eot.index'),
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

    public function createNew($id){
        $eotType = $this->dropdownService->eotType();

        // $milestone = ProjectMilestone::where('PMNo', $id)
        //     // ->whereHas('projectClaim', function ($query) {
        //     //     $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
        //     // })
        //     ->get(['PMDesc', 'PMNo'])
        //     ->pluck('PMDesc', 'PMNo');

        $milestone = [];

        return view('pelaksana.eot.createNew',
            compact('id', 'milestone', 'eotType')
        );
    }

    public function storeNew(Request $request){

        $messages = [
            // 'idMilestone.required'          => 'Milestone diperlukan.',
            'eotType.required'              => 'Jenis lanjutan masa diperlukan.',
            'keterangan.required'           => 'Keterangan diperlukan.',
            'eotWorkDay.required'           => 'Tempoh lanjutan masa diperlukan.',
            'dokumen.required'              => 'Lampiran Dokumen diperlukan.',
            'dokumen.file'                  => 'Lampiran Dokumen harus berupa file.',
        ];

        $validation = [
            // 'idMilestone'      => 'required',
            'eotType'          => 'required',
            'keterangan'       => 'required',
            'eotWorkDay'       => 'required',
            'dokumen'          => 'required|file',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $autoNumber = new AutoNumber();
            $EOTNo = $autoNumber->generateEOTNo();

            $milestone = ProjectMilestone::where('PMNo', $request->idMilestone)->first();

            $eot = new ExtensionOfTime();
            $eot->EOTNo = $EOTNo;
            $eot->EOTDesc = $request->keterangan;
            $eot->EOType = $request->eotType;
            $eot->EOT_PTNo = $request->idProject;
            $eot->EOT_PMNo = $request->idMilestone;
            $eot->EOTWorkDay = $request->eotWorkDay;

            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');
                $fileType = 'EOT';
                $refNo = $EOTNo;
                $this->saveFile($file,$fileType,$refNo);
            }

            $status = "SUBMIT";
            $eot->EOTStatus = $status;
            $eot->EOT_EPCode = 'EOTA';
            $eot->EOTByPelaksana = 1;
            $eot->EOTCB = $user->USCode;
            $eot->EOTMB = $user->USCode;
            $eot->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.eot.index'),
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

    public function createEotBerbayar($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $getMilestone = ProjectMilestone::where('PMNo' , $id)->first();
            $projectNo = $getMilestone->PM_PTNo;

            $project = Project::where('PTNo',$projectNo)->first();

            $autoNumber = new AutoNumber();
            $EOTNo = $autoNumber->generateEOTNo();

            $eotStatus = 'NEW';

            $newEOT = new ExtensionOfTime();
            $newEOT->EOTNo = $EOTNo;
            $newEOT->EOT_PTNo = $project->PTNo;
            $newEOT->EOT_PMNo = $id;
            $newEOT->EOTStatus = $eotStatus;
            $newEOT->EOTDesc = '';
            $newEOT->EOType = 'LD';
            $newEOT->EOTPayment = '1';
            $newEOT->EOTPaid = '1';
            $newEOT->EOTCB = $user->USCode;
            $newEOT->EOTMB = $user->USCode;
            $newEOT->EOT_EPCode = 'EOTN';
            $newEOT->save();

            DB::commit();

            return redirect()->route('pelaksana.eot.create.eotBerbayar', [$EOTNo]);



        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Lanjutan masa gagal di buat!'.$e->getMessage()
            ], 400);
        }

    }

    public function eotBerbayar($id){

        $eotType = $this->dropdownService->eotType();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();
        $statusProcess = $this->dropdownService->statusProcess();

        $eot = ExtensionOfTime::where('EOTNo',$id)->first();

        $EOTdate = isset($eot->EOTStartDate) ? Carbon::parse($eot->EOTStartDate)->format('Y-m-d') : '-';

        $eot->statusEOT = $statusProcess[$eot->EOTStatus] ?? '-';

        $milestone = ProjectMilestone::where('PMNo', $eot->EOT_PMNo)
            // ->whereHas('projectClaim', function ($query) {
            //     $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
            // })
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

        $projectNo = $eot->EOT_PTNo;
        $project = Project::where('PTNo', $projectNo)->first();

        //JENIS PROJEK
        $jenis_projek = $this->dropdownService->jenis_projek();
        $project_type = "";

        if(!empty($project->tenderProposal->tender->TD_PTCode)){
            $project_type = $jenis_projek[$project->tenderProposal->tender->TD_PTCode];
        }

        $project->projectType = $project_type;

        //TARIKH SST
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
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();
        $response_type = $this->dropdownService->response_type();


        return view('pelaksana.eot.eotBerbayar',
            compact('id', 'eot' , 'milestone', 'eotType' , 'project' , 'SAKDate' , 'SSTDate','project','unitMeasurement','header_detail','response_type')
        );

    }

    public function addSpec(Request $request){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $EOTSpec_last = ExtensionOfTimeSpec::where('EOTS_EOTNo', $request->EOTNo)->first();

            $autoNumber = new AutoNumber();
            $EOTSNo = $autoNumber->generateEOTSpecNo();

            // if($request->key == 0){
            //     if($EOTSpec_last){
            //         $seq = $EOTSpec_last->EOTSSeq+1;
            //     }
            //     else{
            //         $seq = 1;
            //     }
            // }
            // else{
            //     $seq = $request->key;

            //     $EOTSpec_after = ExtensionOfTimeSpec::where('EOTSSeq', '>', $seq)
            //         ->where('EOTS_EOTNo', $request->EOTNo)
            //         ->orderBy('EOTSSeq', 'ASC')
            //         ->get();

            //     foreach ($EOTSpec_after as $spec){
            //         $spec->EOTSSeq = $spec->EOTSSeq + 1;
            //         $spec->save();
            //     }
            //     $seq = $request->key+1;
            // }

            if(isset($request->EOTSID)){
                $EOTSIDs = $request->EOTSID;
                $EOTSDescs = $request->EOTSDesc;
                $EOTSStockInds = $request->EOTSStockInd;
                $EOTS_UMCodes = $request->EOTS_UMCode;
                $EOTSQtys = $request->EOTSQty;
                $EOTSProposedAmts = $request->EOTSProposedAmt;

            }
            else{
                $EOTSIDs = [];
            }


            $old_EOTSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $request->EOTNo)->get();

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_EOTSpec) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_EOTSpec as $oEOTSpec){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($EOTSIDs as $EOTSID){
                        if($oEOTSpec->EOTSID == $EOTSID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $oEOTSpec->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($EOTSIDs as $key => $EOTSID){

                    $new_EOTSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $request->EOTNo)->where('EOTSID', $EOTSID)->first();

                    if(!$new_EOTSpec){
                        $autoNumber = new AutoNumber();
                        $EOTSNo = $autoNumber->generateEOTSpecNo();

                        $new_EOTSpec = new ExtensionOfTimeSpec();
                        $new_EOTSpec->EOTSNo            = $EOTSNo;
                        $new_EOTSpec->EOTS_EOTNo        = $request->EOTNo;
                        $new_EOTSpec->EOTSCB             = $user->USCode;
                    }
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                    $new_EOTSpec->EOTSSeq             = $key + 1;
                    $new_EOTSpec->EOTSStockInd        = $EOTSStockInds[$key];
                    $new_EOTSpec->EOTSDesc            = $EOTSDescs[$key];

                    if($EOTSStockInds[$key] == 1){

                        $totalprice = $EOTSProposedAmts[$key] * $EOTSQtys[$key];

                        $new_EOTSpec->EOTSProposeAmt = $EOTSProposedAmts[$key];
                        $new_EOTSpec->EOTSTotalProposeAmt = $totalprice;
                        $new_EOTSpec->EOTSQty = $EOTSQtys[$key];
                        $new_EOTSpec->EOTS_UMCode = $EOTS_UMCodes[$key];
                    }

                    $new_EOTSpec->EOTSMB              = $user->USCode;
                    $new_EOTSpec->save();

                    //d($new_EOTSpec);
                    }
                }
                else{
                    if(count($EOTSIDs) > 0){
                        foreach ($EOTSIDs as $key => $EOTSID){
                            $autoNumber = new AutoNumber();
                            $EOTSNo = $autoNumber->generateEOTSpecNo();

                            $EOTSpec = new ExtensionOfTimeSpec();
                            $EOTSpec->EOTSNo            = $EOTSNo;
                            $EOTSpec->EOTS_EOTNo        = $request->EOTNo;
                            $EOTSpec->EOTSStockInd      = $EOTSStockInds[$key];
                            $EOTSpec->EOTSSeq           = $key + 1;
                            $EOTSpec->EOTSDesc          = $EOTSDescs[$key];

                            if($EOTSStockInds[$key] == 1){

                                $totalprice = $EOTSProposedAmts[$key] * $EOTSQtys[$key];

                                $EOTSpec->EOTSProposeAmt = $EOTSProposedAmts[$key];
                                $EOTSpec->EOTSTotalProposeAmt = $totalprice;
                                $EOTSpec->EOTSQty = $EOTSQtys[$key];
                                $EOTSpec->EOTS_UMCode = $EOTS_UMCodes[$key];
                            }

                            $EOTSpec->EOTSCB = $user->USCode;
                            $EOTSpec->EOTSMB = $user->USCode;
                            $EOTSpec->save();

                        }
                    }
                }




            // if(count($EOTSIDs) > 0){
            //     foreach ($EOTSIDs as $key => $EOTDID){
            //         $autoNumber = new AutoNumber();
            //         $EOTSNo = $autoNumber->generateEOTSpecNo();

            //         $EOTSpec = new ExtensionOfTimeSpec();
            //         $EOTSpec->EOTSNo            = $EOTSNo;
            //         $EOTSpec->EOTS_EOTNo        = $request->EOTNo;
            //         $EOTSpec->EOTSStockInd      = $EOTSStockInds[$key];
            //         $EOTSpec->EOTSSeq           = $seq + 1;
            //         $EOTSpec->EOTSDesc          = $EOTSDescs[$key];

            //         if($EOTSStockInds[$key] == 1){

            //             $totalprice = $EOTSProposedAmts[$key] * $EOTSQtys[$key];

            //             $EOTSpec->EOTSProposeAmt = $EOTSProposedAmts[$key];
            //             $EOTSpec->EOTSTotalProposeAmt = $totalprice;
            //             $EOTSpec->EOTSQty = $EOTSQtys[$key];
            //             $EOTSpec->EOTS_UMCode = $EOTS_UMCodes[$key];
            //         }

            //         $EOTSpec->EOTSCB = $user->USCode;
            //         $EOTSpec->EOTSMB = $user->USCode;
            //         $EOTSpec->save();

            //         dd($EOTSpec);
            //     }
            // }


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.eot.create.eotBerbayar', [$request->EOTNo, 'flag' =>'2']),
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

    // public function updateSpec(Request $request){
    //     try {
    //         DB::beginTransaction();

    //         $user = Auth::user();

    //         $autoNumber = new AutoNumber();

    //         $EOTNo            = $request->EOTNo;

    //         $specNo          = $request->specNo;
    //         $spec_header     = $request->spec_header;
    //         $spec_name       = $request->spec_name;
    //         $spec_unit       = $request->spec_unit;
    //         $spec_kuantiti   = $request->spec_kuantiti;
    //         $spec_priceunit  = $request->spec_priceunit;
    //         $spec_total      = $request->spec_total;

    //         $existingSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $EOTNo)->pluck('EOTSNo')->toArray();

    //         $recordsToDelete = array_diff($existingSpec, $specNo);

    //         if (!empty($recordsToDelete)) {
    //             ExtensionOfTimeSpec::whereIn('EOTSNo', $recordsToDelete)->delete();
    //         }

    //         //INSERT EOT SPEC
    //         if(count($specNo) > 0 || !empty($specNo)){
    //             $count = 0;
    //             foreach($specNo as $index => $spec){

    //                 $EOTSpec = ExtensionOfTimeSpec::where('EOTSNo',$spec)->first();
    //                 $EOTSpec->EOTSDesc = $spec_name[$index];
    //                 $EOTSpec->EOTSQty = $spec_kuantiti[$index];
    //                 $EOTSpec->EOTS_UMCode = $spec_unit[$index];
    //                 $EOTSpec->EOTSProposeAmt = $spec_priceunit[$index];
    //                 $EOTSpec->EOTSTotalProposeAmt = $spec_total[$index];

    //                 if($spec_header[$index] == 1){
    //                     $EOTSpec->EOTSQty = $spec_kuantiti[$index];
    //                     $EOTSpec->EOTS_UMCode = $spec_unit[$index];
    //                 }

    //                 $EOTSpec->EOTSCB = $user->USCode;
    //                 $EOTSpec->EOTSMB = $user->USCode;
    //                 $EOTSpec->save();
    //                 $EOTSpec->EOTSCB = $user->USCode;
    //                 $EOTSpec->EOTSMB = $user->USCode;
    //                 $EOTSpec->save();

    //             }
    //         }

    //         $route = route('pelaksana.eot.create.eotBerbayar', [$request->EOTNo, 'flag' =>'2']);

    //         DB::commit();

	// 		return response()->json([
	// 			'success' => '1',
    //             'redirect' => $route,
	// 			'message' => 'Maklumat berjaya dikemaskini.'
	// 		]);


    //     } catch (\Throwable $e) {
    //         DB::rollback();

    //         Log::info('ERROR', ['$e' => $e]);

    //         return response()->json([
    //             'error' => '1',
    //             'message' => 'Fail tidak berjaya dimuat naik!'.$e->getMessage()
    //         ], 400);
    //     }

    // }

    public function addMaklumatEOT(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $autoNumber = new AutoNumber();

            $EOTNo            = $request->eotno;

            $eot_detail       = $request->detailNo;
            $eot_lampiran     = $request->eot_lampiran;

            if( isset($eot_detail) && count($eot_detail) > 0 || !empty($eot_detail)){

                $oldDatas = ExtensionOfTimeDet::where('EOTD_EOTNo', $request->eotno)->get();
                $count = 0;
                foreach($eot_detail as $index => $detail){


                    $exists = $oldDatas->contains('EOTDNo', $detail);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $EOTDNo = $autoNumber->generateEOTDetNo();
                        $EOTDet = new ExtensionOfTimeDet();
                        $EOTDet->EOTDNo = $EOTDNo;
                        $EOTDet->EOTD_EOTNo = $request->eotno;
                        $EOTDet->EOTDSeq = $count;
                        $EOTDet->EOTDDesc = $eot_lampiran[$index];
                        $EOTDet->EOTDComplete = 0;
                        $EOTDet->EOTDCB = $user->USCode;
                        $EOTDet->EOTDMB = $user->USCode;

                        $EOTDet->save();

                        $file = $request->file('eot_file')[$index];
                        $fileType = 'EOTD';
                        $refNo = $EOTDNo;
                        $this->saveFile($file,$fileType,$refNo);

                    }else{
                        //UPDATE CURRENT DATA
                        $EOTDet = ExtensionOfTimeDet::where('EOTDNo',$detail)->first();
                        $EOTDet->EOTDDesc = $eot_lampiran[$index];
                        $EOTDet->EOTDSeq = $count;
                        $EOTDet->EOTDComplete = 0;
                        $EOTDet->EOTDCB = $user->USCode;
                        $EOTDet->EOTDMB = $user->USCode;
                        $EOTDet->save();

                        if ($request->hasFile('eot_file.' . $index)) {

                            $file = $request->file('eot_file')[$index];
                            $fileType = 'EOTD';
                            $refNo = $EOTDet->EOTDNo;
                            $this->saveFile($file,$fileType,$refNo);
                        }

                    }

                    $count++;

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->EOTDNo, $eot_detail)) {

                        // DELETE
                        $oldData->delete();

                    }

                }

            }


            $route = route('pelaksana.eot.create.eotBerbayar', [$request->eotno, 'flag' =>'1']);

            //$totalVOSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $request->EOTNo)->sum('VOSTotalProposeAmt');


            // $total = array_sum($totalVOSpec);

            // if($request->updateStatus == 1){
            //     $EOT = ExtensionOfTime::where('EOTNo',$request->EOTNo)->first();
            //     $EOT->EOTStatus = 'SUBMIT';
            //     $EOT->EOTMB = $user->USCode;
            //     $EOT->save();

            //     $route = route('pelaksana.vo.index');
            // }

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => $route,
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Fail tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }

    }

    public function addMilestone(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $eot = ExtensionOfTime::where('EOTNo',$request->EOTNo)->first();

            $eot->EOTDesc = $request->keterangan;
            $eot->EOTWorkDay = $request->eotWorkDay;

            // if ($request->hasFile('dokumen')) {

            //     $file = $request->file('dokumen');
            //     $fileType = 'EOT';
            //     $refNo = $request->EOTNo;
            //     $this->saveFile($file,$fileType,$refNo);
            // }

            if($request->updateStatusEOTMS == 1){
                $status = "SUBMIT";
                $eot->EOTStatus = $status;
                $eot->EOTByPelaksana = 1;
                $eot->EOT_EPCode = 'EOTA';
                $eot->EOTMB = $user->USCode;

                $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());

                $approvalController->storeApproval($request->EOTNo, 'EOT-SM');
            }

            $eot->save();

            if($request->updateStatusEOTMS == 1){
                $route = route('pelaksana.eot.index');
            }else{
                $route = route('pelaksana.eot.create.eotBerbayar', [$request->EOTNo, 'flag' =>'3']);
            }

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => $route,
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Fail tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }

    }

    public function editNoPaidLD($id){
        $eotType = $this->dropdownService->eotType();

        $eot = ExtensionOfTime::where('EOTNo', $id)->first();

        $milestone = ProjectMilestone::where('PMNo', $eot->EOT_PMNo)
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

        return view('pelaksana.eot.edit',
            compact('id', 'eot','milestone', 'eotType')
        );
    }

    public function updateNoPaidLD(Request $request){

        $messages = [
            'eotType.required'              => 'Jenis lanjutan masa diperlukan.',
            'keterangan.required'           => 'Keterangan diperlukan.',
            'eotWorkDay.required'           => 'Tempoh lanjutan masa diperlukan.',
            'dokumen.file'                  => 'Lampiran Dokumen harus berupa file.',
        ];

        $validation = [
            'eotType'          => 'required',
            'keterangan'       => 'required',
            'eotWorkDay'       => 'required',
            'dokumen'          => 'file',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $eot = ExtensionOfTime::where('EOTNo', $request->EOTNo)->first();
            $eot->EOTDesc = $request->keterangan;
            $eot->EOTWorkDay = $request->eotWorkDay;

            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');
                $fileType = 'EOT';
                $refNo = $request->EOTNo;
                $this->saveFile($file,$fileType,$refNo);
            }

            $status = "ACCEPT";
            $eot->EOTStatus = $status;
            $eot->EOT_EPCode = 'EOTA';
            $eot->EOTMB = $user->USCode;
            $eot->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.eot.index'),
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

    public function cancelEOT(Request $request){
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $EOTNo = $request->EOTNo;

            $eot = ExtensionOfTime::where('EOTNo', $EOTNo)->first();
            $eot->EOT_EPCode = 'EOTC';
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
