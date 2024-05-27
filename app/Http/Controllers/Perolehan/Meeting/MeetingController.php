<?php

namespace App\Http\Controllers\Perolehan\Meeting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralNotificationController;
use App\Models\BoardMeetingAttendanceList;
use App\Models\Project;
use App\Models\SSMCompany;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


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
use App\Models\BoardMeetingProject;
use App\Models\BoardMeetingTender;
use App\Models\BoardMeetingProposal;
use App\Models\EmailLog;
use App\Models\MeetingEmail;
use App\Models\MeetingNP;
use App\Models\Notification;
use Yajra\DataTables\DataTables;
use Mail;

class MeetingController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }


    public function index(){

        return view('perolehan.meeting.index');
    }


    public function create(Request $request){

        $meetingLocation = $this->dropdownService->meetingLocation();
        $meetingType = $this->dropdownService->boardMeetingType();

        $tenderNo = $request->tenderNo ?? 0;

        $tender = DB::table('TRTender')
        ->select('TRTender.TDNo', 'TRTender.TDTitle', 'TRTenderProposal.TPEvaluationStep')
        ->join('TRTenderProposal', function ($join) {
            $join->on('TRTender.TDNo', '=', 'TRTenderProposal.TP_TDNo')
                ->on('TRTender.TD_TDANo', '=', 'TRTenderProposal.TP_TDANo');
        })
        ->where(function ($query) use($tenderNo) {
            $query->where('TRTender.TD_TPCode', 'OT')
                ->orWhere('TRTender.TD_TPCode', 'ES');
        })
        ->where('TRTenderProposal.TP_TPPCode', 'SB')
        ->where('TRTenderProposal.TPEvaluationStep', 2)
        ->get()
        ->pluck('TDTitle', 'TDNo')
        ->map(function ($item, $key) {
            $code = DB::table('TRTender')->where('TDNo', $key)->value('TDNo');
            return $code . " - " . $item;
        });

        // Check if $tenderNo is not in the list of TDNo values
        if (!$tender->contains($tenderNo) && $tenderNo != 0) {

            // Retrieve the title from the database
            // $tenderTitle = DB::table('TRTender')->where('TDNo', $tenderNo)->value('TDTitle');

            // // Add $tenderNo to the tenders array
            // $tender->put($tenderNo, $tenderNo . " - " . $tenderTitle);
            $tender2 = Tender::where('TDNo', $tenderNo)
            ->pluck('TDTitle', 'TDNo')
            ->map(function ($item, $key) {
                $code = Tender::where('TDNo', $key)->value('TDNo');
                return  $code . " - " . $item;
            });

            $tender = $tender->merge($tender2);
        }

        $boardMeetingCount = BoardMeeting::count();
        $formattedCount = str_pad($boardMeetingCount, 3, '0', STR_PAD_LEFT);
        $currentYear = Carbon::now()->year;

        $title = "Mesyuarat Tender Bil. " . $formattedCount . "/" . $currentYear;

        return view('perolehan.meeting.create',
        compact('tender','tenderNo','title','meetingLocation','meetingType')
        );
    }

    public function store(Request $request){

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

            $autoNumber = new AutoNumber();
            $BMNo = $autoNumber->generateBoardMeeting();

            $meeting_title  = $request->meeting_title;
            $meeting_date   = $request->meeting_date;
            $meeting_time   = $request->meeting_time;
            $meeting_location   = $request->meeting_location;
            $tender         = $request->tender;

            $boardMeeting = new BoardMeeting();

            $boardMeeting->BMNo     = $BMNo;
            $boardMeeting->BMTitle  = $meeting_title;
            $boardMeeting->BMDate   = $meeting_date;
            $boardMeeting->BMTime   = $meeting_time;
            $boardMeeting->BM_LCCode   = $meeting_location;
            $boardMeeting->BMCB     = $user->USCode;
            $boardMeeting->BMMB     = $user->USCode;
            $boardMeeting->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.meeting.edit', [$BMNo]),
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

    public function listTender(Request $request){

        $tenders = DB::table('TRTender')
        ->select('TRTender.TDNo', 'TRTender.TDTitle', 'TRTenderProposal.TPEvaluationStep')
        ->join('TRTenderProposal', function ($join) {
            $join->on('TRTender.TDNo', '=', 'TRTenderProposal.TP_TDNo')
                ->on('TRTender.TD_TDANo', '=', 'TRTenderProposal.TP_TDANo');
        })
        ->where(function ($query){
            $query->where('TRTender.TD_TPCode', 'OT')
                ->where('TRTenderProposal.TP_TPPCode', 'SB')
                ->where('TRTenderProposal.TPEvaluationStep', 2)
                ->orWhere('TRTender.TD_TPCode', 'ES');
        })
        ->get()
        ->pluck('TDTitle', 'TDNo')
        ->map(function ($item, $key) {
            $code = DB::table('TRTender')->where('TDNo', $key)->value('TDNo');
            return $code . " - " . $item;
        });

        $count = 0;
        $proposals = array();

        foreach($tenders as $tender => $title){

            $dataTender = Tender::where('TDNo',$tender)
                ->first();

            $tenderProp = TenderProposal::where('TP_TDNo',$tender)
                ->where('TP_TDANo',$dataTender->TD_TDANo)
                ->where('TP_TPPCode','SB')
                ->where('TPEvaluationStep',2)
                ->get();

            $proposals[$count]['TDNo'] = $tender;
            $proposals[$count]['tender'] = $dataTender;
            $proposals[$count]['tenderProposal'] = $tenderProp;

            $count++;

        }

        return view('perolehan.meeting.include.listTender',
                compact('proposals')
        );
    }

    public function listProject(Request $request){

        $projects = Project::where('PT_PPCode','NPS')->get();
        
        foreach($projects as $project){
            
            $TPNo = $project->PT_TPNo;

            $proposal = TenderProposal::where('TPNo',$TPNo)->first();
            $tender = $proposal->tender;
            $proposalAmt = $proposal->TPTotalAmt;
            $discAmt = 0;

            $originalAmt = $proposal->TPTotalAmt;

            $meetingMNP = MeetingNP::where('MNP_TPNo',$TPNo)
            ->whereHas('meeting',function ($query) {
                $query->where('MStatus','SUBMIT');
            })
            ->orderBy('MNPMD','DESC')
            ->first();
    
            if($meetingMNP){
                $proposalAmt = $meetingMNP->MNPProposalAmt;
                $discAmt = $meetingMNP->MNPDiscAmt;
    
            }
            else{ 
                $proposalAmt = $proposal->TPTotalAmt;
    
            }
    
            $finalAmt = $proposalAmt - $discAmt;
            $discAmt = $originalAmt - $finalAmt;
            $discPercent = ( ($originalAmt - $finalAmt) / $originalAmt ) * 100;
    
            $project['proposal'] = $proposal;
            $project['tender'] = $tender;
            $project['originalAmt'] = number_format($originalAmt,2,'.',',') ;
            $project['proposalAmt'] = number_format($proposalAmt,2,'.',',') ;
            $project['discAmt']     = number_format($discAmt,2,'.',',') ;
            $project['discPercent']     = number_format($discPercent,2,'.',',') ;
            $project['finalAmt']    = number_format($finalAmt,2,'.',',') ;

        }


        return view('perolehan.meeting.include.listProject',
            compact('projects')
        );
        

    }

    public function listProposal(Request $request){

        $tenderNo = array();
        $tenderNo = $request->tender;
        $proposals = array();
        $count = 0;

        foreach($tenderNo as $tender){

            $dataTender = Tender::where('TDNo',$tender)
                ->first();

            $tenderProp = TenderProposal::where('TP_TDNo',$tender)
                ->where('TP_TDANo',$dataTender->TD_TDANo)
                ->where('TP_TPPCode','SB')
                ->where('TPEvaluationStep',2)
                ->get();

            $proposals[$count]['TDNo'] = $tender;
            $proposals[$count]['Tender'] = $dataTender;
            $proposals[$count]['tenderProposal'] = $tenderProp;

            $count++;

        }

        return view('perolehan.meeting.listProposal',
                compact('proposals')
        );

    }


    public function edit($id){

        $tenderMeetingStatus = $this->dropdownService->tenderMeetingStatus();
        $departmentAll = $this->dropdownService->departmentAll();
        $meetingLocation = $this->dropdownService->meetingLocation();
        $meetingType = $this->dropdownService->boardMeetingType();
        $syarat_khas = $this->dropdownService->yt();

        $fileAttachDownloadMN = FileAttach::where('FAFileType','MT-MN')->first();

        // $tender = Tender::whereHas('tenderProposal', function($query){
        //     $query->where('TP_TPPCode','SB')->where('TPEvaluationStep',2);
        // })
        // ->where('TD_TPCode','OT') ////TBR-TBC
        // ->orWhere('TD_TPCode','ES')
        // ->get()->pluck('TDTitle','TDNo')->map(function ($item, $key) {
        //     $code = Tender::where('TDNo', $key)->value('TDNo');
        //     return  $code . " - " . $item; // Appending MACode to MA_MOFCode
        // });

        $boardMeeting = BoardMeeting::where('BMNo',$id)
                        ->first();

        $meeting_date = Carbon::parse($boardMeeting->BMDate)->format('Y-m-d');
        $meeting_time = Carbon::parse($boardMeeting->BMTime)->format('H:i');

        $arrayTender = [];

        foreach($boardMeeting->meetingTender as $meetingTender){

            array_push($arrayTender, $meetingTender->BMT_TDNo);

        }

        //LIST PROPOSAL
        $proposals = array();
        $count = 0;

        foreach($arrayTender as $tenders){

            $dataTender = Tender::where('TDNo',$tenders)
                ->first();

            $tenderProp = TenderProposal::where('TP_TDNo',$tenders)
                ->where('TP_TDANo',$dataTender->TD_TDANo)
                ->where('TP_TPPCode','SB')
                ->where('TPEvaluationStep',2)
                ->get();

            $meetingTenders = BoardMeetingTender::where('BMT_TDNo',$tenders)
            ->where('BMT_TDANo',$dataTender->TD_TDANo)
            ->where('BMT_BMNo',$id)
            ->first();

            $meetProposal = BoardMeetingProposal::where('BMP_BMNo',$id)->get();
            $count2 = 0;
            foreach($tenderProp as $tendProp){

                $props = BoardMeetingProposal::where('BMP_TPNo',$tendProp->TPNo)
                            ->where('BMP_BMNo',$id)
                            ->first();

                $proposals[$count]['tenderProposal'][$count2] = $tendProp;
                $proposals[$count]['tenderProposal'][$count2]['meetingProposal'] = $props;
                $count2++;

            }

            // $proposals[$count]['meetingProposal'] = $meetProposal;

            $proposals[$count]['meetingTender'] = $meetingTenders;

            $proposals[$count]['TDNo'] = $tenders;
            $proposals[$count]['BMNo'] = $id;
            // $proposals[$count]['tenderProposal'] = $tenderProp;

            $count++;

        }

        $tender = DB::table('TRTender')
        ->select('TRTender.TDNo', 'TRTender.TDTitle', 'TRTenderProposal.TPEvaluationStep')
        ->join('TRTenderProposal', function ($join) {
            $join->on('TRTender.TDNo', '=', 'TRTenderProposal.TP_TDNo')
                ->on('TRTender.TD_TDANo', '=', 'TRTenderProposal.TP_TDANo');
        })
        ->where(function ($query) use($arrayTender) {
            $query->orwhere('TRTender.TD_TPCode', 'OT')
                ->orWhereIn('TRTender.TDNo', $arrayTender)
                ->orWhere('TRTender.TD_TPCode', 'ES')
                ->where('TRTenderProposal.TP_TPPCode', 'SB')
                ->where('TRTenderProposal.TPEvaluationStep', 2);
        })
        ->get()
        ->pluck('TDTitle', 'TDNo')
        ->map(function ($item, $key) {
            $code = DB::table('TRTender')->where('TDNo', $key)->value('TDNo');
            return $code . " - " . $item;
        });

        $boardMeetingAttendanceLists = BoardMeetingAttendanceList::where('BMAL_BMNo', $id)->get();

        //meeting Project
        $meetingProjects = array();

        foreach($boardMeeting->meetingProject as $index => $meetingProject){

            $project = $meetingProject->project;
            $tenderProposal = $project->tenderProposal;
            $tender = $tenderProposal->tender;

            $lastMeetingNP = MeetingNP::where('MNP_TPNo',$tenderProposal->TPNo)
            ->where('MNP_MSCode','D')
            ->whereHas('meeting',function ($query) {
                $query->where('MStatus','SUBMIT');
            })
            ->orderBy('MNPMD','DESC')
            ->first();

            $finalAmount = 0;

            if($lastMeetingNP){

                $finalAmount = $lastMeetingNP->MNPFinalAmt;

            }

            $discountAmount = $tenderProposal->TPTotalAmt - $finalAmount;

            $discountPercent = ($tenderProposal->TPTotalAmt != 0) ? ($discountAmount / $tenderProposal->TPTotalAmt) * 100 : 0;
            
            $meetingProjects[$index]['meetingProject'] = $meetingProject;
            $meetingProjects[$index]['project'] = $project;
            $meetingProjects[$index]['tenderProposal'] = $tenderProposal;
            $meetingProjects[$index]['tender'] = $tender;
            // $meetingProjects[$index]['meetingNP'] = $lastMeetingNP;
            $meetingProjects[$index]['discPercent'] = $discountPercent;
            $meetingProjects[$index]['finalAmount'] = $finalAmount;
            $meetingProjects[$index]['discountAmount'] = $discountAmount;

        }

        return view('perolehan.meeting.edit',
            compact('tender','boardMeeting','meeting_date','meeting_time','arrayTender','proposals','fileAttachDownloadMN','meetingType',
                'tenderMeetingStatus', 'departmentAll', 'boardMeetingAttendanceLists','meetingLocation',
                'meetingProjects','syarat_khas'
                )
        );
    }

    public function editListProposal(Request $request){

        $tenderMeetingStatus = $this->dropdownService->tenderMeetingStatus();
        $id = $request->BMNo;
        $tenderNo = array();
        $arrayTender = $request->tender;
        $proposals = array();
        $count = 0;

        foreach($arrayTender as $tenders){

            $dataTender = Tender::where('TDNo',$tenders)
                ->first();

            $tenderProp = TenderProposal::where('TP_TDNo',$tenders)
                ->where('TP_TDANo',$dataTender->TD_TDANo)
                ->where('TP_TPPCode','SB')
                ->where('TPEvaluationStep',2)
                ->get();

            $meetingTenders = BoardMeetingTender::where('BMT_TDNo',$tenders)
            ->where('BMT_TDANo',$dataTender->TD_TDANo)
            ->where('BMT_BMNo',$id)
            ->first();

            $meetProposal = BoardMeetingProposal::where('BMP_BMNo',$id)
                            ->get();
            $count2 = 0;
            foreach($tenderProp as $tendProp){

                $props = BoardMeetingProposal::where('BMP_TPNo',$tendProp->TPNo)
                            ->where('BMP_BMNo',$id)
                            ->first();

                $proposals[$count]['tenderProposal'][$count2] = $tendProp;
                $proposals[$count]['tenderProposal'][$count2]['meetingProposal'] = $props;
                $count2++;

            }

            // $proposals[$count]['meetingProposal'] = $meetProposal;

            $proposals[$count]['meetingTender'] = $meetingTenders;

            $proposals[$count]['TDNo'] = $tenders;
            $proposals[$count]['BMNo'] = $id;
            // $proposals[$count]['tenderProposal'] = $tenderProp;

            $count++;

        }

        return view('perolehan.meeting.editListProposal',
                compact('proposals','tenderMeetingStatus','arrayTender')
        );

    }

    public function updateListMeetingTender(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $BMNo         = $request->BMNo;
            $tender         = $request->tender;

            if(!empty($tender)){

                foreach($tender as $tenderNo){
                    $tenderStatus = "IM";

                    $tender = Tender::where('TDNo',$tenderNo)->first();
                    $tender->TD_TPCode = $tenderStatus;
                    $tender->save();

                    $TDANo = $tender->TD_TDANo;

                    $tenderProposal = TenderProposal::select('TPNo')
                        ->where('TP_TDANo',$TDANo)
                        ->where('TP_TDNo',$tenderNo)
                        ->where('TP_TDANo',$tender->TD_TDANo)
                        ->where('TP_TPPCode','SB')
                        ->where('TPEvaluationStep',2)
                        ->get();

                    $TMSCode = "I"; //default value; get from MSTenderMeetingStatus

                    $boardMeetingTender = new BoardMeetingTender();
                    $boardMeetingTender->BMT_BMNo       = $BMNo;
                    $boardMeetingTender->BMT_TDNo       = $tenderNo;
                    $boardMeetingTender->BMT_TDANo       = $TDANo;
                    $boardMeetingTender->BMT_TMSCode    = $TMSCode;
                    $boardMeetingTender->BMTCB         = $user->USCode;
                    $boardMeetingTender->BMTMB         = $user->USCode;
                    $boardMeetingTender->save();

                    foreach($tenderProposal as $proposal){

                        $TPNo = $proposal->TPNo;
                        $BMPNo = $this->autoNumber->generateBoardMeetingProposal();

                        $boardMeetingProposal = new BoardMeetingProposal();
                        $boardMeetingProposal->BMPNo          = $BMPNo;
                        $boardMeetingProposal->BMP_BMNo       = $BMNo;
                        $boardMeetingProposal->BMP_TPNo       = $TPNo;
                        $boardMeetingProposal->BMPCB          = $user->USCode;
                        $boardMeetingProposal->BMPMB          = $user->USCode;
                        $boardMeetingProposal->save();

                    }


                    if(!empty($tender->tenderPIC)){

                        foreach($tender->tenderPIC as $tenderPIC){

                            if(($tenderPIC->userPIC)){
                                $USEmail = $tenderPIC->userPIC->USEmail;
                                $USCode = $tenderPIC->userPIC->USCode;

                                $currentMeetingEmail = BoardMeetingEmail::where('BME_BMNo',$BMNo)->get();

                                $exists = $currentMeetingEmail->contains('BMEEmailAddr',$USEmail);

                                if( !$exists ){

                                    $meetingEmail = new BoardMeetingEmail();
                                    $meetingEmail->BME_BMNo         = $BMNo;
                                    $meetingEmail->BMEEmailAddr    = $USEmail;
                                    $meetingEmail->BME_USCode    = $USCode;
                                    $meetingEmail->save();

                                }

                            }

                        }

                    }

                }

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.meeting.edit', [$BMNo]),
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

    public function updateListMeetingProject(Request $request){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $BMNo = $request->BMNo;

            $meetingProjects = $request->meetingProject ?? [];
            $oldDatas = BoardMeetingProject::where('BMPT_BMNo',$BMNo)->get();

            if($request->meetingProject){
                
                foreach($request->meetingProject as $index => $projectNo){

                    $exists = $oldDatas->contains('BMPT_PTNo',$projectNo);

                    if (!$exists) {
                        //INSERT NEW DATA

                        $project = Project::where('PTNo',$projectNo)->first();
                    
                        $meeting = new BoardMeetingProject();
                        $meeting->BMPT_BMNo         = $BMNo;
                        $meeting->BMPT_PTNo         = $projectNo;
                        $meeting->BMPT_TMSCode      = 'I';
                        $meeting->BMPTCB             = $user->USCode;
                        $meeting->BMPTMB             = $user->USCode;
                        $meeting->save();

                        $project->PT_PPCode = 'IM';
                        $project->save();

                    }else{
                        //UPDATE CURRENT DATA
                        $meeting = BoardMeetingProject::where('BMPT_PTNo',$projectNo)
                            ->where('BMPT_BMNo',$BMNo)
                            ->first();

                    }


                    $project = Project::where('PTNo',$projectNo)->first();

                    $tenderProposal = $project->tenderProposal;
                    $tender = $tenderProposal->tender;

                    if(!empty($tender->tenderPIC)){

                        foreach($tender->tenderPIC as $tenderPIC){

                            if(($tenderPIC->userPIC)){
                                $USEmail = $tenderPIC->userPIC->USEmail;
                                $USCode = $tenderPIC->userPIC->USCode;
                                $USPhoneNo = $tenderPIC->userPIC->USPhoneNo;

                                $currentMeetingEmail = BoardMeetingEmail::where('BME_BMNo',$BMNo)->get();

                                $exists = $currentMeetingEmail->contains('BMEEmailAddr',$USEmail);

                                if( !$exists ){

                                    $meetingEmail = new BoardMeetingEmail();
                                    $meetingEmail->BME_BMNo         = $BMNo;
                                    $meetingEmail->BMEEmailAddr    = $USEmail;
                                    $meetingEmail->BMEPhoneNo    = $USPhoneNo;
                                    $meetingEmail->BME_USCode    = $USCode;
                                    $meetingEmail->save();

                                }

                            }

                        }

                    }

                }

            }

            //DELETE DATA and UPDATE STATUS
            foreach ($oldDatas as $oldData) {

                if (!in_array($oldData->MNP_TPNo, $meetingProjects)) {

                    // DELETE
                    $oldPTNo = $oldData->BMPT_PTNo;
                    
                    $oldProject = Project::where('PTNo',$oldPTNo)->first();
                    $oldProject->PT_PPCode = 'NPS';
                    $oldProject->save();

                    $oldData->delete();

                }

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.meeting.edit',[$BMNo]),
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

            $BMNo           = $request->BMNo;
            $meeting_title  = $request->meeting_title;
            $meeting_date   = $request->meeting_date;
            $meeting_time   = $request->meeting_time;
            $meeting_location   = $request->meeting_location;

            $boardMeeting = BoardMeeting::where('BMNo',$BMNo)->first();

            $boardMeeting->BMTitle  = $meeting_title;
            $boardMeeting->BMDate   = $meeting_date;
            $boardMeeting->BMTime   = $meeting_time;
            $boardMeeting->BM_LCCode   = $meeting_location;
            $boardMeeting->BMMB     = $user->USCode;
            $boardMeeting->save();

            if($updateConfirm == 1){

                if(!$boardMeeting->fileAttach){

                    if (!$request->hasFile('meetingMinit')) {

                        return response()->json([
                            'error' => 1,
                            'redirect' => route('perolehan.meeting.edit', [$BMNo]),
                            'message' => 'Sila muat-naik minit mesyuarat terlebih dahulu sebelum menghantar maklumat mesyuarat.'
                        ],400);

                    }

                }


                if($request->meetingProposalStatus){
                    if(in_array('I',$request->meetingProposalStatus)){

                        return response()->json([
                            'error' => 1,
                            'redirect' => route('perolehan.meeting.edit', [$BMNo]),
                            'message' => 'Sila kemaskini status mesyuarat yang masih "Belum Selesai".'
                        ],400);

                    }
                }

                if($request->meetingProposalStatusProj){
                    if(in_array('I',$request->meetingProposalStatusProj)){

                        return response()->json([
                            'error' => 1,
                            'redirect' => route('perolehan.meeting.edit', [$BMNo]),
                            'message' => 'Sila kemaskini status mesyuarat yang masih "Belum Selesai".'
                        ],400);

                    }
                }

                if($this->checkTenderProposal($request) == 0){

                    return response()->json([
                        'error' => 1,
                        'redirect' => route('perolehan.meeting.edit', [$BMNo]),
                        'message' => 'Sila pilih sekurangnya seorang pemenang bagi tender yang selesai terlebih dahulu sebelum menghantar maklumat mesyuarat.'
                    ],400);

                }

            }


            if ($request->hasFile('meetingMinit')) {

                $file = $request->file('meetingMinit');
                $fileType = 'TD-BM';
                $refNo = $BMNo;
                $this->saveFile($file,$fileType,$refNo);
            }

            //update Meeting Tender
            if(!empty($request->tenderNo)){

                $tender                  = $request->tenderNo;
                $meetingProposalStatus   = $request->meetingProposalStatus;
                $BMP_TPNo                = $request->BMP_TPNo;
                $winner                  = $request->winner;
                $remark                  = $request->remark;
                $meetingLOI              = $request->meetingLOI;

                //ADD TENDER, ADD/UPDATE PROPOSAL
                $oldMeetingTender = BoardMeetingTender::where('BMT_BMNo',$BMNo)
                                    ->get();

                $oldMeetingProposal = BoardMeetingProposal::where('BMP_BMNo',$BMNo)
                                    ->get();

                $count = 0;
                foreach($tender as $tenderNo){

                    $tenderStatus = "IM";

                    $tender2 = Tender::where('TDNo',$tenderNo)->first();
                    $tender2->TD_TPCode = $tenderStatus;
                    $tender2->save();

                    $TDANo = $tender2->TD_TDANo;

                    $exists = $oldMeetingTender->contains('BMT_TDNo', $tenderNo);

                    if (!$exists) {
                        //INSERT NEW
                        $boardMeetingTender = new BoardMeetingTender();
                        $boardMeetingTender->BMT_BMNo       = $BMNo;
                        $boardMeetingTender->BMT_TDNo       = $tenderNo;
                        $boardMeetingTender->BMT_TDANo       = $TDANo;
                        $boardMeetingTender->BMT_TMSCode    = $meetingProposalStatus[$count];
                        $boardMeetingTender->BMTLOI         = $meetingLOI[$count];
                        $boardMeetingTender->BMTCB         = $user->USCode;
                        $boardMeetingTender->BMTMB         = $user->USCode;
                        $boardMeetingTender->save();
                    }else{
                        //UPDATE TENDER
                        $boardMeetingTender = BoardMeetingTender::where('BMT_BMNo',$BMNo)
                        ->where('BMT_TDANo',$TDANo)
                        ->where('BMT_TDNo',$tenderNo)
                        ->first();

                        $boardMeetingTender->BMT_BMNo       = $BMNo;
                        $boardMeetingTender->BMT_TMSCode    = $meetingProposalStatus[$count];
                        $boardMeetingTender->BMTLOI    = $meetingLOI[$count];
                        $boardMeetingTender->BMTMB         = $user->USCode;
                        $boardMeetingTender->save();

                    }

                    $count++;

                }

                //DELETE TENDER
                foreach ($oldMeetingTender as $meetingTender) {
                    if (!in_array($meetingTender->BMT_TDNo, $tender)) {

                        // DELETE   //TBR-
                        $updateStatus = "ES";
                        $mTDANo = $meetingTender->BMT_TDANo;

                        $tdNo = $meetingTender->BMT_TDNo;
                        $tender2 = Tender::where('TDNo', $tdNo)
                                    ->where('TD_TDANo',$mTDANo)
                                    ->first();

                        if($tender2){
                        
                            $tender2->TD_TPCode = $updateStatus;
                            $tender2->save();

                        }

                        $meetingTender->delete();

                    }

                }

                //DELETE MEETING PROPOSAL
                if(!empty($BMP_TPNo) || $BMP_TPNo != null){

                    foreach ($oldMeetingProposal as $meetingProposal) {
                        if (!in_array($meetingProposal->BMP_TPNo, $BMP_TPNo)) {
                            // DELETE
                            $meetingProposal->delete();

                        }
                    }

                }

                //CHECK PROPOSAL DATA
                $count = 0;
                if(!empty($BMP_TPNo) || $BMP_TPNo != null){

                    $mergerBMP_TPNo = array_merge(...$BMP_TPNo);
                    $remark = array_merge(...$remark);
                    $winner = array_merge(...$winner);

                    foreach($mergerBMP_TPNo as $TPNo){

                        $boardMeetingProposal = BoardMeetingProposal::where('BMP_BMNo',$BMNo)
                                          ->where('BMP_TPNo',$TPNo)
                                          ->first();

                        if(empty($boardMeetingProposal)){
                            //INSERT
                            $BMPNo = $this->autoNumber->generateBoardMeetingProposal();

                            $boardMeetingProposal = new BoardMeetingProposal();
                            $boardMeetingProposal->BMPNo          = $BMPNo;
                            $boardMeetingProposal->BMP_BMNo       = $BMNo;
                            $boardMeetingProposal->BMP_TPNo       = $TPNo;

                            $boardMeetingProposal->BMPRemark       = $remark[$count];
                            $boardMeetingProposal->BMPWinner       = $winner[$count];
                            $boardMeetingProposal->BMPMB          = $user->USCode;
                            $boardMeetingProposal->save();

                        }else{
                            //UPDATE
                            $boardMeetingProposal->BMPRemark       = $remark[$count];
                            $boardMeetingProposal->BMPWinner       = $winner[$count];
                            $boardMeetingProposal->BMPMB          = $user->USCode;
                            $boardMeetingProposal->save();
                        }

                        $count++;


                    }

                }
            }

            //update Meeting Project
            if(!empty($request->projectNo)){

                $project                  = $request->projectNo;
                $meetingProposalStatus   = $request->meetingProposalStatusProj;
                $remark                  = $request->remarkProj;

                //ADD TENDER, ADD/UPDATE PROPOSAL
                $oldMeetingProject = BoardMeetingProject::where('BMPT_BMNo',$BMNo)
                                    ->get();

                $count = 0;
                foreach($project as $projectNo){

                    $projectStatus = "IM";

                    $project2 = Project::where('PTNo',$projectNo)->first();
                    $project2->PT_PPCode = $projectStatus;
                    $project2->save();

                    $exists = $oldMeetingProject->contains('BMPT_PTNo', $projectNo);

                    if (!$exists) {
                        //INSERT NEW
                        $boardMeetingProject = new BoardMeetingProject();
                        $boardMeetingProject->BMPT_BMNo       = $BMNo;
                        $boardMeetingProject->BMPT_PTNo       = $projectNo;
                        $boardMeetingProject->BMPTRemark       = $remark[$count];
                        $boardMeetingProject->BMPT_TMSCode    = $meetingProposalStatus[$count];
                        $boardMeetingProject->BMPTCB         = $user->USCode;
                        $boardMeetingProject->BMPTMB         = $user->USCode;
                        $boardMeetingProject->save();
                    }else{
                        //UPDATE TENDER
                        $boardMeetingProject = BoardMeetingProject::where('BMPT_BMNo',$BMNo)
                        ->where('BMPT_PTNo',$projectNo)
                        ->first();

                        $boardMeetingProject->BMPT_BMNo         = $BMNo;
                        $boardMeetingProject->BMPT_TMSCode      = $meetingProposalStatus[$count];
                        $boardMeetingProject->BMPTRemark        = $remark[$count];
                        $boardMeetingProject->BMPTMB             = $user->USCode;
                        $boardMeetingProject->save();

                    }

                    $count++;

                }

                //DELETE TENDER
                foreach ($oldMeetingProject as $meetingProject) {
                    if (!in_array($meetingProject->BMPT_PTNo, $project)) {

                        // DELETE   //TBR-
                        $updateStatus = "NPS";

                        $ptNo = $meetingProject->BMPT_PTNo;

                        $project2 = Project::where('PTNo', $ptNo)
                                    ->first();

                        if($project2){
                        
                            $project2->PT_PPCode = $updateStatus;
                            $project2->save();

                        }

                        $meetingProject->delete();

                    }

                }
            }

            $old_boardMeetingAttendanceLists = BoardMeetingAttendanceList::where('BMAL_BMNo', $BMNo)->get();
            $BMALIDs = $request->BMALID;
            $BMALNames = $request->BMALName;
            $BMALPositions = $request->BMALPosition;
            $BMAL_DPTCodes = $request->BMAL_DPTCode;

            if(isset($BMALIDs)){
                // Check if every element in the arrays has a CMAL_DPTCodes

                if ($this->areAllValuesSet($BMALNames) && $this->areAllValuesSet($BMALPositions) && $this->areAllValuesSet($BMAL_DPTCodes)) {

                }else{
                    DB::rollback();

                    return response()->json([
                        'error' => '1',
                        'message' => 'Sila lengkapkan semua maklumat di dalam kehadiran.'
                    ], 400);

                }
            }else{
                $BMALIDs = [];
            }

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_boardMeetingAttendanceLists) > 0 ){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_boardMeetingAttendanceLists as $oboardMeetingAttendanceLists){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($BMALIDs as $BMALID){

                        if($oboardMeetingAttendanceLists->BMALID == $BMALID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $oboardMeetingAttendanceLists->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($BMALIDs as $key => $BMALID){

                    $new_boardMeetingAttendanceList = BoardMeetingAttendanceList::where('BMAL_BMNo', $BMNo)
                        ->where('BMALID', $BMALID)->first();
                    if(!$new_boardMeetingAttendanceList){
                        $new_boardMeetingAttendanceList = new BoardMeetingAttendanceList();
                        $new_boardMeetingAttendanceList->BMAL_BMNo = $BMNo;
                        $new_boardMeetingAttendanceList->BMALCB = $user->USCode;
                    }
                    $new_boardMeetingAttendanceList->BMALName = $BMALNames[$key];;
                    $new_boardMeetingAttendanceList->BMALPosition = $BMALPositions[$key];;
                    $new_boardMeetingAttendanceList->BMAL_DPTCode = $BMAL_DPTCodes[$key];
                    $new_boardMeetingAttendanceList->BMALMB = $user->USCode;
                    $new_boardMeetingAttendanceList->save();
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                }
            }
            else{

                if(isset($BMALIDs)){
                    foreach($BMALIDs as $key2 => $BMALID){
                        $new_boardMeetingAttendanceList = new BoardMeetingAttendanceList();
                        $new_boardMeetingAttendanceList->BMAL_BMNo = $BMNo;
                        $new_boardMeetingAttendanceList->BMALName = $BMALNames[$key2];
                        $new_boardMeetingAttendanceList->BMALPosition = $BMALPositions[$key2];
                        $new_boardMeetingAttendanceList->BMAL_DPTCode = $BMAL_DPTCodes[$key2];
                        $new_boardMeetingAttendanceList->BMALCB = $user->USCode;
                        $new_boardMeetingAttendanceList->BMALMB = $user->USCode;
                        $new_boardMeetingAttendanceList->save();
                    }

                }
            }
            //END HERE

            if($updateConfirm == 1){

                $result = $this->updateStatus($BMNo);

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('perolehan.meeting.index'),
                    'message' => 'Maklumat mesyuarat berjaya dikemaskini.'
                ]);

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.meeting.edit', [$BMNo]),
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

            $boardMeeting = BoardMeeting::where('BMNo',$id)->first();
            $boardMeeting->BMStatus = "SUBMIT";
            $boardMeeting->save();

            $meetingTender = $boardMeeting->meetingTender;
            $meetingTender->each(function ($mtender){

                $tenderStatus = "BM";
                $tdNo = $mtender->BMT_TDNo;
                $tdaNo = $mtender->BMT_TDANo;


                if($mtender->BMT_TMSCode == "D"){

                    $tender = Tender::where('TDNo',$tdNo)
                            ->where('TD_TDANo',$tdaNo)
                            ->first();
                    
                    $tender->TD_TPCode = $tenderStatus;
                    $tender->save();

                    foreach($mtender->meetingProposal as $meetingProposal){

                        $winner = $meetingProposal->BMPWinner;

                        if($winner != "" || !empty($winner)){

                            $tenderProposal = $meetingProposal->tenderProposal;
                            $tender = $tenderProposal->tender;

                            // if($tender->TDLOI == 1){
                            if($tender->meetingTender->BMTLOI == 1){
                                $tenderProposal->TP_TRPCode = 'MLI';

                            }else{
                                $tenderProposal->TP_TRPCode = 'MLA';
                            }
                            $tenderProposal->save();

                            $this->createProject($tender, $meetingProposal->tenderProposal,$mtender->BMT_TMSCode);

                        }

                    }



                }
                elseif($mtender->BMT_TMSCode == "M"){

                    $tender = Tender::where('TDNo',$tdNo)
                                ->where('TD_TDANo',$tdaNo)
                                ->first();
                    
                    $tender->TD_TPCode = $tenderStatus;
                    $tender->save();

                    foreach($mtender->meetingProposal as $meetingProposal){

                        $meetingProposal->BMPPriceNegoMeeting = 0;
                        $meetingProposal->save();

                        $winner = $meetingProposal->BMPWinner;

                        if($winner != "" || !empty($winner)){

                            $tenderProposal = $meetingProposal->tenderProposal;
                            $tenderProposal->TP_TRPCode = 'MPN';
                            $tenderProposal->save();

                            $this->createProject($tender, $meetingProposal->tenderProposal,$mtender->BMT_TMSCode);

                        }

                    }
                }
                else{

                    $tender = Tender::where('TDNo',$tdNo)
                            ->where('TD_TDANo',$tdaNo)
                            ->first();

                    $tender->TD_TPCode = 'ES';
                    $tender->save();

                }

            });
            
            $meetingProject = $boardMeeting->meetingProject;
            $meetingProject->each(function ($mProject){

                $tenderStatus = "BM";
                $PTNo = $mProject->BMPT_PTNo;


                if($mProject->BMPT_TMSCode == "D"){

                    $project = $mProject->project;

                    $tenderProposal = $project->tenderProposal;
                    $tender = $tenderProposal->tender;

                    // if($tender->TDLOI == 1){
                    if($tender->meetingTender->BMTLOI == 1){
                        $tenderProposal->TP_TRPCode = 'MLI';
                        $project->PT_PPCode = 'MLI';

                    }else{
                        $tenderProposal->TP_TRPCode = 'MLA';
                        $project->PT_PPCode = 'MLA';
                    }

                    $tenderProposal->save();
                    $project->save();



                }
                elseif($mProject->BMPT_TMSCode == "M"){

                    $project = $mProject->project;

                    $tenderProposal = $project->tenderProposal;
                    $tender = $tenderProposal->tender;

                    $project->PT_PPCode = 'NP';
                    $project->save();

                }
                else{

                    $project = Project::where('PTNo',$PTNo)
                            ->first();

                    $tender->TD_TPCode = 'NPS';
                    $tender->save();

                }

            });

            $this->sendNotification($boardMeeting,'S');
            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.meeting.index'),
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

    public function deleteListMeeting(Request $request){
        try {

            DB::beginTransaction();

            $type = $request->type;
            $refNo = $request->refNo;

            if($type == 'BMT'){

                $meetingTender = BoardMeetingTender::where('BMTID',$refNo)->first();

                if($meetingTender){

                    $BMNo = $meetingTender->BMT_BMNo;

                    $updateStatus = "ES";

                    $TDANo = $meetingTender->BMT_TDANo;
                    $tdNo = $meetingTender->BMT_TDNo;

                    $tender2 = Tender::where('TDNo', $tdNo)
                                ->where('TD_TDANo',$TDANo)
                                ->first();

                    if($tender2){
                    
                        $tender2->TD_TPCode = $updateStatus;
                        $tender2->save();

                    }

                    $meetingTender->delete();

                    $tenderProposal = TenderProposal::where('TP_TDNo',$tdNo)
                    ->where('TP_TDANo',$TDANo)
                    ->where('TP_TPPCode','SB')
                    ->where('TPEvaluationStep',2)
                    ->get();

                    foreach($tenderProposal as $proposal){

                        $TPNo = $proposal->TPNo;

                        $boardMeetingProposal = BoardMeetingProposal::where('BMP_BMNo',$BMNo)->where('BMP_TPNo',$TPNo)->first();
                        $boardMeetingProposal->delete();

                    }
    

                }
            }
            elseif($type == 'BMPT'){

                $meetingProject = BoardMeetingProject::where('BMPTID',$refNo)->first();

                if($meetingProject){

                    $BMNo = $meetingProject->BMPT_BMNo;

                    $updateStatus = "NPS";

                    $ptNo = $meetingProject->BMPT_PTNo;

                    $project = Project::where('PTNo', $ptNo)
                                ->first();

                    if($project){
                    
                        $project->PT_PPCode = $updateStatus;
                        $project->save();

                    }

                    $meetingProject->delete();
    

                }

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.meeting.edit',[$BMNo]),
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

    public function createProject($tender, $tenderProposal,$BMT_TMSCode){
        try {

            DB::beginTransaction();

            //CREATE PROJECT
            $autoNumber                     = new AutoNumber();
            $projectNo                      = $autoNumber->generateProjectNo();
            $activationCode                 = $this->createActivationCode();

            if($BMT_TMSCode == 'D'){
                // if($tender->TDLOI == 1){
                if($tender->meetingTender->BMTLOI != 1){
                    $ppCode = 'LI';
                }
                else{
                    $ppCode = 'LA';
                }
            }
            else if($BMT_TMSCode == 'M'){
                $ppCode = 'NP';
            }
            else{
                $ppCode = null;
            }

            $project                        = new Project();
            $project->PTNo                  = $projectNo;
            $project->PTCode                = $projectNo;
            $project->PT_CONo               = $tenderProposal->TP_CONo;
            $project->PT_TDNo               = $tender->TDNo;
            $project->PT_TPNo               = $tenderProposal->TPNo;
            $project->PTActivationCode      = $activationCode;
            $project->PTActivationDate      = Carbon::now();
            $project->PTActivationSent      = 1;
            $project->PT_PSCode             = 'N';
            $project->PT_PPCode             = $ppCode;
            $project->PTPriority            = 0;
            $project->PTProgress            = 1;
            $project->PTCB                  = Auth::user()->USCode;
            $project->save();

            DB::commit();

            return true;

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function checkTenderProposal(Request $request){

        $tenders = $request->input('tender', []);
        $meetingProposalStatuses = $request->input('meetingProposalStatus', []);
        $tpNumbers = $request->input('BMP_TPNo', []);
        $winners = $request->input('winner', []);

        foreach($tenders as $index => $tender){

            $status = $meetingProposalStatuses[$index];

            if(in_array($status,['D','M'])){

                //check winner
                $yeshave = 0;

                foreach($winners[$index] as $index2 => $winner){

                    if($winner !== null){
                        $yeshave++;
                    }

                }

                if($yeshave == 0){
                    return 0;
                }

            }

        }

        return 1;

    }

    //{{--Working Code Datatable--}}
    public function meetingDatatable(Request $request){

        $user = Auth::user();

        $query = BoardMeeting::orderBy('BMNo', 'DESC')->get();

        $flag = $request->input('flag');

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('BMNo', function($row){

                $route = route('perolehan.meeting.edit',[$row->BMNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->BMNo.' </a>';

                return $result;
            })
            ->editColumn('BMDate', function($row){

                if(empty($row->BMDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->BMDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;

            })
            ->editColumn('BMTime', function($row){

                if(empty($row->BMTime)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->BMTime);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('h:i A');

                }

                return $formattedDate;
            })
            ->editColumn('BMStatus', function($row) {

                $boardMeetingStatus = $this->dropdownService->boardMeetingStatus();

                return $boardMeetingStatus[$row->BMStatus];

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['BMNo','BMDate','BMTime','BMStatus'])
            ->make(true);
    }

    function sendNotification($boardMeeting){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $notification = new GeneralNotificationController();

            $meetingNo = $boardMeeting->BMNo;
            $meetingTender = $boardMeeting->meetingTender;
            $meetingProposal = $boardMeeting->meetingProposal;

            $meetingdate =  Carbon::parse($boardMeeting->BMDate)->format('d/m/Y');
            $meetingTime =  Carbon::parse($boardMeeting->BMTime)->format('h:i A');

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

                        $code = 'TD-BM'; //#NOTIF-024

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
                                $result = $notification->sendNotification($refNo,$notiType,$code,$data);
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
                            $code = "TD-TR"; //#NOTIF-025a

                        }else if($meetingStatus == 'M'){ //done with additional meeting
                            $code = "TD-TRM"; //#NOTIF-025b

                        }else if($meetingStatus == 'P'){ //postpone
                            $code = "TD-TRD"; //#NOTIF-025c

                        }else if($meetingStatus == 'C'){ //cancel
                            $code = "TD-TRC"; //#NOTIF-025d

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
                                $result = $notification->sendNotification($refNo,$notiType,$code,$data);
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
                                    $code = "TD-TRR"; //#NOTIF-026a

                                }else if($winner !== "" && $meetingStatus == 'M'){ //approve w terms
                                    $code = "TD-TRPM"; //#NOTIF-026b

                                }else if($winner !== "" && $meetingStatus == 'D'){ //approve
                                    $code = "TD-TRP"; //#NOTIF-026c

                                }

                                //SEND NOTIFICATION TO PIC
                                $tenderPIC_P = $tender->tenderPIC_P;

                                if(!empty($tenderPIC_P)){

                                    foreach($tenderPIC_P as $pic){

                                        $notiType = "PO";

                                        $refNo = $pic->TPIC_USCode;
                                        $result = $notification->sendNotification($refNo,$notiType,$code,$data);
                                    }

                                }

                            }

                        }


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

    public function sendMailNotification($boardMeeting){

        try{
            DB::beginTransaction();

            $user = Auth::user();


            $meetingNo = $boardMeeting->BMNo;
            $meetingTender = $boardMeeting->meetingTender;
            $meetingProposal = $boardMeeting->meetingProposal;

            $meetingDate =  Carbon::parse($boardMeeting->BMDate)->format('d/m/Y');
            $meetingTime =  Carbon::parse($boardMeeting->BMTime)->format('h:i A');

            $boardMeeting->meetingDate = $meetingDate;
            $boardMeeting->meetingTime = $meetingTime;

            if(isset($boardMeeting->meetingTender) && !empty($meetingTender) ){

                foreach($meetingTender as $mtender){

                    $tenderNo = $mtender->BMT_TDNo;

                    $tender = Tender::where('TDNo',$tenderNo)->first();

                    //#NOTIF-MAIL-005
                    //SEND EMAIL TO PIC
                    $tenderPIC = $tender->tenderPIC;

                    if(!empty($tenderPIC)){

                        foreach($tenderPIC as $pic){

                            $usercode = $pic->userPIC->USCode;
                            $user= User::where('USCode',$usercode)->first();

                            $emailLog = new EmailLog();
                            $emailLog->ELCB 	= $user->USCode;
                            $emailLog->ELType 	= 'Meeting Tender';
                            $emailLog->ELSentTo =  $user->USEmail;

                            // Send Email //#MAIL-NOTIFICATION-TEMPLATE
                            $tokenResult = $user->createToken('Personal Access Token');
                            $token = $tokenResult->token;

                            $emailData = array(
                                'id' => $user->USID,
                                'name'  => $user->USName ?? '',
                                'email' => $user->USEmail,
                                'meeting' => $boardMeeting,
                                'meetingType' => 'BM',
                                'data' => $tender
                            );

                            try {
                                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pemberitahuan Maklumat Mesyuarat Tender');
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

            $id = $request->BMNo;
            $code = $request->sendCode;

            $user = Auth::user();


            $boardMeeting = BoardMeeting::where('BMNo',$id)->first();

            if($boardMeeting->BMStatus == 'SUBMIT'){
                $statusCode = 'S';
            }else if($boardMeeting->BMStatus == 'NEW'){
                $statusCode = 'N';
            }

            $result = null;

            if($code == 'B'){ //BOTH MAIL N NOTIFICATION
                // $result = $this->sendMailNotification($boardMeeting);
                $result = $this->sendNotification($boardMeeting);

                $result = response()->json([
                    'success' => '1',
                    'message' => 'E-mel berjaya dihantar.',
                ], 400);

            }else if($code == 'N'){
                $result = $this->sendNotification($boardMeeting,$statusCode);

            }else if($code == 'E'){
                $result = $this->sendMailNotification($boardMeeting);

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
