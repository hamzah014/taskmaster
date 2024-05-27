<?php

namespace App\Http\Controllers\Pelaksana\Meeting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralNotificationController;
use App\Models\ClaimMeetingAttendanceList;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Mail;
use App\Models\EmailLog;


use App\Http\Requests;
use App\Models\AutoNumber;
use App\Models\ClaimMeeting;
use App\Models\ClaimMeetingDet;
use App\Models\ClaimMeetingEmail;
use App\Models\Role;
use App\Models\Customer;
use App\Models\FileAttach;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectMilestone;
use App\Models\Tender;
use App\Models\TenderProposal;
use App\Services\DropdownService;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Session;
use Yajra\DataTables\DataTables;

class MeetingController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        return view('pelaksana.meeting.index');
    }

    public function create(Request $request){

        $meetingLocation = $this->dropdownService->meetingLocation();

        $tuntutan = ProjectClaim::where('PC_PCPCode','AP')->get()->pluck('PC_PMNo','PCNo')
                                ->map(function ($item, $key) {
                                    $projectClaim = ProjectClaim::where('PCNo', $key)->first();
                                    return  $item.' / '.$projectClaim->PCNo.' / RM' . number_format($projectClaim->PCTotalAmt ?? 0,2, '.', '') ;
                                });

        $claimNo = $request->input('claimNo');

        $claimTitle = "";

        if ($claimNo) {
            $claimTitle = "Mesyuarat Tuntutan " . $claimNo;
        }else{
            $claimTitle = $this->createMeetingTitle('CM');
        }

        return view('pelaksana.meeting.create',
        compact('tuntutan' , 'claimTitle','meetingLocation')
        );
    }

    public function store(Request $request){

        $messages = [
            'meeting_title.required'   => 'Tajuk Mesyuarat diperlukan.',
            'meeting_date.required'   => 'Tarikh Mesyuarat diperlukan.',
            'meeting_time.required'   => 'Masa Mesyuarat diperlukan.',
            'meeting_location.required'   => 'Lokasi Mesyuarat diperlukan.',
            'tuntutan.required'       => 'Sila pilih sekurang-kurangnya satu tuntutan.',
        ];

        $validation = [
            'meeting_title' => 'required|string',
            'meeting_date'  => 'required',
            'meeting_time'  => 'required',
            'meeting_location'  => 'required',
            'tuntutan'      => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $autoNumber = new AutoNumber();
            $CMNo = $autoNumber->generateClaimMeetingNo();

            $meeting_title  = $request->meeting_title;
            $meeting_date   = $request->meeting_date;
            $meeting_time   = $request->meeting_time;
            $meeting_location   = $request->meeting_location;
            $tuntutan       = $request->tuntutan;

            $claimMeeting = new ClaimMeeting();

            $claimMeeting->CMNo     = $CMNo;
            $claimMeeting->CMTitle  = $meeting_title;
            $claimMeeting->CMDate   = $meeting_date;
            $claimMeeting->CMTime   = $meeting_time;
            $claimMeeting->CM_LCCode   = $meeting_location;
            $claimMeeting->CMCB     = $user->USCode;
            $claimMeeting->CMMB     = $user->USCode;
            $claimMeeting->save();

            $projectArray = array();

            if(!empty($tuntutan)){

                foreach($tuntutan as $tuntut){

                    $PC_PCPCode = "BM"; // set the project claim process to Board Meeting
                    $TMSCode = "I"; //default value; get from MSTenderMeetingStatus

                    $projectClaim = ProjectClaim::where('PCNo',$tuntut)->first();
                    $PCNo = $projectClaim->PCNo;

                    $project = $projectClaim->projectMilestone->project;
                    array_push($projectArray, $project);

                    $projectClaim->PC_PCPCode = $PC_PCPCode;
                    $projectClaim->save();

                    $claimMeetingDet = new ClaimMeetingDet();
                    $claimMeetingDet->CMD_CMNo       = $CMNo;
                    $claimMeetingDet->CMD_PCNo       = $PCNo;
                    $claimMeetingDet->CMD_TMSCode    = $TMSCode;
                    // $claimMeetingDet->CMD_CRSCode    = $CRSCode;
                    $claimMeetingDet->CMDCB         = $user->USCode;
                    $claimMeetingDet->CMDMB         = $user->USCode;
                    $claimMeetingDet->save();

                    $project = $projectClaim->project;

                    $tender = $project->tenderProposal->tender;

                    if(!empty($tender->tenderPIC)){

                        foreach($tender->tenderPIC as $tenderPIC){

                            if(($tenderPIC->userPIC)){
                                $USEmail = $tenderPIC->userPIC->USEmail;
                                $USCode = $tenderPIC->userPIC->USCode;

                                $meetingEmail = new ClaimMeetingEmail();
                                $meetingEmail->CME_CMNo         = $CMNo;
                                $meetingEmail->CMEEmailAddr    = $USEmail;
                                $meetingEmail->CME_USCode    = $USCode;
                                $meetingEmail->save();

                            }

                        }

                    }

                }

            }

            $this->sendNotification($claimMeeting,$projectArray,'N');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.meeting.edit',[$CMNo]),
                'message' => 'Maklumat mesyuarat berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }
    }


    public function edit($id){

        $tuntutan = ProjectClaim::whereIn('PC_PCPCode',['AP','BM'])->get()->pluck('PC_PMNo','PCNo')
                                ->map(function ($item, $key) {
                                    $projectClaim = ProjectClaim::where('PCNo', $key)->first();
                                    return  $item.' / '.$projectClaim->PCNo.' / RM' . number_format($projectClaim->PCTotalAmt ?? 0,2, '.', '') ;
                                });
      //  $tuntutan = $this->dropdownService->projectClaimSMRV();
        $tuntutanBM = $this->dropdownService->projectClaim();
        $tmscode = $this->dropdownService->meetingStatus();
        $crscode = $this->dropdownService->claimResultStatus();
        $departmentAll = $this->dropdownService->departmentAll();
        $meetingLocation = $this->dropdownService->meetingLocation();

        $CMNo = $id;

        $claimMeeting = ClaimMeeting::where('CMNo',$id)->first();
        $claimMeeting['claimDate'] = Carbon::parse($claimMeeting->CMDate)->format('Y-m-d') ?? "";
        $claimMeeting['claimTime'] = Carbon::parse($claimMeeting->CMTime)->format('H:i') ?? "";

        $arrayTuntutan = [];

        foreach($claimMeeting->meetingDetail as $detail){

            array_push($arrayTuntutan, $detail->CMD_PCNo);

        }

        $claims = array();
        $count = 0;

        foreach($arrayTuntutan as $claim){

            $claim = ProjectClaim::where('PCNo',$claim)
                    ->first();
            $milest = $claim->PC_PMNo;
            $PCNo = $claim->PCNo;

            $milestone = ProjectMilestone::where('PMNo',$milest)->first();
            $projno = $milestone->PM_PTNo;

            $project = Project::where('PTNO',$projno)->first();
            $propno = $project->PT_TPNo;

            $proposal = TenderProposal::where('TPNo',$propno)->first();

            $claims[$count] = $claim;
            $claims[$count]['milestone'] = $milestone;
            $claims[$count]['project'] = $project;
            $claims[$count]['proposal'] = $proposal;

            $meetingDetail = ClaimMeetingDet::where('CMD_CMNo',$CMNo)
                            ->where('CMD_PCNo',$PCNo)
                            ->get();

            $claims[$count]['meetingDetail'] = $meetingDetail;

            $count++;

        }

        $claimMeetingAttendanceLists = ClaimMeetingAttendanceList::where('CMAL_CMNo', $id)->get();

        $fileAttachDownloadMN = FileAttach::where('FAFileType','MT-MN')->first();

        return view('pelaksana.meeting.edit',
            compact('tuntutan','tmscode','crscode','claimMeeting','arrayTuntutan','claims','tuntutanBM','fileAttachDownloadMN',
                'departmentAll', 'claimMeetingAttendanceLists','meetingLocation')
        );
    }

    public function update(Request $request){

        $messages = [
            'meeting_title.required'   => 'Tajuk Mesyuarat diperlukan.',
            'meeting_date.required'   => 'Tarikh Mesyuarat diperlukan.',
            'meeting_time.required'   => 'Masa Mesyuarat diperlukan.',
            'meeting_location.required'     => 'Lokasi Mesyuarat diperlukan.',
            'tuntutan.required'       => 'Sila pilih sekurang-kurangnya satu tuntutan.',
        ];

        $validation = [
            'meeting_title' => 'required|string',
            'meeting_date'  => 'required',
            'meeting_time'  => 'required',
            'meeting_location'  => 'required',
            'tuntutan'      => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $updateConfirm = $request->updateConfirm;

            $CMNo = $request->CMNo;

            $meeting_title  = $request->meeting_title;
            $meeting_date   = $request->meeting_date;
            $meeting_time   = $request->meeting_time;
            $meeting_location   = $request->meeting_location;
            $tuntutan       = $request->tuntutan;
            $meetingStatus  = $request->meetingStatus;
            $crscode        = $request->crscode;

            $claimMeeting = ClaimMeeting::where('CMNo',$CMNo)->first();

            if($updateConfirm == 1){

                if(!$claimMeeting->fileAttach){

                    if (!$request->hasFile('meetingMinit')) {

                        return response()->json([
                            'error' => 1,
                            'redirect' => route('pelaksana.meeting.edit', [$CMNo]),
                            'message' => 'Sila muat-naik minit mesyuarat terlebih dahulu sebelum menghantar maklumat mesyuarat.'
                        ],400);

                    }

                }

                if(in_array('I',$request->meetingStatus)){

                    return response()->json([
                        'error' => 1,
                        'redirect' => route('pelaksana.meeting.edit', [$CMNo]),
                        'message' => 'Sila kemaskini status mesyuarat yang masih "Belum Selesai".'
                    ],400);

                }

            }


            if ($request->hasFile('meetingMinit')) {

                $file = $request->file('meetingMinit');
                $fileType = 'CM-MM';
                $refNo = $CMNo;
                $this->saveFile($file,$fileType,$refNo);
            }

            $claimMeeting->CMTitle  = $meeting_title;
            $claimMeeting->CMDate   = $meeting_date;
            $claimMeeting->CMTime   = $meeting_time;
            $claimMeeting->CM_LCCode   = $meeting_location;
            $claimMeeting->CMMB     = $user->USCode;
            $claimMeeting->save();

            //CODEZ : #MULTI-LOOP-CRUD
            if(!empty($tuntutan)){

                $oldDatas = ClaimMeetingDet::where('CMD_CMNo',$CMNo)->get();

                $count = 0;
                foreach($tuntutan as $tuntut){

                    $PC_PCPCode = "BM"; // set the project claim process to Board Meeting

                    $projectClaim = ProjectClaim::where('PCNo',$tuntut)->first();
                    $projectClaim->PC_PCPCode = $PC_PCPCode;
                    $projectClaim->save();

                    $TMSCode = $meetingStatus[$count];
                    $CRSCode = $crscode[$count];


                    $exists = $oldDatas->contains('CMD_PCNo', $tuntut);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $claimMeetingDet = new ClaimMeetingDet();
                        $claimMeetingDet->CMD_CMNo       = $CMNo;
                        $claimMeetingDet->CMD_PCNo       = $tuntut;
                        $claimMeetingDet->CMD_TMSCode    = $TMSCode;
                        $claimMeetingDet->CMD_CRSCode    = $CRSCode;
                        $claimMeetingDet->CMDCB         = $user->USCode;
                        $claimMeetingDet->CMDMB         = $user->USCode;
                        $claimMeetingDet->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $claimMeetingDet = ClaimMeetingDet::where('CMD_CMNo',$CMNo)
                                            ->where('CMD_PCNo',$tuntut)
                                            ->first();

                        $claimMeetingDet->CMD_TMSCode    = $TMSCode;
                        $claimMeetingDet->CMD_CRSCode    = $CRSCode;
                        $claimMeetingDet->CMDMB         = $user->USCode;
                        $claimMeetingDet->save();

                    }

                    $count++;

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->CMD_PCNo, $tuntutan)) {

                        // DELETE
                        $PCNo = $oldData->CMD_PCNo;

                        //UPDATE STATUS
                        $updateStatus = "SM"; // set the project claim process to REVIEW

                        $projectClaim = ProjectClaim::where('PCNo', $PCNo)->first();
                        $projectClaim->PC_PCPCode = $updateStatus;
                        $projectClaim->save();

                        $oldData->delete();

                    }

                }

            }

            $old_claimMeetingAttendanceLists = ClaimMeetingAttendanceList::where('CMAL_CMNo', $CMNo)->get();
            $CMALIDs = $request->CMALID;
            $CMALNames = $request->CMALName;
            $CMALPositions = $request->CMALPosition;
            $CMAL_DPTCodes = $request->CMAL_DPTCode;

            if(isset($CMALIDs)){
                // Check if every element in the arrays has a CMAL_DPTCodes
                if ($this->areAllValuesSet($CMALNames) && $this->areAllValuesSet($CMALPositions) && $this->areAllValuesSet($CMAL_DPTCodes)) {

                }else{
                    DB::rollback();

                    return response()->json([
                        'error' => '1',
                        'message' => 'Sila lengkapkan semua maklumat di dalam kehadiran.'
                    ], 400);

                }
            }else{
                $CMALIDs = [];
            }

