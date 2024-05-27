<?php

namespace App\Http\Controllers\Perolehan\Mesyuarat;

use App\Helper\Custom;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralNotificationController;
use App\Http\Controllers\MeetingEmailController;
use App\Http\Controllers\SchedulerController;
use App\Models\ExtensionOfTime;
use App\Models\Meeting;
use App\Models\MeetingDet;
use App\Models\MeetingEOT;
use App\Models\MeetingType;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectMilestoneLog;
use App\Models\SSMCompany;
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
use App\Models\Role;
use App\Models\Customer;
use App\Models\Tender;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use App\Services\DropdownService;
use App\Models\TenderApplication;
use App\Models\TenderFormDetail;
use App\Models\TenderProcess;
use App\Models\TenderApplicationAuthSign;
use App\Models\TenderDetail;
use App\Models\TenderProposal;
use App\Models\TenderProposalDetail;
use App\Models\TenderProposalSpec;
use App\Models\FileAttach;
use App\Models\AutoNumber;
use App\Models\BoardMeeting;
use App\Models\BoardMeetingEmail;
use App\Models\BoardMeetingTender;
use App\Models\BoardMeetingProposal;
use App\Models\ClaimMeeting;
use App\Models\ClaimMeetingEmail;
use App\Models\ExtensionOfTimeSpec;
use App\Models\KickOffMeeting;
use App\Models\KickOffMeetingEmail;
use App\Models\LetterIntent;
use App\Models\MeetingAttendanceList;
use App\Models\MeetingEmail;
use App\Models\MeetingEOTAJK;
use App\Models\MeetingLI;
use App\Models\MeetingNP;
use App\Models\MeetingPT;
use App\Models\MeetingPTA;
use App\Models\MeetingPTE1;
use App\Models\MeetingPTE2;
use App\Models\MeetingVO;
use App\Models\MeetingVOAJK;
use App\Models\Notification;
use App\Models\ProjectClaim;
use App\Models\ProjectTender;
use App\Models\ProjectTenderDept;
use App\Models\VariantOrder;
use Yajra\DataTables\DataTables;

