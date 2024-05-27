<?php

namespace App\Http\Controllers\Pelaksana\Meeting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralNotificationController;
use App\Models\KickOffMeetingAttendanceList;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\AutoNumber;
use App\Models\ClaimMeeting;
use App\Models\ClaimMeetingDet;
use App\Models\Contractor;
use App\Models\Role;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\FileAttach;
use App\Models\KickOffMeeting;
use App\Models\KickOffMeetingEmail;
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
use Mail;

class KickOffController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        return view('pelaksana.meeting.kickoff.index');
    }

    public function create(Request $request){

        $project = $this->dropdownService->projectNew();
        $genSAKNo = $this->autoNumber->generateSAKNo();

        $meetingLocation = $this->dropdownService->meetingLocation();

        $projectNo = $request->input('projectNo');

        $title = "";

        if ($projectNo) {
            $title = "Mesyuarat KickOff " . $projectNo;
        }else{
            $title = $this->createMeetingTitle('KOM');
        }

        return view('pelaksana.meeting.kickoff.create',
        compact('project','genSAKNo', 'title','meetingLocation')
        );
    }

    public function store(Request $request){

        $messages = [
            'meeting_title.required'    => 'Tajuk Mesyuarat diperlukan.',
            'meeting_date.required'     => 'Tarikh Mesyuarat diperlukan.',
            'meeting_location.required'     => 'Lokasi Mesyuarat diperlukan.',
            'project.required'          => 'Sila pilih projek.',
            // 'sakNo.required'            => 'No. Rujukan SAK diperlukan.',
            // 'sakDate.required'          => 'Tarikh SAK diperlukan.',
            // 'startDate.required'        => 'Tarikh Mula projek diperlukan.',
        ];

        $validation = [
            'meeting_title' => 'required|string',
            'meeting_date'  => 'required',
            'meeting_time'  => 'required',
            'meeting_location'  => 'required',
            'project'       => 'required',
            // 'sakNo'         => 'required',
            // 'sakDate'       => 'required',
            // 'startDate'     => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $autoNumber = new AutoNumber();
            $kickoffNo = $autoNumber->generateKickOffNo();

            $meeting_title  = $request->meeting_title;
            $meeting_date   = $request->meeting_date;
            $meeting_time   = $request->meeting_time;
            $meeting_location   = $request->meeting_location;
            $project        = $request->project;
            // $sakNo          = $request->sakNo;
            // $sakDate        = $request->sakDate;
            // $startDate      = $request->startDate;

            $kickoff = new KickOffMeeting();

            $kickoff->KOMNo           = $kickoffNo;
            $kickoff->KOMTitle        = $meeting_title;
            $kickoff->KOMDate         = $meeting_date ;
            $kickoff->KOMTime         = $meeting_time ;
            $kickoff->KOM_LCCode         = $meeting_location ;
            $kickoff->KOM_PTNo        = $project      ;
            // $kickoff->KOMSAKNo        = $sakNo        ;
            // $kickoff->KOMSAKDate      = $sakDate      ;
            // $kickoff->KOMStartDate    = $startDate    ;
            $kickoff->KOMCB           = $user->USCode;
            $kickoff->KOMMB           = $user->USCode;
            $kickoff->save();

            $projectStatus = 'KOM';
            $PPCode = 'KOM';
            $projects = Project::where('PTNO',$project)->first();
            $projects->PTStatus = $projectStatus;
            $projects->PT_PPCode = $PPCode;
            $projects->save();

            $tender = $projects->tenderProposal->tender;

            if(!empty($tender->tenderPIC)){

                foreach($tender->tenderPIC as $tenderPIC){

                    if(($tenderPIC->userPIC)){
                        $USEmail = $tenderPIC->userPIC->USEmail;
                        $USCode = $tenderPIC->userPIC->USCode;

                        $meetingEmail = new KickOffMeetingEmail();
                        $meetingEmail->KOME_KOMNo         = $kickoffNo;
                        $meetingEmail->KOMEEmailAddr    = $USEmail;
                        $meetingEmail->KOME_USCode    = $USCode;
                        $meetingEmail->save();

                    }

                }

            }

            //SEND NOTIFICATION
            $this->sendNotification($kickoff,'N');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.meeting.kickoff.edit',[$kickoffNo]),
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
        $departmentAll = $this->dropdownService->departmentAll();
        $meetingLocation = $this->dropdownService->meetingLocation();

        $kickoff = KickOffMeeting::where('KOMNo',$id)->first();

        $kickoff['meetingDate'] = Carbon::parse($kickoff->KOMDate)->format('Y-m-d');
        $kickoff['meetingTime'] = Carbon::parse($kickoff->KOMTime)->format('H:i');

        // $kickoff['SAKDate'] =  ($kickoff->KOMSAKDate) ? Carbon::parse($kickoff->KOMSAKDate)->format('Y-m-d') : null;
        // $kickoff['startDate'] = ($kickoff->KOMSAKDate) ? Carbon::parse($kickoff->KOMStartDate)->format('Y-m-d') : null;

        $genSAKNo = $this->autoNumber->generateSAKNo();

        $kickoff['sakNo'] = $kickoff->KOMSAKNo ?? $genSAKNo;

        $kickoff['project'] = $kickoff->project;
        if($kickoff->KOMStatus == 'NEW'){
            $project = $this->dropdownService->projectNewKOM($kickoff->project->PTNo);
        }else if($kickoff->KOMStatus == 'SUBMIT'){

            $project = $this->dropdownService->projectNewKOM($kickoff->project->PTNo);
        }

        $sak =  $kickoff->project->sak;

        $kickoff['SAKDate'] =  ($sak) ? Carbon::parse($sak->SAKDate)->format('Y-m-d') : null;
        $kickoff['startDate'] = ($sak) ? Carbon::parse($sak->SAKStartDate)->format('Y-m-d') : null;

        $kickoffMeetingAttendanceLists = KickOffMeetingAttendanceList::where('KMAL_KOMNo', $id)->get();

        $fileAttachDownloadMN = FileAttach::where('FAFileType','MT-MN')->first();

        return view('pelaksana.meeting.kickoff.edit',
            compact('project','kickoff','genSAKNo', 'departmentAll','fileAttachDownloadMN',
            'kickoffMeetingAttendanceLists','meetingLocation')
        );
    }

    public function update(Request $request){

        $messages = [
            'meeting_title.required'    => 'Tajuk Mesyuarat diperlukan.',
            'meeting_date.required'     => 'Tarikh Mesyuarat diperlukan.',
            'meeting_time.required'     => 'Masa Mesyuarat diperlukan.',
            'meeting_location.required'     => 'Lokasi Mesyuarat diperlukan.',
            'sakNo.required'            => 'No. Rujukan SAK diperlukan.',
            'sakDate.required'          => 'Tarikh SAK diperlukan.',
            'startDate.required'        => 'Tarikh Mula projek diperlukan.',
            'project.required'          => 'Sila pilih projek.',
        ];

        if($request->sendStatus == 1){

            $validation = [
                'meeting_title' => 'required|string',
                'meeting_date'  => 'required',
                'meeting_time'  => 'required',
                'meeting_location'  => 'required',
                'sakNo'         => 'required',
                'sakDate'       => 'required',
                'startDate'     => 'required',
                'project'       => 'required',
            ];

        }else{


            $validation = [
                'meeting_title' => 'required|string',
                'meeting_date'  => 'required',
                'meeting_time'  => 'required',
                'meeting_location'  => 'required',
                'project'       => 'required',
            ];
        }

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $kickoffNo = $request->kickoffNo;
            $sendStatus = $request->sendStatus;

            $meeting_title  = $request->meeting_title;
            $meeting_date   = $request->meeting_date;
            $meeting_time   = $request->meeting_time;
            $meeting_location   = $request->meeting_location;
            $project        = $request->project;
            $sakNo          = $request->sakNo;
            $sakDate        = $request->sakDate;
            $startDate      = $request->startDate;

            $kickoff = KickOffMeeting::where('KOMNo',$kickoffNo)->first();

            if($sendStatus == 1){

                if(!$kickoff->fileAttach){

                    if (!$request->hasFile('meetingMinit')) {

                        return response()->json([
                            'error' => 1,
                            'redirect' => route('pelaksana.meeting.kickoff.edit', [$kickoffNo]),
                            'message' => 'Sila muat-naik minit mesyuarat terlebih dahulu sebelum menghantar maklumat mesyuarat.'
                        ],400);

                    }

                }

            }


            if ($request->hasFile('meetingMinit')) {

                $file = $request->file('meetingMinit');
                $fileType = 'KOM-MM';
                $refNo = $kickoffNo;
                $this->saveFile($file,$fileType,$refNo);
            }

            $kickoff->KOMTitle        = $meeting_title;
            $kickoff->KOMDate         = $meeting_date ;
            $kickoff->KOMTime         = $meeting_time ;
            $kickoff->KOM_LCCode         = $meeting_location ;
            $kickoff->KOMSAKNo        = $sakNo        ;
            $kickoff->KOMSAKDate      = $sakDate      ;
            $kickoff->KOMStartDate    = $startDate    ;
            $kickoff->KOMCB           = $user->USCode;
            $kickoff->KOMMB           = $user->USCode;

            $oldProjectNo = $kickoff->KOM_PTNo;

            if($oldProjectNo !== $project){

                $projectStatus = 'KOM';
                $PPCode = 'KOM';
                $projects = Project::where('PTNO',$project)->first();
                $projects->PTStatus = $projectStatus;
                $projects->PT_PPCode = $PPCode;
                $projects->save();

//                $oldProjectStatus = 'NEW';
                $oldPPCode = 'KO';
                $oldProjects = Project::where('PTNO',$oldProjectNo)->first();
                $oldProjects->PTStatus = $projectStatus;
                $oldProjects->PT_PPCode = $oldPPCode;
                $oldProjects->save();

                // set new value for sakdate and sakstartdate
                $sak =  $projects->tenderProposal->sak;

                $kickoff->KOMSAKDate =  ($sak) ? Carbon::parse($sak->SAKDate)->format('Y-m-d') : null;
                $kickoff->KOMStartDate = ($sak) ? Carbon::parse($sak->SAKStartDate)->format('Y-m-d') : null;

            }

            $kickoff->KOM_PTNo = $project;
            $kickoff->save();

            $milestones = ProjectMilestone::where('PM_PTNo', $project)->orderBy('PMSeq')->get();

            $startDate = Carbon::parse($startDate);
            $startDateOri = $startDate->copy();

            foreach($milestones as $key => $milestone){
                $startDatetemp = $startDate->copy();

                $milestone->PMStartDate = $startDatetemp; // Create a copy of $startDate
                $milestone->PMProposeStartDate = $startDatetemp; // Create a copy of $startDate

                $endDate = $startDate->copy()->addDays($milestone->PMWorkDay-1);

                $milestone->PMEndDate = $endDate;
                $milestone->PMProposeEndDate = $endDate;
                $milestone->save();

                $startDate = $endDate->addDays(1)->copy();
            }

            $endDateP = $endDate->subDays(1)->copy();

            $old_kickOffMeetingAttendanceLists = KickOffMeetingAttendanceList::where('KMAL_KOMNo', $kickoffNo)->get();
            $KMALIDs = $request->KMALID;
            $KMALNames = $request->KMALName;
            $KMALPositions = $request->KMALPosition;
            $KMAL_DPTCodes = $request->KMAL_DPTCode;

            if(isset($KMALIDs)){
                // Check if every element in the arrays has a CMAL_DPTCodes
                if ($this->areAllValuesSet($KMALNames) && $this->areAllValuesSet($KMALPositions) && $this->areAllValuesSet($KMAL_DPTCodes)) {

                }else{
                    DB::rollback();

                    return response()->json([
                        'error' => '1',
                        'message' => 'Sila lengkapkan semua maklumat di dalam kehadiran.'
                    ], 400);

                }
            }else{
                $KMALIDs = [];
            }

//ARRAY UPDATE MULTIPLE ROW
            if(count($old_kickOffMeetingAttendanceLists) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_kickOffMeetingAttendanceLists as $okickOffMeetingAttendanceLists){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($KMALIDs as $KMALID){
                        if($okickOffMeetingAttendanceLists->KMALID == $KMALID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $okickOffMeetingAttendanceLists->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($KMALIDs as $key => $KMALID){

                    $new_kickOffMeetingAttendanceList = KickOffMeetingAttendanceList::where('KMAL_KOMNo', $kickoffNo)
                        ->where('KMALID', $KMALID)->first();

                    if(!$new_kickOffMeetingAttendanceList){
                        $new_kickOffMeetingAttendanceList = new KickOffMeetingAttendanceList();
                        $new_kickOffMeetingAttendanceList->KMAL_KOMNo = $kickoffNo;
                        $new_kickOffMeetingAttendanceList->KMALCB = $user->USCode;
                    }
                    $new_kickOffMeetingAttendanceList->KMALName = $KMALNames[$key];;
                    $new_kickOffMeetingAttendanceList->KMALPosition = $KMALPositions[$key];;
                    $new_kickOffMeetingAttendanceList->KMAL_DPTCode = $KMAL_DPTCodes[$key];
                    $new_kickOffMeetingAttendanceList->KMALMB = $user->USCode;
                    $new_kickOffMeetingAttendanceList->save();
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                }
            }
            else{
                if(isset($KMALIDs)){
                    foreach($KMALIDs as $key2 => $KMALID){
                        $new_kickOffMeetingAttendanceList = new KickOffMeetingAttendanceList();
                        $new_kickOffMeetingAttendanceList->KMAL_KOMNo = $kickoffNo;
                        $new_kickOffMeetingAttendanceList->KMALName = $KMALNames[$key2];
                        $new_kickOffMeetingAttendanceList->KMALPosition = $KMALPositions[$key2];
                        $new_kickOffMeetingAttendanceList->KMAL_DPTCode = $KMAL_DPTCodes[$key2];
                        $new_kickOffMeetingAttendanceList->KMALCB = $user->USCode;
                        $new_kickOffMeetingAttendanceList->KMALMB = $user->USCode;
                        $new_kickOffMeetingAttendanceList->save();
                    }
                }
            }
//END HERE

            if($sendStatus == 1){

                $meeting = KickOffMeeting::where('KOMNo',$kickoffNo)->first();
                $meeting->KOMStatus = 'SUBMIT';
                $meeting->save();

                // $projectStatus = 'SAK';
                // $projects = Project::where('PTNo',$project)->first();
                // $projects->PTSAKNo = $sakNo;
                // $projects->PTSAKDate = $sakDate;
                // $projects->PTStatus = $projectStatus;
                // $projects->save();

                $dateNow = Carbon::now();

//                if($dateNow >= $startDateOri){
//                    $ppcode = 'PS';
//                }else{
//                    $ppcode = 'IKOMS';
//                }

                $projects = Project::where('PTNo',$project)->first();
                $projects->PTStartDate = $startDateOri;
                $projects->PTEndDate = $endDateP;
                $projects->PTEstimateEndDate = $endDateP;
                $projects->PT_PPCode = 'PS';
                $projects->save();


            }else if($sendStatus == 2){ //send invitation

                $meeting = KickOffMeeting::where('KOMNo',$kickoffNo)->first();
                $meeting->KOMSentInd  = 1;
                $meeting->save();

                $result = $this->sendNotification($meeting);


            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.meeting.kickoff.edit',[$kickoffNo]),
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


    public function listProject(Request $request){

        $projectNo = $request->project;
        $count = 0;

        $project = Project::where('PTNO',$projectNo)->first();
        $propno = $project->PT_TPNo;

        return view('pelaksana.meeting.kickoff.listProject',
                compact('project')
        );

    }

    public function kickOffDatatable(Request $request){

        $user = Auth::user();

        $query = KickOffMeeting::orderBy('KOMNo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('KOMNo', function($row){

                $route = route('pelaksana.meeting.kickoff.edit',[$row->KOMNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->KOMNo.' </a>';

                return $result;
            })
            ->editColumn('KOMDate', function($row){

                if(empty($row->KOMDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->KOMDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;

            })
            ->editColumn('KOMTime', function($row){

                if(empty($row->KOMTime)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->KOMTime);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('h:i A');

                }

                return $formattedDate;
            })
            ->editColumn('KOMStatus', function($row) {

                $boardMeetingStatus = $this->dropdownService->boardMeetingStatus();

                return $boardMeetingStatus[$row->KOMStatus];

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['KOMNo','KOMDate','KOMTime','KOMStatus'])
            ->make(true);


    }


    function sendNotification($kickoff){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $kickNo = $kickoff->KOMNo;
            $kickdate =  Carbon::parse($kickoff->KOMDate)->format('d/m/Y');
            $kicktime =  Carbon::parse($kickoff->KOMTime)->format('h:i A');

            if($kickoff->KOMStatus == 'NEW'){
                //#NOTIF-004
                $code = 'KOM-E';


            }elseif ($kickoff->KOMStatus  == 'SUBMIT') {
                //#NOTIF-005
                $code = 'KOM-C';
            }

            $notification = new GeneralNotificationController();

            $projectNo = $kickoff->KOM_PTNo;
            $project = Project::where('PTNo',$projectNo)->first();

            $tender = $project->tenderProposal->tender;

            //SEND NOTIFICATION TO PELAKSANA
            $tenderPICT = $tender->tenderPIC_T;
            $tenderPIC = $tender->tenderPIC;
            $notiType = "SO";

            $data = array(
                'kickoff' => $kickoff
            );

            $dataPIC = array();

            if(!empty($tenderPICT)){

                foreach($tenderPICT as $pict){

                    $refNo = $pict->TPIC_USCode;
                    array_push($dataPIC,$refNo);

                    $result = $notification->sendNotification($refNo,$notiType,$code,$data);
                }

            }

            foreach($tenderPIC as $pic){
                $notiType = "SO";

                if(isset($pic->userSV) && !empty($pic->userSV)){

                    $sv = $pic->userSV;
                    $refNo = $sv->USCode;

                    if(!in_array($refNo,$dataPIC)){
                        $result = $notification->sendNotification($refNo,$notiType,$code,$data);
                    }

                }

            }

            //SEND TO CONTRACTOR
            $contractorNo = $project->PT_CONo;
            $contractorType = 'CO';

            $notiType = $contractorType;
            $refNo = $contractorNo;

            $result = $notification->sendNotification($refNo,$notiType,$code,$data);
            /////////////////////////////////////////////////////////////////

            //SEND TO PUBLIC USER
            $tenderProposal = $project->tenderProposal;
            $PUNo = $tenderProposal->TP_CONo;
            $publicUserType = 'PU';

            $notiType = $publicUserType;
            $refNo = $PUNo;

            $result = $notification->sendNotification($refNo,$notiType,$code,$data);

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat notifikasi tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function sendMailNotification($kickoff){

        try{
            DB::beginTransaction();

            $user = Auth::user();

            $kickNo = $kickoff->KOMNo;
            $meetingDate =  Carbon::parse($kickoff->KOMDate)->format('d/m/Y');
            $meetingTime =  Carbon::parse($kickoff->KOMTime)->format('h:i A');

            $kickoff->meetingDate = $meetingDate;
            $kickoff->meetingTime = $meetingTime;

            $projectNo = $kickoff->KOM_PTNo;
            $project = Project::where('PTNo',$projectNo)->first();

            $tender = $project->tenderProposal->tender;

            //SEND EMAIL TO CONTRACTOR
            //#NOTIF-MAIL-001
            $contractorNo = $project->PT_CONo;
            $contractor= Contractor::where('CONo',$contractorNo)->first();

            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCode;
            $emailLog->ELType 	= 'Kick Off Meeting';
            $emailLog->ELSentTo =  $contractor->COEmail;

            // Send Email //#MAIL-NOTIFICATION-TEMPLATE
            $tokenResult = $contractor->createToken('Personal Access Token');
            $token = $tokenResult->token;

            $emailData = array(
                'id' => $contractor->COID,
                'name'  => $contractor->COName ?? '',
                'email' => $contractor->COEmail,
                'meeting' => $kickoff,
                'meetingType' => 'KOM',
                'data' => $project
            );

            try {
                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Kick-Off');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
            } catch (\Exception $e) {
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
            }

            $emailLog->save();
            //#CONTINUEEEEEEEEEEE

            //SEND EMAIL TO PUBLIC USER
            //##NOTIF-MAIL-002
            $tenderProposal = $project->tenderProposal;
            $PUNo = $tenderProposal->TP_CONo;
            $user= User::where('USCode',$PUNo)->first();

            $emailLog2 = new EmailLog();
            $emailLog2->ELCB 	= $user->USCode;
            $emailLog->ELType 	= 'Kick Off Meeting';
            $emailLog2->ELSentTo =  $user->USEmail;

            // Send Email //#MAIL-NOTIFICATION-TEMPLATE
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;

            $emailData = array(
                'id' => $user->USID,
                'name'  => $user->USName ?? '',
                'email' => $user->USEmail,
                'meeting' => $kickoff,
                'meetingType' => 'KOM',
                'data' => $project
            );

            try {
                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Kick-Off');
                });

                $emailLog2->ELMessage = 'Success';
                $emailLog2->ELSentStatus = 1;
            } catch (\Exception $e) {
                $emailLog2->ELMessage = $e->getMessage();
                $emailLog2->ELSentStatus = 2;
            }

            $emailLog2->save();


            //SEND EMAIL TO PELAKSANA, BOSS
            //##NOTIF-MAIL-003
            $tenderPIC = $tender->tenderPIC;

            if(!empty($tenderPIC)){

                foreach($tenderPIC as $pic){

                    if($pic->TPICType == 'T'){
                        $picUserCode = $pic->userPIC->USCode;

                        $user= User::where('USCode',$picUserCode)->first();

                        $emailLog3 = new EmailLog();
                        $emailLog3->ELCB 	= $user->USCode;
                        $emailLog->ELType 	= 'Kick Off Meeting';
                        $emailLog3->ELSentTo =  $user->USEmail;

                        // Send Email //#MAIL-NOTIFICATION-TEMPLATE
                        $tokenResult = $user->createToken('Personal Access Token');
                        $token = $tokenResult->token;

                        $emailData = array(
                            'id' => $user->USID,
                            'name'  => $user->USName ?? '',
                            'email' => $user->USEmail,
                            'meeting' => $kickoff,
                            'meetingType' => 'KOM',
                            'data' => $project
                        );

                        try {
                            Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Kick-Off');
                            });

                            $emailLog3->ELMessage = 'Success';
                            $emailLog3->ELSentStatus = 1;
                        } catch (\Exception $e) {
                            $emailLog3->ELMessage = $e->getMessage();
                            $emailLog3->ELSentStatus = 2;
                        }

                        $emailLog3->save();

                    }elseif(isset($pic->userSV) && $pic->userSV !== null){
                        //SEND TO BOSS
                        $picUserCode = $pic->userPIC->USCode;

                        $user= User::where('USCode',$picUserCode)->first();

                        $emailLog3 = new EmailLog();
                        $emailLog3->ELCB 	= $user->USCode;
                        $emailLog->ELType 	= 'Kick Off Meeting';
                        $emailLog3->ELSentTo =  $user->USEmail;

                        // Send Email //#MAIL-NOTIFICATION-TEMPLATE
                        $tokenResult = $user->createToken('Personal Access Token');
                        $token = $tokenResult->token;

                        $emailData = array(
                            'id' => $user->USID,
                            'name'  => $user->USName ?? '',
                            'email' => $user->USEmail,
                            'meeting' => $kickoff,
                            'meetingType' => 'KOM',
                            'data' => $project
                        );

                        try {
                            Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Kick-Off');
                            });

                            $emailLog3->ELMessage = 'Success';
                            $emailLog3->ELSentStatus = 1;
                        } catch (\Exception $e) {
                            $emailLog3->ELMessage = $e->getMessage();
                            $emailLog3->ELSentStatus = 2;
                        }

                        $emailLog3->save();

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

            $id = $request->kickoffNo;
            $code = $request->sendCode;

            $user = Auth::user();

            $kickoffMeeting = KickOffMeeting::where('KOMNo',$id)->first();

            if($kickoffMeeting->KOMStatus == 'SUBMIT'){
                $statusCode = 'S';
            }else if($kickoffMeeting->KOMStatus == 'NEW'){
                $statusCode = 'N';
            }

            if($code == 'B'){ //BOTH MAIL N NOTIFICATION
                // $result = $this->sendMailNotification($kickoffMeeting);
                $result = $this->sendNotification($kickoffMeeting,$statusCode);

                $result = response()->json([
                    'success' => '1',
                    'message' => 'Jemputan berjaya dihantar.',
                ]);

            }else if($code == 'N'){
                $result = $this->sendNotification($kickoffMeeting,$statusCode);

            }else if($code == 'E'){
                $result = $this->sendMailNotification($kickoffMeeting);

            }
            return $result;

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Jemputan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }



    }

}
