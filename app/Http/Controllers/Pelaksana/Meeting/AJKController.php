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
use App\Models\ExtensionOfTime;
use App\Models\ExtensionOfTimeSpec;
use App\Models\FileAttach;
use App\Models\KickOffMeeting;
use App\Models\KickOffMeetingEmail;
use App\Models\Meeting;
use App\Models\MeetingAttendanceList;
use App\Models\MeetingDet;
use App\Models\MeetingEmail;
use App\Models\MeetingEOTAJK;
use App\Models\MeetingType;
use App\Models\MeetingVOAJK;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectMilestone;
use App\Models\Tender;
use App\Models\TenderProposal;
use App\Models\VariantOrder;
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

class AJKController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        return view('pelaksana.meeting.ajk.index');
    }

    public function create(Request $request){

        $project = $this->dropdownService->projectNew();
        $meetingType = $this->dropdownService->ajkMeetingType();
        $meetingLocation = $this->dropdownService->meetingLocation();

        $mea = ExtensionOfTime::whereIn('EOT_EPCode',['EOTA'])
            ->get()
            ->pluck('EOTDesc','EOTNo')
            ->map(function ($item, $key) {
                $eot = ExtensionOfTime::where('EOTNo', $key)->first();
                return $key . " ( " . $eot->EOTDesc . " )";
            });

        $mva = VariantOrder::whereIn('VO_VPCode',['VOA'])
            ->get()
            ->pluck('VODesc','VONo')
            ->map(function ($item, $key) {
                $vo = VariantOrder::where('VONo', $key)->first();
                return $key . " ( " . $vo->VODesc . " )";
            });

        $title = "";

        $type = $request->type ?? "AJK";
        $ref = $request->refNo;

        $title = $this->createMeetingTitle($type);

        return view('pelaksana.meeting.ajk.create',
        compact(
            'project', 'title','meetingLocation','meetingType',
            'mea', 'mva'
        )
        );
    }

    public function listMEA(Request $request){
        $eotNo = array();
        $meaNo = $request->mea;
        $meas = array();

        foreach($meaNo as $key => $mea){

            $dataEOT = ExtensionOfTime::where('EOTNo',$mea)
                ->first();


            $totalSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $mea)->sum('EOTSTotalProposeAmt');

            $meas[$key] = $dataEOT;
            $meas[$key]['totalspec'] = $totalSpec ?? 0;
        }

        return view('pelaksana.meeting.ajk.showList.listMEA',
            compact('meas')
        );

    }

    public function listMVA(Request $request){

        $voNo = array();
        $mvaNo = $request->mva;
        $mvas = array();

        foreach($mvaNo as $key => $mva){

            $data = VariantOrder::where('VONo',$mva)
                ->first();

            $mvas[$key] = $data;
        }

        return view('pelaksana.meeting.ajk.showList.listMVA',
            compact('mvas')
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

            $MNo = $this->autoNumber->generateMeetingNo();

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

            $route = route('pelaksana.meeting.ajk.index');

            if($meetingType == 'MEA'){

                $mea            = $request->mea;

                foreach($mea as $eotNo){

                    $dataEOT = ExtensionOfTime::where('EOTNo',$eotNo)->first();
                    $dataEOT->EOT_EPCode = 'AJKM';
                    $dataEOT->save();

                    $TMSCode = "I"; //default value; get from MSTenderMeetingStatus

                    $meetingEOT = new MeetingEOTAJK();
                    $meetingEOT->MEA_MNo         = $MNo;
                    $meetingEOT->MEA_EOTNo       = $eotNo;
                    $meetingEOT->MEA_MSCode      = $TMSCode;
                    $meetingEOT->MEACB           = $user->USCode;
                    $meetingEOT->MEAMB           = $user->USCode;
                    $meetingEOT->save();

                    $project = Project::where('PTNO',$dataEOT->EOT_PTNo)->first();

                    $tender = $project->tenderProposal->tender;

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

                $route = route('pelaksana.meeting.ajk.edit',[$MNo]);
            }
            elseif($meetingType == 'MVA'){

                $mva            = $request->mva;

                foreach($mva as $voNo){

                    $data = VariantOrder::where('VONo',$voNo)->first();
                    $data->VO_VPCode = 'AJKM';
                    $data->save();

                    $TMSCode = "I"; //default value; get from MSTenderMeetingStatus

                    $meeting = new MeetingVOAJK();
                    $meeting->MVA_MNo         = $MNo;
                    $meeting->MVA_VONo        = $voNo;
                    $meeting->MVA_MSCode      = $TMSCode;
                    $meeting->MVACB           = $user->USCode;
                    $meeting->MVAMB           = $user->USCode;
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

                $route = route('pelaksana.meeting.ajk.edit',[$MNo]);
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
        $meetingType = $this->dropdownService->ajkMeetingType();
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

        //MEA
        $arrayMeetingEA = [];

        foreach($meeting->meetingEA as $meetingEA){
            array_push($arrayMeetingEA, $meetingEA->MEA_EOTNo);

        }

        $mea = ExtensionOfTime::whereIn('EOT_EPCode',['EOTA'])
        ->orWhereIn('EOTNo',$arrayMeetingEA)
        ->get()
        ->pluck('EOTDesc','EOTNo')
        ->map(function ($item, $key) {
            $eot = ExtensionOfTime::where('EOTNo', $key)->first();
            return $key . " ( " . $eot->EOTDesc . " )";
        });

        //LIST MEA
        $dataMEAs = array();

        //MVA
        $arrayMeetingVA = [];

        foreach($meeting->meetingVA as $meetingVA){
            array_push($arrayMeetingVA, $meetingVA->MVA_VONo);

        }

        $mva = VariantOrder::whereIn('VO_VPCode',['VOA'])
            ->orWhereIn('VONo',$arrayMeetingVA)
            ->get()
            ->pluck('VO_PTNo', 'VONo')
            ->map(function ($item, $key) {
                $vo = VariantOrder::where('VONo', $key)->first();
                return $key . " ( " . $vo->VODesc . " )";
            });

        //LIST MVA
        $dataMVAs = array();

        return view('pelaksana.meeting.ajk.edit',
            compact('meeting','meeting_date','meeting_time', 'meetingType', 'tenderMeetingStatus','fileAttachDownloadMN',
                'crscode','arrayMeetingType', 'meetingAttendanceLists','departmentAll','meetingLocation',
                'mea', 'dataMEAs','arrayMeetingEA',
                'mva', 'dataMVAs','arrayMeetingVA',
            )
        );
    }

    public function editListMEA(Request $request){

        $meetingType = $this->dropdownService->meetingType();
        $tenderMeetingStatus = $this->dropdownService->meetingStatus();
        $mrscode = $this->dropdownService->meetingResultStatus('EOTA');

        $id = $request->MNo;
        $dataMEAs = array();
        $arrayMEAs = $request->mea;

        foreach($arrayMEAs as $key => $eot){

            $dataEOT = ExtensionOfTime::where('EOTNo',$eot)
                ->with('milestone')
                ->first();

            $meetingEA = MeetingEOTAJK::where('MEA_EOTNo',$eot)
                ->where('MEA_MNo',$id)
                ->first();

            $projectMilestones = ProjectMilestone::where('PM_PTNo', $dataEOT->EOT_PTNo)
                ->get()
                ->pluck('PMDesc', 'PMNo');

            $totalSpec = ExtensionOfTimeSpec::where('EOTS_EOTNo', $eot)->sum('EOTSTotalProposeAmt');

            $dataMEAs[$key]['eot'] = $dataEOT;
            $dataMEAs[$key]['meetingEA'] = $meetingEA;
            $dataMEAs[$key]['choice'] = $projectMilestones;
            $dataMEAs[$key]['totalspec'] = $totalSpec ?? 0;
        }

        return view('pelaksana.meeting.ajk.editList.editListMEA',
            compact(
                'mrscode', 'meetingType', 'tenderMeetingStatus',
                'dataMEAs', 'arrayMEAs', 'mrscode'
            )
        );
    }

    public function editListMVA(Request $request){

        $mrscode = $this->dropdownService->meetingResultStatus('VOA');
        $meetingType = $this->dropdownService->meetingType();
        $tenderMeetingStatus = $this->dropdownService->meetingStatus();

        $id = $request->MNo;
        $dataMVAs = array();
        $arrayMVAs = $request->mva;

        foreach($arrayMVAs as $key => $vo){

            $dataVO = VariantOrder::where('VONo',$vo)
                ->first();

            $meetingVA = MeetingVOAJK::where('MVA_VONo',$vo)
                ->where('MVA_MNo',$id)
                ->first();

            $dataMVAs[$key]['vo'] = $dataVO;
            $dataMVAs[$key]['meetingVA'] = $meetingVA;

        }


        return view('pelaksana.meeting.ajk.editList.editListMVA',
            compact(
                'mrscode', 'meetingType', 'tenderMeetingStatus',
                'dataMVAs', 'arrayMVAs'
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

            $route = route('pelaksana.meeting.ajk.edit',[$MNo]);

            if($updateConfirm == 1){

                if(!$meeting->fileAttach){

                    if (!$request->hasFile('meetingMinit')) {

                        $arrayGlobalMeeting = ['MEA','MVA'];

                        if(in_array($meetingType,$arrayGlobalMeeting)){

                            $route = route('pelaksana.meeting.ajk.edit',[$MNo]);

                        }

                        return response()->json([
                            'error' => 1,
                            'redirect' => $route,
                            'message' => 'Sila muat-naik minit mesyuarat terlebih dahulu sebelum menghantar maklumat mesyuarat.'
                        ],400);

                    }

                }


                if($request->meetingProposalStatus){
                    if(in_array('I',$request->meetingProposalStatus)){

                        return response()->json([
                            'error' => 1,
                            'redirect' => route('pelaksana.meeting.ajk.edit', [$MNo]),
                            'message' => 'Sila kemaskini status mesyuarat yang masih "Belum Selesai".'
                        ],400);

                    }
                }

            }

            if ($request->hasFile('meetingMinit')) {

                $file = $request->file('meetingMinit');
                $fileType = 'MD';
                $refNo = $MNo;
                $result = $this->saveFile($file,$fileType,$refNo);
            }

            if($meetingType == 'MEA'){
                $meas            = $request->mea;
                $this->updateMEA($request,$meas);

                $route = route('pelaksana.meeting.ajk.edit',[$MNo]);

            }

            else if($meetingType == 'MVA'){
                $mvas            = $request->mva;
                $result = $this->updateMVA($request,$mvas);

                $route = route('pelaksana.meeting.ajk.edit',[$MNo]);
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

            $route = route('pelaksana.meeting.ajk.index');

            if($meetingType == 'MEA'){

                foreach ($meeting->meetingEA  as $meetingEA){

                    $dataEOT = ExtensionOfTime::where('EOTNo', $meetingEA->MEA_EOTNo)->first();

                    if($meetingEA->MEA_MSCode == 'D'){

                        if($meetingEA->MEA_MRSCode == 'P'){
                            $dataEOT->EOT_EPCode = 'AJKA';
                        }
                        else if($meetingEA->MEA_MRSCode == 'V'){
                            $dataEOT->EOT_EPCode = 'AJKV';

                        }
                        else if($meetingEA->MEA_MRSCode == 'R'){
                            $dataEOT->EOT_EPCode = 'AJKR';
                        }
                    }
                    else{
                        $dataEOT->EOT_EPCode = 'EOTA';
                    }

                    $dataEOT->save();
                }

            }
            else if($meetingType == 'MVA'){

                foreach($meeting->meetingVA as $meetingVA){

                    $variantOrder = VariantOrder::where('VONo',$meetingVA->MVA_VONo)->first();

                    if($meetingVA->MVA_MSCode == 'D'){

                        if($meetingVA->MVA_MRSCode == 'P'){
                            $variantOrder->VO_VPCode = 'AJKA';
                        }
                        else if($meetingVA->MVA_MRSCode == 'V'){
                            $variantOrder->VO_VPCode = 'AJKV';

                        }
                        else if($meetingVA->MVA_MRSCode == 'R'){
                            $variantOrder->VO_VPCode = 'AJKR';
                        }

                    }
                    else{
                        $variantOrder->VO_VPCode = 'VOA';

                    }
                    $variantOrder->save();

                }


            }

            // $this->sendNotification($id,$meetingType,'S');

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

    public function updateMEA(Request $request,$datas){
        try{
            DB::beginTransaction();
            $user = Auth::user();

            $MNo                    = $request->MNo;
            $meetingProposalStatus  = $request->meetingProposalStatus;
            $EOTNos                  = $request->EOTNo;
            $mrscode                = $request->mrscode;
            $remarks                 = $request->remark;

            if(!empty($datas)){

                $oldDatas = MeetingEOTAJK::where('MEA_MNo',$MNo)->get();

                foreach($datas as $key => $data){

                    $TMSCode    = $meetingProposalStatus[$key];
                    $MRSCode    = $mrscode[$key];
                    $remark     = $remarks[$key];
                    $EOTNo      = $EOTNos[$key];

                    $exists = $oldDatas->contains('MEA_EOTNo', $data);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $meetingEOT = new MeetingEOTAJK();
                        $meetingEOT->MEA_MNo         = $MNo;
                        $meetingEOT->MEA_EOTNo       = $EOTNo;
                        $meetingEOT->MEA_MSCode      = $TMSCode;
                        $meetingEOT->MEA_MRSCode      = $MRSCode;
                        $meetingEOT->MEARemark      = $remark;
                        $meetingEOT->MEACB           = $user->USCode;
                        $meetingEOT->MEAMB           = $user->USCode;
                        $meetingEOT->save();

                    }else{

                        //UPDATE CURRENT DATA
                        $meetingData = MeetingEOTAJK::where('MEA_EOTNo',$data)
                            ->where('MEA_MNo',$MNo)
                            ->first();

                        $meetingData->MEA_EOTNo       = $EOTNo;
                        $meetingData->MEA_MSCode      = $TMSCode;
                        $meetingData->MEA_MRSCode     = $MRSCode;
                        $meetingData->MEARemark       = $remark;
                        $meetingData->MEAMB           = $user->USCode;
                        $meetingData->save();

                    }

                    $dataEOT = ExtensionOfTime::where('EOTNo', $EOTNo)->first();
                    $dataEOT->EOT_EPCode = 'AJKM';
                    $dataEOT->save();

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->MEA_EOTNo, $datas)) {

                        // DELETE
                        $EOTNo = $oldData->MEA_EOTNo;

                        //UPDATE STATUS

                        $dataEOT = ExtensionOfTime::where('EOTNo', $EOTNo)->first();
                        $dataEOT->EOT_EPCode = 'EOTA';
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

    public function updateMVA(Request $request,$datas){

        try{
            DB::beginTransaction();

            $user = Auth::user();

            $MNo                    = $request->MNo;
            $meetingProposalStatus  = $request->meetingProposalStatus;
            $VONos                  = $request->VONo;
            $mrscode                = $request->mrscode;
            $remarks                 = $request->remark;

            if(!empty($datas)){

                $oldDatas = MeetingVOAJK::where('MVA_MNo',$MNo)->get();

                foreach($datas as $key => $data){

                    $TMSCode    = $meetingProposalStatus[$key];
                    $MRSCode    = $mrscode[$key];
                    $remark     = $remarks[$key];
                    $VONo       = $VONos[$key];


                    $exists = $oldDatas->contains('MVA_VONo', $data);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $meeting = new MeetingVOAJK();
                        $meeting->MVA_MNo         = $MNo;
                        $meeting->MVA_VONo        = $VONo;
                        $meeting->MVA_MSCode      = $TMSCode;
                        $meeting->MVA_MRSCode     = $MRSCode;
                        $meeting->MVARemark       = $remark;
                        $meeting->MVACB           = $user->USCode;
                        $meeting->MVAMB           = $user->USCode;
                        $meeting->save();

                    }else{

                        //UPDATE CURRENT DATA
                        $meetingData = MeetingVOAJK::where('MVA_VONo',$data)
                            ->where('MVA_MNo',$MNo)
                            ->first();

                        $meetingData->MVA_VONo        = $VONo;
                        $meetingData->MVA_MSCode      = $TMSCode;
                        $meetingData->MVA_MRSCode     = $MRSCode;
                        $meetingData->MVARemark       = $remark;
                        $meetingData->MVAMB           = $user->USCode;
                        $meetingData->save();

                    }

                    $updateStatus = "AJKM"; // set the project claim process to REVIEW

                    $variantOrder = VariantOrder::where('VONo', $VONo)->first();
                    $variantOrder->VO_VPCode = $updateStatus;
                    $variantOrder->save();

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->MVA_VONo, $datas)) {

                        // DELETE
                        $VONo = $oldData->MVA_VONo;

                        //UPDATE STATUS
                        $updateStatus = "VOA"; // set the project claim process to REVIEW

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

    public function ajkDatatable(Request $request){

        $user = Auth::user();

        $query = Meeting::orderBy('MNo', 'DESC')
        ->whereHas('meetingDet', function($query){
            $query->whereIn('MD_MTCode',['MEA','MVA']);
        })
        ->get();

        $count = 0;

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
                    $result .= $meetingType->MTDesc;
                }
                else{
                    $result .= ', ' . $meetingType->MDesc;
                }

            }

            return $result;
        })
        ->editColumn('MNo', function($row){

            $route = route('pelaksana.meeting.ajk.edit',[$row->MNo]);
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


}