class MesyuaratController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        return view('perolehan.mesyuarat.index');
    }

    public function mesyuaratDatatable(Request $request){

        $user = Auth::user();

        $query = Meeting::orderBy('MNo','DESC')
        ->whereHas('meetingDet', function($query){
            $query->whereHas('meetingType', function($query2){
                $query2->where('MTActive',1);
            });
        })
        ->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('mType', function($row) {
                $result = '';

                foreach($row->meetingDet as $key => $meetingDet){
                    $meetingType = MeetingType::where('MTCode', $meetingDet->MD_MTCode)->first();

                    if($key == 0){
                        $result .= $meetingType->MTDesc ?? "";
                    }
                    else{
                        $result .= ', ' . $meetingType->MDesc;
                    }

                }

                return $result;
            })
            ->editColumn('MNo', function($row){
                if($row->MStatus == 'SUBMIT'){
                    $route = route('perolehan.mesyuarat.edit',[$row->MNo]);
                    // if(isset($row->meetingEOT)) {
                    // }
                }
                else{
                    $route = route('perolehan.mesyuarat.edit',[$row->MNo]);
                }

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->MNo.' </a>';

                return $result;
            })
            ->editColumn('MDate', function($row){

                if(empty($row->MDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->MDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;

            })
            ->editColumn('MTime', function($row){

                if(empty($row->MTime)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->MTime);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('h:i A');

                }

                return $formattedDate;
            })
            ->editColumn('MStatus', function($row) {

                $boardMeetingStatus = $this->dropdownService->boardMeetingStatus();

                return $boardMeetingStatus[$row->MStatus];

            })
            // ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            // ->setRowId('indexNo')
            ->rawColumns(['mType', 'MNo','MDate','MTime','MStatus'])
            ->make(true);
    }

    public function create(Request $request){
        $meetingType = $this->dropdownService->meetingType();
        // $projectTender = $this->dropdownService->projectTenderC();
        $meetingLocation = $this->dropdownService->meetingLocation();

        $eot = $this->dropdownService->meetingEOTList();

        $vo = $this->dropdownService->meetingVOList();

        $mpte1 = ProjectTender::where(function ($query) {
            $query->whereIn('PTD_PTSCode',['COMPLETE','EM1S']);
        })
        ->get()
        ->pluck('PTDTitle', 'PTDNo')
        ->map(function ($item, $key) {
            $ptd = ProjectTender::where('PTDNo', $key)->first();
            return $key . " ( " . $ptd->PTDTitle . " )"; // Appending MACode to MA_MOFCode
        });

        $mpte2 = ProjectTender::where(function ($query) {
            $query->whereIn('PTD_PTSCode',['EM2S','EM1A']);
        })
        ->get()
        ->pluck('PTDTitle', 'PTDNo')
        ->map(function ($item, $key) {
            $ptd = ProjectTender::where('PTDNo', $key)->first();
            return $key . " ( " . $ptd->PTDTitle . " )"; // Appending MACode to MA_MOFCode
        });

        //mpta
        $projectTender = ProjectTender::where(function ($query) {
            $query->whereIn('PTD_PTSCode',['EM3S','EM2A','EM2S','EM1A']);
        })
        ->get()
        ->pluck('PTDTitle', 'PTDNo')
        ->map(function ($item, $key) {
            $ptd = ProjectTender::where('PTDNo', $key)->first();
            return $key . " ( " . $ptd->PTDTitle . " )"; // Appending MACode to MA_MOFCode
        });

        //mli
        $tenderProposal = TenderProposal::where(function ($query) {
            $query->whereIn('TP_TRPCode',['LIA']);
        })
        ->whereHas('project')
        ->get()
        ->pluck('TP_TDNo', 'TPNo')
        ->map(function ($item, $key) {
            $tend = Tender::where('TDNo', $item)->first();
            return $key . " ( " . $tend->TDTitle . " )"; // Appending MACode to MA_MOFCode
        });

        //mnp
        $mnp = TenderProposal::whereHas('project', function ($query) {
            $query->where('PT_PPCode','NP');
        })
        ->get()
        ->pluck('TP_TDNo', 'TPNo')
        ->map(function ($item, $key) {
            $tend = Tender::where('TDNo', $item)->first();
            return $key . " ( " . $tend->TDTitle . " )"; // Appending Tender title
        });

        $title = "";
        $setLocation = null;
        $setDate = null;
        $setTime = null;

        $type = $request->type ?? "";
        $ref = $request->refNo;

        $title = $this->createMeetingTitle($type);

        //SET LOCATION
        if($type == 'MLI'){
            $letterIntent = LetterIntent::where('LI_TPNo',$ref)->orderBy('LICD','DESC')->first();
            $setLocation = $letterIntent->LILocation;
            $setDate = Carbon::parse($letterIntent->LIDate)->format('Y-m-d');
            $setTime = Carbon::parse($letterIntent->LITime)->format('H:i');
        }

        return view('perolehan.mesyuarat.create',
            compact('meetingLocation','title','setLocation','type','setTime','setDate',
            'eot', 'meetingType', 'vo', 'projectTender',
            'mpte1','mpte2','tenderProposal','mnp'
            )
        );
    }

    public function listEOT(Request $request){
        $eotNo = array();
        $eotNo = $request->eot;
        $eots = array();

        foreach($eotNo as $key => $eot){

            $dataEOT = ExtensionOfTime::where('EOTNo',$eot)
                ->first();


            $totalSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $eot)->sum('EOTSTotalProposeAmt');

            $eots[$key] = $dataEOT;
            $eots[$key]['totalspec'] = $totalSpec ?? 0;
        }

        return view('perolehan.mesyuarat.showList.listEOT',
            compact('eots')
        );

    }

    public function listVO(Request $request){

        $voNo = array();
        $voNo = $request->vo;
        $vos = array();

        foreach($voNo as $key => $vo){

            $data = VariantOrder::where('VONo',$vo)
                ->first();

            $vos[$key] = $data;
        }

        return view('perolehan.mesyuarat.showList.listVO',
            compact('vos')
        );

    }

    public function listMPTA(Request $request){

        $data = array();
        $mpta = $request->mpta;
        $mptas = array();

        foreach($mpta as $key => $mpta){

            $dataPTD = ProjectTender::where('PTDNo',$mpta)->first();

            $mptas[$key] = $dataPTD;
        }

        return view('perolehan.mesyuarat.showList.listMPTA',
            compact(
                'mptas'
            )
        );
    }

    public function listMPTE1(Request $request){

        $data = array();
        $mpte1 = $request->mpte1;
        $mpte1s = array();

        foreach($mpte1 as $key => $mpte1){

            $dataPTD = ProjectTender::where('PTDNo',$mpte1)->first();
            $meetingPT = MeetingPT::where('MPT_PTDNo',$dataPTD->PTDNo)->with('meeting')->get();

            $mpte1s[$key]['projectTender'] = $dataPTD;
            $mpte1s[$key]['projectMeeting'] = $meetingPT;
        }

        return view('perolehan.mesyuarat.showList.listMPTE1',
            compact(
                'mpte1s'
            )
        );
    }

    public function listMPTE2(Request $request){

        $tccode = $this->dropdownService->tender_sebutharga();

        $data = array();
        $mpte2 = $request->mpte2;
        $mpte2s = array();

        foreach($mpte2 as $key => $mpte2){

            $dataPTD = ProjectTender::where('PTDNo',$mpte2)->first();
            $meetingPT = MeetingPT::where('MPT_PTDNo',$dataPTD->PTDNo)->with('meeting')->get();
            $meetingPTE1 = MeetingPTE1::where('MPTE1_PTDNo',$dataPTD->PTDNo)->with('meeting')->get();

            $mpte2s[$key]['projectTender'] = $dataPTD;
            $mpte2s[$key]['projectMeeting'] = $meetingPT;
            $mpte2s[$key]['projectMPTE1'] = $meetingPTE1;
        }

        return view('perolehan.mesyuarat.showList.listMPTE2',
            compact(
                'mpte2s','tccode'
            )
        );
    }

    public function listMLI(Request $request){

        $data = array();
        $mli = $request->mli;
        $mlis = array();

        $proposal = TenderProposal::where('TPNo',$mli)->first();
        $tender = $proposal->tender;
        $letterIntent = $proposal->letterIntent;

        $mlis['proposal'] = $proposal;
        $mlis['tender'] = $tender;
        $mlis['letterIntent'] = $letterIntent;

        return view('perolehan.mesyuarat.showList.listMLI',
            compact(
                'mlis'
            )
        );
    }

    public function listMNP(Request $request){

        $data = array();
        $mnp = $request->mnp;
        $mnps = array();

        foreach($mnp as $key => $mnp){

            $proposal = TenderProposal::where('TPNo',$mnp)->first();
            $tender = $proposal->tender;
            $project = $proposal->project;

            $mnps[$key]['proposal'] = $proposal;
            $mnps[$key]['tender'] = $tender;
            $mnps[$key]['project'] = $project;
        }

        return view('perolehan.mesyuarat.showList.listMNP',
            compact(
                'mnps'
            )
        );
    }

    public function store(Request $request){

        $messages = [
            'meeting_title.required'   => 'Tajuk Mesyuarat diperlukan.',
            'meeting_date.required'   => 'Tarikh Mesyuarat diperlukan.',
            'meeting_time.required'   => 'Masa Mesyuarat diperlukan.',
            'meeting_location.required'   => 'Lokasi Mesyuarat diperlukan.',
            //  'tender.required'       => 'Sila pilih sekurang-kurangnya satu tender.',
        ];

        $validation = [
            'meeting_title' => 'required|string',
            'meeting_date' => 'required',
            'meeting_time' => 'required',
            'meeting_location' => 'required',
            // 'tender'        => 'required',

        ];

        $request->validate($validation, $messages);
        try {

            DB::beginTransaction();

            $user = Auth::user();

            $autoNumber = new AutoNumber();
            $MNo = $autoNumber->generateMeetingNo();

            $meeting_title       = $request->meeting_title;
            $meeting_date        = $request->meeting_date;
            $meeting_time        = $request->meeting_time;
            $meetingType         = $request->meetingType;
            $meeting_location    = $request->meeting_location;

            $meeting = new Meeting();
            $meeting->MNo     = $MNo;
            $meeting->MTitle  = $meeting_title;
            $meeting->MDate   = $meeting_date;
            $meeting->MTime   = $meeting_time;
            $meeting->M_LCCode   = $meeting_location;
            $meeting->MStatus   = "NEW";
            $meeting->MCB     = $user->USCode;
            $meeting->MMB     = $user->USCode;
            $meeting->save();

            $meetingDet = new MeetingDet();
            $meetingDet->MD_MNo     = $MNo;
            $meetingDet->MD_MTCode  = $meetingType;
            $meetingDet->save();

            $route = route('perolehan.mesyuarat.index');

            if($meetingType == 'EOT'){

                $eot            = $request->eot;

                foreach($eot as $eotNo){

                    $dataEOT = ExtensionOfTime::where('EOTNo',$eotNo)->first();
                    $dataEOT->EOT_EPCode = 'MT';
                    $dataEOT->save();

                    $TMSCode = "I"; //default value; get from MSTenderMeetingStatus

                    $meetingEOT = new MeetingEOT();
                    $meetingEOT->ME_MNo         = $MNo;
                    $meetingEOT->ME_EOTNo       = $eotNo;
                    $meetingEOT->ME_MSCode     = $TMSCode;
                    $meetingEOT->MECB           = $user->USCode;
                    $meetingEOT->MEMB           = $user->USCode;
                    $meetingEOT->save();

                    $projects = Project::where('PTNO',$dataEOT->EOT_PTNo)->first();

                    $tender = $projects->tenderProposal->tender;

                    if(!empty($tender->tenderPIC)){

                        foreach($tender->tenderPIC as $tenderPIC){

                            if(($tenderPIC->userPIC)){
                                $USEmail = $tenderPIC->userPIC->USEmail;
                                $USCode = $tenderPIC->userPIC->USCode;
                                $USPhoneNo = $tenderPIC->userPIC->USPhoneNo;

                                $currentMeetingEmail = MeetingEmail::where('MAE_MNo',$MNo)->get();

                                $exists = $currentMeetingEmail->contains('MAEEmailAddr',$USEmail);

                                if( !$exists ){

                                    $meetingEmail = new MeetingEmail();
                                    $meetingEmail->MAE_MNo         = $MNo;
                                    $meetingEmail->MAEEmailAddr    = $USEmail;
                                    $meetingEmail->MAE_USCode    = $USCode;
                                    $meetingEmail->MAEPhoneNo    = $USPhoneNo;
                                    $meetingEmail->save();

                                }

                            }

                        }

                    }
                }

                $route = route('perolehan.mesyuarat.edit',[$MNo]);
            }

            if($meetingType == 'VO'){

                $vo            = $request->vo;

                foreach($vo as $voNo){

                    $data = VariantOrder::where('VONo',$voNo)->first();
                    $data->VO_VPCode = 'MT';
                    $data->save();

                    $TMSCode = "I"; //default value; get from MSTenderMeetingStatus

                    $meeting = new MeetingVO();
                    $meeting->MV_MNo         = $MNo;
                    $meeting->MV_VONo        = $voNo;
                    $meeting->MV_MSCode     = $TMSCode;
                    $meeting->MVCB           = $user->USCode;
                    $meeting->MVMB           = $user->USCode;
                    $meeting->save();

                    $projects = Project::where('PTNO',$data->VO_PTNo)->first();

                    $tender = $projects->tenderProposal->tender;

                    if(!empty($tender->tenderPIC)){

                        foreach($tender->tenderPIC as $tenderPIC){

                            if(($tenderPIC->userPIC)){
                                $USEmail = $tenderPIC->userPIC->USEmail;
                                $USCode = $tenderPIC->userPIC->USCode;
                                $USPhoneNo = $tenderPIC->userPIC->USPhoneNo;

                                $currentMeetingEmail = MeetingEmail::where('MAE_MNo',$MNo)->get();

                                $exists = $currentMeetingEmail->contains('MAEEmailAddr',$USEmail);

                                if( !$exists ){

                                    $meetingEmail = new MeetingEmail();
                                    $meetingEmail->MAE_MNo         = $MNo;
                                    $meetingEmail->MAEEmailAddr    = $USEmail;
                                    $meetingEmail->MAE_USCode    = $USCode;
                                    $meetingEmail->MAEPhoneNo    = $USPhoneNo;
                                    $meetingEmail->save();

                                }

                            }

                        }

                    }
                }

                $route = route('perolehan.mesyuarat.edit',[$MNo]);
            }

            if($meetingType == 'PTENDER'){

                $TMSCode = "I"; //default value; get from MSTenderMeetingStatus
                $ptdNo = $request->ptdNo;

                $meetingPT = MeetingPT::where('MPT_PTDNo',$ptdNo)->orderBy('MPTID','DESC')->first();

                if($meetingPT){
                    if($meetingPT->meeting->MStatus == "NEW"){

                        $errorMsg = "Mesyuarat terakhir bagi ". $meetingPT->meeting->MNo . " masih belum selesai. Sila kemaskini status mesyuarat.";

                        return response()->json([
                            'error' => '1',
                            'message' => $errorMsg
                        ], 400);
                    }
                }

                $meeting = new MeetingPT();
                $meeting->MPT_MNo         = $MNo;
                $meeting->MPT_PTDNo       = $ptdNo;
                $meeting->MPT_MSCode     = $TMSCode;
                $meeting->MPTCB           = $user->USCode;
                $meeting->MPTMB           = $user->USCode;
                $meeting->save();

                $currentEmail = array();

                $projectTender = ProjectTender::where('PTDNo',$ptdNo)->first();

                if($projectTender->projectTenderDept){

                    foreach($projectTender->projectTenderDept as $projectTenderDept){

                        if($projectTenderDept->department){

                            if($projectTenderDept->department->user){

                                $user = $projectTenderDept->department->user;

                                $USEmail = $user->USEmail;
                                $USCode = $user->USCode;
                                $USPhoneNo = $user->USPhoneNo;

                                if(!in_array($USEmail, $currentEmail)){

                                    $meetingEmail = new MeetingEmail();
                                    $meetingEmail->MAE_MNo         = $MNo;
                                    $meetingEmail->MAEEmailAddr    = $USEmail;
                                    $meetingEmail->MAE_USCode    = $USCode;
                                    $meetingEmail->MAEPhoneNo    = $USPhoneNo;
                                    $meetingEmail->save();

                                }

                                array_push($currentEmail,$USEmail);

                            }

                        }

                    }

                }

                $route = route('pelaksana.projectTender.edit',[$ptdNo,'flag'=>3,'opm'=>$MNo]);
            }

            if($meetingType == 'MNP'){

                $TMSCode = "I"; //default value; get from MSTenderMeetingStatus
                $mnps = $request->mnp;

                foreach($mnps as $mnp){

                    $meetingNP = MeetingNP::where('MNP_TPNo',$mnp)->orderBy('MNPID','DESC')->first();

                    if($meetingNP){
                        if($meetingNP->meeting->MStatus == "NEW"){
                            $haveError = true;
                            $errorMsg = "Mesyuarat terakhir bagi ". $meetingNP->meeting->MNo . " (" . $mnp  . ")" . " masih belum selesai. Sila kemaskini status mesyuarat.";

                            return response()->json([
                                'error' => '1',
                                'message' => $errorMsg
                            ], 400);
                        }
                    }

                    $tenderProposal = TenderProposal::where('TPNo',$mnp)->first();
                    $project = $tenderProposal->project;
                    $meetingProposal = $tenderProposal->meetingProposal;
                    $proposalAmt = $tenderProposal->TPTotalAmt;
                    $discAmt = 0;

                    $findOldMNP = MeetingNP::where('MNP_TPNo',$mnp)
                            ->where('MNPNegotiate',1)
                            ->whereHas('meeting',function ($query) {
                                $query->where('MStatus','SUBMIT');
                            })
                            ->orderBy('MNPMD','DESC')
                            ->first();

                    if($findOldMNP){ //if exist
                        $proposalAmt = $findOldMNP->MNPFinalAmt;

                    }

                    $finalAmt = $proposalAmt - $discAmt;

                    $meeting = new MeetingNP();
                    $meeting->MNP_MNo         = $MNo;
                    $meeting->MNP_MSCode      = $TMSCode;
                    $meeting->MNPProposalAmt  = $proposalAmt;
                    $meeting->MNPDiscAmt      = $discAmt;
                    $meeting->MNPFinalAmt     = $finalAmt;
                    // $meeting->MNPRemark       = $remark;
                    $meeting->MNP_TPNo        = $mnp;
                    $meeting->MNP_PTNo        = $project->PTNo;
                    $meeting->MNP_BMPNo       = $meetingProposal->BMPNo;
                    $meeting->MNPCB           = $user->USCode;
                    $meeting->MNPMB           = $user->USCode;
                    $meeting->save();

                    $project->PT_PPCode = 'NPM';
                    $project->save();

                    $tender = $tenderProposal->tender;

                    if(!empty($tender->tenderPIC)){

                        foreach($tender->tenderPIC as $tenderPIC){

                            if(($tenderPIC->userPIC)){
                                $USEmail = $tenderPIC->userPIC->USEmail;
                                $USCode = $tenderPIC->userPIC->USCode;
                                $USPhoneNo = $tenderPIC->userPIC->USPhoneNo;

                                $currentMeetingEmail = MeetingEmail::where('MAE_MNo',$MNo)->get();

                                $exists = $currentMeetingEmail->contains('MAEEmailAddr',$USEmail);

                                if( !$exists ){

                                    $meetingEmail = new MeetingEmail();
                                    $meetingEmail->MAE_MNo         = $MNo;
                                    $meetingEmail->MAEEmailAddr    = $USEmail;
                                    $meetingEmail->MAE_USCode    = $USCode;
                                    $meetingEmail->MAEPhoneNo    = $USPhoneNo;
                                    $meetingEmail->save();

                                }

                            }

                        }

                    }

                }

                if($request->meetingSingle && $request->meetingSingle == 1){
                    $route = route('perolehan.negoMeeting.list',['opm' => $MNo, 'id' => $request->tenderProposalNo]);

                }else{

                    $route = route('perolehan.mesyuarat.edit',[$MNo]);

                }
            }

            if($meetingType == 'MPTA'){

                $TMSCode = "I"; //default value; get from MSTenderMeetingStatus
                $mpta = $request->mpta;

                $currentEmail = array();


                foreach($mpta as $mptaNo){

                    $meeting = new MeetingPTA();
                    $meeting->MPTA_MNo         = $MNo;
                    $meeting->MPTA_PTDNo       = $mptaNo;
                    $meeting->MPTA_MSCode      = $TMSCode;
                    $meeting->MPTACB           = $user->USCode;
                    $meeting->MPTAMB           = $user->USCode;
                    $meeting->save();

                    $projectTender = ProjectTender::where('PTDNo',$mptaNo)->first();
                    $projectTender->PTD_PTSCode = 'EM3';
                    $projectTender->save();

                    if($projectTender->projectTenderDept){

                        foreach($projectTender->projectTenderDept as $projectTenderDept){

                            if($projectTenderDept->department){

                                if($projectTenderDept->department->user){

                                    $user = $projectTenderDept->department->user;

                                    $USEmail = $user->USEmail;
                                    $USCode = $user->USCode;
                                    $USPhoneNo = $user->USPhoneNo;

                                    if(!in_array($USEmail, $currentEmail)){

                                        $meetingEmail = new MeetingEmail();
                                        $meetingEmail->MAE_MNo         = $MNo;
                                        $meetingEmail->MAEEmailAddr    = $USEmail;
                                        $meetingEmail->MAE_USCode    = $USCode;
                                        $meetingEmail->MAEPhoneNo    = $USPhoneNo;
                                        $meetingEmail->save();
                                    }

                                    array_push($currentEmail,$USEmail);

                                }

                            }

                        }

                    }

                }

                $route = route('perolehan.mesyuarat.edit',[$MNo]);
            }

            if($meetingType == 'MPTE1'){

                $TMSCode = "I"; //default value; get from MSTenderMeetingStatus
                $mpte1s = $request->mpte1;

                $currentEmail = array();

                foreach($mpte1s as $mpte1No){

                    $meeting = new MeetingPTE1();
                    $meeting->MPTE1_MNo         = $MNo;
                    $meeting->MPTE1_PTDNo       = $mpte1No;
                    $meeting->MPTE1_MSCode      = $TMSCode;
                    $meeting->MPTE1CB           = $user->USCode;
                    $meeting->MPTE1MB           = $user->USCode;
                    $meeting->save();

                    $projectTender = ProjectTender::where('PTDNo',$mpte1No)->first();
                    $projectTender->PTD_PTSCode = 'EM1';
                    $projectTender->save();

                    if($projectTender->projectTenderDept){

                        foreach($projectTender->projectTenderDept as $projectTenderDept){

                            if($projectTenderDept->department){

                                if($projectTenderDept->department->user){

                                    $user = $projectTenderDept->department->user;

                                    $USEmail = $user->USEmail;
                                    $USCode = $user->USCode;
                                    $USPhoneNo = $user->USPhoneNo;

                                    if(!in_array($USEmail, $currentEmail)){

                                        $meetingEmail = new MeetingEmail();
                                        $meetingEmail->MAE_MNo         = $MNo;
                                        $meetingEmail->MAEEmailAddr    = $USEmail;
                                        $meetingEmail->MAE_USCode    = $USCode;
                                        $meetingEmail->MAEPhoneNo    = $USPhoneNo;
                                        $meetingEmail->save();
                                    }

                                    array_push($currentEmail,$USEmail);

                                }

                            }

                        }

                    }

                }

                $route = route('perolehan.mesyuarat.edit',[$MNo]);
            }

            if($meetingType == 'MPTE2'){

                $TMSCode = "I"; //default value; get from MSTenderMeetingStatus
                $mpte2s = $request->mpte2;

                $currentEmail = array();

                foreach($mpte2s as $mpte2No){

                    $meeting = new MeetingPTE2();
                    $meeting->MPTE2_MNo         = $MNo;
                    $meeting->MPTE2_PTDNo       = $mpte2No;
                    $meeting->MPTE2_MSCode      = $TMSCode;
                    $meeting->MPTE2CB           = $user->USCode;
                    $meeting->MPTE2MB           = $user->USCode;
                    $meeting->save();

                    $projectTender = ProjectTender::where('PTDNo',$mpte2No)->first();
                    $projectTender->PTD_PTSCode = 'EM2';
                    $projectTender->save();

                    if($projectTender->projectTenderDept){

                        foreach($projectTender->projectTenderDept as $projectTenderDept){

                            if($projectTenderDept->department){

                                if($projectTenderDept->department->user){

                                    $user = $projectTenderDept->department->user;

                                    $USEmail = $user->USEmail;
                                    $USCode = $user->USCode;
                                    $USPhoneNo = $user->USPhoneNo;

                                    if(!in_array($USEmail, $currentEmail)){

                                        $meetingEmail = new MeetingEmail();
                                        $meetingEmail->MAE_MNo         = $MNo;
                                        $meetingEmail->MAEEmailAddr    = $USEmail;
                                        $meetingEmail->MAE_USCode    = $USCode;
                                        $meetingEmail->MAEPhoneNo    = $USPhoneNo;
                                        $meetingEmail->save();

                                    }

                                    array_push($currentEmail,$USEmail);

                                }

                            }

                        }

                    }

                }

                $route = route('perolehan.mesyuarat.edit',[$MNo]);
            }

            if($meetingType == 'MLI'){

                $TMSCode = "I"; //default value; get from MSTenderMeetingStatus
                $mli = $request->mli;

                $tenderProposal = TenderProposal::where('TPNo',$mli)->first();
                $tenderProposal->TP_TRPCode = 'LIM';
                $tenderProposal->save();

                $project = $tenderProposal->project;
                $project->PT_PPCode = 'LIM';
                $project->save();

                $letterIntent = $tenderProposal->letterIntent;

                $tender = $tenderProposal->tender;

                $currentEmail = array();

                $meeting = new MeetingLI();
                $meeting->MLI_MNo         = $MNo;
                $meeting->MLI_LINo       = $letterIntent->LINo;
                $meeting->MLI_MSCode      = $TMSCode;
                $meeting->MLI_TCCode      = $tender->TD_TCCode;
                $meeting->MLICB           = $user->USCode;
                $meeting->MLIMB           = $user->USCode;
                $meeting->save();

                if(!empty($tender->tenderPIC)){

                    foreach($tender->tenderPIC as $tenderPIC){

                        if(($tenderPIC->userPIC)){
                            $USEmail = $tenderPIC->userPIC->USEmail;
                            $USCode = $tenderPIC->userPIC->USCode;
                            $USPhoneNo = $tenderPIC->userPIC->USPhoneNo;

                            $currentMeetingEmail = MeetingEmail::where('MAE_MNo',$MNo)->get();

                            $exists = $currentMeetingEmail->contains('MAEEmailAddr',$USEmail);

                            if( !$exists ){

                                $meetingEmail = new MeetingEmail();
                                $meetingEmail->MAE_MNo         = $MNo;
                                $meetingEmail->MAEEmailAddr    = $USEmail;
                                $meetingEmail->MAE_USCode    = $USCode;
                                $meetingEmail->MAEPhoneNo    = $USPhoneNo;
                                $meetingEmail->save();

                            }

                        }

                    }

                }

                $route = route('perolehan.mesyuarat.edit',[$MNo]);
            }


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => $route,
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
        $crscode = $this->dropdownService->claimResultStatus();
        $meetingType = $this->dropdownService->meetingType();
        $departmentAll = $this->dropdownService->departmentAll();
        $meetingLocation = $this->dropdownService->meetingLocation();

        $fileAttachDownloadMN = FileAttach::where('FAFileType','MT-MN')->first();

        $meeting = Meeting::where('MNo',$id)->first();

        $tenderMeetingStatus = $this->dropdownService->MeetingStatus();
        $meeting_date = Carbon::parse($meeting->MDate)->format('Y-m-d');
        $meeting_time = Carbon::parse($meeting->MTime)->format('H:i');

        $meetingAttendanceLists = MeetingAttendanceList::where('MAL_MNo',$id)->get();

        $arrayMeetingType = [];

        foreach($meeting->meetingDet as $meetingDet){
            array_push($arrayMeetingType, $meetingDet->MD_MTCode);
        }


        //EOT
        $arrayMeetingEOT = [];

        foreach($meeting->meetingEOT as $meetingEOT){
            array_push($arrayMeetingEOT, $meetingEOT->ME_EOTNo);

        }

        $eot = $this->dropdownService->meetingEOTList($arrayMeetingEOT);

        //LIST EOT
        $dataEOTs = array();

        //VO
        $arrayMeetingVO = [];

        foreach($meeting->meetingVO as $meetingVO){
            array_push($arrayMeetingVO, $meetingVO->MV_VONo);

        }
        $vo = $this->dropdownService->meetingVOList($arrayMeetingVO);
        //LIST VO
        $dataVOs = array();

        //MPTA
        $arrayMeetingPTA = [];

        foreach($meeting->meetingPTA as $meetingPTA){
            array_push($arrayMeetingPTA, $meetingPTA->MPTA_PTDNo);

        }

        $mpta = ProjectTender::where(function ($query) {
            $query->whereIn('PTD_PTSCode',['EM2A','EM3S']);
        })
        ->orWhereIn('PTDNo', $arrayMeetingPTA)
        ->orderBy('PTDID','DESC')
        ->get()
        ->pluck('PTDTitle', 'PTDNo')
        ->map(function ($item, $key) {
            $ptd = ProjectTender::where('PTDNo', $key)->first();
            return $key . " ( " . $ptd->PTDTitle . " )";
        });
        //LIST Accept PTD
        $dataMPTA = array();

        //MPTE1
        $arrayMeetingPTE1 = [];

        foreach($meeting->meetingPTE1 as $meetingPTE1){
            array_push($arrayMeetingPTE1, $meetingPTE1->MPTE1_PTDNo);

        }

        $mpte1 = ProjectTender::where(function ($query) {
            $query->whereIn('PTD_PTSCode',['COMPLETE','EM1S']);
        })
        ->orWhereIn('PTDNo', $arrayMeetingPTE1)
        ->orderBy('PTDID','DESC')
        ->get()
        ->pluck('PTDTitle', 'PTDNo')
        ->map(function ($item, $key) {
            $ptd = ProjectTender::where('PTDNo', $key)->first();
            return $key . " ( " . $ptd->PTDTitle . " )";
        });

        //LIST MPTE1
        $dataMPTE1 = array();

        //MPTE2
        $arrayMeetingPTE2 = [];

        foreach($meeting->meetingPTE2 as $meetingPTE2){
            array_push($arrayMeetingPTE2, $meetingPTE2->MPTE2_PTDNo);

        }

        $mpte2 = ProjectTender::where(function ($query) {
            $query->whereIn('PTD_PTSCode',['COMPLETE','EM1A','EM2S']);
        })
        ->orWhereIn('PTDNo', $arrayMeetingPTE2)
        ->orderBy('PTDID','DESC')
        ->get()
        ->pluck('PTDTitle', 'PTDNo')
        ->map(function ($item, $key) {
            $ptd = ProjectTender::where('PTDNo', $key)->first();
            return $key . " ( " . $ptd->PTDTitle . " )";
        });

        //LIST MPTE2
        $dataMPTE2 = array();

        //MLI
        $arrayMeetingLI = $meeting->meetingLI->letterIntent->LI_TPNo ?? null;

        $mli = TenderProposal::where(function ($query) {
            $query->whereIn('TP_TRPCode',['LIA']);
        })
        ->whereHas('project')
        ->orWhere('TPNo', $arrayMeetingLI)
        ->orderBy('TPID','DESC')
        ->get()
        ->pluck('TP_TDNo', 'TPNo')
        ->map(function ($item, $key) {
            $tend = Tender::where('TDNo', $item)->first();
            return $key . " ( " . $tend->TDTitle . " )"; // Appending MACode to MA_MOFCode
        });

        //LIST MLI
        $dataMLI = array();

        //MNP
        $arrayMeetingNP = [];

        foreach($meeting->meetingNP as $meetingNP){
            array_push($arrayMeetingNP, $meetingNP->MNP_TPNo);

        }

        $mnp = TenderProposal::whereHas('project', function ($query) {
            $query->where('PT_PPCode','NP');
        })
        ->orWhereIn('TPNo', $arrayMeetingNP)
        ->get()
        ->pluck('TP_TDNo', 'TPNo')
        ->map(function ($item, $key) {
            $tend = Tender::where('TDNo', $item)->first();
            return $key . " ( " . $tend->TDTitle . " )"; // Appending Tender title
        });

        //LIST MNP
        $dataMNP = array();

        return view('perolehan.mesyuarat.edit',
            compact('meeting','meeting_date','meeting_time', 'meetingType', 'tenderMeetingStatus','fileAttachDownloadMN',
                'crscode','arrayMeetingType', 'meetingAttendanceLists','departmentAll','meetingLocation',
                'eot', 'dataEOTs','arrayMeetingEOT',
                'vo', 'dataVOs','arrayMeetingVO',
                'mpta', 'dataMPTA','arrayMeetingPTA',
                'mpte1', 'dataMPTE1','arrayMeetingPTE1',
                'mpte2', 'dataMPTE2','arrayMeetingPTE2',
                'mli', 'dataMLI','arrayMeetingLI',
                'mnp', 'dataMNP','arrayMeetingNP'
            )
        );
    }

    public function view($id){
        $crscode = $this->dropdownService->claimResultStatus();
        $meetingType = $this->dropdownService->meetingType();
        $departmentAll = $this->dropdownService->departmentAll();

        $meeting = Meeting::where('MNo',$id)->first();

        $tenderMeetingStatus = $this->dropdownService->meetingStatus();
        $meeting_date = Carbon::parse($meeting->MDate)->format('Y-m-d');
        $meeting_time = Carbon::parse($meeting->MTime)->format('H:i');

        $meetingAttendanceLists = MeetingAttendanceList::where('MAL_MNo',$id)->get();

        $arrayMeetingType = [];

        foreach($meeting->meetingDet as $meetingDet){
            array_push($arrayMeetingType, $meetingDet->MD_MTCode);
        }


        //EOT
        if($meeting->MStatus == 'NEW'){
            $eotStatus = ['ACCEPT'];
        }else{
            $eotStatus = ['ACCEPT', 'MEETING', 'APPROVE'];

        }
        $eot = ExtensionOfTime::whereIn('EOTStatus', $eotStatus)
        ->get()
            ->pluck('EOTDesc','EOTNo');

        // $eot = ExtensionOfTime::get()
        //     ->pluck('EOTDesc','EOTNo');

        $arrayMeetingEOT = [];

        foreach($meeting->meetingEOT as $meetingEOT){
            array_push($arrayMeetingEOT, $meetingEOT->ME_EOTNo);

            $eot2 = ExtensionOfTime::where('EOTNo', $meetingEOT->ME_EOTNo)
            ->get()
            ->pluck('EOTDesc','EOTNo');

            foreach ($eot2 as $key => $value) {
                $eot[$key] = $value;
            }

        }

        //LIST EOT
        $dataEOTs = array();

        if(in_array('EOT',$arrayMeetingType)){

            foreach($arrayMeetingEOT as $key => $meetingEOT){

                $dataEOT = ExtensionOfTime::where('EOTNo',$meetingEOT)
                    ->with('milestone')
                    ->first();

                $meetingEOT = MeetingEOT::where('ME_EOTNo',$meetingEOT)
                    ->where('ME_MNo',$id)
                    ->first();

                $projectMilestones = ProjectMilestone::where('PM_PTNo', $dataEOT->EOT_PTNo)
                    ->get()
                    ->pluck('PMDesc', 'PMNo');

                $dataEOTs[$key]['eot'] = $dataEOT;
                $dataEOTs[$key]['meetingEOT'] = $meetingEOT;
                $dataEOTs[$key]['choice'] = $projectMilestones;

            }

        }

        //VO
        $vo = $this->dropdownService->variantOrderEditList($meeting->MStatus);
        $arrayMeetingVO = [];

        foreach($meeting->meetingVO as $meetingVO){
            array_push($arrayMeetingVO, $meetingVO->MV_VONo);

        }
        //LIST VO
        $dataVOs = array();

        if(in_array('VO',$arrayMeetingType)){

            foreach($arrayMeetingVO as $key => $arrayMeetingVO){

                $dataVO = VariantOrder::where('VONo',$arrayMeetingVO)
                    ->first();

                $meetingVO = MeetingVO::where('MV_VONo',$arrayMeetingVO)
                    ->where('MV_MNo',$id)
                    ->first();

                if ($meetingVO !== null) {
                    $startDate = !empty($meetingVO->MVStartDate) ? Carbon::parse($meetingVO->MVStartDate)->format('Y-m-d') : null ;
                } else {
                    $startDate = null;
                }

                $dataVOs[$key]['vo'] = $dataVO;
                $dataVOs[$key]['meetingVO'] = $meetingVO;
                $dataVOs[$key]['startDate'] = $startDate;

            }

        }

        //Accept PTD
        $arrayMeetingPTA = [];

        foreach($meeting->meetingPTA as $meetingPTA){
            array_push($arrayMeetingPTA, $meetingPTA->MPTA_PTDNo);

        }

        $projectTender = $this->dropdownService->projectTenderC2($arrayMeetingPTA);
        $mpta = $projectTender;
        $arrayMeetingPTA = [];


        foreach($meeting->meetingPTA as $meetingPTA){
            array_push($arrayMeetingPTA, $meetingPTA->MPTA_PTDNo);

        }
        //LIST Accept PTD
        $dataMPTAs = array();

        if(in_array('MPTA',$arrayMeetingType)){

            foreach($arrayMeetingPTA as $key => $mptaNO){

                $dataPTD = ProjectTender::where('PTDNo',$mptaNO)->first();

                $meetingMPTA = MeetingPTA::where('MPTA_PTDNo',$mptaNO)
                ->where('MPTA_MNo',$id)
                ->first();

                $dataMPTAs[$key]['projectTender'] = $dataPTD;
                $dataMPTAs[$key]['meetingMPTA'] = $meetingMPTA;

            }

        }

        return view('perolehan.mesyuarat.edit',
            compact('meeting','meeting_date','meeting_time', 'meetingType', 'tenderMeetingStatus',
                'crscode','arrayMeetingType','meetingAttendanceLists','departmentAll',
                'eot', 'dataEOTs','arrayMeetingEOT',
                'vo', 'dataVOs','arrayMeetingVO',
                'mpta', 'dataMPTAs','arrayMeetingPTA'
            )
        );
    }

    public function editListEOT(Request $request){

        $crscode = $this->dropdownService->resultStatus();
        $meetingType = $this->dropdownService->meetingType();
        $tenderMeetingStatus = $this->dropdownService->meetingStatus();

        $id = $request->MNo;
        $dataEOTs = array();
        $arrayEOTs = $request->eot;

        foreach($arrayEOTs as $key => $eot){

            $dataEOT = ExtensionOfTime::where('EOTNo',$eot)
                ->with('milestone')
                ->first();

            if($dataEOT->EOType){
                $meetingEOT = MeetingEOT::where('ME_EOTNo',$eot)
                    ->where('ME_MNo',$id)
                    ->first();
            }
            else{
                $meetingEOT = MeetingEOT::where('ME_EOTNo',$eot)
                    ->first();
            }

            $projectMilestones = ProjectMilestone::where('PM_PTNo', $dataEOT->EOT_PTNo)
                ->get()
                ->pluck('PMDesc', 'PMNo');

            $totalSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $eot)->sum('EOTSTotalProposeAmt');

            $dataEOTs[$key]['eot'] = $dataEOT;
            $dataEOTs[$key]['meetingEOT'] = $meetingEOT;
            $dataEOTs[$key]['choice'] = $projectMilestones;
            $dataEOTs[$key]['totalspec'] = $totalSpec ?? 0;
        }

        return view('perolehan.mesyuarat.editList.editListEOT',
            compact('tenderMeetingStatus', 'eot', 'dataEOTs', 'arrayEOTs', 'crscode')
        );
    }

    public function editListVO(Request $request){

        $crscode = $this->dropdownService->resultStatus();
        $meetingType = $this->dropdownService->meetingType();
        $tenderMeetingStatus = $this->dropdownService->meetingStatus();

        $id = $request->MNo;
        $dataVOs = array();
        $arrayVOs = $request->vo;

        foreach($arrayVOs as $key => $vo){

            $dataVO = VariantOrder::where('VONo',$vo)
                ->first();

            $meetingVO = MeetingVO::where('MV_VONo',$vo)
                ->where('MV_MNo',$id)
                ->first();

            if ($meetingVO !== null) {
                $startDate = !empty($meetingVO->MVStartDate) ? Carbon::parse($meetingVO->MVStartDate)->format('Y-m-d') : null ;
            } else {
                $startDate = null;
            }

            $dataVOs[$key]['vo'] = $dataVO;
            $dataVOs[$key]['meetingVO'] = $meetingVO;
            $dataVOs[$key]['startDate'] = $startDate;

        }


        return view('perolehan.mesyuarat.editList.editListVO',
            compact(
                'crscode', 'meetingType', 'tenderMeetingStatus',
                'dataVOs', 'arrayVOs'
            )
        );
    }

    public function editListMPTA(Request $request){

        $crscode = $this->dropdownService->meetingResultStatus('PTA');
        $meetingType = $this->dropdownService->meetingType();
        $tenderMeetingStatus = $this->dropdownService->meetingStatus();
        $tccode = $this->dropdownService->tender_sebutharga();

        $id = $request->MNo;
        $data = array();
        $arrayMPTAs = $request->mpta;

        foreach($arrayMPTAs as $key => $mpta){

            $dataPTD = ProjectTender::where('PTDNo',$mpta)->first();

            $meetingMPTA = MeetingPTA::where('MPTA_PTDNo',$mpta)
            ->where('MPTA_MNo',$id)
            ->first();

            $dataMPTAs[$key]['projectTender'] = $dataPTD;
            $dataMPTAs[$key]['meetingMPTA'] = $meetingMPTA;

        }

        return view('perolehan.mesyuarat.editList.editListMPTA',
            compact(
                'crscode', 'meetingType', 'tenderMeetingStatus','tccode',
                'dataMPTAs', 'arrayMPTAs'
            )
        );
    }

    public function editListMPTE1(Request $request){

        $crscode = $this->dropdownService->meetingResultStatus('PTE1');
        $meetingType = $this->dropdownService->meetingType();
        $tenderMeetingStatus = $this->dropdownService->meetingStatus();
        $tccode = $this->dropdownService->tender_sebutharga();

        $id = $request->MNo;
        $dataMPTE1s = array();
        $arrayMPTE1s = $request->mpte1;

        foreach($arrayMPTE1s as $key => $mpte1){

            $dataPTD = ProjectTender::where('PTDNo',$mpte1)->first();

            $meetingMPTE1 = MeetingPTE1::where('MPTE1_PTDNo',$mpte1)
            ->where('MPTE1_MNo',$id)
            ->first();

            $dataMPTE1s[$key]['projectTender'] = $dataPTD;
            $dataMPTE1s[$key]['meetingMPTE1'] = $meetingMPTE1;

        }

        return view('perolehan.mesyuarat.editList.editListMPTE1',
            compact(
                'crscode', 'meetingType', 'tenderMeetingStatus','tccode',
                'dataMPTE1s', 'arrayMPTE1s'
            )
        );
    }

    public function editListMPTE2(Request $request){

        $crscode = $this->dropdownService->meetingResultStatus('PTE2');
        $meetingType = $this->dropdownService->meetingType();
        $tenderMeetingStatus = $this->dropdownService->meetingStatus();
        $tccode = $this->dropdownService->tender_sebutharga();

        $id = $request->MNo;
        $dataMPTE2s = array();
        $arrayMPTE2s = $request->mpte2;

        foreach($arrayMPTE2s as $key => $mpte2){

            $dataPTD = ProjectTender::where('PTDNo',$mpte2)->first();

            $meetingMPTE2 = MeetingPTE2::where('MPTE2_PTDNo',$mpte2)
            ->where('MPTE2_MNo',$id)
            ->first();

            $dataMPTE2s[$key]['projectTender'] = $dataPTD;
            $dataMPTE2s[$key]['meetingMPTE2'] = $meetingMPTE2;

        }

        return view('perolehan.mesyuarat.editList.editListMPTE2',
            compact(
                'crscode', 'meetingType', 'tenderMeetingStatus','tccode',
                'dataMPTE2s', 'arrayMPTE2s'
            )
        );
    }

    public function editListMLI(Request $request){

        $meetingType = $this->dropdownService->meetingType();
        $tenderMeetingStatus = $this->dropdownService->meetingStatus();
        $tccode = $this->dropdownService->tender_sebutharga();

        $id = $request->MNo;
        $dataMLIs = array();
        $arrayMLIs = $request->mli;

        $proposal = TenderProposal::where('TPNo',$arrayMLIs)->first();
        $project = $proposal->project;
        $letterIntent = $proposal->letterIntent;
        $meetingLI = MeetingLI::where('MLI_LINo',$letterIntent->LINo)
        ->where('MLI_MNo',$id)
        ->first();

        $dataMLIs['letterIntent'] = $letterIntent;
        $dataMLIs['project'] = $project;
        $dataMLIs['meetingLI'] = $meetingLI;

        return view('perolehan.mesyuarat.editList.editListMLI',
            compact(
                'meetingType', 'tenderMeetingStatus','tccode',
                'dataMLIs', 'arrayMLIs'
            )
        );
    }

    public function editListMNP(Request $request){

        $meetingType = $this->dropdownService->meetingType();
        $tenderMeetingStatus = $this->dropdownService->meetingStatus();
        $tccode = $this->dropdownService->tender_sebutharga();

        $id = $request->MNo;
        $dataMNPs = array();
        $arrayMNPs = $request->mnp;

        foreach($arrayMNPs as $key => $mnp){

            $proposal = TenderProposal::where('TPNo',$mnp)->first();
            $tender = $proposal->tender;
            $project = $proposal->project;
            $proposalAmt = $proposal->TPTotalAmt;
            $discAmt = 0;

            $meetingMNP = MeetingNP::where('MNP_TPNo',$mnp)
            ->where('MNP_MNo',$id)
            ->first();

            $findOldMNP = MeetingNP::where('MNP_TPNo',$mnp)
            ->where('MNPNegotiate',1)
            ->whereHas('meeting',function ($query) {
                $query->where('MStatus','SUBMIT');
            })
            ->orderBy('MNPMD')
            ->first();

            $findDoneMNP = MeetingNP::where('MNP_TPNo',$mnp)
            ->where('MNP_MSCode','D')
            ->whereHas('meeting',function ($query) {
                $query->where('MStatus','SUBMIT');
            })
            ->orderBy('MNPMD','DESC')
            ->first();

            if($findDoneMNP){
                $proposalAmt = $findDoneMNP->MNPFinalAmt;

            }
            elseif($meetingMNP){
                $proposalAmt = $meetingMNP->MNPFinalAmt;

            }
            elseif($findOldMNP){ //if exist
                $proposalAmt = $findOldMNP->MNPFinalAmt;

            }
            $discAmt = 0;

            $finalAmt = $proposalAmt - $discAmt;

            $dataMNPs[$key]['proposal'] = $proposal;
            $dataMNPs[$key]['project'] = $project;
            $dataMNPs[$key]['tender'] = $tender;
            $dataMNPs[$key]['meetingNP'] = $meetingMNP;
            $dataMNPs[$key]['finalAmt'] = $finalAmt;
            $dataMNPs[$key]['proposalAmt'] = $proposalAmt;
            $dataMNPs[$key]['discAmt'] = $discAmt;

        }

        return view('perolehan.mesyuarat.editList.editListMNP',
            compact(
                'meetingType', 'tenderMeetingStatus','tccode',
                'dataMNPs', 'arrayMNPs'
            )
        );
    }

    public function update(Request $request){
        $messages = [
            'meeting_title.required'   => 'Tajuk Mesyuarat diperlukan.',
            'meeting_date.required'   => 'Tarikh Mesyuarat diperlukan.',
            'meeting_time.required'   => 'Masa Mesyuarat diperlukan.',
            'meeting_location.required'   => 'Lokasi Mesyuarat diperlukan.',
        ];

        $validation = [
            'meeting_title' => 'required|string',
            'meeting_date' => 'required',
            'meeting_time' => 'required',
            'meeting_location' => 'required',

        ];

        $request->validate($validation, $messages);
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $updateConfirm = $request->updateConfirm;

            $MNo            = $request->MNo;
            $meeting_title  = $request->meeting_title;
            $meeting_date   = $request->meeting_date;
            $meeting_time   = $request->meeting_time;
            $meetingType    = $request->meetingType;
            $meeting_location    = $request->meeting_location;

            $meeting = Meeting::where('MNo',$MNo)->first();
            $meeting->MTitle  = $meeting_title;
            $meeting->MDate   = $meeting_date;
            $meeting->MTime   = $meeting_time;
            $meeting->M_LCCode   = $meeting_location;
            $meeting->MMB     = $user->USCode;
            $meeting->save();

            $meetingDet = MeetingDet::where('MD_MNo',$MNo)->first();
            $meetingDet->MD_MTCode  = $meetingType;
            $meetingDet->save();

            $route = route('perolehan.mesyuarat.edit', [$MNo]);


            if($updateConfirm == 1){

                if(!$meeting->fileAttach){

                    if (!$request->hasFile('meetingMinit')) {

                        $arrayGlobalMeeting = ['EOT','VO','MPTA','MPTE1','MPTE2','MLI','MNP'];

                        if(in_array($meetingType,$arrayGlobalMeeting)){

                            $route = route('perolehan.mesyuarat.edit',[$MNo]);

                        }
                        else if($meetingType == 'PTENDER'){
                            $ptdNo            = $request->ptdNo;
                            $route = route('pelaksana.projectTender.edit',[$ptdNo,'flag'=>3,'opm'=>$MNo]);

                        }

                        else if($meetingType == 'MNP'){
                            $meetingProposalNo            = $request->meetingProposalNo;
                            $route = route('perolehan.negoMeeting.list',['opm' => $MNo, 'id' => $meetingProposalNo]);

                        }

                        return response()->json([
                            'error' => 1,
                            'redirect' => $route,
                            'message' => 'Sila muat-naik minit mesyuarat terlebih dahulu sebelum menghantar maklumat mesyuarat.'
                        ],400);

                    }

                }

            }

            if ($request->hasFile('meetingMinit')) {

                $file = $request->file('meetingMinit');
                $fileType = 'MD';
                $refNo = $MNo;
                $this->saveFile($file,$fileType,$refNo);
            }

            if($meetingType == 'EOT'){
                $eots            = $request->eot;
                $this->updateEOT($request,$eots);
                $route = route('perolehan.mesyuarat.edit',[$MNo]);

            }

            else if($meetingType == 'VO'){
                $vo            = $request->vo;
                $this->updateVO($request,$vo);

                $route = route('perolehan.mesyuarat.edit',[$MNo]);
            }

            else if($meetingType == 'PTENDER'){
                $ptdNo            = $request->ptdNo;
                $route = route('pelaksana.projectTender.edit',[$ptdNo,'flag'=>3,'opm'=>$MNo]);

                if($updateConfirm == 1 && $request->meetingProposalStatus == 'I'){

                    return response()->json([
                        'error' => 1,
                        'redirect' => $route,
                        'message' => 'Sila kemaskini status mesyuarat yang masih "Belum Selesai".'
                    ],400);

                }

                $this->updateProjectTender($request,$ptdNo);

            }

            else if($meetingType == 'MNP'){
                $mnp            = $request->mnp;

                if($request->meetingSingle && $request->meetingSingle == 1){
                    $route = route('perolehan.negoMeeting.list',['opm' => $MNo, 'id' => $request->tenderProposalNo]);

                }else{

                    $route = route('perolehan.mesyuarat.edit',[$MNo]);

                }

                if($updateConfirm == 1 && $request->meetingProposalStatus == 'I'){

                    return response()->json([
                        'error' => 1,
                        'redirect' => $route,
                        'message' => 'Sila kemaskini status mesyuarat yang masih "Belum Selesai".'
                    ],400);

                }

                $result = $this->updateMeetingNP($request,$mnp);

            }

            else if($meetingType == 'MPTA'){
                $mpta            = $request->mpta;
                $route = route('perolehan.mesyuarat.edit',[$MNo]);

                if($updateConfirm == 1 && in_array('I',$request->meetingProposalStatus)){

                    return response()->json([
                        'error' => 1,
                        'redirect' => $route,
                        'message' => 'Sila kemaskini status mesyuarat yang masih "Belum Selesai".'
                    ],400);

                }
                $this->updateMeetingPTA($request,$mpta);
            }

            else if($meetingType == 'MPTE1'){
                $mpte1            = $request->mpte1;

                $route = route('perolehan.mesyuarat.edit',[$MNo]);

                if($updateConfirm == 1 && in_array('I',$request->meetingProposalStatus)){

                    return response()->json([
                        'error' => 1,
                        'redirect' => $route,
                        'message' => 'Sila kemaskini status mesyuarat yang masih "Belum Selesai".'
                    ],400);

                }
                $this->updateMeetingPTE1($request,$mpte1);
            }

            else if($meetingType == 'MPTE2'){
                $mpte2            = $request->mpte2;

                $route = route('perolehan.mesyuarat.edit',[$MNo]);

                if($updateConfirm == 1 && in_array('I',$request->meetingProposalStatus)){

                    return response()->json([
                        'error' => 1,
                        'redirect' => $route,
                        'message' => 'Sila kemaskini status mesyuarat yang masih "Belum Selesai".'
                    ],400);

                }

                $this->updateMeetingPTE2($request,$mpte2);
            }

            else if($meetingType == 'MLI'){
                $mli            = $request->mli;

                $route = route('perolehan.mesyuarat.edit',[$MNo]);

                $result = $this->updateMeetingLI($request,$mli);

            }

            $old_meetingAttendanceLists = MeetingAttendanceList::where('MAL_MNo', $MNo)->get();
            $MALIDs = $request->MALID;
            $MALNames = $request->MALName;
            $MALPositions = $request->MALPosition;
            $MAL_DPTCodes = $request->MAL_DPTCode;

            if(isset($MALIDs)){
                // Check if every element in the arrays has a value
                if ($this->areAllValuesSet($MALNames) && $this->areAllValuesSet($MALPositions) && $this->areAllValuesSet($MAL_DPTCodes)) {

                }else{
                    DB::rollback();

                    return response()->json([
                        'error' => '1',
                        'message' => 'Sila lengkapkan semua maklumat di dalam kehadiran.'
                    ], 400);

                }
            }else{
                $MALIDs = [];
            }

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_meetingAttendanceLists) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_meetingAttendanceLists as $omeetingAttendanceLists){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($MALIDs as $MALID){
                        if($omeetingAttendanceLists->MALID == $MALID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $omeetingAttendanceLists->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($MALIDs as $key => $MALID){

                    $new_meetingAttendanceList = MeetingAttendanceList::where('MAL_MNo', $MNo)
                        ->where('MALID', $MALID)->first();

                    if(!$new_meetingAttendanceList){
                        $new_meetingAttendanceList = new MeetingAttendanceList();
                        $new_meetingAttendanceList->MAL_MNo = $MNo;
                        $new_meetingAttendanceList->MALCB = $user->USCode;
                    }
                    $new_meetingAttendanceList->MALName = $MALNames[$key];;
                    $new_meetingAttendanceList->MALPosition = $MALPositions[$key];;
                    $new_meetingAttendanceList->MAL_DPTCode = $MAL_DPTCodes[$key];
                    $new_meetingAttendanceList->MALMB = $user->USCode;
                    $new_meetingAttendanceList->save();
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                }
            }
            else{
                if(isset($MALIDs)){
                    foreach($MALIDs as $key2 => $MALID){
                        $new_meetingAttendanceList = new MeetingAttendanceList();
                        $new_meetingAttendanceList->MAL_MNo = $MNo;
                        $new_meetingAttendanceList->MALName = $MALNames[$key2];
                        $new_meetingAttendanceList->MALPosition = $MALPositions[$key2];
                        $new_meetingAttendanceList->MAL_DPTCode = $MAL_DPTCodes[$key2];
                        $new_meetingAttendanceList->MALCB = $user->USCode;
                        $new_meetingAttendanceList->MALMB = $user->USCode;
                        $new_meetingAttendanceList->save();
                    }
                }
            }
            //END HERE

            if($updateConfirm == 1){
                $result = $this->updateStatus($request,$MNo,$meetingType);

            }else if($updateConfirm == 2){ //send invitation

                $meeting = Meeting::where('MNo',$MNo)->first();
                $meeting->MSSentInd  = 1;
                $meeting->MSSentDate  = Carbon::now();
                $meeting->MSSentBy  = $user->USCode;
                $meeting->save();

                // $result = $this->sendNotification($MNo,$meetingType,'S');

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => $route,
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

    public function updateStatus(Request $request, $id,$meetingType){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $meeting = Meeting::where('MNo',$id)->first();
            $meeting->MStatus = "SUBMIT";
            $meeting->save();

            $route = route('perolehan.mesyuarat.index');

            if($meetingType == 'EOT'){

                foreach ($meeting->meetingEOT  as $meetingEOT){

                    $dataEOT = ExtensionOfTime::where('EOTNo', $meetingEOT->ME_EOTNo)->first();

                    if($meetingEOT->ME_MSCode == 'D'){
                        if($meetingEOT->ME_CRSCode == 'P' || $meetingEOT->ME_CRSCode == 'T'){
                            $dataEOT->EOTStatus = 'APPROVE';
                            $dataEOT->EOT_EPCode = 'MTA';
                            $dataEOT->EOTAmount = $meetingEOT->MEAmount;

                            if($dataEOT->EOType == 'LD'){
                                if($meetingEOT->ME_PMNo != null){
                                    $old_milestones = ProjectMilestone::where('PM_PTNo', $dataEOT->EOT_PTNo)->get();

                                    $getLogSeq = ProjectMilestoneLog::where('PML_PTNo', $dataEOT->EOT_PTNo)
                                        ->orderBy('PMLSeq', 'DESC')->first();

                                    if($getLogSeq){
                                        $seq = $getLogSeq->PMLSeq+1;
                                    }
                                    else{
                                        $seq = 1;
                                    }

                                    foreach ($old_milestones as $old_milestone){
                                        $milestoneLog = new ProjectMilestoneLog();
                                        $milestoneLog->PML_EOTNo       = $dataEOT->EOTNo;
                                        $milestoneLog->PML_PMNo        = $old_milestone->PMNo;
                                        $milestoneLog->PMLSeq          = $seq;
                                        $milestoneLog->PML_PTNo        = $old_milestone->PM_PTNo;
                                        $milestoneLog->PMLDesc         = $old_milestone->PMDesc;
                                        $milestoneLog->PMLWorkDay      = $old_milestone->PMWorkDay;
                                        $milestoneLog->PMLStartDate    = $old_milestone->PMStartDate;
                                        $milestoneLog->PMLEndDate      = $old_milestone->PMEndDate;
                                        $milestoneLog->PMLComplete     = $old_milestone->PMComplete;
                                        $milestoneLog->PMLCompleteDate = $old_milestone->PMCompleteDate;
                                        $milestoneLog->PMLMeetingDate  = $old_milestone->PMCompleteDate;
                                        $milestoneLog->save();
                                    }

                                    $schedulerController = new SchedulerController(new DropdownService(), new AutoNumber());

                                    $schedulerController->calculateEOTMilestone($meetingEOT);
                                }
                            }
                            else{
                                $schedulerController = new SchedulerController(new DropdownService(), new AutoNumber());
                                $module = 'EOT';
                                $schedulerController->calculateNewEOTMilestone($meetingEOT, $module);

                            }
                        }
                        else if($meetingEOT->ME_CRSCode == 'R'){
                            $dataEOT->EOTStatus = 'REJECT';
                            $dataEOT->EOT_EPCode = 'MTR';
                        }
                        else{
                            $dataEOT->EOTStatus = 'ACCEPT';

                            if($dataEOT->EOTPaid == 1 ){
                                $dataEOT->EOT_EPCode = 'PA';

                            }else{
                                $dataEOT->EOT_EPCode = 'AJKA';
                            }
                            $dataEOT->save();
                        }
                    }
                    else{
                        $dataEOT->EOTStatus = 'ACCEPT';

                        if($dataEOT->EOTPaid == 1 ){
                            $dataEOT->EOT_EPCode = 'PA';

                        }else{
                            $dataEOT->EOT_EPCode = 'AJKA';
                        }
                    }

                    $dataEOT->save();

                    // $this->sendNotification($meeting,'S');
                }

            }
            else if($meetingType == 'VO'){

                foreach($meeting->meetingVO as $meetingVO){

                    $variantOrder = VariantOrder::where('VONo',$meetingVO->MV_VONo)->first();
                    if($meetingVO->MV_MSCode == 'D'){
                        $variantOrder->VOStatus = 'APPROVE';
                        $variantOrder->VO_VPCode = 'MTA';

                        if($variantOrder->VOWorkDay > 0 && $variantOrder->VOStartDate != null){
                            $schedulerController = new SchedulerController(new DropdownService(), new AutoNumber());
                            $module = 'VO';
                            $schedulerController->calculateNewEOTMilestone($meetingVO, $module);
                        }

                    }
                    else if($meetingVO->MV_MSCode == 'R'){
                        $variantOrder->VOStatus = 'REJECT';
                        $variantOrder->VO_VPCode = 'MTR';


                    }
                    else{
                        $variantOrder->VOStatus = 'ACCEPT';
                        $variantOrder->VO_VPCode = 'PA';


                    }
                    $variantOrder->save();


                }


            }
            else if($meetingType == 'MPTA'){

                foreach($meeting->meetingPTA as $meetingPTA){

                    $projectTender = ProjectTender::where('PTDNo',$meetingPTA->MPTA_PTDNo)->first();
                    $tccode = $meetingPTA->MPTA_TCCode;

                    if($meetingPTA->MPTA_MSCode == 'D'){

                        if($meetingPTA->MPTA_CRSCode == 'P'){
                            $status = 'EM3A';

                        }elseif($meetingPTA->MPTA_CRSCode == 'R'){
                            $status = 'EM3R';

                        }else{
                            $status = 'EM3V';

                        }

                    }else{
                        $status = 'EM2A';

                    }

                    $projectTender->PTD_PTSCode = $status;
                    $projectTender->save();

                }

            }
            else if($meetingType == 'MPTE1'){

                foreach($meeting->meetingPTE1 as $meetingPTE1){

                    $projectTender = ProjectTender::where('PTDNo',$meetingPTE1->MPTE1_PTDNo)->first();
                    $tccode = $meetingPTE1->MPTE1_TCCode;

                    if($meetingPTE1->MPTE1_MSCode == 'D'){

                        if($meetingPTE1->MPTE1_CRSCode == 'P'){
                            $status = 'EM1A';
                            $projectTender->PTD_TCCode = $tccode;

                        }elseif($meetingPTE1->MPTE1_CRSCode == 'R'){
                            $status = 'EM1R';

                        }elseif($meetingPTE1->MPTE1_CRSCode == 'V'){
                            $status = 'EM1V';

                        }

                    }else{
                        $status = 'COMPLETE';

                    }

                    $projectTender->PTD_PTSCode = $status;
                    $projectTender->save();

                }

            }
            else if($meetingType == 'MPTE2'){

                foreach($meeting->meetingPTE2 as $meetingPTE2){

                    $projectTender = ProjectTender::where('PTDNo',$meetingPTE2->MPTE2_PTDNo)->first();

                    if($meetingPTE2->MPTE2_MSCode == 'D'){

                        if($meetingPTE2->MPTE2_CRSCode == 'P'){
                            $status = 'EM2A';

                        }elseif($meetingPTE2->MPTE2_CRSCode == 'R'){
                            $status = 'EM2R';

                        }elseif($meetingPTE2->MPTE2_CRSCode == 'V'){
                            $status = 'EM2V';

                        }

                    }else{
                        $status = 'EM1A';

                    }

                    $projectTender->PTD_PTSCode = $status;
                    $projectTender->save();

                }

            }
            else if($meetingType == 'MLI'){

                if($meeting->meetingLI){

                    $meetingLI = $meeting->meetingLI;

                    $letterIntent = LetterIntent::where('LINo',$meetingLI->MLI_LINo)->first();
                    $tenderProposal = $letterIntent->tenderProposal;
                    $status = 'LIMS';

                    $tenderProposal->TP_TRPCode = $status;
                    $tenderProposal->save();

                    $project = $tenderProposal->project;
                    $statusPP = 'LIMS';

                    $project->PT_PPCode = $statusPP;
                    $project->save();

                }

            }
            else if($meetingType == 'MNP'){

                foreach($meeting->meetingNP as $meetingNP){

                    $project = Project::where('PTNo',$meetingNP->MNP_PTNo)->first();

                    if($meetingNP->MNP_MSCode == 'D'){

                        if($meetingNP->MNPNegotiate == 1){
                            $status = 'NP';

                        }else{
                            $status = 'NPS';

                        }

                    }else{
                        $status = 'NP';

                    }

                    $project->PT_PPCode = $status;
                    $project->save();

                }

                if($request->meetingSingle && $request->meetingSingle == 1){
                    $route = route('perolehan.negoMeeting.list',['opm' => $id, 'id' => $request->tenderProposalNo]);

                }

            }

            $this->sendNotification($id,$meetingType,'S');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => $route,
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

    public function updateEOT(Request $request,$datas){
        try{
            DB::beginTransaction();
            $user = Auth::user();

            $MNo                    = $request->MNo;
            $meetingProposalStatus  = $request->meetingProposalStatus;
            $EOTNo                  = $request->EOTNo;
            $crscode                = $request->crscode;
            $chooseMilestone        = $request->chooseMilestone;
            $workDay                = $request->workDay;
            $remark                 = $request->remark;
            $totalspec              = $request->totalspec;
            $startDates              = $request->startDate;

            if(!empty($datas)){

                $oldDatas = MeetingEOT::where('ME_MNo',$MNo)->get();

                foreach($datas as $key => $data){

                    $TMSCode = $meetingProposalStatus[$key];
                    $CRSCode = $crscode[$key];

                    $startDate = $startDates[$key];

                    $exists = $oldDatas->contains('ME_EOTNo', $data);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $meetingData = new MeetingEOT();
                        $meetingData->ME_MNo       = $MNo;
                        $meetingData->ME_EOTNo      = $data;
                        $meetingData->ME_MSCode    = $TMSCode;
                        $meetingData->ME_CRSCode    = $CRSCode;
                        $meetingData->ME_PMNo       = $chooseMilestone[$key];
                        $meetingData->MEWorkday     = $workDay[$key];
                        $meetingData->MERemark      = $remark[$key];
                        $meetingData->MEAmount      = $totalspec[$key];
                        $meetingData->MECB          = $user->USCode;
                        $meetingData->MEMB          = $user->USCode;
                        if(isset($startDate) && $startDate != 0) {
                            $meetingData->MEStartDate = $startDate;
                        }
                        $meetingData->save();

                    }else{

                        //UPDATE CURRENT DATA
                        $meetingData = MeetingEOT::where('ME_EOTNo',$data)
                            ->where('ME_MNo',$MNo)
                            ->first();

                        $meetingData->ME_MSCode    = $TMSCode;
                        $meetingData->ME_CRSCode    = $CRSCode;
                        $meetingData->ME_PMNo       = $chooseMilestone[$key];
                        $meetingData->MEWorkday     = $workDay[$key];
                        $meetingData->MERemark      = $remark[$key];
                        $meetingData->MEAmount      = $totalspec[$key];
                        $meetingData->MEMB          = $user->USCode;
                        if(isset($startDate) && $startDate != 0) {
                            $meetingData->MEStartDate = $startDate;
                        }

                        $meetingData->save();

                    }

                    $updateStatus = "MT";

                    $eot = ExtensionOfTime::where('EOTNo', $data)->first();
                    $eot->EOT_EPCode = $updateStatus;
                    $eot->save();

                }


                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->ME_EOTNo, $datas)) {

                        // DELETE
                        $EOTNo = $oldData->ME_EOTNo;

                        //UPDATE STATUS

                        $dataEOT = ExtensionOfTime::where('EOTNo', $EOTNo)->first();

                        if($dataEOT->EOTPaid == 1 ){
                            $dataEOT->EOT_EPCode = 'PA';

                        }else{
                            $dataEOT->EOT_EPCode = 'AJKA';
                        }
                        $dataEOT->save();

                        $oldData->delete();

                    }

                }

            }
            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function updateVO(Request $request,$datas){

        try{
            DB::beginTransaction();

            $user = Auth::user();

            $MNo                    = $request->MNo;
            $meetingProposalStatus  = $request->meetingProposalStatus;
            $EOTNo                  = $request->EOTNo;
            $crscode                = $request->crscode;
            $startDate              = $request->startDate;
            $workDay                = $request->workDay;
            $remark                 = $request->remark;

            if(!empty($datas)){

                $oldDatas = MeetingVO::where('MV_MNo',$MNo)->get();

                foreach($datas as $key => $data){

                    $TMSCode = $meetingProposalStatus[$key];
                    $CRSCode = $crscode[$key];


                    $exists = $oldDatas->contains('MV_VONo', $data);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $meetingData = new MeetingVO();
                        $meetingData->MV_MNo       = $MNo;
                        $meetingData->MV_VONo       = $data;
                        $meetingData->MV_MSCode    = $TMSCode;
                        $meetingData->MV_CRSCode    = $CRSCode;
                        $meetingData->MVStartDate   = $startDate[$key];
                        $meetingData->MVWorkday     = $workDay[$key];
                        $meetingData->MVRemark      = $remark[$key];
                        $meetingData->MVCB          = $user->USCode;
                        $meetingData->MVMB          = $user->USCode;
                        $meetingData->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $meetingData = MeetingVO::where('MV_VONo',$data)
                            ->where('MV_MNo',$MNo)
                            ->first();

                        $meetingData->MV_MSCode    = $TMSCode;
                        $meetingData->MV_CRSCode    = $CRSCode;
                        $meetingData->MVStartDate   = $startDate[$key];
                        $meetingData->MVWorkday     = $workDay[$key];
                        $meetingData->MVRemark      = $remark[$key];
                        $meetingData->MVMB          = $user->USCode;
                        $meetingData->save();

                    }

                    $updateStatus = "MT"; // set the project claim process to REVIEW

                    $variantOrder = VariantOrder::where('VONo', $data)->first();
                    $variantOrder->VO_VPCode = $updateStatus;
                    $variantOrder->save();

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->MV_VONo, $datas)) {

                        // DELETE
                        $VONo = $oldData->MV_VONo;

                        //UPDATE STATUS
                        $updateStatus = "PA"; // set the project claim process to REVIEW

                        $variantOrder = VariantOrder::where('VONo', $VONo)->first();
                        $variantOrder->VO_VPCode = $updateStatus;
                        $variantOrder->save();

                        $oldData->delete();

                    }

                }

            }

            DB::commit();


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function updateProjectTender(Request $request,$datas){

        try{
            DB::beginTransaction();

            $user = Auth::user();

            $MNo                    = $request->MNo;
            $meetingProposalStatus  = $request->meetingProposalStatus;
            $ptdNo                  = $request->ptdNo;
            $crscode                = $request->crscode;
            $remark                 = $request->remark;

            if(!empty($datas)){

                $meeting = MeetingPT::where('MPT_MNo',$MNo)
                    ->where('MPT_PTDNo', $ptdNo)
                    ->first();
                $meeting->MPT_MSCode     = $meetingProposalStatus;
                // $meeting->MPT_CRSCode     = $crscode;
                $meeting->MPTRemark       = $remark;
                $meeting->MPTMB           = $user->USCode;
                $meeting->save();

            }



            DB::commit();


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function updateMeetingNP(Request $request,$datas){

        try{
            DB::beginTransaction();

            $user = Auth::user();

            $MNo                    = $request->MNo;
            $meetingProposalStatus  = $request->meetingProposalStatus;
            $mnp                    = $request->mnp;
            $proposalAmts            = $request->proposalAmt;
            $discAmts                = $request->discAmt;
            $finalAmts               = $request->finalAmt;
            $negos                   = $request->hiddenNego ?? null;
            $remarks                 = $request->remark;

            if(!empty($datas)){

                $oldDatas = MeetingNP::where('MNP_MNo',$MNo)->get();
                foreach($datas as $key => $data){

                    $TMSCode     = $meetingProposalStatus[$key];
                    $mnpNo       = $mnp[$key];
                    $remark      = $remarks[$key];
                    $nego        = $negos[$key] ?? null;
                    $finalAmt    = $finalAmts[$key];
                    $discAmt     = $discAmts[$key];
                    $proposalAmt = $proposalAmts[$key];

                    $exists = $oldDatas->contains('MNP_TPNo', $data);

                    if (!$exists) {
                        //INSERT NEW DATA

                        $tenderProposal = TenderProposal::where('TPNo',$data)->first();
                        $project = $tenderProposal->project;
                        $meetingProposal = $tenderProposal->meetingProposal;

                        $meeting = new MeetingNP();
                        $meeting->MNP_MNo         = $MNo;
                        $meeting->MNP_MSCode      = $TMSCode;
                        $meeting->MNPProposalAmt  = $proposalAmt;
                        $meeting->MNPDiscAmt      = $discAmt;
                        $meeting->MNPFinalAmt     = $finalAmt;
                        $meeting->MNPRemark       = $remark;
                        $meeting->MNPNegotiate    = $nego;
                        $meeting->MNP_TPNo        = $mnpNo;
                        $meeting->MNP_PTNo        = $project->PTNo;
                        $meeting->MNP_BMPNo       = $meetingProposal->BMPNo;
                        $meeting->MNPCB           = $user->USCode;
                        $meeting->MNPMB           = $user->USCode;
                        $meeting->save();

                        $project->PT_PPCode = 'NPM';
                        $project->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $meeting = MeetingNP::where('MNP_TPNo',$data)
                            ->where('MNP_MNo',$MNo)
                            ->first();

                        $meeting->MNP_MSCode      = $TMSCode;
                        $meeting->MNPProposalAmt  = $proposalAmt;
                        $meeting->MNPDiscAmt      = $discAmt;
                        $meeting->MNPFinalAmt     = $finalAmt;
                        $meeting->MNPNegotiate    = $nego;
                        $meeting->MNPRemark       = $remark;
                        $meeting->save();

                    }


                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->MNP_TPNo, $datas)) {

                        // DELETE
                        $oldPTNo = $oldData->MNP_PTNo;

                        $oldProject = Project::where('PTNo',$oldPTNo)->first();
                        $oldProject->PT_PPCode = 'NP';
                        $oldProject->save();

                        $oldData->delete();

                    }

                }

            }

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function updateMeetingPTA(Request $request,$datas){


        try{
            DB::beginTransaction();

            $user = Auth::user();

            $MNo                    = $request->MNo;
            $meetingProposalStatus  = $request->meetingProposalStatus;
            $mpta                   = $request->mpta;
            $crscode                = $request->crscode;
            $remarks                 = $request->remark;
            $tccodes                 = $request->tccode;

            if(!empty($datas)){

                $oldDatas = MeetingPTA::where('MPTA_MNo',$MNo)->get();
                foreach($datas as $key => $data){

                    $TMSCode = $meetingProposalStatus[$key];
                    $CRSCode = $crscode[$key];
                    $mptaNo = $mpta[$key];
                    $remark = $remarks[$key];
                    $tccode = $tccodes[$key];

                    $exists = $oldDatas->contains('MPTA_PTDNo', $data);

                    if (!$exists) {
                        //INSERT NEW DATA

                        $meeting = new MeetingPTA();
                        $meeting->MPTA_MNo         = $MNo;
                        $meeting->MPTA_PTDNo       = $data;
                        $meeting->MPTA_MSCode      = $TMSCode;
                        $meeting->MPTA_CRSCode     = $CRSCode;
                        $meeting->MPTA_TCCode      = $tccode;
                        $meeting->MPTARemark       = $remark;
                        $meeting->MPTACB           = $user->USCode;
                        $meeting->MPTAMB           = $user->USCode;
                        $meeting->save();

                        $projectTender = ProjectTender::where('PTDNo',$data)->first();
                        $projectTender->PTD_PTSCode = 'EM3';
                        $projectTender->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $meeting = MeetingPTA::where('MPTA_PTDNo',$data)
                            ->where('MPTA_MNo',$MNo)
                            ->first();

                        $meeting->MPTA_PTDNo       = $data;
                        $meeting->MPTA_MSCode      = $TMSCode;
                        $meeting->MPTA_CRSCode     = $CRSCode;
                        $meeting->MPTA_TCCode      = $tccode;
                        $meeting->MPTARemark       = $remark;
                        $meeting->MPTAMB           = $user->USCode;
                        $meeting->save();

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->MPTA_PTDNo, $datas)) {

                        // DELETE
                        $oldPTDNo = $oldData->MPTA_PTDNo;

                        $oldProjectTender = ProjectTender::where('PTDNo',$oldPTDNo)->first();
                        $oldProjectTender->PTD_PTSCode = 'EM2A';
                        $oldProjectTender->save();

                        $oldData->delete();

                    }

                }

            }

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function updateMeetingPTE1(Request $request,$datas){


        try{
            DB::beginTransaction();

            $user = Auth::user();

            $MNo                    = $request->MNo;
            $meetingProposalStatus  = $request->meetingProposalStatus;
            $mpte1                   = $request->mpte1;
            $crscode                = $request->crscode;
            $remarks                 = $request->remark;
            $tccodes                 = $request->tccode;

            if(!empty($datas)){

                $oldDatas = MeetingPTE1::where('MPTE1_MNo',$MNo)->get();
                foreach($datas as $key => $data){

                    $TMSCode = $meetingProposalStatus[$key];
                    $CRSCode = $crscode[$key];
                    $mpte1No = $mpte1[$key];
                    $remark = $remarks[$key];
                    $tccode = $tccodes[$key];

                    $exists = $oldDatas->contains('MPTE1_PTDNo', $data);

                    if (!$exists) {
                        //INSERT NEW DATA

                        $meeting = new MeetingPTE1();
                        $meeting->MPTE1_MNo         = $MNo;
                        $meeting->MPTE1_PTDNo       = $data;
                        $meeting->MPTE1_MSCode      = $TMSCode;
                        $meeting->MPTE1_CRSCode     = $CRSCode;
                        $meeting->MPTE1_TCCode      = $tccode;
                        $meeting->MPTE1Remark       = $remark;
                        $meeting->MPTE1CB           = $user->USCode;
                        $meeting->MPTE1MB           = $user->USCode;
                        $meeting->save();

                        $projectTender = ProjectTender::where('PTDNo',$data)->first();
                        $projectTender->PTD_PTSCode = 'EM1';
                        $projectTender->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $meeting = MeetingPTE1::where('MPTE1_PTDNo',$data)
                            ->where('MPTE1_MNo',$MNo)
                            ->first();

                        $meeting->MPTE1_PTDNo       = $data;
                        $meeting->MPTE1_MSCode      = $TMSCode;
                        $meeting->MPTE1_CRSCode     = $CRSCode;
                        $meeting->MPTE1_TCCode      = $tccode;
                        $meeting->MPTE1Remark       = $remark;
                        $meeting->MPTE1MB           = $user->USCode;
                        $meeting->save();

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->MPTE1_PTDNo, $datas)) {

                        // DELETE
                        $oldPTDNo = $oldData->MPTE1_PTDNo;

                        $oldProjectTender = ProjectTender::where('PTDNo',$oldPTDNo)->first();
                        $oldProjectTender->PTD_PTSCode = 'COMPLETE';
                        $oldProjectTender->save();

                        $oldData->delete();

                    }

                }

            }

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function updateMeetingPTE2(Request $request,$datas){


        try{
            DB::beginTransaction();

            $user = Auth::user();

            $MNo                    = $request->MNo;
            $meetingProposalStatus  = $request->meetingProposalStatus;
            $mpte2                   = $request->mpte2;
            $crscode                = $request->crscode;
            $remarks                 = $request->remark;

            if(!empty($datas)){

                $oldDatas = MeetingPTE2::where('MPTE2_MNo',$MNo)->get();
                foreach($datas as $key => $data){

                    $TMSCode = $meetingProposalStatus[$key];
                    $CRSCode = $crscode[$key];
                    $mpte2No = $mpte2[$key];
                    $remark = $remarks[$key];

                    $exists = $oldDatas->contains('MPTE2_PTDNo', $data);

                    if (!$exists) {
                        //INSERT NEW DATA

                        $meeting = new MeetingPTE2();
                        $meeting->MPTE2_MNo         = $MNo;
                        $meeting->MPTE2_PTDNo       = $data;
                        $meeting->MPTE2_MSCode      = $TMSCode;
                        $meeting->MPTE2_CRSCode     = $CRSCode;
                        $meeting->MPTE2Remark       = $remark;
                        $meeting->MPTE2CB           = $user->USCode;
                        $meeting->MPTE2MB           = $user->USCode;
                        $meeting->save();

                        $projectTender = ProjectTender::where('PTDNo',$data)->first();
                        $projectTender->PTD_PTSCode = 'EM2';
                        $projectTender->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $meeting = MeetingPTE2::where('MPTE2_PTDNo',$data)
                            ->where('MPTE2_MNo',$MNo)
                            ->first();

                        $meeting->MPTE2_PTDNo       = $data;
                        $meeting->MPTE2_MSCode      = $TMSCode;
                        $meeting->MPTE2_CRSCode     = $CRSCode;
                        $meeting->MPTE2Remark       = $remark;
                        $meeting->MPTE2MB           = $user->USCode;
                        $meeting->save();

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->MPTE2_PTDNo, $datas)) {

                        // DELETE
                        $oldPTDNo = $oldData->MPTE2_PTDNo;

                        $oldProjectTender = ProjectTender::where('PTDNo',$oldPTDNo)->first();
                        $oldProjectTender->PTD_PTSCode = 'EM1A';
                        $oldProjectTender->save();

                        $oldData->delete();

                    }

                }

            }

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function updateMeetingLI(Request $request,$datas){


        try{
            DB::beginTransaction();

            $user = Auth::user();

            $MNo                    = $request->MNo;
            $mli                   = $request->mli;
            $remark                 = $request->remark;

            if( isset($mli) ){

                $proposalNo = $mli;

                $letterIntent = LetterIntent::where('LI_TPNo',$proposalNo)->first();

                $meetingLI = MeetingLI::where('MLI_MNo',$MNo)->first();

                if(isset($meetingLI)){

                    $oldLINo = $meetingLI->MLI_LINo;

                    if($oldLINo != $letterIntent->LINo){
                        $meetingLI->MLI_LINo = $letterIntent->LINo;

                        $oldLI = LetterIntent::where('LINo',$oldLINo)->first();
                        $oldproject = $oldLI->tenderProposal->project;
                        $oldproject->PT_PPCode = 'LIA';
                        $oldproject->save();
                    }
                    $meetingLI->MLIRemark      = $remark;
                    $meetingLI->save();
                }else{

                    $currentEmail = array();
                    $tenderProposal = $letterIntent->tenderProposal;
                    $tender = $tenderProposal->tender;

                    $meeting = new MeetingLI();
                    $meeting->MLI_MNo         = $MNo;
                    $meeting->MLI_LINo       = $letterIntent->LINo;
                    $meeting->MLI_MSCode      = 'I';
                    $meeting->MLI_TCCode      = $tender->TD_TCCode;
                    $meeting->MLIRemark      = $remark;
                    $meeting->MLICB           = $user->USCode;
                    $meeting->MLIMB           = $user->USCode;
                    $meeting->save();

                    if(!empty($tender->tenderPIC)){

                        foreach($tender->tenderPIC as $tenderPIC){

                            if(($tenderPIC->userPIC)){
                                $USEmail = $tenderPIC->userPIC->USEmail;
                                $USCode = $tenderPIC->userPIC->USCode;
                                $USPhoneNo = $tenderPIC->userPIC->USPhoneNo;

                                $currentMeetingEmail = MeetingEmail::where('MAE_MNo',$MNo)->get();

                                $exists = $currentMeetingEmail->contains('MAEEmailAddr',$USEmail);

                                if( !$exists ){

                                    $meetingEmail = new MeetingEmail();
                                    $meetingEmail->MAE_MNo         = $MNo;
                                    $meetingEmail->MAEEmailAddr    = $USEmail;
                                    $meetingEmail->MAEPhoneNo    = $USPhoneNo;
                                    $meetingEmail->MAE_USCode    = $USCode;
                                    $meetingEmail->save();
                                }

                            }

                        }

                    }

                }

                $project = $letterIntent->tenderProposal->project;
                $project->PT_PPCode = 'LIM';
                $project->save();

            }
            else{

                $meetingLI = MeetingLI::where('MLI_MNo',$MNo)->first();

                if($meetingLI){

                    $oldLINo = $meetingLI->MLI_LINo;
                    $meetingLI->MLI_LINo = $letterIntent->LINo;

                    $oldLI = LetterIntent::where('LINo',$oldLINo)->first();

                    $oldProposal = $oldLI->tenderProposal;
                    $oldProposal->TP_TRPCode = 'LIA';
                    $oldProposal->save();

                    $oldproject = $oldLI->tenderProposal->project;
                    $oldproject->PT_PPCode = 'LIA';
                    $oldproject->save();

                    $meetingLI->delete();
                }

            }

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function getMeetingEmailList(Request $request){

        $meetingNo      = $request->meetingNo;
        $meetingType    = $request->meetingType;
        $meetingURL     = $request->meetingURL;
        $meetingStatus  = "";

        if($meetingType == 'M'){
            $meetingEmails = MeetingEmail::where('MAE_MNo',$meetingNo)->with('meeting')->get();
            $meetingStatus = $meetingEmails[0]->meeting->MStatus ?? null;
        }
        elseif($meetingType == 'BM'){
            $meetingEmails = BoardMeetingEmail::where('BME_BMNo',$meetingNo)->with('boardMeeting')->get();
            $meetingStatus = $meetingEmails[0]->boardMeeting->BMStatus ?? null;
        }
        elseif($meetingType == 'CM'){
            $meetingEmails = ClaimMeetingEmail::where('CME_CMNo',$meetingNo)->with('claimMeeting')->get();
            $meetingStatus = $meetingEmails[0]->claimMeeting->CMStatus ?? null;
        }
        elseif($meetingType == 'KOM'){
            $meetingEmails = KickOffMeetingEmail::where('KOME_KOMNo',$meetingNo)->with('kickoffMeeting')->get();
            $meetingStatus = $meetingEmails[0]->kickoffMeeting->KOMStatus ?? null;
        }

        return view('perolehan.mesyuarat.listMeetingEmail',
            compact('meetingEmails','meetingType','meetingStatus'
            )
        );

    }

    public function updateMeetingEmailList(Request $request){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $meetingEmailController = new MeetingEmailController();

            $meetingNo      = $request->meetingNo;
            $meetingType    = $request->meetingType;
            $meetingURL     = $request->meetingURL;
            $datas          = $request->meetingEmailID;
            $emails         = $request->email;

            $route = route('perolehan.mesyuarat.edit',[$meetingNo]);

            if($meetingType == 'M'){

                $meetingMain = Meeting::where('MNo',$meetingNo)->first();
                $meetingMain->MSSentInd  = 1;
                $meetingMain->MSSentDate  = Carbon::now();
                $meetingMain->MSSentBy  = $user->USCode;
                $meetingMain->save();

                $oldDatas = MeetingEmail::where('MAE_MNo',$meetingNo)->get();

                foreach($datas as $key => $data){

                    $email = $emails[$key];

                    if($email){

                        $exists = $oldDatas->contains('MAEID', $data);

                        if (!$exists) {
                            //INSERT NEW DATA

                            $meeting = new MeetingEmail();
                            $meeting->MAE_MNo         = $meetingNo;
                            $meeting->MAEEmailAddr    = $email;
                            $meeting->save();

                        }else{
                            //UPDATE CURRENT DATA
                            $meeting = MeetingEmail::where('MAEID',$data)
                                ->where('MAE_MNo',$meetingNo)
                                ->first();

                            $meeting->MAEEmailAddr    = $email;
                            $meeting->save();

                        }

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->MAEID, $datas)) {

                        // DELETE
                        $oldData->delete();

                    }

                }

                $meetingDet = MeetingDet::where('MD_MNo',$meetingNo)->first();
                $meetingType2 = $meetingDet->MD_MTCode;

                if($meetingType2 == 'PTENDER'){
                    $meetingPT = MeetingPT::where('MPT_MNo',$meetingNo)->first();
                    $ptdNo = $meetingPT->MPT_PTDNo;
                    $route = route('pelaksana.projectTender.edit',[$ptdNo,'flag'=>3,'opm'=>$meetingNo]);
                }
                else if($meetingType2 == 'MNP'){
                    $meetingNP = MeetingNP::where('MNP_MNo',$meetingNo)->get();
                    if($meetingURL != 0){

                        $route = route('perolehan.negoMeeting.list',['opm' => $meetingNo, 'id' => $meetingNP[0]->MNP_TPNo]);

                    }
                }
                else if($meetingType2 == 'MEA'){
                    $meetingEA = MeetingEOTAJK::where('MEA_MNo',$meetingNo)->get();
                        $route = route('pelaksana.meeting.ajk.edit',[$meetingNo]);
                }
                else if($meetingType2 == 'MVA'){
                    $meetingVA = MeetingVOAJK::where('MVA_MNo',$meetingNo)->get();

                    $route = route('pelaksana.meeting.ajk.edit',[$meetingNo]);

                }

            }
            else if($meetingType == 'BM'){

                $route = route('perolehan.meeting.edit',[$meetingNo]);

                $boardMeeting = BoardMeeting::where('BMNo',$meetingNo)->first();
                $boardMeeting->BMSentInd = 1;
                $boardMeeting->save();

                $oldDatas = BoardMeetingEmail::where('BME_BMNo',$meetingNo)->get();

                foreach($datas as $key => $data){

                    $email = $emails[$key];

                    $exists = $oldDatas->contains('BMEID', $data);

                    if (!$exists) {
                        //INSERT NEW DATA

                        $meeting = new BoardMeetingEmail();
                        $meeting->BME_BMNo         = $meetingNo;
                        $meeting->BMEEmailAddr    = $email;
                        $meeting->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $meeting = BoardMeetingEmail::where('BMEID',$data)
                            ->where('BME_BMNo',$meetingNo)
                            ->first();

                        $meeting->BMEEmailAddr    = $email;
                        $meeting->save();

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->BMEID, $datas)) {

                        // DELETE
                        $oldData->delete();

                    }

                }

                $meetingType2 = 'BM';

            }
            else if($meetingType == 'CM'){

                $route = route('pelaksana.meeting.edit',[$meetingNo]);

                $meetingClaim = ClaimMeeting::where('CMNo',$meetingNo)->first();
                $meetingClaim->CMSentInd  = 1;
                $meetingClaim->save();

                $oldDatas = ClaimMeetingEmail::where('CME_CMNo',$meetingNo)->get();

                foreach($datas as $key => $data){

                    $email = $emails[$key];

                    $exists = $oldDatas->contains('CMEID', $data);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $meeting = new ClaimMeetingEmail();
                        $meeting->CME_CMNo         = $meetingNo;
                        $meeting->CMEEmailAddr    = $email;
                        $meeting->CMEPhoneNo    = $whatsapp;
                        $meeting->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $meeting = ClaimMeetingEmail::where('CMEID',$data)
                            ->where('CME_CMNo',$meetingNo)
                            ->first();

                        $meeting->CMEEmailAddr    = $email;
                        $meeting->save();

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->CMEID, $datas)) {

                        // DELETE
                        $oldData->delete();

                    }

                }
                $meetingType2 = 'CM';

            }
            else if($meetingType == 'KOM'){

                $route = route('pelaksana.meeting.kickoff.edit',[$meetingNo]);

                $meetingKOM = KickOffMeeting::where('KOMNo',$meetingNo)->first();
                $meetingKOM->KOMSentInd  = 1;
                $meetingKOM->save();

                $oldDatas = KickOffMeetingEmail::where('KOME_KOMNo',$meetingNo)->get();

                foreach($datas as $key => $data){

                    $email = $emails[$key];

                    $exists = $oldDatas->contains('KOMEID', $data);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $meeting = new KickOffMeetingEmail();
                        $meeting->KOME_KOMNo         = $meetingNo;
                        $meeting->KOMEEmailAddr    = $email;
                        $meeting->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $meeting = KickOffMeetingEmail::where('KOMEID',$data)
                            ->where('KOME_KOMNo',$meetingNo)
                            ->first();

                        $meeting->KOMEEmailAddr    = $email;
                        $meeting->save();

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->KOMEID, $datas)) {

                        // DELETE
                        $oldData->delete();

                    }

                }
                $meetingType2 = 'KOM';

            }

            $resultEmail = $meetingEmailController->blastEmail($meetingNo,$meetingType); //send email
            $result2 = $this->sendNotification($meetingNo,$meetingType2);
            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => $route,
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

    public function getPTDMeetingInfo(Request $request, $id){

        $meetingLocation = $this->dropdownService->meetingLocation();

        $PTDNo = $id;
        $type = $request->type;
        $meetingPTs = MeetingPT::where('MPT_PTDNo',$id)
                    ->whereHas('meeting', function ($query) {
                        $query->where('MStatus', 'SUBMIT');
                    })
                    ->orderBy('MPTID','DESC')
                    ->with('meeting')->get();

        $meetingPTE1s = MeetingPTE1::where('MPTE1_PTDNo',$id)->orderBy('MPTE1ID','DESC')
                    ->whereHas('meeting', function ($query) {
                        $query->where('MStatus', 'SUBMIT');
                    })
                    ->with('meeting')->get();

        $meetingPTE2s = MeetingPTE2::where('MPTE2_PTDNo',$id)->orderBy('MPTE2ID','DESC')
                    ->whereHas('meeting', function ($query) {
                        $query->where('MStatus', 'SUBMIT');
                    })
                    ->with('meeting')->get();

        $meetingPTAs = MeetingPTA::where('MPTA_PTDNo',$id)
                    ->whereHas('meeting', function ($query) {
                        $query->where('MStatus', 'SUBMIT');
                    })
                    ->orderBy('MPTAID','DESC')->with('meeting')->get();

        return view('perolehan.mesyuarat.listPTDMeeting',
            compact('meetingPTs','meetingPTE1s','meetingPTE2s','meetingPTAs'
            ,'PTDNo','meetingLocation'
            )
        );


    }

    function sendNotification($meetingID,$meetingType){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $notification = new GeneralNotificationController();

            if(in_array($meetingType, ['EOT','VO','PTENDER','MNP','MPTA','MLI'])){

                $meeting = Meeting::where('MNo',$meetingID)->first();

                $meetingDate =  Carbon::parse($meeting->MDate)->format('d/m/Y');
                $meetingTime =  Carbon::parse($meeting->MTime)->format('h:i A');

                if($meeting->MStatus == 'SUBMIT'){
                    $statusCode = 'S';
                }else if($meeting->MStatus == 'NEW'){
                    $statusCode = 'N';
                }

            }

            $output = array();

            // if($meetingType == 'EOT'){

            //     $meetingEOT = $meeting->meetingEOT;
            //     foreach($meetingEOT as $meot){

            //         $extendOfTime = $meot->extendOfTime;
            //         $projectNo = $extendOfTime->EOT_PTNo;
            //         $project = Project::where('PTNo', $projectNo)->first();

            //         if($statusCode == 'N'){
            //             //#NOTIF-037
            //             $code = 'EOT-MT';

            //         }elseif ($statusCode == 'S') {

            //             $meetingStatus = $meot->ME_MSCode;

            //             if($meetingStatus != 'I'){

            //                 if($meetingStatus == 'D'){ //done
            //                     $code = "EOT-RS"; //#NOTIF-038

            //                 }else if($meetingStatus == 'P'){ //postpone
            //                     $code = "EOT-P"; //#NOTIF-038a

            //                 }else if($meetingStatus == 'C'){ //cancel
            //                     $code = "EOT-C"; //#NOTIF-038b

            //                 }

            //             }
            //         }

            //         //SEND NOTIFICATION TO PIC - PELAKSANA
            //         $tender = $project->tenderProposal->tender;
            //         $tenderPIC = $tender->tenderPIC;

            //         $data = array(
            //             'MT' => $meeting,
            //             'EOT' => $extendOfTime,
            //         );

            //         if(!empty($tenderPIC)){

            //             foreach($tenderPIC as $pic){


            //                 if(isset($pic->userSV) && !empty($pic->userSV)){ //boss among the PIC

            //                     $sv = $pic->userSV;
            //                     $refNo = $sv->USCode;
            //                     $notiType = "SO";
            //                     $result = $notification->sendNotification($refNo,$notiType,$code,$data);

            //                 }else{


            //                     if($pic->TPICType == 'T'){
            //                         $notiType = "SO";

            //                         $refNo = $pic->TPIC_USCode;
            //                         $result = $notification->sendNotification($refNo,$notiType,$code,$data);

            //                     }else if($pic->TPICType == 'P'){
            //                         $notiType = "PO";
            //                         $refNo = $pic->TPIC_USCode;
            //                         $result = $notification->sendNotification($refNo,$notiType,$code,$data);

            //                     }

            //                 }
            //             }

            //         }

            //         if($statusCode == 'S'){

            //             $meetingStatus = $meot->ME_MSCode;

            //             if($meetingStatus == 'D'){

            //                 $status = $meot->ME_CRSCode;
            //                 if($status == 'R'){ //TIDAK LULUS
            //                     //#NOTIF-038
            //                     $code = 'EOT-RS';

            //                 }elseif($status == 'P'){ //LULUS

            //                     $title = "Mesyuarat Permohonnan Lanjutan Masa ($meot->ME_EOTNo) telah diumumkan.";
            //                     $desc = "Tahniah, mesyuarat permohonan lanjutan masa bagi projek, $meot->ME_EOTNo anda telah diluluskan.";

            //                 }

            //                 //SEND NOTIFICATION TO CONTRACTOR
            //                 $contractorNo = $extendOfTime->project->PT_CONo;
            //                 $contractorType = 'CO';

            //                 $notification = new Notification();
            //                 $notification->NO_RefCode = $contractorNo;
            //                 $notification->NOType = $contractorType;
            //                 $notification->NO_NTCode = $notiType;
            //                 $notification->NOTitle = $title;
            //                 $notification->NODescription = $desc;
            //                 $notification->NORead = 0;
            //                 $notification->NOSent = 1;
            //                 $notification->NOActive = 1;
            //                 $notification->NOCB = $user->USCode;
            //                 $notification->NOMB = $user->USCode;
            //                 $notification->save();

            //             }

            //         }

            //     }

            // }
            // else if($meetingType == 'VO'){


            //     $meetingVO = $meeting->meetingVO;
            //     foreach($meetingVO as $mvo){

            //         $variantOrder = VariantOrder::where('VONo',$mvo->MV_VONo)->first();
            //         $projectNo = $variantOrder->VO_PTNo;
            //         $project = Project::where('PTNo', $projectNo)->first();

            //         if($statusCode == 'N'){
            //             //#NOTIF-040
            //             $title = "Mesyuarat Perubahan Kerja (VO) telah diumumkan - $meeting->MNo";
            //             $desc = "Perhatian, mesyuarat Perubahan Kerja (VO) ($meeting->MNo) akan dijalankan pada tarikh $meetingDate , jam $meetingTime.";

            //         }elseif ($statusCode == 'S') {
            //             //#NOTIF-041
            //             $title = "Mesyuarat Perubahan Kerja (VO) telah tamat - $meeting->MNo";
            //             $desc = "Perhatian, mesyuarat Perubahan Kerja (VO) ($meeting->MNo) telah membuat keputusan didalam mesyuarat.";
            //         }

            //         //SEND NOTIFICATION TO PIC - PELAKSANA
            //         $tender = $project->tenderProposal->tender;
            //         $tenderPICT = $tender->tenderPIC_T;
            //         $tenderPIC = $tender->tenderPIC;
            //         $pelaksanaType = "SO";
            //         $notiType = 'BM-VO';


            //         if(!empty($tenderPICT)){

            //             foreach($tenderPICT as $pict){

            //                 $notification = new Notification();
            //                 $notification->NO_RefCode = $pict->TPIC_USCode;
            //                 $notification->NOType = $pelaksanaType;
            //                 $notification->NO_NTCode = $notiType;
            //                 $notification->NOTitle = $title;
            //                 $notification->NODescription = $desc;
            //                 $notification->NORead = 0;
            //                 $notification->NOSent = 1;
            //                 $notification->NOActive = 1;
            //                 $notification->NOCB = $user->USCode;
            //                 $notification->NOMB = $user->USCode;
            //                 $notification->save();
            //             }

            //         }

            //         //SEND NOTIFICATION TO BOSS
            //         if(!empty($tenderPIC) && isset($tenderPIC->userSV) && !empty($tenderPIC->userSV)){

            //             foreach($tenderPIC->userSV as $sv){

            //                 $notification = new Notification();
            //                 $notification->NO_RefCode = $sv->USCode;
            //                 $notification->NOType = $pelaksanaType;
            //                 $notification->NO_NTCode = $notiType;
            //                 $notification->NOTitle = $title;
            //                 $notification->NODescription = $desc;
            //                 $notification->NORead = 0;
            //                 $notification->NOSent = 1;
            //                 $notification->NOActive = 1;
            //                 $notification->NOCB = $user->USCode;
            //                 $notification->NOMB = $user->USCode;
            //                 $notification->save();
            //             }

            //         }

            //         if($statusCode == 'S'){
            //             $meetingStatus = $mvo->MV_MSCode;
            //             if($meetingStatus == 'D'){

            //                 $status = $mvo->MV_CRSCode;

            //                 //##NOTIF-039
            //                 if($status == 'T'){ //LULUS BERSYARAT

            //                     $title = "Mesyuarat Perubahan Kerja (VO) ($meot->ME_EOTNo) telah diumumkan.";
            //                     $desc = "Tahniah, mesyuarat Perubahan Kerja (VO) bagi projek, $meot->ME_EOTNo anda telah dilulus dengan bersyarat.";

            //                 }elseif($status == 'R'){ //TIDAK LULUS

            //                     $title = "Mesyuarat Perubahan Kerja (VO) ($meot->ME_EOTNo) telah diumumkan.";
            //                     $desc = "Perhatian, mesyuarat Perubahan Kerja (VO) bagi projek, $meot->ME_EOTNo anda telah ditolak.";

            //                 }elseif($status == 'P'){ //LULUS

            //                     $title = "Mesyuarat Perubahan Kerja (VO) ($meot->ME_EOTNo) telah diumumkan.";
            //                     $desc = "Tahniah, mesyuarat Perubahan Kerja (VO) bagi projek, $meot->ME_EOTNo anda telah diluluskan.";

            //                 }

            //                 //SEND NOTIFICATION TO CONTRACTOR
            //                 $contractorNo = $variantOrder->project->PT_CONo;
            //                 $contractorType = 'CO';

            //                 $notification = new Notification();
            //                 $notification->NO_RefCode = $contractorNo;
            //                 $notification->NOType = $contractorType;
            //                 $notification->NO_NTCode = $notiType;
            //                 $notification->NOTitle = $title;
            //                 $notification->NODescription = $desc;
            //                 $notification->NORead = 0;
            //                 $notification->NOSent = 1;
            //                 $notification->NOActive = 1;
            //                 $notification->NOCB = $user->USCode;
            //                 $notification->NOMB = $user->USCode;
            //                 $notification->save();

            //             }

            //         }

            //     }


            // }
            // else if($meetingType == 'PTENDER'){
            //     $meetingPT = $meeting->meetingPT;
            //     $projectTenderNo = $meetingPT->MPT_PTDNo;
            //     $projectTender = ProjectTender::where('PTDNo',$projectTenderNo)->first();
            //     $projectTenderDept = $projectTender->projectTenderDept;


            //     if($statusCode == 'N'){
            //         //#NOTIF-044
            //         $title = "Mesyuarat Projek Tender telah diumumkan - $meeting->MNo";
            //         $desc = "Perhatian, mesyuarat Projek Tender ($meeting->MNo) akan dijalankan pada tarikh $meetingDate , jam $meetingTime.";

            //     }elseif ($statusCode == 'S') {
            //         //#NOTIF-045
            //         $title = "Mesyuarat Projek Tender telah tamat - $meeting->MNo";
            //         $desc = "Perhatian, mesyuarat Projek Tender ($meeting->MNo) telah membuat keputusan didalam mesyuarat.";
            //     }

            //     //SEND NOTIFICATION TO PIC
            //     $notiType = 'BM-PTD';

            //     $texts = "";

            //     foreach($projectTenderDept as $tenderDept){

            //         $settingNotiDept = $tenderDept->settingNotiDept;
            //         if($settingNotiDept){

            //             $userPIC = $settingNotiDept->userPIC;
            //             if($userPIC){

            //                 $pelaksanaType = $userPIC->USType;

            //                 $notification = new Notification();
            //                 $notification->NO_RefCode = $pict->TPIC_USCode;
            //                 $notification->NOType = $pelaksanaType;
            //                 $notification->NO_NTCode = $notiType;
            //                 $notification->NOTitle = $title;
            //                 $notification->NODescription = $desc;
            //                 $notification->NORead = 0;
            //                 $notification->NOSent = 1;
            //                 $notification->NOActive = 1;
            //                 $notification->NOCB = $user->USCode;
            //                 $notification->NOMB = $user->USCode;
            //                 $notification->save();

            //             }

            //         }


            //     }
            // }
            // else if($meetingType == 'MNP'){

            //     $meetingNP = $meeting->meetingNP;
            //     $meetingProposal = BoardMeetingProposal::where('BMPNo',$meetingNP->MNP_BMPNo)->first();

            //     $contractor = $meetingProposal->tenderProposal->tenderApplication->contractor;
            //     $tender = $meetingProposal->tenderProposal->tender;

            //     if($statusCode == 'N'){
            //         //#NOTIF-042
            //         $title = "Mesyuarat Rundingan Harga telah diumumkan - $meeting->MNo";
            //         $desc = "Perhatian, mesyuarat Rundingan Harga ($meeting->MNo) akan dijalankan pada tarikh $meetingDate , jam $meetingTime.";

            //     }elseif ($statusCode == 'S') {
            //         //#NOTIF-043
            //         $title = "Mesyuarat Rundingan Harga telah tamat - $meeting->MNo";
            //         $desc = "Perhatian, mesyuarat Rundingan Harga ($meeting->MNo) telah membuat keputusan didalam mesyuarat.";
            //     }


            //     $tenderPICT = $tender->tenderPIC_T;
            //     $tenderPIC = $tender->tenderPIC;
            //     $pelaksanaType = "SO";
            //     $notiType = 'BM-NP';



            //     if(!empty($tenderPICT)){

            //         foreach($tenderPICT as $pict){

            //             $notification = new Notification();
            //             $notification->NO_RefCode = $pict->TPIC_USCode;
            //             $notification->NOType = $pelaksanaType;
            //             $notification->NO_NTCode = $notiType;
            //             $notification->NOTitle = $title;
            //             $notification->NODescription = $desc;
            //             $notification->NORead = 0;
            //             $notification->NOSent = 1;
            //             $notification->NOActive = 1;
            //             $notification->NOCB = $user->USCode;
            //             $notification->NOMB = $user->USCode;
            //             $notification->save();
            //         }

            //     }

            //     //SEND NOTIFICATION TO BOSS
            //     if(!empty($tenderPIC) && isset($tenderPIC->userSV) && !empty($tenderPIC->userSV)){

            //         foreach($tenderPIC->userSV as $sv){

            //             $notification = new Notification();
            //             $notification->NO_RefCode = $sv->USCode;
            //             $notification->NOType = $pelaksanaType;
            //             $notification->NO_NTCode = $notiType;
            //             $notification->NOTitle = $title;
            //             $notification->NODescription = $desc;
            //             $notification->NORead = 0;
            //             $notification->NOSent = 1;
            //             $notification->NOActive = 1;
            //             $notification->NOCB = $user->USCode;
            //             $notification->NOMB = $user->USCode;
            //             $notification->save();
            //         }

            //     }


            //     if($statusCode == 'S'){
            //         $meetingStatus = $meetingNP->MNP_MSCode;

            //         if($meetingStatus == 'D'){

            //             $title = "Mesyuarat Rundingan Harga (". $meetingProposal->BMP_TPNo .") telah diumumkan.";
            //             $desc = "Tahniah, mesyuarat Rundingan Harga bagi tender cadangan, ". $meetingProposal->BMP_TPNo ." anda telah diluluskan.";

            //             //SEND NOTIFICATION TO CONTRACTOR
            //             $contractorNo = $contractor->CONo;
            //             $contractorType = 'CO';

            //             $notification = new Notification();
            //             $notification->NO_RefCode = $contractorNo;
            //             $notification->NOType = $contractorType;
            //             $notification->NO_NTCode = $notiType;
            //             $notification->NOTitle = $title;
            //             $notification->NODescription = $desc;
            //             $notification->NORead = 0;
            //             $notification->NOSent = 1;
            //             $notification->NOActive = 1;
            //             $notification->NOCB = $user->USCode;
            //             $notification->NOMB = $user->USCode;
            //             $notification->save();

            //         }

            //     }
            // }
            // else if($meetingType == 'MPTA'){

            //     $meetingPTA = $meeting->meetingPTA;
            //     $projectTenderDept = $meetingPTA->projectTender->projectTenderDept;

            //     if(!empty($projectTenderDept)){

            //         foreach($projectTenderDept as $index => $department){

            //             $data = array(
            //                 'MT' => $meeting,
            //                 'PTDNo' => $department->PTDD_PTDNo,
            //             );

            //             $settingNoti = $department->settingNotiDept;

            //             if(isset($settingNoti->userPIC)){

            //                 $meetingStatus = $meetingPTA->MPTA_MSCode;
            //                 $projectTenderStatus = $meetingPTA->MPTA_CRSCode;

            //                 if($statusCode == 'N'){
            //                     //#NOTIF-046
            //                     $code = "PTD-MT";

            //                 }elseif ($statusCode == 'S') {

            //                     if($meetingStatus == 'D'){ //done

            //                         if($projectTenderStatus == 'P'){ //LULUS
            //                             $code = "PTD-P"; //#NOTIF-047a

            //                         }else if($projectTenderStatus == 'R'){ //TIDAK LULUS
            //                             $code = "PTD-R"; //#NOTIF-047b

            //                         }

            //                     }else if($meetingStatus == 'C'){ //cancel
            //                         $code = "PTD-C"; //#NOTIF-047c

            //                     }else if($meetingStatus == 'P'){ //postpone
            //                         $code = "PTD-D"; //#NOTIF-047d

            //                     }
            //                 }

            //                 $USCode = $settingNoti->SND_USCode;
            //                 $notiType = "DP"; //HOLD FOR DEFINE OTHER SUITABLE NOTYPE

            //                 $refNo = $USCode;
            //                 $result = $notification->sendNotification($refNo,$notiType,$code,$data);

            //                 array_push($output,$result);

            //             }

            //         }



            //     }
            // }

            if($meetingType == 'BM'){

                $boardMeeting = BoardMeeting::where('BMNo',$meetingID)->first();
                $meetingNo = $boardMeeting->BMNo;
                $meetingTender = $boardMeeting->meetingTender;
                $meetingProposal = $boardMeeting->meetingProposal;

                if($boardMeeting->BMStatus == 'SUBMIT'){
                    $statusCode = 'S';
                }else if($boardMeeting->BMStatus == 'NEW'){
                    $statusCode = 'N';
                }

                if($statusCode == 'N'){

                    if(isset($boardMeeting->meetingTender) && !empty($meetingTender) ){

                        foreach($meetingTender as $mtender){

                            $tenderNo = $mtender->BMT_TDNo;

                            $tender = Tender::where('TDNo',$tenderNo)->first();

                            $data = array(
                                'BM' => $boardMeeting,
                            );

                            $code2 = 'TD-BM'; //#NOTIF-024

                            //SEND NOTIFICATION TO PIC
                            $tenderPIC = $tender->tenderPIC;

                            if(!empty($tenderPIC)){

                                foreach($tenderPIC as $pic){

                                    if($pic->TPICType == 'T'){
                                        $notiType = "SO";

                                    }else if($pic->TPICType == 'K'){
                                        $notiType = "FO";

                                    }else if($pic->TPICType == 'P'){
                                        $notiType = "PO";

                                    }

                                    $refNo = $pic->TPIC_USCode;
                                    $result = $notification->sendNotification($refNo,$notiType,$code2,$data);
                                }

                            }

                        }

                    }

                }
                else if($statusCode == 'S'){

                    if(isset($boardMeeting->meetingTender) && !empty($meetingTender) ){

                        foreach($meetingTender as $mtender){

                            $tenderNo = $mtender->BMT_TDNo;
                            $meetingStatus = $mtender->BMT_TMSCode;

                            $tender = Tender::where('TDNo',$tenderNo)->first();

                            $data = array(
                                'BM' => $boardMeeting,
                                'TD' => $tender,
                            );

                            // I - Belum Selesai, C - dibatalkan, P - ditangguhkan, D - selesai

                            if($meetingStatus == 'D'){ //done x
                                $code2 = "TD-TR"; //#NOTIF-025a

                            }else if($meetingStatus == 'M'){ //postponex
                                $code2 = "TD-TRM"; //#NOTIF-025b

                            }else if($meetingStatus == 'P'){ //postpone
                                $code2 = "TD-TRD"; //#NOTIF-025c

                            }else if($meetingStatus == 'C'){ //cancel
                                $code2 = "TD-TRC"; //#NOTIF-025d

                            }

                            //SEND NOTIFICATION TO PIC
                            $tenderPIC = $tender->tenderPIC;

                            if(!empty($tenderPIC)){

                                foreach($tenderPIC as $pic){

                                    if($pic->TPICType == 'T'){
                                        $notiType = "SO";

                                    }else if($pic->TPICType == 'K'){
                                        $notiType = "FO";

                                    }else if($pic->TPICType == 'P'){
                                        $notiType = "PO";

                                    }

                                    $refNo = $pic->TPIC_USCode;
                                    $result = $notification->sendNotification($refNo,$notiType,$code2,$data);
                                }

                            }

                        }

                        if($meetingStatus == 'D' || $meetingStatus == 'M'){

                            //SENT IF THE MEETING SELESAI
                            if(isset($boardMeeting->meetingProposal) && !empty($meetingProposal) ){

                                foreach($meetingProposal as $mProposal){

                                    $proposalNo = $mProposal->BMP_TPNo;
                                    $winner = $mProposal->BMPWinner;

                                    $tenderProposal = TenderProposal::where('TPNo',$proposalNo)->first();
                                    $tender = $tenderProposal->tender;

                                    $data = array(
                                        'BM' => $boardMeeting,
                                        'TD' => $tender,
                                    );

                                    // I - Belum Selesai, C - dibatalkan, P - ditangguhkan, D - selesai

                                    if($winner == ""){ //reject
                                        $code2 = "TD-TRR"; //#NOTIF-026a

                                    }else if($winner !== "" && $meetingStatus == 'M'){ //approve w terms
                                        $code2 = "TD-TRPM"; //#NOTIF-026b

                                    }else if($winner !== "" && $meetingStatus == 'D'){ //approve
                                        $code2 = "TD-TRP"; //#NOTIF-026c

                                    }

                                    //SEND NOTIFICATION TO PIC
                                    $tenderPIC_P = $tender->tenderPIC_P;

                                    if(!empty($tenderPIC_P)){

                                        foreach($tenderPIC_P as $pic){

                                            $notiType = "PO";

                                            $refNo = $pic->TPIC_USCode;
                                            $result = $notification->sendNotification($refNo,$notiType,$code2,$data);
                                        }

                                    }

                                }

                            }


                        }

                    }

                }

            }
            else if($meetingType == 'CM'){

                $claimMeeting = ClaimMeeting::where('CMNo',$meetingID)->with('meetingDetail')
                ->first();

                if($claimMeeting->CMStatus == 'SUBMIT'){
                    $statusCode = 'S';
                }else if($claimMeeting->CMStatus == 'NEW'){
                    $statusCode = 'N';
                }

                $projects = array();

                $claimMeetingDet = $claimMeeting->meetingDetail;

                foreach($claimMeetingDet as $meetingDet){

                    $PCNo = $meetingDet->CMD_PCNo;

                    $projectClaim = ProjectClaim::where('PCNo', $PCNo)->first();

                    $project = $projectClaim->projectMilestone->project;
                    array_push($projects, $project);

                }

                $claimMeetingDet = $claimMeeting->meetingDetail;

                foreach($projects as $project){
                    $tender = $project->tenderProposal->tender;

                    $data = array(
                        'CM' => $claimMeeting,
                        'PTNo' => $project->PTNo,
                    );

                    if($statusCode == 'N'){
                        //#NOTIF-010
                        $code = 'PC-MT';

                    }elseif ($statusCode == 'S') {
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

                        }
                        $result = $notification->sendNotification($refNo,$notiType,$code,$data);

                    }

                }

                if($statusCode == 'S') {

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

                        }


                    }

                }



            }
            else if($meetingType == 'KOM'){

                $kickoff = KickOffMeeting::where('KOMNo',$meetingID)->first();

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

                if($kickoff->KOMStatus == 'NEW'){
                    //#NOTIF-004
                    $code = 'KOM-E';


                }elseif ($kickoff->KOMStatus  == 'SUBMIT') {
                    //#NOTIF-005
                    $code = 'KOM-C';
                }

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

    public function sendMailNotification($meetingID,$meetingType){

        try{
            DB::beginTransaction();

            $user = Auth::user();

            $meeting = Meeting::where('MNo',$meetingID)->first();

            $meetingDate =  Carbon::parse($meeting->MDate)->format('d/m/Y');
            $meetingTime =  Carbon::parse($meeting->MTime)->format('h:i A');

            $meeting->meetingDate = $meetingDate;
            $meeting->meetingTime = $meetingTime;

            if($meetingType == 'EOT'){

                $meetingEOT = $meeting->meetingEOT;
                foreach($meetingEOT as $meot){

                    $extendOfTime = $meot->extendOfTime;
                    $projectNo = $extendOfTime->EOT_PTNo;
                    $project = Project::where('PTNo', $projectNo)->first();

                    //SEND NOTIFICATION TO PIC - PELAKSANA
                    //##NOTIF-MAIL-006
                    $tender = $project->tenderProposal->tender;
                    $tenderPICT = $tender->tenderPIC_T;
                    $tenderPIC = $tender->tenderPIC;
                    $notiType = 'BM-EOT';


                    if(!empty($tenderPIC)){

                        foreach($tenderPIC as $pic){

                            $usercode = $pic->userPIC->USCode;
                            $user= User::where('USCode',$usercode)->first();


                            if($pic->TPICType == 'T'){

                                $emailLog = new EmailLog();
                                $emailLog->ELCB 	= $user->USCode;
                                $emailLog->ELType 	= 'EOT Meeting';
                                $emailLog->ELSentTo =  $user->USEmail;

                                // Send Email
                                $tokenResult = $user->createToken('Personal Access Token');
                                $token = $tokenResult->token;

                                $emailData = array(
                                    'id' => $user->USID,
                                    'name'  => $user->USName ?? '',
                                    'email' => $user->USEmail,
                                    'meeting' => $meeting,
                                    'meetingType' => 'EOT',
                                    'data' => $tender
                                );

                                try {
                                    Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                        $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Permohonan Lanjutan Masa');
                                    });

                                    $emailLog->ELMessage = 'Success';
                                    $emailLog->ELSentStatus = 1;
                                } catch (\Exception $e) {
                                    $emailLog->ELMessage = $e->getMessage();
                                    $emailLog->ELSentStatus = 2;
                                }

                                $emailLog->save();


                            }elseif(isset($pic->userSV) && $pic->userSV !== null){

                                //SEND NOTIFICATION TO PIC - BOSS
                                //##NOTIF-MAIL-007
                                $emailLog = new EmailLog();
                                $emailLog->ELCB 	= $user->USCode;
                                $emailLog->ELType 	= 'EOT Meeting';
                                $emailLog->ELSentTo =  $user->USEmail;

                                // Send Email
                                $tokenResult = $user->createToken('Personal Access Token');
                                $token = $tokenResult->token;

                                $emailData = array(
                                    'id' => $user->USID,
                                    'name'  => $user->USName ?? '',
                                    'email' => $user->USEmail,
                                    'meeting' => $meeting,
                                    'meetingType' => 'EOT',
                                    'data' => $tender
                                );

                                try {
                                    Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                        $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Permohonan Lanjutan Masa');
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
            }
            else if($meetingType == 'VO'){

                $meetingVO = $meeting->meetingVO;
                foreach($meetingVO as $mvo){

                    $variantOrder = VariantOrder::where('VONo',$mvo->MV_VONo)->first();
                    $projectNo = $variantOrder->VO_PTNo;
                    $project = Project::where('PTNo', $projectNo)->first();

                    //SEND NOTIFICATION TO PIC - PELAKSANA
                    //##NOTIF-MAIL-008
                    $tender = $project->tenderProposal->tender;
                    $tenderPICT = $tender->tenderPIC_T;
                    $tenderPIC = $tender->tenderPIC;
                    $notiType = 'BM-VO';

                    if(!empty($tenderPIC)){

                        foreach($tenderPIC as $pic){

                            $usercode = $pic->userPIC->USCode;
                            $user= User::where('USCode',$usercode)->first();


                            if($pic->TPICType == 'T'){

                                $emailLog = new EmailLog();
                                $emailLog->ELCB 	= $user->USCode;
                                $emailLog->ELType 	= 'Variantion Order Meeting';
                                $emailLog->ELSentTo =  $user->USEmail;

                                // Send Email
                                $tokenResult = $user->createToken('Personal Access Token');
                                $token = $tokenResult->token;

                                $emailData = array(
                                    'id' => $user->USID,
                                    'name'  => $user->USName ?? '',
                                    'email' => $user->USEmail,
                                    'meeting' => $meeting,
                                    'meetingType' => 'VO',
                                    'data' => $tender
                                );

                                try {
                                    Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                        $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Perubahan Kerja (VO)');
                                    });

                                    $emailLog->ELMessage = 'Success';
                                    $emailLog->ELSentStatus = 1;
                                } catch (\Exception $e) {
                                    $emailLog->ELMessage = $e->getMessage();
                                    $emailLog->ELSentStatus = 2;
                                }

                                $emailLog->save();


                            }elseif(isset($pic->userSV) && $pic->userSV !== null){

                                //SEND NOTIFICATION TO PIC - BOSS
                                //##NOTIF-MAIL-009
                                $emailLog = new EmailLog();
                                $emailLog->ELCB 	= $user->USCode;
                                $emailLog->ELType 	= 'Variantion Order Meeting';
                                $emailLog->ELSentTo =  $user->USEmail;

                                // Send Email
                                $tokenResult = $user->createToken('Personal Access Token');
                                $token = $tokenResult->token;

                                $emailData = array(
                                    'id' => $user->USID,
                                    'name'  => $user->USName ?? '',
                                    'email' => $user->USEmail,
                                    'meeting' => $meeting,
                                    'meetingType' => 'VO',
                                    'data' => $tender
                                );

                                try {
                                    Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                        $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Perubahan Kerja (VO)');
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
            }
            else if($meetingType == 'PTENDER'){
                $meetingPT = $meeting->meetingPT;
                $projectTenderNo = $meetingPT->MPT_PTDNo;
                $projectTender = ProjectTender::where('PTDNo',$projectTenderNo)->first();
                $projectTenderDept = $projectTender->projectTenderDept;

                //SEND NOTIFICATION TO PIC
                //##NOTIF-MAIL-012

                foreach($projectTenderDept as $tenderDept){

                    $settingNotiDept = $tenderDept->settingNotiDept;
                    if($settingNotiDept){

                        $userPIC = $settingNotiDept->userPIC;
                        if($userPIC){

                            $emailLog = new EmailLog();
                            $emailLog->ELCB 	= $user->USCode;
                            $emailLog->ELType 	= 'Project Tender Meeting';
                            $emailLog->ELSentTo =  $user->USEmail;

                            // Send Email
                            $tokenResult = $user->createToken('Personal Access Token');
                            $token = $tokenResult->token;

                            $emailData = array(
                                'id' => $userPIC->USID,
                                'name'  => $userPIC->USName ?? '',
                                'email' => $userPIC->USEmail,
                                'meeting' => $meeting,
                                'meetingType' => 'PTD',
                                'data' => $tender
                            );

                            try {
                                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Projek Tender');
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
            else if($meetingType == 'MNP'){

                $meetingNP = $meeting->meetingNP;
                $meetingProposal = BoardMeetingProposal::where('BMPNo',$meetingNP->MNP_BMPNo)->first();

                $contractor = $meetingProposal->tenderProposal->tenderApplication->contractor;
                $tender = $meetingProposal->tenderProposal->tender;
                $tenderPIC = $tender->tenderPIC;


                $user= User::where('USCode',$contractor->CONo)->first();

                //SEND NOTIFICATION TO CONTRACTOR
                //##NOTIF-MAIL-010
                $emailLog = new EmailLog();
                $emailLog->ELCB 	= $user->USCode;
                $emailLog->ELType 	= 'Meeting NP';
                $emailLog->ELSentTo =  $contractor->COEmail;

                // Send Email
                $tokenResult = $user->createToken('Personal Access Token');
                $token = $tokenResult->token;

                $emailData = array(
                    'id' => $user->USID,
                    'name'  => $user->USName ?? '',
                    'email' => $user->USEmail,
                    'meetingType' => 'NP',
                    'data' => $tender
                );

                try {
                    Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                        $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Mesyuarat Rundingan Harga');
                    });

                    $emailLog->ELMessage = 'Success';
                    $emailLog->ELSentStatus = 1;
                } catch (\Exception $e) {
                    $emailLog->ELMessage = $e->getMessage();
                    $emailLog->ELSentStatus = 2;
                }

                $emailLog->save();


                if(!empty($tenderPIC)){

                    foreach($tenderPIC as $pic){

                        $usercode = $pic->userPIC->USCode;
                        $user= User::where('USCode',$usercode)->first();


                        if($pic->TPICType == 'T'){

                            $emailLog = new EmailLog();
                            $emailLog->ELCB 	= $user->USCode;
                            $emailLog->ELType 	= 'Meeting NP';
                            $emailLog->ELSentTo =  $user->USEmail;

                            // Send Email
                            $tokenResult = $user->createToken('Personal Access Token');
                            $token = $tokenResult->token;

                            $emailData = array(
                                'id' => $user->USID,
                                'name'  => $user->USName ?? '',
                                'email' => $user->USEmail,
                                'meeting' => $meeting,
                                'meetingType' => 'NP',
                                'data' => $tender
                            );

                            try {
                                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Mesyuarat Rundingan Harga');
                                });

                                $emailLog->ELMessage = 'Success';
                                $emailLog->ELSentStatus = 1;
                            } catch (\Exception $e) {
                                $emailLog->ELMessage = $e->getMessage();
                                $emailLog->ELSentStatus = 2;
                            }

                            $emailLog->save();


                        }elseif(isset($pic->userSV) && $pic->userSV !== null){

                            //SEND NOTIFICATION TO PIC - BOSS
                            //##NOTIF-MAIL-011
                            $emailLog = new EmailLog();
                            $emailLog->ELCB 	= $user->USCode;
                            $emailLog->ELType 	= 'Meeting NP';
                            $emailLog->ELSentTo =  $user->USEmail;

                            // Send Email
                            $tokenResult = $user->createToken('Personal Access Token');
                            $token = $tokenResult->token;

                            $emailData = array(
                                'id' => $user->USID,
                                'name'  => $user->USName ?? '',
                                'email' => $user->USEmail,
                                'meeting' => $meeting,
                                'meetingType' => 'Meeting NP',
                                'data' => $tender
                            );

                            try {
                                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Mesyuarat Rundingan Harga');
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
            ]);


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

            $id = $request->MNo;
            $code = $request->sendCode;
            $meetingType = $request->meetingType;

            $user = Auth::user();

            $meeting = Meeting::where('MNo',$id)->first();
            $meetingID = $meeting->MNo;

            if($meeting->MStatus == 'SUBMIT'){
                $statusCode = 'S';
            }else if($meeting->MStatus == 'NEW'){
                $statusCode = 'N';
            }

            $result = null;

            if($code == 'B'){ //BOTH MAIL N NOTIFICATION
                $result = $this->sendMailNotification($meetingID,$meetingType);
                $result = $this->sendNotification($meetingID,$meetingType,$statusCode);

                $result = response()->json([
                    'success' => '1',
                    'message' => 'E-mel dan notifikasi berjaya dihantar.',
                ]);

            }else if($code == 'N'){
                $result = $this->sendNotification($meetingID,$meetingType,$statusCode);

                $result = response()->json([
                    'success' => '1',
                    'message' => 'Notifikasi berjaya dihantar.',
                ]);

            }else if($code == 'E'){
                $result = $this->sendMailNotification($meetingID,$meetingType);

                $result = response()->json([
                    'success' => '1',
                    'message' => 'E-mel berjaya dihantar.',
                ]);

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
