<?php

namespace App\Http\Controllers\Pelaksana\ProjectTender;

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
use App\Models\ProjectTenderMilestone;
use App\Models\TemplateClaimFile;
use App\Models\TemplateMilestone;
use App\Models\TemplateMilestoneDet;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Cassandra\Exception\ExecutionException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Yajra\DataTables\DataTables;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Meeting;
use App\Models\MeetingAttendanceList;
use App\Models\MeetingPT;
use App\Models\MeetingType;
use App\Models\Notification;
use App\Models\ProjectTender;
use App\Models\ProjectTenderDept;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use DateTime;
use DateInterval;
use ZipStream\File;

class ProjectTenderController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        return view('pelaksana.projectTender.index'
        );
    }

    public function create(){

        $projectType = $this->dropdownService->jenis_projek();
        $department = $this->dropdownService->department();
        $projectTender = null;

        return view('pelaksana.projectTender.create',
        compact('projectType','department','projectTender')
        );
    }

    public function store(Request $request){

        $messages = [
            'title.required'          => 'Tajuk project diperlukan.',
            'duration.required'       => 'Tempoh project diperlukan.',
            'amount.required'         => 'Harga project diperlukan.',
            'projectType.required'    => 'Jenis Projek diperlukan.',
            'department.required'    => 'Jenis jabatan diperlukan.',
        ];

        $validation = [
            'title'      => 'required',
            'duration'      => 'required',
            'amount'      => 'required',
            'department'      => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $projectTenderNo = $this->autoNumber->generateProjectTenderNo();

            $projectTender = new ProjectTender();
            $projectTender->PTDNo = $projectTenderNo;
            $projectTender->PTDTitle = $request->title;
            $projectTender->PTDPeriod = $request->duration;
            $projectTender->PTDAmt = $request->amount;
            $projectTender->PTD_PTCode = $request->projectType;
            $projectTender->PTD_DPTCode = $request->department;
            $projectTender->PTD_PTSCode = "NEW";
            $projectTender->PTDCB = $user->USCode;
            $projectTender->PTDMB = $user->USCode;
            $projectTender->save();

            $tenderDept = new ProjectTenderDept();
            $tenderDept->PTDD_PTDNo      = $projectTenderNo;
            $tenderDept->PTDD_DPTCode    = $request->department;
            $tenderDept->PTDDCB          = $user->USCode;
            $tenderDept->PTDDMB          = $user->USCode;
            $tenderDept->save();


            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => route('pelaksana.projectTender.edit',[$projectTenderNo]),
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }
    }

    public function edit($id){

        $projectType = $this->dropdownService->jenis_projek();
        $department = $this->dropdownService->department();
        $meetingType = $this->dropdownService->meetingTypeAll();
        $projectTenderStatus = $this->dropdownService->projectTenderStatus();
        // $departmentAll = $this->dropdownService->departmentAll();
        $yt = $this->dropdownService->yt();

        $projectTender = ProjectTender::where('PTDNo',$id)->first();

        $arrayDepartment = [];

        foreach($projectTender->projectTenderDept as $tenderDepart){

            array_push($arrayDepartment, $tenderDepart->PTDD_DPTCode);

        }

        $now = Carbon::now();

        if ($now instanceof DateTime) {
            $dateAfterContractPeriod = clone $now; // Create a copy of $dateNow
            // $dateAfterContractPeriod->add(new DateInterval('P' . $contractPeriodInMonths . 'M')); // Add months
            $dateAfterContractPeriod->add(new DateInterval('P' . $projectTender->PTDPeriod . 'M'));

        } else {
            // Handle the case where $dateNow is not a DateTime object
            $dateAfterContractPeriod = null; // Or any other error handling as needed
        }

        if ($now instanceof DateTime && $dateAfterContractPeriod instanceof DateTime) {
            // Calculate the difference between $dateNow and $dateAfterContractPeriod
            $interval = $now->diff($dateAfterContractPeriod);

            // Get the total number of days
            $totalDays = $interval->days;

            // Now, $totalDays contains the difference in days between the two dates
        } else {
            // Handle the case where $dateNow or $dateAfterContractPeriod is not a DateTime object
            $totalDays = 0; // Or any other error handling as needed
        }

        $ptscode = $projectTender->PTD_PTSCode ?? 'NEW';

        $projectTender->statusProjek =  $projectTenderStatus[$ptscode] ;


        return view('pelaksana.projectTender.edit',
        compact('projectType','department','projectTender','arrayDepartment','meetingType', 'yt', 'totalDays')
        );
    }

    public function updateMaklumatProjekTender(Request $request){

        $messages = [
            'title.required'          => 'Tajuk project diperlukan.',
            'duration.required'       => 'Tempoh project diperlukan.',
            'amount.required'         => 'Harga project diperlukan.',
            'projectType.required'    => 'Jenis Projek diperlukan.',
            'department.required'    => 'Jenis jabatan diperlukan.',
        ];

        $validation = [
            'title'      => 'required',
            'duration'      => 'required',
            'amount'      => 'required',
            'department'      => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $projectTenderNo = $request->projectTenderNo;

            $projectTender = ProjectTender::where('PTDNo',$projectTenderNo)->first();
            $projectTender->PTDTitle = $request->title;
            $projectTender->PTDPeriod = $request->duration;
            $projectTender->PTDAmt = $request->amount;
            $projectTender->PTD_PTCode = $request->projectType;
            $projectTender->PTD_DPTCode = $request->department;


            $projectTender->save();

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => route('pelaksana.projectTender.edit',[$projectTenderNo]),
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateMklumatMilestone(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $old_projectTenderMilestone = ProjectTenderMilestone::where('PTDM_PTDNo', $request->PTDNo)->get();

            $PTDMIDs = $request->PTDMID;
            $PTDMDescs = $request->PTDMDesc;
            $PTDMWorkDays = $request->PTDMWorkDay;
            $PTDMWorkPercents = $request->PTDMWorkPercent;
            $PTDMClaimables = $request->PTDMClaimable;
            $PTDEstimateAmts = $request->PTDEstimateAmt;

            // ARRAY UPDATE MULTIPLE ROW
            if(count($old_projectTenderMilestone) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_projectTenderMilestone as $oprojectTenderMilestone){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($PTDMIDs as $PTDMID){
                        if($oprojectTenderMilestone->PTDMID == $PTDMID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $oprojectTenderMilestone->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($PTDMIDs as $key => $PTDMID){
                    $new_projectTenderMilestone = ProjectTenderMilestone::where('PTDM_PTDNo', $request->PTDNo)
                        ->where('PTDMID', $PTDMID)->first();
                    if(!$new_projectTenderMilestone){
                        $new_projectTenderMilestone = new ProjectTenderMilestone();
                        $new_projectTenderMilestone->PTDM_PTDNo = $request->PTDNo;
                        $new_projectTenderMilestone->PTDMCB = Auth::user()->USCode;
                    }
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                    $new_projectTenderMilestone->PTDMSeq = $key+1;
                    $new_projectTenderMilestone->PTDMDesc = $PTDMDescs[$key];
                    $new_projectTenderMilestone->PTDMWorkPercent = $PTDMWorkPercents[$key];
                    $new_projectTenderMilestone->PTDMWorkDay = $PTDMWorkDays[$key];
                    $new_projectTenderMilestone->PTDMClaimable = $PTDMClaimables[$key];
                    if($PTDMClaimables[$key] == 1){
                        $new_projectTenderMilestone->PTDEstimateAmt = $PTDEstimateAmts[$key];
                    }
                    else{
                        $new_projectTenderMilestone->PTDEstimateAmt = null;
                    }
                    $new_projectTenderMilestone->save();
                }
            }
            else{
                foreach($PTDMIDs as $key => $PTDMID){
                    $new_projectTenderMilestone = new ProjectTenderMilestone();
                    $new_projectTenderMilestone->PTDM_PTDNo = $request->PTDNo;
                    $new_projectTenderMilestone->PTDMCB = Auth::user()->USCode;
                    $new_projectTenderMilestone->PTDMSeq = $key+1;
                    $new_projectTenderMilestone->PTDMDesc = $PTDMDescs[$key];
                    $new_projectTenderMilestone->PTDMWorkPercent = $PTDMWorkPercents[$key];
                    $new_projectTenderMilestone->PTDMWorkDay = $PTDMWorkDays[$key];
                    $new_projectTenderMilestone->PTDMClaimable = $PTDMClaimables[$key];
                    if($PTDMClaimables[$key] == 1){
                        $new_projectTenderMilestone->PTDEstimateAmt = $PTDEstimateAmts[$key];
                    }
                    else{
                        $new_projectTenderMilestone->PTDEstimateAmt = null;
                    }
                    $new_projectTenderMilestone->save();
                }
            }
            //END HERE


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.projectTender.edit',[$request->PTDNo,'flag'=>'1']),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateMaklumatDepart(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $projectTenderNo = $request->projectTenderNo;

            if($request->departmentCode){

                $datas = $request->departmentCode;

                $oldDatas = ProjectTenderDept::where('PTDD_PTDNo',$projectTenderNo)->get();

                foreach($request->departmentCode as $data){


                    $exists = $oldDatas->contains('PTDD_DPTCode', $data);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $tenderDept = new ProjectTenderDept();
                        $tenderDept->PTDD_PTDNo      = $projectTenderNo;
                        $tenderDept->PTDD_DPTCode    = $data;
                        $tenderDept->PTDDCB          = $user->USCode;
                        $tenderDept->PTDDMB          = $user->USCode;
                        $tenderDept->save();

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->PTDD_DPTCode, $datas)) {

                        // DELETE
                        $oldData->delete();

                    }

                }

            }

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => route('pelaksana.projectTender.edit',[$projectTenderNo,'flag'=>'2']),
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateMaklumatResult(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $projectTenderNo = $request->projectTenderNo;

            $projectTender = ProjectTender::where('PTDNo',$projectTenderNo)->first();
            $projectTender->PTDResult = $request->result;

            if($request->updateStatusResult == 1){

                if($projectTender->PTD_PTSCode == 'EM1V'){
                    $status = "EM1S";
//                    $status = "EM1S-RQ";

//                    $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//                    $approvalController->storeApproval($projectTenderNo, 'PTD-EM1');
                }
                elseif($projectTender->PTD_PTSCode == 'EM2V'){
                    $status = "EM2S";
//                    $status = "EM2S-RQ";

//                    $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//                    $approvalController->storeApproval($projectTenderNo, 'PTD-EM2');
                }
                elseif($projectTender->PTD_PTSCode == 'EM3V'){
                    $status = "EM3S";
//                    $status = "EM3S-RQ";

//                    $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//                    $approvalController->storeApproval($projectTenderNo, 'PTD-EM3S');
                }else{
                    $status = "COMPLETE";
//                    $status = "COMPLETE-RQ";

//                    $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//                    $approvalController->storeApproval($projectTenderNo, 'PTD-SM');
                }

                $projectTender->PTDComplete = 1;
                $projectTender->PTD_PTSCode = $status;

            }
            else if($request->updateStatusResult == 2){

                $projectTender->PTDComplete = 1;
                $projectTender->PTD_PTSCode = 'CANCEL';

            }

            $projectTender->save();

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => route('pelaksana.projectTender.edit',[$projectTenderNo,'flag'=>'4']),
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }
    }

    public function viewMeeting(Request $request){

        $projectTenderNo = $request->projectTenderNo;
        $mno = $request->meetingNo;
        $meetingLocation = $this->dropdownService->meetingLocation();

        $meeting = Meeting::where('MNo',$mno)->first();
        $meetingType = $this->dropdownService->meetingTypeAll();
        $tmscode = $this->dropdownService->meetingStatus();
        $crscode = $this->dropdownService->claimResultStatus();

        $projectTender = ProjectTender::where('PTDNo',$projectTenderNo)->first();

        if ($meeting == null || !isset($meeting)) {

            $countPTMeeting = MeetingPT::get()->count();

            $meetingDate = null;
            $meetingTime = null;
            $meetingTitle = $this->createMeetingTitle('MPT');
        } else {
            $meetingDate = Carbon::parse($meeting->MDate)->format('Y-m-d');
            $meetingTime = Carbon::parse($meeting->MTime)->format('H:i');
            $meetingTitle = $meeting->MTitle;
        }

        $meetingAttendanceLists = MeetingAttendanceList::where('MAL_MNo',$mno)->get();
        $departmentAll = $this->dropdownService->departmentAll();

        $meeting['meetingDate'] = $meetingDate;
        $meeting['meetingTime'] = $meetingTime;
        $meeting['meetingTitle'] = $meetingTitle;

        $fileAttachDownloadMN = FileAttach::where('FAFileType','MT-MN')
            ->first();

        return view('pelaksana.projectTender.include.viewMeeting',
        compact('projectTenderNo','meetingType','meeting','tmscode','crscode','projectTender','fileAttachDownloadMN'
        ,'meetingAttendanceLists','departmentAll','meetingLocation'
        )
        );

    }

    public function projectTenderDatatable(Request $request){

        $user = Auth::user();

        $query = ProjectTender::orderby('PTDNo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PTDNo', function($row){

                $route = route('pelaksana.projectTender.edit',[$row->PTDNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->PTDNo.' </a>';

                return $result;
            })
            ->editColumn('PTD_PTCode', function($row) {

                $type = $this->dropdownService->jenis_projek();

                return $type[$row->PTD_PTCode];

            })
            ->editColumn('PTD_DPTCode', function($row) {

                $type = $this->dropdownService->department();

                return $type[$row->PTD_DPTCode];

            })
            ->editColumn('PTD_PTSCode', function($row) {

                $type = $this->dropdownService->projectTenderStatus();

                return $type[$row->PTD_PTSCode ?? 'NEW'] ?? $type['TD'];

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['PTDNo','PTD_PTCode','PTD_DPTCode','PTD_PTSCode'])
            ->make(true);


    }


    public function mesyuaratDatatable(Request $request){

        $user = Auth::user();

        $ptdNo = $request->ptdNo;

        $query = Meeting::whereHas('meetingPT', function ($query2) use ($ptdNo) {
            $query2->where('MPT_PTDNo', $ptdNo);
        })
        ->get();

        return DataTables::of($query)
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

                $result = '<a href="#" id="MT'.$row->MNo.'" data-bs-toggle="modal" data-bs-stacked-modal="#meetingModal"  onclick="openModalMesyuarat(\'' . $row->MNo . '\',\'' . $row->MStatus . '\',\'' . $row->MSSentInd . '\')">
                ' . $row->MNo . '
                </a>';
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
            ->rawColumns(['mType', 'MNo','MDate','MTime','MStatus'])
            ->make(true);
    }

    public function templateMilestoneDatatable(Request $request){

        $user = Auth::user();

        $projectTender = ProjectTender::where('PTDNo', $request->idPTDNo)->first();

        $query = TemplateMilestone::where('TM_PTCode', $projectTender->PTD_PTCode)
            ->where('TM_DPTCode', $projectTender->PTD_DPTCode)->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('action', function($row){

                $result = '<button onclick="pilihMilestone(\' '.$row->TMCode.' \')" class="btn btn-primary btn-sm">Pilih</button>';

                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['TMDesc','action'])
            ->make(true);


    }

    public function chooseMilestone(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $projectTender = ProjectTender::where('PTDNo', $request->idPTDNo)->first();

            $old_projectTenderMilestone = ProjectTenderMilestone::where('PTDM_PTDNo', $request->idPTDNo)->get();

            foreach($old_projectTenderMilestone as $oprojectTenderMilestone){
                $oprojectTenderMilestone->delete();
            }

            $now = Carbon::now();

            if ($now instanceof DateTime) {
                $dateAfterContractPeriod = clone $now; // Create a copy of $dateNow
                // $dateAfterContractPeriod->add(new DateInterval('P' . $contractPeriodInMonths . 'M')); // Add months
                $dateAfterContractPeriod->add(new DateInterval('P' . $projectTender->PTDPeriod . 'M'));

            } else {
                // Handle the case where $dateNow is not a DateTime object
                $dateAfterContractPeriod = null; // Or any other error handling as needed
            }

            if ($now instanceof DateTime && $dateAfterContractPeriod instanceof DateTime) {
                // Calculate the difference between $dateNow and $dateAfterContractPeriod
                $interval = $now->diff($dateAfterContractPeriod);

                // Get the total number of days
                $totalDays = $interval->days;

                // Now, $totalDays contains the difference in days between the two dates
            } else {
                // Handle the case where $dateNow or $dateAfterContractPeriod is not a DateTime object
                $totalDays = 0; // Or any other error handling as needed
            }

            $templateMilestoneDets = TemplateMilestoneDet::where('TMD_TMCode', $request->tmCode)
                ->orderBy('TMDSeq')->get();

            $total = 0;

            if(count($templateMilestoneDets) > 0){
                foreach ($templateMilestoneDets as $templateMilestoneDet){

                    if($totalDays > 0){
                        if($templateMilestoneDet->TMDPercent > 0){

                            $beforeRound = $templateMilestoneDet->TMDPercent / 100 * $totalDays;

                            $PTDMWorkDay = round($beforeRound);
                        }
                        else{
                            $PTDMWorkDay = 0;
                        }
                    }
                    else{
                        $PTDMWorkDay = 0;
                    }

                    $new_projectTenderMilestone = new ProjectTenderMilestone();
                    $new_projectTenderMilestone->PTDM_PTDNo = $request->idPTDNo;
                    $new_projectTenderMilestone->PTDMCB = Auth::user()->USCode;
                    $new_projectTenderMilestone->PTDMSeq = $templateMilestoneDet->TMDSeq;
                    $new_projectTenderMilestone->PTDMDesc = $templateMilestoneDet->TMDDesc;
                    $new_projectTenderMilestone->PTDMWorkPercent = $templateMilestoneDet->TMDPercent;
                    $new_projectTenderMilestone->PTDMWorkDay = $PTDMWorkDay;
                    $new_projectTenderMilestone->PTDMClaimable = null;
                    $new_projectTenderMilestone->PTDEstimateAmt = null;
                    $new_projectTenderMilestone->save();

                    $total += $PTDMWorkDay;
                }
            }

            if($total != $totalDays){
                $dif = $total - $totalDays;

                $new_last_projectTenderMilestone = ProjectTenderMilestone::where('PTDM_PTDNo', $request->idPTDNo)
                    ->orderBy('PTDMSeq', 'DESC')
                    ->first();
                $new_last_projectTenderMilestone->PTDMWorkDay = $new_last_projectTenderMilestone->PTDMWorkDay-$dif;
                $new_last_projectTenderMilestone->save();
            }

            DB::commit();

            return true;

        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }
    }

}
