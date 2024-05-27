<?php

namespace App\Http\Controllers\Contractor\Milestone;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralNotificationController;
use App\Models\ProjectMileStoneDet;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use App\Http\Controllers\Perolehan\Tender\TenderController;
use App\Services\DropdownService;
use App\Models\ProjectMileStone;
use App\Models\Project;
use DateInterval;
use DateTime;

class MilestoneController extends Controller
{
    public function __construct(DropdownService $dropdownService, TenderController $tenderController)
    {
        $this->dropdownService = $dropdownService;
        $this->tenderController = $tenderController;
    }

    public function index(){

        $projectNo = Session::get('project');

        $negeri = $this->dropdownService->negeri();
        $jenis_projek = $this->dropdownService->jenis_projek();
        $department = $this->dropdownService->department();
        $projectStatus = $this->dropdownService->projectStatus();
        $yt = $this->dropdownService->yt();

        $project = Project::where('PTNo',$projectNo)->first();
        $project['SAKDate'] = Carbon::parse($project->PTSAKDate)->format('d/m/Y');
        $project['status'] = $projectStatus[$project->PT_PSCode];

        $projectMileStone = $project->projectMilestone;
        $totalDayMileStone = 0;

        if($project->projectMileStone){
            foreach($projectMileStone as $pms){
                $pms['startDate'] = Carbon::parse($pms->PMStartDate)->format('Y-m-d');
                $pms['endDate'] = Carbon::parse($pms->PMEndDate)->format('Y-m-d');

                $totalDayMileStone += $pms->PMWorkDay;

            }
        }
        $commentLog = $project->commentLog;

        if($project->commentLog){
            foreach($commentLog as $comment){
                $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');

            }
        }

        $project['totalDayMileStone'] = $totalDayMileStone;

        $contractPeriodInMonths = $project->tenderProposal->tender->TDContractPeriod ?? 0;

        if(empty($project->PTStartDate)){
            $dateNow = $project->PTMD;
        }else{
            $dateNow = new DateTime($project->PTStartDate);
        }

        // Calculate the total number of days by adding the contract period in months to $dateNow// Calculate the total number of days by adding the contract period in months to $dateNow
        if ($dateNow instanceof DateTime) {
            $dateAfterContractPeriod = clone $dateNow; // Create a copy of $dateNow
            // $dateAfterContractPeriod->add(new DateInterval('P' . $contractPeriodInMonths . 'M')); // Add months
            $dateAfterContractPeriod->add(new DateInterval('P' . $contractPeriodInMonths . 'M'));

        } else {
            // Handle the case where $dateNow is not a DateTime object
            $dateAfterContractPeriod = null; // Or any other error handling as needed
        }

        // Calculate the difference in days between $dateNow and $dateAfterContractPeriod
        if ($dateNow instanceof DateTime && $dateAfterContractPeriod instanceof DateTime) {
            // Calculate the difference between $dateNow and $dateAfterContractPeriod
            $interval = $dateNow->diff($dateAfterContractPeriod);

            // Get the total number of days
            $totalDays = $interval->days;

            // Now, $totalDays contains the difference in days between the two dates
        } else {
            // Handle the case where $dateNow or $dateAfterContractPeriod is not a DateTime object
            $totalDays = null; // Or any other error handling as needed
        }

        $project['totalDays'] = $totalDays;
        $project['balanceDays'] = $totalDayMileStone - $totalDays;

        return view('contractor.milestone.create',compact('project','projectMileStone','commentLog', 'yt'));

    }

