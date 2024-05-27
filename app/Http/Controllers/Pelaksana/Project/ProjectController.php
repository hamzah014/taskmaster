<?php

namespace App\Http\Controllers\Pelaksana\Project;

use App\Helper\Custom;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralNotificationController;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Http\Controllers\SchedulerController;
use App\Models\InvoicePayment;
use App\Models\Tender;
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
use App\Models\ProjectMileStone;
use App\Models\Project;
use App\Models\CommentLog;
use App\Services\DropdownService;
use Yajra\DataTables\DataTables;
use App\Models\AutoNumber;
use App\Models\Contractor;
use App\Models\ExtensionOfTime;
use App\Models\KickOffMeeting;
use App\Models\ProjectClaimDet;
use App\Models\ProjectClaim;
use App\Models\ProjectBudget;
use App\Models\ProjectBudgetYear;
use App\Models\ProjectBudgetDet;
use App\Models\ProjectMileStoneDet;
use App\Models\TemplateClaimFile;
use DateInterval;
use DateTime;
use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\Invoice;
use App\Models\LetterAcceptance;
use App\Models\MeetingEOT;
use App\Models\SuratArahanKerja;
use App\Models\TenderApplication;
use App\Models\TenderProposal;
use App\Models\VariantOrder;
use App\Models\WebSetting;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions\F;