//ARRAY UPDATE MULTIPLE ROW\

            if(count($old_claimMeetingAttendanceLists) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_claimMeetingAttendanceLists as $oclaimMeetingAttendanceLists){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($CMALIDs as $CMALID){
                        if($oclaimMeetingAttendanceLists->CMALID == $CMALID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $oclaimMeetingAttendanceLists->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($CMALIDs as $key => $CMALID){

                    $new_claimMeetingAttendanceList = ClaimMeetingAttendanceList::where('CMAL_CMNo', $CMNo)
                        ->where('CMALID', $CMALID)->first();
                    if(!$new_claimMeetingAttendanceList){
                        $new_claimMeetingAttendanceList = new ClaimMeetingAttendanceList();
                        $new_claimMeetingAttendanceList->CMAL_CMNo = $CMNo;
                        $new_claimMeetingAttendanceList->CMALCB = $user->USCode;
                    }
                    $new_claimMeetingAttendanceList->CMALName = $CMALNames[$key];;
                    $new_claimMeetingAttendanceList->CMALPosition = $CMALPositions[$key];;
                    $new_claimMeetingAttendanceList->CMAL_DPTCode = $CMAL_DPTCodes[$key];
                    $new_claimMeetingAttendanceList->CMALMB = $user->USCode;
                    $new_claimMeetingAttendanceList->save();
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                }
            }
            else{

                if(isset($CMALIDs)){
                    foreach($CMALIDs as $key2 => $CMALID){
                        $new_claimMeetingAttendanceList = new ClaimMeetingAttendanceList();
                        $new_claimMeetingAttendanceList->CMAL_CMNo = $CMNo;
                        $new_claimMeetingAttendanceList->CMALName = $CMALNames[$key2];
                        $new_claimMeetingAttendanceList->CMALPosition = $CMALPositions[$key2];
                        $new_claimMeetingAttendanceList->CMAL_DPTCode = $CMAL_DPTCodes[$key2];
                        $new_claimMeetingAttendanceList->CMALCB = $user->USCode;
                        $new_claimMeetingAttendanceList->CMALMB = $user->USCode;
                        $new_claimMeetingAttendanceList->save();
                    }
                }
            }
//END HERE

            if($updateConfirm == 1){

                $claimMeeting = ClaimMeeting::where('CMNo',$CMNo)->with('meetingDetail')->first();

                $claimMeeting->CMStatus  = 'SUBMIT';
                $claimMeeting->save();

                $projectArray = array();

                $claimMeetingDet = $claimMeeting->meetingDetail;

                foreach($claimMeetingDet as $meetingDet){

                    $PCNo = $meetingDet->CMD_PCNo;

                    $projectClaim = ProjectClaim::where('PCNo', $PCNo)->first();

                    if($meetingDet->CMD_TMSCode == 'D'){

                        if($meetingDet->CMD_CRSCode == 'R') {
                            $projectClaim->PCRejectedDate = Carbon::now();
                            $projectClaim->PCRejectedBy = $user->USCode;
                            $updateStatus = "CRR";
                        }
                        else if($meetingDet->CMD_CRSCode == 'T'){
                            $projectClaim->PCApprovedDate = Carbon::now();
                            $projectClaim->PCApprovedBy = $user->USCode;
                            $updateStatus = "CRT";
                        }
                        else{
                            $projectClaim->PCApprovedDate = Carbon::now();
                            $projectClaim->PCApprovedBy = $user->USCode;
                            $updateStatus = "CRP";
                        }

                    }else{

                        $updateStatus = "AP";
                    }

                    $PCNo = $meetingDet->CMD_PCNo;

                    $projectClaim->PC_PCPCode = $updateStatus;
                    $projectClaim->save();

                    $project = $projectClaim->projectMilestone->project;
                    array_push($projectArray, $project);

                }

                //SEND NOTIFICATION TO ALL PIC
                $result = $this->sendNotification($claimMeeting,$projectArray,'S');

            }else if($updateConfirm == 2){ //send invitation

                $meeting = ClaimMeeting::where('CMNo',$CMNo)->first();
                $meeting->CMSentInd  = 1;
                $meeting->save();

                // $result = $this->sendNotification($claimMeeting,$projectArray,'N');
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.meeting.edit',[$CMNo]),
                'message' => 'Maklumat mesyuarat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function updateStatus($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $claimMeeting = ClaimMeeting::where('CMNo',$id)->with('meetingDetail')
                            ->first();

            $claimMeeting->CMStatus  = 'SUBMIT';
            $claimMeeting->save();

            $projectArray = array();

            $claimMeetingDet = $claimMeeting->meetingDetail;

            foreach($claimMeetingDet as $meetingDet){

                if($meetingDet->CMD_TMSCode == 'D'){

                    if($meetingDet->CMD_CRSCode == 'R') {
                        $updateStatus = "CRR";
                    }
                    else if($meetingDet->CMD_CRSCode == 'T'){
                        $updateStatus = "CRT";
                    }
                    else{
                        $updateStatus = "CRP";
                    }

                }else{

                    $updateStatus = "AP";
                }

                $PCNo = $meetingDet->CMD_PCNo;

                $projectClaim = ProjectClaim::where('PCNo', $PCNo)->first();
                $projectClaim->PC_PCPCode = $updateStatus;
                $projectClaim->save();

                $project = $projectClaim->projectMilestone->project;
                array_push($projectArray, $project);

            }

            //SEND NOTIFICATION TO ALL PIC
            $result = $this->sendNotification($claimMeeting,$projectArray,'S');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.meeting.index'),
                'message' => 'Maklumat mesyuarat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function listClaim(Request $request){
        $claimNo = array();
        $claimNo = $request->claim;
        $claims = array();
        $count = 0;

        foreach($claimNo as $claim){

            $claim = ProjectClaim::where('PCNo',$claim)
                    ->first();
            $milest = $claim->PC_PMNo;

            $milestone = ProjectMilestone::where('PMNo',$milest)->first();
            $projno = $milestone->PM_PTNo;

            $project = Project::where('PTNO',$projno)->first();
            $propno = $project->PT_TPNo;

            $proposal = TenderProposal::where('TPNo',$propno)->first();

            $claims[$count] = $claim;
            $claims[$count]['milestone'] = $milestone;
            $claims[$count]['project'] = $project;
            $claims[$count]['proposal'] = $proposal;

            $count++;

        }

        return view('pelaksana.meeting.listClaim',
                compact('claims')
        );

    }

    public function editListClaim(Request $request){

        $tuntutan = $this->dropdownService->projectClaimSM();
        $tmscode = $this->dropdownService->tenderMeetingStatus();
        $crscode = $this->dropdownService->claimResultStatus();

        $claimNo = array();
        $CMNo = $request->CMNo;
        $claimNo = $request->claim;

        $claimMeeting = ClaimMeeting::where('CMNo',$CMNo)->first();
        $claimMeeting['claimDate'] = Carbon::parse($claimMeeting->CMDate)->format('Y-m-d') ?? "";
        $claimMeeting['claimTime'] = Carbon::parse($claimMeeting->CMTime)->format('H:i') ?? "";

        $arrayTuntutan = [];

        foreach($claimMeeting->meetingDetail as $detail){

            array_push($arrayTuntutan, $detail->CMD_PCNo);

        }

        $claims = array();
        $count = 0;

        foreach($claimNo as $claim){

            $claim = ProjectClaim::where('PCNo',$claim)
                    ->first();
            $milest = $claim->PC_PMNo;
            $PCNo = $claim->PCNo;

            $milestone = ProjectMilestone::where('PMNo',$milest)->first();
            $projno = $milestone->PM_PTNo;

            $project = Project::where('PTNO',$projno)->first();
            $propno = $project->PT_TPNo;

            $proposal = TenderProposal::where('TPNo',$propno)->first();

            $claims[$count] = $claim;
            $claims[$count]['milestone'] = $milestone;
            $claims[$count]['project'] = $project;
            $claims[$count]['proposal'] = $proposal;

            $meetingDetail = ClaimMeetingDet::where('CMD_CMNo',$CMNo)
                            ->where('CMD_PCNo',$PCNo)
                            ->get();

            $claims[$count]['meetingDetail'] = $meetingDetail;

            $count++;

        }

        return view('pelaksana.meeting.editListClaim',
            compact('tuntutan','tmscode','crscode','claimMeeting','arrayTuntutan','claims')
        );

    }

    public function claimMeetingDatatable(Request $request){

        $user = Auth::user();

        $query = ClaimMeeting::orderBy('CMNo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('CMNo', function($row){

                $route = route('pelaksana.meeting.edit',[$row->CMNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->CMNo.' </a>';

                return $result;
            })
            ->editColumn('CMDate', function($row){

                if(empty($row->CMDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->CMDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;

            })
            ->editColumn('CMTime', function($row){

                if(empty($row->CMTime)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->CMTime);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('h:i A');

                }

                return $formattedDate;
            })
            ->editColumn('CMStatus', function($row) {

                $boardMeetingStatus = $this->dropdownService->boardMeetingStatus();

                return $boardMeetingStatus[$row->CMStatus];

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['CMNo','CMDate','CMTime','CMStatus'])
            ->make(true);


    }

    function sendNotification($claimMeeting,$projects,$status){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $claimMeetNo = $claimMeeting->CMNo;
            $meetingdate =  Carbon::parse($claimMeeting->CMDate)->format('d/m/Y');
            $meetingTime =  Carbon::parse($claimMeeting->CMTime)->format('h:i A');

            $claimMeetingDet = $claimMeeting->meetingDetail;

            $notification = new GeneralNotificationController();

            foreach($projects as $project){
                $tender = $project->tenderProposal->tender;

                $data = array(
                    'CM' => $claimMeeting,
                    'PTNo' => $project->PTNo,
                );

                if($status == 'N'){
                    //#NOTIF-010
                    $code = 'PC-MT';

                }elseif ($status == 'S') {
                    //#NOTIF-011
                    $code = 'PC-CR';
                }

                //SEND NOTIFICATION TO ALL PIC - PELAKSANA, KEWANGAN(?), BOSS
                $tenderPIC = $tender->tenderPIC;

                foreach($tenderPIC as $pic){

                    if(isset($pic->userSV) && !empty($pic->userSV)){

                        $sv = $pic->userSV;
                        $refNo = $sv->USCode;
                        $notiType = "SO";

                    }else{

                        $refNo = $pic->TPIC_USCode;

                        if($pic->TPICType == 'T'){
                            $notiType = 'SO';
                        }
                        // else if($pic->TPICType == 'K'){
                        //     $notiType = 'FO';
                        // }

                    }
                    $result = $notification->sendNotification($refNo,$notiType,$code,$data);

                }

            }

            $output = array();

            if($status == 'S') {

                foreach($claimMeetingDet as $meetingDet){

                    $meetingStatus = $meetingDet->CMD_TMSCode;
                    $status = $meetingDet->CMD_CRSCode;
                    $PCNo = $meetingDet->CMD_PCNo;

                    $projectClaim = ProjectClaim::where('PCNo', $PCNo)->first();
                    $project = $projectClaim->project;

                    $data = array(
                        'CM' => $claimMeeting,
                        'PTNo' => $project->PTNo,
                    );

                    if($meetingStatus == 'D'){

                        if($status == 'P'){ //LULUS
                            //##NOTIF-012a
                            $code = 'PC-CRP';

                        }
                        else if($status == 'T'){ //LULUS BERSYARAT
                            //##NOTIF-012b
                            $code = 'PC-CRT';

                        }else if($status == 'R'){ //TIDAK LULUS
                            //##NOTIF-012c
                            $code = 'PC-CRR';

                        }

                        //SEND NOTIFICATION TO CONTRACTOR
                        $contractorNo = $project->PT_CONo;

                        $refNo = $contractorNo;
                        $notiType = 'CO';
                        $result = $notification->sendNotification($refNo,$notiType,$code,$data);
                        array_push($output,$result);

                    }


                }

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Maklumat notifikasi berjaya dihantar.',
            ], 400);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat notifikasi tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function sendMailNotification($claimMeeting,$projects){

        try{
            DB::beginTransaction();

            $user = Auth::user();

            $claimMeetNo = $claimMeeting->CMNo;
            $meetingdate =  Carbon::parse($claimMeeting->CMDate)->format('d/m/Y');
            $meetingTime =  Carbon::parse($claimMeeting->CMTime)->format('h:i A');

            $claimMeeting->meetingDate = Carbon::parse($claimMeeting->CMDate)->format('d/m/Y');
            $claimMeeting->meetingTime = Carbon::parse($claimMeeting->CMTime)->format('h:i A');

            //#NOTIF-MAIL-004
            $notiType = "BM-CL";

            foreach($projects as $project){
                $tender = $project->tenderProposal->tender;

                //SEND EMAIL TO ALL PIC - PELAKSANA, KEWANGAN, BOSS
                $tenderPIC = $tender->tenderPIC;

                if(!empty($tenderPIC)){

                    foreach($tenderPIC as $pic){

                        $usercode = $pic->userPIC->USCode;
                        $user= User::where('USCode',$usercode)->first();


                        if($pic->TPICType == 'T'){

                            $emailLog = new EmailLog();
                            $emailLog->ELCB 	= $user->USCode;
                            $emailLog->ELType 	= 'Claim Meeting';
                            $emailLog->ELSentTo =  $user->USEmail;

                            // Send Email
                            $tokenResult = $user->createToken('Personal Access Token');
                            $token = $tokenResult->token;

                            $emailData = array(
                                'id' => $user->USID,
                                'name'  => $user->USName ?? '',
                                'email' => $user->USEmail,
                                'meeting' => $claimMeeting,
                                'meetingType' => 'CM',
                                'data' => $tender
                            );

                            try {
                                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Tuntutan');
                                });

                                $emailLog->ELMessage = 'Success';
                                $emailLog->ELSentStatus = 1;
                            } catch (\Exception $e) {
                                $emailLog->ELMessage = $e->getMessage();
                                $emailLog->ELSentStatus = 2;
                            }

                            $emailLog->save();


                        }elseif($pic->TPICType == 'K'){

                            $emailLog = new EmailLog();
                            $emailLog->ELCB 	= $user->USCode;
                            $emailLog->ELType 	= 'Claim Meeting';
                            $emailLog->ELSentTo =  $user->USEmail;

                            // Send Email
                            $tokenResult = $user->createToken('Personal Access Token');
                            $token = $tokenResult->token;

                            $emailData = array(
                                'id' => $user->USID,
                                'name'  => $user->USName ?? '',
                                'email' => $user->USEmail,
                                'meeting' => $claimMeeting,
                                'meetingType' => 'CM',
                                'data' => $tender
                            );

                            try {
                                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Tuntutan');
                                });

                                $emailLog->ELMessage = 'Success';
                                $emailLog->ELSentStatus = 1;
                            } catch (\Exception $e) {
                                $emailLog->ELMessage = $e->getMessage();
                                $emailLog->ELSentStatus = 2;
                            }

                            $emailLog->save();


                        }elseif(isset($pic->userSV) && $pic->userSV !== null){

                            $emailLog = new EmailLog();
                            $emailLog->ELCB 	= $user->USCode;
                            $emailLog->ELType 	= 'Claim Meeting';
                            $emailLog->ELSentTo =  $user->USEmail;

                            // Send Email
                            $tokenResult = $user->createToken('Personal Access Token');
                            $token = $tokenResult->token;

                            $emailData = array(
                                'id' => $user->USID,
                                'name'  => $user->USName ?? '',
                                'email' => $user->USEmail,
                                'meeting' => $claimMeeting,
                                'meetingType' => 'CM',
                                'data' => $tender
                            );

                            try {
                                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Tuntutan');
                                });

                                $emailLog->ELMessage = 'Success';
                                $emailLog->ELSentStatus = 1;
                            } catch (\Exception $e) {
                                $emailLog->ELMessage = $e->getMessage();
                                $emailLog->ELSentStatus = 2;
                            }

                            $emailLog->save();

                        }

                    }

                }

            }
            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Maklumat notifikasi berjaya di E-mel.',
            ], 400);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'status' => '0',
                'message' => 'Maklumat notifikasi tidak berjaya di E-mel!'.$e->getMessage()
            ], 400);
        }
    }

    public function sendEmail(Request $request){

        try{

            $id = $request->CMNo;
            $code = $request->sendCode;

            $user = Auth::user();


            $claimMeeting = ClaimMeeting::where('CMNo',$id)->first();

            if($claimMeeting->CMStatus == 'SUBMIT'){
                $statusCode = 'S';
            }else if($claimMeeting->CMStatus == 'NEW'){
                $statusCode = 'N';
            }

            $claimMeetingDet = $claimMeeting->meetingDetail;

            $projectArray = array();

            foreach($claimMeetingDet as $meetingDet){

                $PCNo = $meetingDet->CMD_PCNo;

                $projectClaim = ProjectClaim::where('PCNo', $PCNo)->first();

                $project = $projectClaim->projectMilestone->project;
                array_push($projectArray, $project);
            }

            $result = null;

            if($code == 'B'){ //BOTH MAIL N NOTIFICATION
                // $result = $this->sendMailNotification($claimMeeting,$projectArray);
                $result = $this->sendNotification($claimMeeting,$projectArray,$statusCode);

                $result = response()->json([
                    'success' => '1',
                    'message' => 'E-mel dan notifikasi berjaya dihantar.',
                ], 400);

            }else if($code == 'N'){
                $result = $this->sendNotification($claimMeeting,$projectArray,$statusCode);

            }else if($code == 'E'){
                $result = $this->sendMailNotification($claimMeeting,$projectArray);

            }
            return $result;

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat E-mel tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }



    }

}