    public function store(Request $request){

        $messages = [
            'title.required'        => 'Tajuk kandungan diperlukan.',
            // 'startDate.required'    => 'Tarikh mula diperlukan.',
            // 'endDate.required'      => 'Tarikh akhir diperlukan.',
            'workday.required'      => 'Masa diperlukan.',
            'percetage.required'      => 'Peratus kerja diperlukan.',
//            'claimable.required'      => 'Boleh Dituntut diperlukan.',
            'dayBalance.in'        => 'Sila pastikan tiada baki terkurang atau terlebih bagi masa.',
            'percentBalance.in'        => 'Sila pastikan tiada baki terkurang atau terlebih bagi peratus kerja.',
            // 'totalEstimate.in'      => 'Sila pastikan tiada baki terkurang atau terlebih bagi jumlah anggaran.'
            'anggaranBalance.in'      => 'Sila pastikan tiada baki terkurang atau terlebih bagi jumlah anggaran.'
        ];

        $validation = [
            'title'         => 'required',
            // 'startDate'     => 'required',
            // 'endDate'       => 'required',
            'workday' => 'required',
            'percetage' => 'required',
            'dayBalance' => 'in:0',
            'percentBalance' => 'in:0',
            // 'totalEstimate' => 'in:0',
            'anggaranBalance' => 'in:0',
//            'claimable' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $sendStatus    = $request->sendStatus;
            $projectNo     = $request->projectNo;
            $mileNo        = $request->mileNo;
            $title         = $request->title;
            $startDate     = $request->startDate;
            $endDate       = $request->endDate;
            $workday       = $request->workday;
            $percetage     = $request->percetage;
            $claimable     = $request->claimable;
            $estimateAmt = $request->estimateAmt;
            $totalEstimate = $request->totalEstimate;
            $totalworkDays = $request->totalDays;
            $workPercent   = $request->totalKerjaPercent;


            $project = Project::where('PTNo',$projectNo)->first();
            $projectMileStone = $project->projectMilestone;

            $totalDayMileStone = 0;

            if($project->projectMileStone){
                foreach($projectMileStone as $pms){
                    $pms['startDate'] = Carbon::parse($pms->PMStartDate)->format('Y-m-d');
                    $pms['endDate'] = Carbon::parse($pms->PMEndDate)->format('Y-m-d');

                    $totalDayMileStone += $pms->PMWorkDay;

                }
            }

            $LATotalAmount = $project->letterAccept->LATotalAmount;

            if($totalEstimate != $LATotalAmount){
                abort(400, ' Jumlah Projek Amaun tidak sama. Sila semak semula.');
            }


            //CHECKING DELETE AND INSERT NEW MULTIPLE DATA
            if(!empty($mileNo)){

                $oldDatas = ProjectMileStone::where('PM_PTNo',$projectNo)->get();

                //DELETE OLD DATA
                foreach ($oldDatas as $oldData) {
                    if (!in_array($oldData->PMID, $mileNo)) {

                        // DELETE
                        $oldData->delete();

                    }
                }

                //CHECK EXIST DATA
                $count = 0;
                $sequence = 0;

                foreach($mileNo as $id){

                    $desc       = $title[$count];
                    // $startdate  = $startDate[$count];
                    // $enddate    = $endDate[$count];
                    $day        = $workday[$count];
                    $percent    = $percetage[$count];
                    $claim        = $claimable[$count];
                    $estAmt    = $estimateAmt[$count];

                    if(!$desc == "" || !empty($desc)){

                        $sequence++;

                        $existData = ProjectMileStone::where('PM_PTNo',$projectNo)
                                        ->where('PMNo',$id)
                                        ->first();

                        if(empty($existData)){

                            //GENERATE KEY 3DIGIT INCREMENT
                            $latest = ProjectMileStone::where('PM_PTNo',$projectNo)
                                        ->orderBy('PMNo','desc')
                                        ->first();

                            if($latest){
                                $PMNo = $this->tenderController->increment3digit($latest->PMNo);
                            }
                            else{
                                $PMNo = $projectNo.'-'.$formattedCounter = sprintf("%03d", 1);
                            }

                            //INSERT
                            $newData = new ProjectMileStone();

                            $newData->PMNo         = $PMNo;
                            $newData->PMRefType    = 'PJ';
                            $newData->PMRefNo      = $projectNo;
                            $newData->PM_PTNo      = $projectNo;
                            $newData->PMSeq        = $sequence;
                            $newData->PMDesc       = $desc;
                            $newData->PMWorkDay    = $day;
                            $newData->PMEstimateAmt    = $estAmt;
                            $newData->PMWorkPercent    = $percent;
                            $newData->PMClaimInd   = $claim;
                            // $newData->PMStartDate  = $startdate;
                            // $newData->PMEndDate    = $enddate;
                            $newData->PMPriority     = 0;
                            $newData->PMProgress     = 1;
                            $newData->PMActive     = 1;
                            $newData->PM_PMSCode   = 'D';
                            $newData->PMCB         = $user->USCode;
                            $newData->PMMB         = $user->USCode;
                            $newData->save();

                        }else{

                            //UPDATE
                            $existData->PMSeq        = $sequence;
                            $existData->PMDesc       = $desc;
                            $existData->PMWorkDay    = $day;
                            $existData->PMEstimateAmt    = $estAmt;
                            $existData->PMWorkPercent  = $percent;
                            $existData->PMClaimInd     = $claim;
                            // $existData->PMStartDate  = $startdate;
                            // $existData->PMEndDate    = $enddate;
                            $existData->PM_PMSCode   = 'D';
                            $existData->PMActive       = 1;
                            $existData->PMMB         = $user->USCode;

                            $existData->save();
                        }

                    }
                    $count++;
                }

            }

            $existData = ProjectMileStone::where('PM_PTNo',$projectNo)
                ->get();

            if($sendStatus == 0){
                $projectStatus       = "N";
                $msg = 'Maklumat jadual tuntutan berjaya ditambah.';
            }else if($sendStatus == 1){
                $projectStatus       = "S";
                $msg = 'Maklumat jadual tuntutan berjaya dihantar. Sila menunggu untuk semakan!';
                $this->sendSubmitNotification($projectNo);

            }

            $project = Project::where('PTNo',$projectNo)->first();
            $project->PT_PSCode = $projectStatus;
            $project->PTMB = $user->USCode;
            $project->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.milestone.index'),
                'message' => $msg
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat jadual tuntutan tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }

    public function updateStatus($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $projectNo     = $id;
            $projectStatus = "S";

            $project = Project::where('PTNo',$projectNo)->first();
            $project->PT_PSCode = $projectStatus;
            $project->save();

            DB::commit();

            return redirect()->route('contractor.milestone.index');

            // return response()->json([
            //     'success' => '1',
            //     'redirect' => route('contractor.milestone.create',[$projectNo]),
            //     'message' => 'Maklumat milestone berjaya ditambah.'
            // ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat jadual tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }

    function sendSubmitNotification($id){

        //#SEND-NOTIFICATION-EXAMPLE
        //#NOTIF-001
        try {

            DB::beginTransaction();

            $user = Auth::user();

            $project = Project::where('PTNo',$id)->first();

            $tender = $project->tenderProposal->tender;

            $picTechnical = $tender->tenderPIC_T;

            $title = $project->PTNo . "menghantar senarai milestone.";
            $desc = $project->PTNo . "telah menghantar senarai maklumat milestone untuk disemak.";

            foreach($picTechnical as $pic){

                $refNo = $pic->TPIC_USCode;
                $notiType = 'SO';
                $code = 'PM-SM';

                $data = array(
                    'PTNo' => $project->PTNo
                );

                $notification = new GeneralNotificationController();
                $result = $notification->sendNotification($refNo,$notiType,$code,$data);

            }

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat milestone tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function edit($id){
        $claimInd = $this->dropdownService->claimInd();
        $yt = $this->dropdownService->yt();

        $milestone = ProjectMilestone::where('PMNo', $id)->first();

        $commentLog = $milestone->commentLog;
        $commentLog->each(function ($comment) {
            $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
        });

        return view('contractor.milestone.edit',
            compact('milestone', 'id', 'yt', 'claimInd')
        );
    }
    public function update (Request $request){
        try {

            DB::beginTransaction();

            $user = Auth::user();

            $PMNo     = $request->PMNo;
            $PMSCode = "I";

            $milestone = ProjectMileStone::where('PMNo',$PMNo)->first();
            $milestone->PM_PMSCode = $PMSCode;
            $milestone->PMSubmitDate = Carbon::now();
            $milestone->save();

            $checkWajib = 0;

            foreach($milestone->projectMilestoneDetM as $milestoneM){

                if( $milestoneM->PMDComplete != null && !$milestoneM->fileAttachPMD){
                    $checkWajib = 1;
                    break;
                }

            }

            if($checkWajib == 1){

                return response()->json([
                    'error' => '1',
                    'message' => 'Sila lengkapkan senarai lampiran yang wajib.'
                ], 400);

            }else{

                DB::commit();

                 return response()->json([
                     'success' => '1',
                     'redirect' => route('contractor.index'),
                     'message' => 'Maklumat Perbatuan berjaya dikemaskini.'
                 ]);

            }

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat Perbatuan tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function checklistMilestoneDatatable(Request $request){
        $projectNo = Session::get('project');

        $query = ProjectMileStoneDet::where('PMD_PMNo', $request->idMilestone)
            ->where('PMDType', 'M')
            ->orderBy('PMDSeq')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PMDComplete', function($row) {

                $result = '';

//                if($row->PMDComplete == null){
//
//                }
//                else{
//                    if($row->PMDComplete == 1){
//                        $result = '<i class="material-icons">check_box</i>';
//                    }
//                    else{
//                        $result = '<i class="material-icons">check_box_outline_blank</i>';
//                    }
//                }
                if($row->PMDComplete == null){
                    $result = '<i class="ki-solid ki-cross-square text-danger fs-2"></i>';
                }
                else{
                    $result = '<i class="ki-solid ki-check-square text-success fs-2"></i>';
                }

                return $result;
            })
            ->addColumn('action', function($row) {
                $result = '';

                if(in_array($row->milestone->PM_PMSCode, ["D", "N", "R"])){
                    $route = "openUploadModal('$row->PMDNo','" . Auth::user()->USCode . "','PMD','PMD','$row->PMDNo')";

                    $result .= '<a class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal" onclick="' . $route . '">
                                    <i class="ki-solid ki-folder-up fs-2"></i>Lampiran</a>
                                </a>';
                }

                if($row->fileAttachPMD){
                    $route2 = route('file.view', [$row->fileAttachPMD->FAGuidID]);
                    $result .= ' <a target="_blank" class="new modal-trigger btn btn-sm btn-light-primary" href="' . $route2 . '"><i class="ki-solid ki-eye fs-2"></i>Papar</a>';
                }

                return $result;
            })
            ->rawColumns(['indexNo', 'PMDComplete', 'action'])
            ->make(true);
    }
}