class ProjectController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        $closeType = $this->dropdownService->closeType();

        return view('pelaksana.project.index',
        compact(
            'closeType'
        ));
    }

    public function view($id){

        $negeri = $this->dropdownService->negeri();
        $jenis_projek = $this->dropdownService->jenis_projek();
        $department = $this->dropdownService->department();
        $projectStatus = $this->dropdownService->projectStatus();
        $mileStoneStatus = $this->dropdownService->mileStoneStatus();
        $claimProcess = $this->dropdownService->claimProcess();
        $claimProcessC = $this->dropdownService->claimProcessC();
        $templateChecklistMilestone = $this->dropdownService->templateChecklistMilestone();
        $templateChecklistClaim = $this->dropdownService->templateChecklistClaim();
        $closeType = $this->dropdownService->closeType();

        $projectNo = $id;

        $project = Project::where('PTNo',$id)->first();

        $SAK = SuratArahanKerja::where('SAKNo' , $project->PT_SAKNo)->first();

        $project['SAKDate'] = isset($SAK->SAKDate) ? Carbon::parse($SAK->SAKDate)->format('d/m/Y') : null;

        $project['status'] = $projectStatus[$project->PT_PSCode];

        $projectMileStone = ProjectMileStone::where('PMRefType','PJ')->where('PM_PTNo',$id)->get();
        $eotMileStone = ProjectMileStone::where('PMRefType','EOT')->where('PM_PTNo',$id)->get();
        $voMileStone = ProjectMileStone::where('PMRefType','VO')->where('PM_PTNo',$id)->get();
        $totalDayMileStone = 0;
        $totalPercentMileStone=0;

        if($project->projectMileStone){
            foreach($projectMileStone as $pms){

                $pms['startDate'] = Carbon::parse($pms->PMStartDate)->format('d/m/Y');
                $pms['endDate'] = Carbon::parse($pms->PMEndDate)->format('d/m/Y');
                $pms['milestoneStatus'] = $mileStoneStatus[$pms->PM_PMSCode ?? 'D'];

                if(count($pms->projectClaimM) > 0){
                    foreach($pms->projectClaimM as $projectClaimM){
                        if($projectClaimM->PC_PCPCode != 'DF'){
                            $pms['claimStatus'] = $projectClaimM->PCNo . ' - ' . $claimProcess[$projectClaimM->PC_PCPCode];
                        }
                    }

                }
                else{
                    $pms['claimStatus'] = 'Belum Tuntut';
                }

                $totalDayMileStone += $pms->PMWorkDay;
                $totalPercentMileStone += $pms->PMWorkPercent;

            }
        }

        $tender = $project->tenderProposal->tender;
        $tender['projectType'] = $jenis_projek[$tender->TD_PTCode];
        $tender['department'] = $department[$tender->TD_DPTCode];

        $tenderProposal = $project->tenderProposal;

        $contractor = $project->contractor;
        $contractor['state'] = $negeri[$contractor->COReg_StateCode] ?? '';

        $letterAccept = $tenderProposal->letterAcceptance;
        $letterAcceptSST = null;

        if($letterAccept){
            $letterAccept['SSTDate'] = $letterAccept ? Carbon::parse($letterAccept->LAConfirmDate)->format('d/m/Y') : null;

            $letterAcceptSST = $letterAccept ? $letterAccept->letterAcceptanceDet->where('LAD_MTCode','LA')->first() : null;

        }

        $commentLog = $project->commentLog;
        $commentLog->each(function ($comment) {
            $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
        });

        $project['totalDayMileStone'] = $totalDayMileStone;
        $project['totalPercentMileStone'] = $totalPercentMileStone;

        $contractPeriodInMonths = $project->tenderProposal->tender->TDContractPeriod;

        if(empty($project->PTStartDate)){
            $dateNow = $project->PTMD;
        }else{
            $dateNow = new DateTime($project->PTStartDate);
        }

        // Calculate the total number of days by adding the contract period in months to $dateNow// Calculate the total number of days by adding the contract period in months to $dateNow
        if ($dateNow instanceof DateTime) {
            $dateAfterContractPeriod = clone $dateNow; // Create a copy of $dateNow
            $dateAfterContractPeriod->add(new DateInterval('P' . $contractPeriodInMonths . 'M')); // Add months
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

        $totalWorkDaysEOT = 0;

        if(count($project->eotApprove) > 0){
            foreach( $project->eotApprove as $eot){
                $totalWorkDaysEOT += $eot->meetingEot->MEWorkday;
            }
        }

        $purchaseOrder = PurchaseOrder::with('invoices')->where('PO_PTNo' , $project->PTNo)->get();

        foreach ($purchaseOrder as $purchaseOrder) {
            $invoices = $purchaseOrder->invoices;

            //dd($invoices);
            foreach ($invoices as $invoice) {
                $IVAmt = $invoice->IVAmt;
                // Do something with $IVAmt
            }
        }

        $project['totalDays'] = $totalDays;
        $project['balanceDays'] = $totalDayMileStone - $totalDays;

        $project['balancePercent'] = 100 - $totalPercentMileStone;

        $currentYear = Carbon::now()->year;

        $projectBudget = ProjectBudget::where('PB_PTNo', $projectNo)->first();

        $projectBudgetAmt = ProjectBudgetYear::where('PBY_PTNo', $id)
                                            ->where('PBYContractNo',$project->PTContractNo)
                                            ->where('PBYYear', $currentYear)
                                            ->sum('PBYBudgetAmt');

        $totalClaimYearly = InvoicePayment::where('IVP_PTNo' , $id)->whereYear('IVPDate',$currentYear)->sum('IVPAmtPaid');

        $totalClaim = InvoicePayment::where('IVP_PTNo' , $id)->sum('IVPAmtPaid');

        $budgetBalance = $projectBudgetAmt - $totalClaimYearly;

        $projects = Project::where('PTStatus', 'SAK')
                            ->with('tenderProposal.tender')
                            ->get()
                            ->pluck('tenderProposal.tender.TDTitle', 'PTNo');

            $milestone = ProjectMilestone::where('PM_PTNo', $projectNo)
            // ->where('PMClaimInd', 1)
            // ->whereHas('projectClaim', function ($query) {
            //     $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
            // })
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

        $sumOfVOAmount = VariantOrder::where('VO_PTNo' , $id)->sum('VOAmount');


        //dd($id, $projectBudget, $totalClaim , $totalClaimYearly);

        return view('pelaksana.project.view',
            compact('project','contractor','tender','tenderProposal','projectMileStone','eotMileStone','voMileStone','letterAccept','commentLog',
                'letterAcceptSST', 'templateChecklistMilestone', 'templateChecklistClaim','claimProcessC' , 'totalWorkDaysEOT' , 'purchaseOrder' ,
                'projectBudgetAmt' , 'projectBudget', 'totalClaimYearly' , 'totalClaim' , 'projects' , 'closeType' , 'milestone' , 'sumOfVOAmount',
                'budgetBalance', 'SAK')
        );
    }

    public function commentStore(Request $request){

        $user = Auth::user();

		$messages = [
            'review_description.required' 	=> "Keterangan diperlukan.",
		];

		$validation = [
			'review_description' 	=> 'required',
		];


        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $CLNo              = $autoNumber->generateCommentLog();

			$projectNo      = $request->projectNo;
			$review		    = $request->review_description;
            $type           = "M";
            $projectStatus  = "R";


            $project = Project::where('PTNo',$projectNo)->first();
            $project->PT_PSCode = $projectStatus;
            $project->save();

            $commentLog = new CommentLog();
            $commentLog->CLNo = $CLNo;
            $commentLog->CLRefNo = $projectNo;
            $commentLog->CLType = $type;
            $commentLog->CL_USCode = $user->USCode;
            $commentLog->CLDesc = $review;
            $commentLog->save();

            $this->sendSubmitNotification($projectNo,$projectStatus);

            DB::commit();

			return response()->json([
				'success' => '1',
				'redirect' => route('pelaksana.project.view',[$projectNo]),
				'message' => 'Komen bagi milestone berjaya ditambah.'
			]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Komen bagi milestone tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }

    public function updateMileStoneStatus(Request $request, $id){

        $messages = [
            'milestoneTemplate.at_least_one_not_null' => "Template Senarai Semak Milestone diperlukan.",
            'claimTemplate.at_least_one_not_null' => "Template Senarai Semak Tuntutan diperlukan.",
        ];

        $validation = [
            'milestoneTemplate' => 'required|at_least_one_not_null',
            'claimTemplate' => 'required|at_least_one_not_null',
        ];

        $request->validate($validation, $messages);

        $user = Auth::user();

        try {

            DB::beginTransaction();

            $totalEstimate = $request->totalEstimate;

            $milestoneNos = $request->milestoneNo;
            $milestoneTemplates = $request->milestoneTemplate;
            $claimTemplates = $request->claimTemplate;
            $estimateAmts = $request->estimateAmt;

            $projectNo      = $id;
            $projectStatus  = "A";

            $project = Project::where('PTNo',$id)->first();
            $project->PT_PSCode = $projectStatus;
            $project->PTEstimateEndDate = $project->PTEndDate;

            $LATotalAmount = $project->letterAccept->LATotalAmount;


            if($totalEstimate != $LATotalAmount){
                abort(400, ' Jumlah Projek Amaun tidak sama. Sila semak semula.');
            }
            $project->save();

            $kickoffMeeting = KickOffMeeting::where('KOM_PTNo',$projectNo)->where('KOMStatus','SUBMIT')->first();

            $startDate = $kickoffMeeting->KOMStartDate ?? null;
            $startDate = Carbon::parse($startDate);

            foreach($milestoneNos as $key => $milestoneNo){

                $milestone = ProjectMileStone::where('PMNo', $milestoneNo)->first();

                if($kickoffMeeting){
                    $startDateOri = $startDate->copy();
                    $startDatetemp = $startDate->copy();

                    $milestone->PMStartDate = $startDatetemp; // Create a copy of $startDate
                    $milestone->PMProposeStartDate = $startDatetemp; // Create a copy of $startDate

                    $endDate = $startDate->copy()->addDays($milestone->PMWorkDay-1);

                    $milestone->PMEndDate = $endDate;
                    $milestone->PMProposeEndDate = $endDate;
                    // $milestone->save();

                    $startDate = $endDate->addDays(1)->copy();

                }

                $milestone->PMEstimateAmt = $estimateAmts[$key];
                $milestone->save();

                $pmNo = $milestone->PMNo;

                $templateClaimFileM = TemplateClaimFile::where('TCFCode', $milestoneTemplates[$key])->first();

                foreach($templateClaimFileM->claimFileDet as $claimDet){

                    $desc = $claimDet->TCFDDesc;
                    $uploadType = $claimDet->TCFDUploadType;

                    $latest = ProjectMileStoneDet::where('PMD_PMNo', $pmNo)
                            ->orderBy('PMDNo', 'desc')
                            ->first();

                    //#DIGIT-INCREMENT-USAGE
                    if($latest){
                        $PMDNo = $this->increment3DigitNo($latest->PMDNo,"PT");
                        $allData = ProjectMileStoneDet::where('PMD_PMNo', $pmNo)->get();
                        $seq = count($allData)+1;
                    }
                    else{
                        $PMDNo = $pmNo.'-'. sprintf("%03d", 1);
                        $seq = 1;
                    }

                    $newData                 = new ProjectMileStoneDet();
                    $newData->PMDNo          = $PMDNo;
                    $newData->PMD_PMNo       = $pmNo;
                    $newData->PMDType        = 'M';
                    $newData->PMDSeq         = $seq;
                    $newData->PMDDesc        = $desc;
                    // $newData->PMDUploadType  = $uploadType;
                    $newData->PMDCB          = $user->USCode;
                    $newData->PMDMB          = $user->USCode;
                    if($uploadType == 'M'){
                        $newData->PMDComplete    = 0;
                    }
                    $newData->save();

                }
                if($claimTemplates[$key] != '0'){
                    $templateClaimFileC = TemplateClaimFile::where('TCFCode', $claimTemplates[$key])->first();


                    foreach($templateClaimFileC->claimFileDet as $claimDet2){

                        $desc = $claimDet2->TCFDDesc;
                        $uploadType = $claimDet2->TCFDUploadType;

                        $latest = ProjectMileStoneDet::where('PMD_PMNo', $pmNo)
                            ->orderBy('PMDNo', 'desc')
                            ->first();

                        //#DIGIT-INCREMENT-USAGE
                        if($latest){
                            $PMDNo = $this->increment3DigitNo($latest->PMDNo,"PT");
                            $allData = ProjectMileStoneDet::where('PMD_PMNo', $pmNo)->get();
                            $seq = count($allData)+1;
                        }
                        else{
                            $PMDNo = $pmNo.'-'. sprintf("%03d", 1);
                            $seq = 1;
                        }

                        $newData                 = new ProjectMileStoneDet();
                        $newData->PMDNo          = $PMDNo;
                        $newData->PMD_PMNo       = $pmNo;
                        $newData->PMDType        = 'C';
                        $newData->PMDSeq         = $seq;
                        $newData->PMDDesc        = $desc;
                        // $newData->PMDUploadType  = $uploadType;
                        $newData->PMDCB          = $user->USCode;
                        $newData->PMDMB          = $user->USCode;
                        if($uploadType == 'M'){
                            $newData->PMDComplete    = 0;
                        }
                        $newData->save();

                    }
                }
            }

            $this->sendSubmitNotification($projectNo,$projectStatus);

            DB::commit();


			return response()->json([
				'success' => '1',
				'redirect' => route('pelaksana.project.view',[$projectNo]),
				'message' => 'Jadual Projek berjaya diterima.'
			]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Jadual Projek tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }



    }

    public function projectDatatable(Request $request){

        $user = Auth::user();

        $query = Project::whereHas('tenderProposal.tender')
        ->with('fileAttachCPRDC')
        ->orderBy('PTNo', 'DESC')->get();

        return DataTables::of($query)
            ->editColumn('PTNo', function($row) {

                $route = route('pelaksana.project.view',[$row->PTNo]);
                $result = '<a href="'.$route.'">'.$row->PTNo.'</a>';

                return $result;
            })
            ->addColumn('projectName', function($row) {

                $result = $row->tenderProposal->tender->TDTitle;

                return $result;
            })
            ->addColumn('projectType', function($row) {

                $jenis_projek = $this->dropdownService->jenis_projek();

                $result = $jenis_projek[$row->tenderProposal->tender->TD_PTCode];

                return $result;
            })
            ->addColumn('COName', function($row) {

                $result = $row->contractor->COName;

                return $result;
            })
            ->addColumn('tenderNo', function($row) {

                $result = $row->tenderProposal->tender->TDNo;

                return $result;
            })
            ->editColumn('PTSAKDate', function($row) {

                $formattedDate = Carbon::parse($row->PTSAKDate)->format('d/m/Y');

                return $formattedDate;
            })
            ->addColumn('status', function($row) {

                $projectStatus = $this->dropdownService->projectStatus();

                $result = $projectStatus[$row->PT_PSCode];

                return $result;
            })
            ->addColumn('action', function($row) {

                $result = '<a class="btn btn-light-primary btn-sm" href="#updateContractModal" data-bs-toggle="modal" data-bs-stacked-modal="#updateContractModal" onclick="updateProject(\'' . $row->PTNo . '\',\'' . $row->PTContractNo . '\')" >
                Kemaskini Nombor Projek</a>';

                if( $row->PT_PPCode == 'CP'){
                    $result .= '<a class="btn btn-light-info btn-sm" onclick="generateCloseProj(\'' . $row->PTNo . '\')">Surat Penutupan Projek</a>';
                }
                else{
                    $result .= '<a class="btn btn-light-danger btn-sm" href="#tutupProjekModal" data-bs-toggle="modal" data-bs-stacked-modal="#meetingModal" onclick="closeProject(\'' . $row->PTNo . '\')">
                    Tutup Projek</a>';
                }
                return $result;
            })
            ->rawColumns(['PTNo', 'projectName','projectType','COName','tenderNo','PTSAKDate','status','action'])
            ->make(true);
    }

    public function createTutup(){
        $closeType = $this->dropdownService->closeType();

        $projects = Project::where('PTStatus', 'SAK')
            ->with('tenderProposal.tender')
            ->get()
            ->pluck('tenderProposal.tender.TDTitle', 'PTNo');

        return view('pelaksana.project.createTutup',
            compact('projects', 'closeType')
        );
    }

    public function storeTutup(Request $request){

        $messages = [
            'project.required' 	    => "Projek diperlukan.",
            'remark.required' 	    => "Catatan diperlukan.",
            'closetype.required' 	=> "Jenis Penutupan Projek diperlukan.",
        ];

        $validation = [
            'project' 	=> 'required',
            'remark' 	=> 'required',
            'closetype' => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $project = Project::where('PTNo', $request->project)
                ->first();
            $project->PTCloseRemark = $request->remark;
            $project->PT_PCTCode = $request->closetype;
            $project->PTRating = $request->rating;
            $project->PTRatingMax = $request->ratingMax;
            $project->PT_PPCode = 'CP';
            $project->save();

            $result = $this->processCloseProj($project->PTNo);

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.project.index'),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ],400);
        }
    }

    function sendSubmitNotification($id,$status){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $project = Project::where('PTNo',$id)->first();

            if($status == 'R'){
                //#NOTIF-002
                $code = 'PM-RV';


            }elseif($status == 'A'){
                //#NOTIF-003
                $code = 'PM-AP';

            }

            $refNo = $project->PT_CONo;
            $notiType = 'CO';

            $data = array(
                'PTNo' => $project->PTNo
            );

            $notification = new GeneralNotificationController();
            $result = $notification->sendNotification($refNo,$notiType,$code,$data);

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

    function sendCloseProjNotif($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $project = Project::where('PTNo',$id)->first();

            $notification = new GeneralNotificationController();

            $tender = $project->tenderProposal->tender;

            //SEND NOTIFICATION TO PIC - PELAKSANA, PEROLEHAN, BOSS
            $tenderPIC = $tender->tenderPIC;
            //#NOTIF-015
            $code = 'PT-C';

            $data = array(
                'PTNo' => $project->PTNo,
            );

            $output = array();

            if(!empty($tenderPIC)){

                foreach($tenderPIC as $pic){

                    $refNo = $pic->TPIC_USCode;

                    if($pic->TPICType == 'T'){
                        $notiType = "SO";

                    }else if($pic->TPICType == 'P'){
                        $notiType = "PO";

                    }else if(isset($pic->userSV) && $pic->userSV !== null){
                        $notiType = "SO";

                    }

                    $result = $notification->sendNotification($refNo,$notiType,$code,$data);
                    array_push($output,$result);

                }

            }
            DB::commit();

            return $output;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }
    }

    public function editMilestone($id){
        $claimInd = $this->dropdownService->claimInd();
        $yt = $this->dropdownService->yt();

        $milestone = ProjectMilestone::where('PMNo', $id)->first();

        $commentLog = $milestone->commentLog;
        $commentLog->each(function ($comment) {
            $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
        });

        return view('pelaksana.project.editMilestone',
            compact('milestone', 'id', 'claimInd', 'yt')
        );
    }

    public function createMilestoneDet($id){
        $yt = $this->dropdownService->yt();

        $milestone = ProjectMilestone::where('PMNo', $id)->first();

        return view('pelaksana.project.createMilestoneDet',
            compact('milestone', 'id', 'yt')
        );
    }

    public function storeMilestoneDet(Request $request){

        $messages = [
            'keterangan.required' 	=> "Keterangan diperlukan.",
            'wajib.required' 	    => "Wajib diperlukan.",
        ];

        $validation = [
            'keterangan' 	=> 'required',
            'wajib' 	=> 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $last_milestoneDet = ProjectMileStoneDet::where('PMD_PMNo', $request->idMilestone)
                ->orderBy('PMDSeq', 'DESC')
                ->first();

            if($last_milestoneDet){
                $seq = $last_milestoneDet->PMDSeq +1;
                $PMDNo = $this->increment3digitDet($last_milestoneDet->PMDNo);
            }
            else{
                $seq = 1;
                $PMDNo = $request->idMilestone.'-'.$formattedCounter = sprintf("%03d", $seq);
            }

            $milestoneDet = new ProjectMileStoneDet();
            $milestoneDet->PMDNo    = $PMDNo;
            $milestoneDet->PMDType  = $request->type;
            $milestoneDet->PMD_PMNo = $request->idMilestone;
            $milestoneDet->PMDSeq   = $seq;
            $milestoneDet->PMDDesc  = $request->keterangan;
            if($request->wajib == 1){
                $milestoneDet->PMDComplete = 0;
            }
            $milestoneDet->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.project.milestone.edit', [$request->idMilestone]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ],400);
        }
    }

    public function deleteMilestoneDet ($id){
        try {
            DB::beginTransaction();

            $milestoneDet = ProjectMileStoneDet::where('PMDNo', $id)->first();
            $PMD_PMNo = $milestoneDet->PMD_PMNo;
            $milestoneDet->delete();

            DB::commit();

            return redirect()->route('pelaksana.project.milestone.edit', [$PMD_PMNo] );

        }catch (\Throwable $e) {
            DB::rollback();

            return redirect()->route('pelaksana.project.milestone.edit', [$PMD_PMNo] );
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
                $route3 = route('pelaksana.project.milestone.deleteDet', [$row->PMDNo]);

                $result .= '<a class="btn btn-sm btn-light-primary" href="'.$route3.'"><i class="ki-solid ki-trash fs-2"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo', 'PMDComplete', 'action'])
            ->make(true);
    }

    public function checklistClaimDatatable(Request $request){
        $projectNo = Session::get('project');

        $query = ProjectMileStoneDet::where('PMD_PMNo', $request->idMilestone)
            ->where('PMDType', 'C')
            ->orderBy('PMDSeq')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PMDComplete', function($row) {

                $result = '';

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
                $route3 = route('pelaksana.project.milestone.deleteDet', [$row->PMDNo]);

                $result .= '<a class="btn btn-light-primary" href="'.$route3.'"><i class="ki-solid ki-trash fs-2"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo', 'PMDComplete', 'action'])
            ->make(true);
    }

    public function increment3digitDet($no){
        $prefix = substr($no, 0, 15);   // "TD"

        // Extract last 3 digits
        $last3Digits = substr($no, -3, 3); // "001"

        // Convert to integer, increment, and format with leading zeros
        $incrementedLast3Digits = str_pad((int)$last3Digits + 1, 3, "0", STR_PAD_LEFT);

        // Combine all parts
        $incrementedno = $prefix . $incrementedLast3Digits;

        return $incrementedno;
    }

    public function completeMilestoneDet(Request $request){
        try {
            DB::beginTransaction();

            $schedulerController = new SchedulerController(new DropdownService(), new AutoNumber());

            $schedulerController->calculateCompleteMilestone($request);

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.project.view', [$request->PTNo]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ],400);
        }
    }

    public function update(Request $request){
        try {
            DB::beginTransaction();

            $PMNo    = $request->PMNo;
            $PMClaimInd = $request->claimable;

            $milestone = ProjectMileStone::where('PMNo',$PMNo)->first();
            $milestone->PMClaimInd = $PMClaimInd;
            $milestone->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.project.milestone.edit', [$request->PMNo]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ],400);
        }
    }

    public function reviewMilestone($id){
        $claimInd = $this->dropdownService->claimInd();
        $yt = $this->dropdownService->yt();

        $milestone = ProjectMilestone::where('PMNo', $id)->first();

        $commentLog = $milestone->commentLog;
        $commentLog->each(function ($comment) {
            $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
        });

        $validDateFrom = '2023-09-01';
        $validDateTo = '2023-10-07';

        return view('pelaksana.project.reviewMilestone',
            compact('milestone', 'id', 'yt', 'claimInd', 'validDateFrom', 'validDateTo')
        );
    }

    public function reviewChecklistMilestoneDatatable(Request $request){
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
                if($row->fileAttachPMD){
                    $route2 = route('file.view', [$row->fileAttachPMD->FAGuidID]);
                    $result .= ' <a target="_blank" class="btn-sm fw-bold btn btn-light-primary" href="' . $route2 . '"><i class="ki-solid ki-eye fs-2"></i>Papar</a>';
                }

                return $result;
            })
            ->rawColumns(['indexNo', 'PMDComplete', 'action'])
            ->make(true);
    }

    public function editProject($id){
        $projectNo = Session::get('project');

        $project = Project::where('PTNo',$id)->first();

        $tender = $project->tenderProposal->tender;

        $tenderProposal = $project->tenderProposal;

        $contractor = $project->contractor;

        return view('pelaksana.project.editContractNo',
            compact('project','contractor','tender','tenderProposal')
        );

    }

    public function updateContractNo(Request $request){
        try {
            DB::beginTransaction();

            $PTNo    = $request->PTNo;
            $contractNo = $request->contractNo;

            $project = Project::where('PTNo',$PTNo)->first();
            $project->PTContractNo = $contractNo;
            $project->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.project.view', [$request->PTNo]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ],400);
        }
    }

    public function purchaseOrderDatatable(Request $request){

        $project = $request->idProject;

        //$purchaseOrder = PurchaseOrder::with('invoices')->where('PO_PTNo' , $project->PTNo)->get();
        //$query = PurchaseOrder::where('PO_PTNo', $request->idProject)->get();

        $query = PurchaseOrder::where('PO_PTNo', $request->idProject)
                                ->select( 'TRPurchaseOrder.PORefNo', 'TRPurchaseOrder.POApprovedDate','TRInvoice.IVAmt' ,
                                'TRInvoice.IVRefNo' , 'TRInvoice.IVDate' ,
                                'TRInvoicePayment.IVPAmtPaid' , 'TRInvoicePayment.IVPDate')
                                ->leftjoin("TRInvoice",function($join){
                                    $join->on("TRInvoice.IV_PORefID","TRPurchaseOrder.PORefID")
                                        ->on("TRInvoice.IV_PTNo","TRPurchaseOrder.PO_PTNo")
                                        ->on("TRInvoice.IVContractNo","TRPurchaseOrder.POContractNo");
                                })
                                ->leftjoin("TRInvoicePayment",function($join){
                                    $join->on("TRInvoice.IVRefID","TRInvoicePayment.IVP_IVRefID")
                                        ->on("TRInvoice.IV_PTNo","TRInvoicePayment.IVP_PTNo")
                                        ->on("TRInvoice.IVContractNo","TRInvoicePayment.IVPContractNo");
                                })
                                ->get();


        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('PORefNo', function ($row) {
                // $route = route('pelaksana.project.getInvoices', ['PORefID' => $row->PORefID]);
                // $result = '<a href="' . $route . '">' . $row->PORefNo . '</a>';

                //$result = '<a href="#POModal" class="new modal-trigger" onclick="POModal(\'' . $row->PORefID . '\')">' . $row->PORefNo .'</a>';
                $result = $row->PORefNo;

                return $result;
            })
            ->addColumn('PODate', function ($row) {
                $result = isset($row->POApprovedDate) ? Carbon::parse($row->POApprovedDate)->format('d/m/Y') : null;

                return $result;
            })
            ->addColumn('IVRefNo', function ($row) {
                $result = $row->IVRefNo;

                return $result;
            })
            ->addColumn('IVDate', function ($row) {
                $result = isset($row->IVDate) ? Carbon::parse($row->IVDate)->format('d/m/Y') : null;

                return $result;
            })
            ->addColumn('IVAmt', function ($row) {

                $result = number_format($row->IVAmt ?? 0, 2, '.', ',');

                return $result;
            })
            ->addColumn('IVPaymentDate', function ($row) {
                $result = isset($row->IVPDate) ? Carbon::parse($row->IVPDate)->format('d/m/Y') : null;
                return $result;
            })
            ->addColumn('IVPAmtPaid', function ($row) {
                $result = isset($row->IVPAmtPaid) ? number_format($row->IVPAmtPaid ?? 0, 2, '.', ',') : null;

                return $result;
            })
            ->with(['count' => 0])
            ->setRowId('indexNo')
            ->rawColumns(['indexNo' , 'PORefNo' , 'PODate' , 'IVRefNo' , 'IVPaymentDate' , 'IVDate' , 'IVAmt' , 'IVAmtPaid'])
            ->make(true);

    }

    public function budgetDatatable(Request $request){

        $project = $request->idProject;
        $query = ProjectBudgetYear::where('PBY_PTNo', $request->idProject)->orderby('PBYYear','asc')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {
                $count++;
                return $count;
            })
            ->editColumn('PBYYear', function ($row) {
                // $result = '<a href="#budgetModal" class="new modal-trigger " onclick="openBudgetModal('.$row->PBYYear.')">'.$row->PBYYear.'</a>';

                $result = '<a href="#" data-bs-toggle="modal" data-bs-stacked-modal="#budgetModal" onclick="openBudgetModal('.$row->PBYYear.')">'.$row->PBYYear.'</a>';
                return $result;
            })
            ->editColumn('PBYBudgetAmt', function ($row) {
                $result =  number_format($row->PBYBudgetAmt ?? 0, 2, '.', ',');
                return $result;
            })
            ->addColumn('PaidAmt', function ($row) {
                $totalClaim = InvoicePayment::where('IVP_PTNo' , $row->PBY_PTNo)->whereYear('IVPDate',$row->PBYYear)->sum('IVPAmtPaid');
                $result =  number_format($totalClaim ?? 0, 2, '.', ',');
                return $result;
            })
            ->editColumn('PaidPercent', function ($row) {
                $totalClaim = InvoicePayment::where('IVP_PTNo' , $row->PBY_PTNo)->whereYear('IVPDate',$row->PBYYear)->sum('IVPAmtPaid');
                $pbdBudgetAmt = $row->PBYBudgetAmt ?? 0;

                //$percent = $totalClaim / $row->PBDBudgetAmt * 100;
                $percent = ($pbdBudgetAmt != 0) ? ($totalClaim / $pbdBudgetAmt * 100) : 0;
                $result =  number_format($percent ?? 0, 2, '.', ',');
                return $result;
            })
            ->with(['count' => 0])
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','PBYYear' ])
            ->make(true);

    }


    public function budgetDetailDatatable(Request $request){

        $query = ProjectBudgetDet::where('PBD_PTNo', $request->idProject)->whereYear('PBDEffectiveDate', $request->year)->orderby('PBDEffectiveDate','desc')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {
                $count++;
                return $count;
            })
            ->editColumn('PBDEffectiveDate', function ($row) {
                $result = isset($row->PBDEffectiveDate) ? Carbon::parse($row->PBDEffectiveDate)->format('d/m/Y') : null;
                return $result;
            })
            ->editColumn('PBDDebit', function ($row) {
                $result =  number_format($row->PBDDebit ?? 0, 2, '.', ',');
                return $result;
            })
            ->editColumn('PBDCredit', function ($row) {
                $result =  number_format($row->PBDCredit ?? 0, 2, '.', ',');
                return $result;
            })
            ->with(['count' => 0])
            ->setRowId('indexNo')
            ->rawColumns(['indexNo'])
            ->make(true);
    }

    public function claimDatatable(Request $request){

        $project = $request->idProject;

        //$purchaseOrder = PurchaseOrder::with('invoices')->where('PO_PTNo' , $project->PTNo)->get();
        //$query = PurchaseOrder::where('PO_PTNo', $request->idProject)->get();

        $query = ProjectClaim::join('TRProjectMilestone','PC_PMNo', 'PMNo', 'IVAmtPaid')
                                ->join('TRProjectInvoice','PCI_PCNo','PCNo')
                                ->leftjoin('MSProjectClaimProcess','PCPCode','PC_PCPCode')
                                ->leftjoin("TRInvoice",function($join){
                                    $join->on("TRInvoice.IVRefNo","TRProjectInvoice.PCIInvNo")
                                        ->on("TRInvoice.IV_PTNo","TRProjectMilestone.PM_PTNo");
                                })
                                ->where('PM_PTNo', $project)
                                ->orderby('PCID','asc')
                                ->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PCSubmittedDate', function ($row) {
                $result = isset($row->PCSubmittedDate) ? Carbon::parse($row->PCSubmittedDate)->format('d/m/Y') : null;

                return $result;
            })
            ->editColumn('PCIInvDate', function ($row) {
                $result = isset($row->PCIInvDate) ? Carbon::parse($row->PCIInvDate)->format('d/m/Y') : null;

                return $result;
            })
            ->editColumn('PCIInvAmt', function ($row) {
                $result = number_format($row->PCIInvAmt ?? 0, 2, '.', ',');
                return $result;
            })
            ->addColumn('PaidStatus', function ($row) {
                if ($row->IVAmtPaid > 0){
                    $result = '<i class="ki-solid ki-check-square text-success fs-2"></i>';
                }else{
                    $result = '<i class="ki-solid ki-cross-square text-danger fs-2"></i>';
                }

                return $result;
            })
            ->with(['count' => 0])
            ->setRowId('indexNo')
            ->rawColumns(['PaidStatus'])
            ->make(true);

    }

    public function getInvoices($PORefID)
    {
        $invoices = Invoice::where('IV_PORefID', $PORefID)->get();
        return view('pelaksana.project.viewInvoice', ['invoices' => $invoices]);
    }

    public function viewEot(Request $request,$projectNo,$EotNo){

        $id = $EotNo;

        $eotType = $this->dropdownService->eotType();
        $meetingStatus = $this->dropdownService->boardMeetingStatus();

        $eot = ExtensionOfTime::where('EOTNO', $id)->first();
        $PTNo = $projectNo;

        $milestone = ProjectMileStone::where('PM_PTNo', $PTNo)
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

        return view('pelaksana.project.viewEot',
        compact('eot','eotType', 'PTNo', 'milestone', 'meetingEot')
        );

    }

    public function printLetter($id){

        $user = Auth::user();

        $webSetting = WebSetting::first();

        $letterAccept = LetterAcceptance::where('LANo',$id)->first();

        $tenderProposal  = TenderProposal::where('TPNo' , $letterAccept->LA_TPNo)->first();

        $tenderApplication = TenderApplication::where('TANo' , $tenderProposal->TP_TANo)->first();

        $contractor = Contractor::where('CONo', $tenderApplication->TA_CONo)->first();

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$letterAccept->tenderProposal->contractor->COReg_StateCode];

        $dpt = $this->dropdownService->department();
        $department = $dpt[$tenderApplication->tender->TD_DPTCode];

        $responseDate = \Carbon\Carbon::parse($letterAccept->LAResponseDate)->format('d/m/Y');

        $dateExp = \Carbon\Carbon::parse($letterAccept->LAResponseDate)
                    ->addDays($webSetting->SSTExpDay)
                    ->format('d/m/Y');

        $bondPercent = $letterAccept->LATotalAmount * $webSetting->SSTBondPercent;

        $bondInWords = $this->DigitToWords($bondPercent);

        $insurance  = $webSetting->SSTInsuranceAmt;

        $insuranceInWords = $this->DigitToWords($insurance);

        $template = "LETTER";
        $download = false; //true for download or false for view
        $templateName = "ACCEPTANCE"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName','letterAccept' , 'COState', 'department' , 'responseDate' ,'dateExp' , 'bondPercent' , 'bondInWords'  , 'insurance' , 'insuranceInWords')
        );
        $response = $this->generatePDF($view,$download);

        return $response;

    }

    public function generateCloseProjLetter($id){

        $user = Auth::user();

        $webSetting = WebSetting::first();
        $department = $this->dropdownService->department();

        $currentYear = Carbon::now()->year;

        $project = Project::where('PTNo',$id)->first();

        $departmentCode = $project->tenderProposal->tender->TD_DPTCode;
        $project['departmentName'] = $department[$departmentCode];
        $tender = $project->tenderProposal->tender;

        $project['SAKDate'] = isset($project->PTSAKDate) ? Carbon::parse($project->PTSAKDate)->format('d/m/Y') : null;

        $contractor = Contractor::where('CONo', $project->PT_CONo)->first();

        $letterAccept = LetterAcceptance::where('LANo' , $project->PT_LANo)->first();

        $responseDate = \Carbon\Carbon::parse($letterAccept->LAResponseDate)->format('d/m/Y');

        $bondPercent = $webSetting->SSTBondPercent * 100;

        $bondAmt = $letterAccept->LATotalAmount * $webSetting->SSTBondPercent;

        $bondInWords = "";

        $insurance  = $webSetting->SSTInsuranceAmt;

        $insuranceInWords = "";

        $contractAmtWord = "";

        $discAmtWord = "";

        $taxAmtWord = "";

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$project->contractor->COReg_StateCode];

        $project['sumVO'] = VariantOrder::where('VO_PTNo',$id)->get()->sum('VOAmount');

        $project['sumEOT'] = ExtensionOfTime::where('EOT_PTNo',$id)->get()->sum('EOTWorkDay');

        $upName = "-";
        $tarikhRujukan = Carbon::parse($letterAccept->LAResponseDate)->format('j F Y');

        $nilaiKontrakAsal = number_format($letterAccept->LAOriginalAmt,2,'.',',');
        $nilaiPerubahanTambahan = number_format($letterAccept->LAContractAmt,2,'.',',');
        $nilaiPerubahanKurangan = number_format($letterAccept->LAContractAmt,2,'.',',');
        $nilaiKontrakSemasa = number_format($letterAccept->LATotalAmount,2,'.',',');

        $bayaranKemajuan = number_format($letterAccept->LATotalAmount,2,'.',',');
        $bayaranKontraktor = number_format($letterAccept->LATotalAmount,2,'.',',');
        $bayaranTerakhir = number_format($letterAccept->LATotalAmount,2,'.',',');


        $taxGST = 1000;
        $taxSST =  0;

        $tempohAsalStart = Carbon::parse($project->PTStartDate)->format('j F Y') ?? null;
        $tempohAsalEnd = Carbon::parse($project->PTEstimateEndDate)->format('j F Y') ?? null;

        $tempohLastStart = Carbon::parse($project->PTStartDate)->format('j F Y') ?? null;

        if(isset($project->PTExtendDate)){

            $tempohLastEnd = Carbon::parse($project->PTExtendDate)->format('j F Y');

        }
        else{

            $tempohLastEnd = Carbon::parse($project->PTEndDate)->format('j F Y') ?? null;

        }

        $completeDate = Carbon::now()->format('j F Y');
        $devDate = Carbon::now()->format('j F Y');
        $maintainanceDate = Carbon::now()->format('j F Y');
        $butiranKerja = "TIADA";

        $template = "PROJECT";
        $download = false; //true for download or false for view
        $templateName = "CLOSE"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName', 'COState', 'project' , 'letterAccept', 'responseDate' , 'bondAmt' , 'contractAmtWord' ,
                'discAmtWord','taxAmtWord' , 'bondPercent' , 'bondInWords'  , 'insurance' , 'insuranceInWords' , 'bondPercent' , 'currentYear' ,
                'departmentCode','tender',
                'upName','tarikhRujukan',
                'taxGST','taxSST',
                'tempohAsalStart','tempohAsalEnd','tempohLastStart','tempohLastEnd',
                'completeDate','devDate','maintainanceDate','butiranKerja',
                'nilaiKontrakAsal' , 'nilaiPerubahanTambahan' , 'nilaiPerubahanKurangan' , 'nilaiKontrakSemasa',
                'bayaranKemajuan' , 'bayaranKontraktor' , 'bayaranTerakhir'
                )
        );
        $response = $this->generatePDF($view,$download);
        return $response;

    }

    public function generateCloseProjReport($id){

        $user = Auth::user();

        $webSetting = WebSetting::first();
        $department = $this->dropdownService->department();
        $jenis_projek = $this->dropdownService->jenis_projek();
        $closeType = $this->dropdownService->closeType();
        $mileStoneStatus = $this->dropdownService->mileStoneStatus();
        $claimProcess = $this->dropdownService->claimProcess();

        $currentYear = Carbon::now()->year;

        $project = Project::where('PTNo',$id)->first();
        $projectClaim = $project->projectClaim->sum('PCTotalAmt');

        $departmentCode = $project->tenderProposal->tender->TD_DPTCode;
        $project['departmentName'] = $department[$departmentCode] ?? '-';
        $project['closeType'] = $closeType[$project->PT_PCTCode] ?? '-';

        $tender = $project->tenderProposal->tender;
        $tender['projectType'] = $jenis_projek[$tender->TD_PTCode] ?? '-';

        $project['SAKDate'] = isset($project->PTSAKDate) ? Carbon::parse($project->PTSAKDate)->format('d/m/Y') : null;

        $contractor = Contractor::where('CONo', $project->PT_CONo)->first();

        $eotLDs = ExtensionOfTime::where('EOT_PTNo',$project->PTNo)->where('EOType','LD')->where('EOT_EPCode','MTA')->get();
        $eotESs = ExtensionOfTime::where('EOT_PTNo',$project->PTNo)->where('EOType','ES')->where('EOT_EPCode','MTA')->get();
        $eotType = $this->dropdownService->eotType();

        $vos = VariantOrder::where('VO_PTNo',$project->PTNo)->whereIn('VO_VPCode',['MTA','AJKA'])->get();

        $projectMileStone = $project->projectMileStone;
        $totalDayMileStone = 0;
        $totalPercentMileStone=0;

        if($project->projectMileStone){
            foreach($projectMileStone as $pms){

                $pms['startDate'] = Carbon::parse($pms->PMStartDate)->format('d/m/Y');
                $pms['endDate'] = Carbon::parse($pms->PMEndDate)->format('d/m/Y');
                $pms['milestoneStatus'] = $mileStoneStatus[$pms->PM_PMSCode ?? 'D'];

                if(count($pms->projectClaimM) > 0){
                    foreach($pms->projectClaimM as $projectClaimM){
                        if($projectClaimM->PC_PCPCode != 'DF'){
                            $pms['claimStatus'] = $projectClaimM->PCNo . ' - ' . $claimProcess[$projectClaimM->PC_PCPCode];
                        }
                    }

                }
                else{
                    $pms['claimStatus'] = 'Belum Tuntut';
                }

                $totalDayMileStone += $pms->PMWorkDay;
                $totalPercentMileStone += $pms->PMWorkPercent;

            }
        }

        $invoices = Invoice::where('IV_PTNo', $project->PTNo)->get();

        $imagePath = public_path('assets/images/letterHead/letterHead_'.$departmentCode.'.png');
        $badgeColor = "";

        if($project->PTRating < 4){
            $badgeColor = "badgeRed";

        }else{
            $badgeColor = "badgeGrin";

        }

        $headerHtml = '
        <div class="header" style="position: fixed; top: 0; width: 95%;">
            <div class="row">
                <div class="col m8">
                    <img src="'.$imagePath.'" alt="" style="margin-left: -30px; margin-top: -50px; width: 100%">
                </div>
                <div class="col m4" style="text-align:right;margin-left: -40px; margin-top: -30px">
                    <div class="badge '.$badgeColor.'">
                        <span class="text-inside-circle">'.$project->PTRating.'/'.$project->PTRatingMax.'</span>
                    </div>
                </div>
            </div>
        </div>
        ';

        $template = "PROJECT";
        $download = false; //true for download or false for view
        $templateName = "CLOSE-RPT"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName',
                'project','tender','contractor','departmentCode',
                'projectClaim',
                'eotLDs','eotESs','eotType',
                'vos',
                'projectMileStone',
                'invoices'
                )
        );
        // $response = $this->generateReportWHeader($view,$download,$headerHtml);

        try {

            $contentHtml = $view->render();

            $html = $headerHtml . $contentHtml;

            // Generate the PDF
            $dompdf_options = array("enable_javascript" => true, "enable_html5_parser" => true, "enable_font_subsetting" => true);
            $pdf = new Dompdf();
            $pdf->set_options($dompdf_options);
            $pdf->set_option('chroot', public_path());
            $pdf->loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();

            $temporaryFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'LAPORAN_PENUTUPAN_PROJEK.pdf';

            $fileput = file_put_contents($temporaryFilePath, $pdf->output());

            if (file_exists($temporaryFilePath)) {

                $file = $temporaryFilePath;

                $fileType = 'PT-CPR';
                $refNo = $project->PTNo;

                $fileContent = file_get_contents($file);
                $base64File = base64_encode($fileContent);

                // $result = $this->saveFile($file,$fileType,$refNo);
                $result = $this->saveFile(new \Illuminate\Http\UploadedFile($file, 'LAPORAN_PENUTUPAN_PROJEK.pdf'), $fileType, $refNo);

                // return true;
                return $base64File;

            }else{
                return false;
            }

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function processCloseProj($id){
        try {
            DB::beginTransaction();

            $project = Project::where('PTNo', $id)
                ->first();
            $project->PTClosedDate = Carbon::now();
            $project->PT_PSCode = 'C';
            $project->PTStatus = 'CLOSE';
            $project->save();

            $result = $this->sendCloseProjNotif($project->PTNo);

            $closePDF = $this->generateCloseProjReport($project->PTNo);

            // $imagePath = public_path('assets/images/chop/dbkl_chop.png');
            // $imageContent = file_get_contents($imagePath);
            // $stamp = base64_encode($imageContent);

            DB::commit();

            return true;
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return false;
        }
    }

}
