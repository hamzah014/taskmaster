<?php

namespace App\Http\Controllers\Pelaksana\Approval;

use App\Helper\Custom;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pelaksana\Project\ProjectController;
use App\Http\Controllers\Perolehan\Letter\AcceptLetterController;
use App\Http\Controllers\Perolehan\Letter\IntentLetterController;
use App\Http\Controllers\Perolehan\Tender\TenderController;
use App\Models\Approval;
use App\Models\AutoNumber;
use App\Models\LetterAcceptance;
use App\Models\LetterIntent;
use App\Models\Project;
use App\Models\ProjectTender;
use App\Models\SSMCompany;
use App\Models\TugasanPelaksana;
use App\Models\ViewApproval;
use App\Models\ViewApprovalAuthUser;
use App\Models\WebSetting;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Yajra\DataTables\DataTables;


use App\Http\Requests;
use App\Models\BoardMeeting;
use App\Models\BoardMeetingProposal;
use App\Models\BoardMeetingTender;
use App\Models\Role;
use App\Models\Customer;
use App\Models\ExtensionOfTime;
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
use App\Models\SuratArahanKerja;
use App\Models\VariantOrder;
use DateTime;
use DateInterval;

class ApprovalController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        return view('pelaksana.approval.index');
    }

    public function indexPerolehan(){
        return view('perolehan.approval.index');
    }

    public function indexDirector(){
        return view('director.approval.index');
    }

    public function view($apNo){
        $approval = Approval::where('APNo', $apNo)->first();

        if(in_array($approval->AP_ATCode, ['PTD-SM', 'PTD-EM1', 'PTD-EM2', 'PTD-EM3'])){
            $id = $approval->APRefNo;

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

            return view('pelaksana.approval.view',
                compact('projectType','department','projectTender','arrayDepartment',
                    'meetingType', 'yt', 'totalDays', 'approval')
            );
        }
        else if( in_array($approval->AP_ATCode, ['SAK-SM', 'EOT-RQA', 'EOT-SM', 'VO-RQ', 'VO-SM', 'PT-CP'])){
            return view('pelaksana.approval.view',
                compact('approval')
            );
        }
        else if( in_array($approval->AP_ATCode, ['TD-SM', 'LI-SM', 'LA-SM', 'EOT-SMP', 'VO-SMP', 'TD-CT', 'TD-RT'])){
            return view('perolehan.approval.view',
                compact('approval')
            );
        }
        else if( in_array($approval->AP_ATCode, ['TP-SM']) ){
            return view('director.approval.view',
                compact('approval')
            );
        }
        else if( in_array($approval->AP_ATCode, ['LI-RP']) ){
            return view('director.approval.view',
                compact('approval')
            );
        }
        else if( in_array($approval->AP_ATCode, ['LA-RP']) ){
            return view('director.approval.view',
                compact('approval')
            );
        }
        else if( in_array($approval->AP_ATCode, ['SAK-RP']) ){
            return view('director.approval.view',
                compact('approval')
            );
        }
        else if( in_array($approval->AP_ATCode, ['EOT-RQ']) ){
            return view('director.approval.view',
                compact('approval')
            );
        }
        else if( in_array($approval->AP_ATCode, ['EOT-RP']) ){
            return view('director.approval.view',
                compact('approval')
            );
        }
        else if( in_array($approval->AP_ATCode, ['VO-RP']) ){
            return view('director.approval.view',
                compact('approval')
            );
        }
        else{
            return view('pelaksana.approval.view',
                compact('approval')
            );
        }


    }

    public function storeApproval($id, $type){
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $APNo = $this->autoNumber->generateApprovalNo();

            $approval = new Approval();
            $approval->APNo = $APNo;
            $approval->APRefNo = $id;
            $approval->AP_ATCode = $type;
            $approval->APCB = $user->USCode;
            $approval->APMB = $user->USCode;
            $approval->save();

            DB::commit();

            return $approval;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat Tidak Berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $webSetting = WebSetting::first();
            $FaceScoreRate = $webSetting->FaceScoreRate *100;

            if($request->faceScore >= $FaceScoreRate){
                $approval = Approval::where('APNo', $request->APNo)->first();
                $approval->APApprove = $request->approval;
                $approval->APFaceScore = $request->faceScore;
                $approval->APResponseDate = Carbon::now();
                $approval->APMB = $user->USCode;
                $approval->save();


                if($request->approval == 1){
                    if($approval->AP_ATCode == 'PTD-SM'){
                        $ptd = ProjectTender::where('PTDNo', $approval->APRefNo)->first();
                        $ptd->PTD_PTSCode = 'COMPLETE';
                        $ptd->save();
                    }
                    else if($approval->AP_ATCode == 'PTD-EM1'){
                        $ptd = ProjectTender::where('PTDNo', $approval->APRefNo)->first();
                        $ptd->PTD_PTSCode = 'EM1S';
                        $ptd->save();
                    }
                    else if($approval->AP_ATCode == 'PTD-EM2'){
                        $ptd = ProjectTender::where('PTDNo', $approval->APRefNo)->first();
                        $ptd->PTD_PTSCode = 'EM2S';
                        $ptd->save();
                    }
                    else if($approval->AP_ATCode == 'PTD-EM3'){
                        $ptd = ProjectTender::where('PTDNo', $approval->APRefNo)->first();
                        $ptd->PTD_PTSCode = 'EM3S';
                        $ptd->save();
                    }
                    else if($approval->AP_ATCode == 'SAK-SM'){
                        $sak = Project::where('PT_SAKNo', $approval->APRefNo)->first();
                        $sak->PT_PPCode = 'KO';
                        $sak->save();
                    }
                    else if($approval->AP_ATCode == 'EOT-RQA'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'RQA';
                        $eot->save();
                    }
                    else if($approval->AP_ATCode == 'EOT-SM'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'EOTS';
                        $eot->save();
                    }
                    else if($approval->AP_ATCode == 'VO-RQ'){
                        $vo = VariantOrder::where('VONo', $approval->APRefNo)->first();
                        $vo->VO_VPCode = 'RQ';
                        $vo->save();
                    }
                    else if($approval->AP_ATCode == 'VO-SM'){
                        $vo = VariantOrder::where('VONo', $approval->APRefNo)->first();
                        $vo->VO_VPCode = 'VOS';
                        $vo->save();
                    }
                    else if($approval->AP_ATCode == 'PT-CP'){
                        $pt = Project::where('PTNo', $approval->APRefNo)->first();
                        $pt->PT_PPCode = 'CP';
                        $pt->save();

                        $projectController = new ProjectController (new DropdownService(), new AutoNumber());

                        $projectController->processCloseProj($approval->APRefNo);
                    }

                }
                else if($request->approval == 0){
                    if($approval->AP_ATCode == 'PTD-SM'){
                        $ptd = ProjectTender::where('PTDNo', $approval->APRefNo)->first();
                        $ptd->PTD_PTSCode = 'NEW';
                        $ptd->save();
                    }
                    else if($approval->AP_ATCode == 'PTD-EM1'){
                        $ptd = ProjectTender::where('PTDNo', $approval->APRefNo)->first();
                        $ptd->PTD_PTSCode = 'EM1V';
                        $ptd->save();
                    }
                    else if($approval->AP_ATCode == 'PTD-EM2'){
                        $ptd = ProjectTender::where('PTDNo', $approval->APRefNo)->first();
                        $ptd->PTD_PTSCode = 'EM2V';
                        $ptd->save();
                    }
                    else if($approval->AP_ATCode == 'PTD-EM3'){
                        $ptd = ProjectTender::where('PTDNo', $approval->APRefNo)->first();
                        $ptd->PTD_PTSCode = 'EM3V';
                        $ptd->save();
                    }
                    else if($approval->AP_ATCode == 'SAK-SM'){
                        $sak = Project::where('PT_SAKNo', $approval->APRefNo)->first();
                        $sak->PT_PPCode = 'SAK';
                        $sak->save();
                    }
                    else if($approval->AP_ATCode == 'EOT-RQA'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'RQ';
                        $eot->save();
                    }
                    else if($approval->AP_ATCode == 'EOT-SM'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'EOTN';
                        $eot->save();
                    }
                    else if($approval->AP_ATCode == 'VO-RQ'){
                        $vo = VariantOrder::where('VONo', $approval->APRefNo)->first();
                        $vo->VO_VPCode = 'VON';
                        $vo->save();
                    }
                    else if($approval->AP_ATCode == 'VO-SM'){
                        $vo = VariantOrder::where('VONo', $approval->APRefNo)->first();
                        $vo->VO_VPCode = 'VON';
                        $vo->save();
                    }
                    else if($approval->AP_ATCode == 'PT-CP'){
                        $pt = Project::where('PTNo', $approval->APRefNo)->first();
                        $pt->PT_PPCode = 'PS';
                        $pt->save();
                    }
                }
            }
            else{
                return response()->json([
                    'error' => '1',
                    'message' => 'Pengecaman Muka tidak berjaya.'
                ], 400);
            }


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.approval.index'),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat Tidak Berjaya!'.$e->getMessage()
            ], 400);
        }
    }

    public function updatePerolehan(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $webSetting = WebSetting::first();
            $FaceScoreRate = $webSetting->FaceScoreRate *100;

            if($request->faceScore >= $FaceScoreRate){
                $approval = Approval::where('APNo', $request->APNo)->first();
                $approval->APApprove = $request->approval;
                $approval->APFaceScore = $request->faceScore;
                $approval->APResponseDate = Carbon::now();
                $approval->APMB = $user->USCode;
                $approval->save();


                if($request->approval == 1){
                    if($approval->AP_ATCode == 'TD-SM'){
                        $td = Tender::where('TDNo', $approval->APRefNo)->first();
                        $td->TD_TPCode = 'PA';
                        $td->save();
                    }
                    else if($approval->AP_ATCode == 'LI-SM'){
                        $li = LetterIntent::where('LINo', $approval->APRefNo)->first();
                        $pt = Project::where('PT_TPNo', $li->LI_TPNo)->first();
                        $pt->PT_PPCode = 'LIS';
                        $pt->save();

                        $intentLetterController = new IntentLetterController (new DropdownService());

                        $intentLetterController->processSentActivation($approval->APRefNo);
                    }
                    else if($approval->AP_ATCode == 'LA-SM'){
                        $pt = Project::where('PT_LANo', $approval->APRefNo)->first();
                        $pt->PT_PPCode = 'LAS';
                        $pt->save();

                        $acceptLetterController = new AcceptLetterController (new DropdownService(), new AutoNumber());

                        $acceptLetterController->processSentActivation($approval->APRefNo);
                    }
                    else if($approval->AP_ATCode == 'EOT-SMP'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'PA';
                        $eot->save();
                    }
                    else if($approval->AP_ATCode == 'VO-SMP'){
                        $vo = VariantOrder::where('VONo', $approval->APRefNo)->first();
                        $vo->VO_VPCode = 'PA';
                        $vo->save();
                    }
                    else if($approval->AP_ATCode == 'TD-RT'){
                        $td = Tender::where('TDNo', $approval->APRefNo)->first();
                        $td->TD_TPCode = 'RT';
                        $td->save();

                        $controller = $this->processReTender($approval->APRefNo);
                    }
                    else if($approval->AP_ATCode == 'TD-CT'){
                        $td = Tender::where('TDNo', $approval->APRefNo)->first();
                        $td->TD_TPCode = 'CT';
                        $td->TDCancelDate = Carbon::now();
                        $td->save();
                    }
                }
                else if($request->approval == 0){
                    if($approval->AP_ATCode == 'TD-SM'){
                        $td = Tender::where('TDNo', $approval->APRefNo)->first();
                        $td->TD_TPCode = 'DF';
                        $td->save();
                    }
                    else if($approval->AP_ATCode == 'LI-SM'){
                        $li = LetterIntent::where('LINo', $approval->APRefNo)->first();
                        $pt = Project::where('PT_TPNo', $li->LI_TPNo)->first();
                        $pt->PT_PPCode = 'MLI';
                        $pt->save();
                    }
                    else if($approval->AP_ATCode == 'LA-SM'){
                        $pt = Project::where('PT_LANo', $approval->APRefNo)->first();
                        $pt->PT_PPCode = 'MLA';
                        $pt->save();
                    }
                    else if($approval->AP_ATCode == 'EOT-SMP'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'AJKA';
                        $eot->save();
                    }
                    else if($approval->AP_ATCode == 'VO-SMP'){
                        $vo = VariantOrder::where('VONo', $approval->APRefNo)->first();
                        $vo->VO_VPCode = 'AJKA';
                        $vo->save();
                    }
                    else if($approval->AP_ATCode == 'TD-RT'){
                        $td = Tender::where('TDNo', $approval->APRefNo)->first();
                        if($td->TD_TPCode == 'RT-CA-RQ'){
                            $td->TD_TPCode = "CA";
                        }
                        else if($td->TD_TPCode == 'RT-OT-RQ'){
                            $td->TD_TPCode = "OT";
                        }
                        else if($td->TD_TPCode == 'RT-BM-RQ'){
                            $td->TD_TPCode = "BM";
                        }
                        else{
                            $td->TD_TPCode = "CA";
                        }
                        $td->save();
                    }
                    else if($approval->AP_ATCode == 'TD-CT'){
                        $td = Tender::where('TDNo', $approval->APRefNo)->first();
                        if($td->TD_TPCode == 'CT-DF-RQ'){
                            $td->TD_TPCode = "DF";
                        }
                        else if($td->TD_TPCode == 'CT-PA-RQ'){
                            $td->TD_TPCode = "PA";
                        }
                        else if($td->TD_TPCode == 'CT-CA-RQ'){
                            $td->TD_TPCode = "CA";
                        }
                        else if($td->TD_TPCode == 'CT-OT-RQ'){
                            $td->TD_TPCode = "OT";
                        }
                        else if($td->TD_TPCode == 'CT-ES-RQ'){
                            $td->TD_TPCode = "ES";
                        }
                        else{
                            $td->TD_TPCode = "DF";
                        }
                    }
                }
            }
            else{
                return response()->json([
                    'error' => '1',
                    'message' => 'Pengecaman Muka tidak berjaya.'
                ], 400);
            }


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.approval.index'),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat Tidak Berjaya!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateDirector(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $webSetting = WebSetting::first();
            $FaceScoreRate = $webSetting->FaceScoreRate *100;

            if($request->faceScore >= $FaceScoreRate){
                $approval = Approval::where('APNo', $request->APNo)->first();
                $approval->APApprove = $request->approval;
                $approval->APFaceScore = $request->faceScore;
                $approval->APResponseDate = Carbon::now();
                $approval->APMB = $user->USCode;
                $approval->save();


                if($request->approval == 1){
                    if($approval->AP_ATCode == 'TP-SM'){
                       $td = TenderProposal::where('TPNo', $approval->APRefNo)->first();
                       $td->TP_TPPCode = 'SB';
                       $td->save();
                    }
                    elseif($approval->AP_ATCode == 'LI-RP'){
                        $letterIntent = LetterIntent::where('LINo',$approval->APRefNo)->first();

                        $project = Project::where('PT_TPNo', $letterIntent->LI_TPNo)->first();
                        $project->PT_PPCode = 'LIA';
                        $project->save();

                        $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LI_TPNo)->first();
                        $tenderProposal->TP_TRPCode = 'LIA';
                        $tenderProposal->save();
                    }
                    elseif($approval->AP_ATCode == 'LA-RP'){
                        $letterAccept = LetterAcceptance::where('LANo',$approval->APRefNo)->first();

                        $project = Project::where('PT_TPNo', $letterAccept->LA_TPNo)->first();
                        $project->PT_PPCode = 'LAA';
                        $project->save();

                        $tenderProposal = TenderProposal::where('TPNo',$letterAccept->LA_TPNo)->first();
                        $tenderProposal->TP_TRPCode = 'LAA';
                        $tenderProposal->save();
                    }
                    elseif($approval->AP_ATCode == 'SAK-RP'){
                        $sak = SuratArahanKerja::where('SAKNo', $approval->APRefNo)->first();
                        $sak->SAKStatus = 'UPLOAD';
                        $sak->save();
                    }
                    elseif($approval->AP_ATCode == 'EOT-RQ'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'RQ';
                        $eot->save();
                    }
                    elseif($approval->AP_ATCode == 'EOT-RP'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'EOTA';
                        $eot->save();
                    }
                    elseif($approval->AP_ATCode == 'VO-RP'){
                        $variantOrder = VariantOrder::where('VONo', $approval->APRefNo)->first();
                        $variantOrder->VO_VPCode = 'VOA';
                        $variantOrder->save();
                    }
                }
                else if($request->approval == 0){
                    if($approval->AP_ATCode == 'TP-SM'){
                        $td = TenderProposal::where('TPNo', $approval->APRefNo)->first();
                        $td->TP_TPPCode = 'DF';
                        $td->save();
                    }
                    elseif($approval->AP_ATCode == 'LI-RP'){
                        $letterIntent = LetterIntent::where('LINo',$approval->APRefNo)->first();

                        $project = Project::where('PT_TPNo', $letterIntent->LI_TPNo)->first();
                        $project->PT_PPCode = 'LIS';
                        $project->save();

                        $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LI_TPNo)->first();
                        $tenderProposal->TP_TRPCode = 'LIS';
                        $tenderProposal->save();
                    }
                    elseif($approval->AP_ATCode == 'LA-RP'){
                        $letterAccept = LetterAcceptance::where('LANo',$approval->APRefNo)->first();

                        $project = Project::where('PT_TPNo', $letterAccept->LA_TPNo)->first();
                        $project->PT_PPCode = 'LAS';
                        $project->save();

                        $tenderProposal = TenderProposal::where('TPNo',$letterAccept->LA_TPNo)->first();
                        $tenderProposal->TP_TRPCode = 'LAS';
                        $tenderProposal->save();

                        //DIGITAL CERT
                        $imagePath = public_path('assets/images/chop/dbkl_chop.png');
                        $imageContent = file_get_contents($imagePath);
                        $stamp = base64_encode($imageContent);

                        $laGuid = $letterAccept->fileAttachGS->FAGuidID;

                        $pdf = $this->getFile64($laGuid);

                        try{
//                            $custom = new Custom();
//                            $jsonArray = $custom->generateDigitalCert($pdf, $stamp , $letterAccept->LANo , 'LA-DC');

                        }catch (\Exception $e) {

                            return response()->json([
                                'error' => '1',
                                'message' => $e->getMessage()
                            ], 400);
                        }
                    }
                    elseif($approval->AP_ATCode == 'SAK-RP'){
                        $sak = SuratArahanKerja::where('SAKNo', $approval->APRefNo)->first();
                        $sak->SAKStatus = 'SUBMIT';
                        $sak->save();
                    }
                    elseif($approval->AP_ATCode == 'EOT-RQ'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'RQV';
                        $eot->save();
                    }
                    elseif($approval->AP_ATCode == 'EOT-RP'){
                        $eot = ExtensionOfTime::where('EOTNo', $approval->APRefNo)->first();
                        $eot->EOT_EPCode = 'EOTS';
                        $eot->save();
                    }
                    elseif($approval->AP_ATCode == 'VO-RP'){
                        $variantOrder = VariantOrder::where('VONo', $approval->APRefNo)->first();
                        $variantOrder->VO_VPCode = 'VOS';
                        $variantOrder->save();
                    }
                }
            }
            else{
                return response()->json([
                    'error' => '1',
                    'message' => 'Pengecaman Muka tidak berjaya.'
                ], 400);
            }


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('director.approval.index'),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat Tidak Berjaya!'.$e->getMessage()
            ], 400);
        }
    }

    public function approvalDatatable(Request $request){

        $user = Auth::user();

//        $query = Approval::where('APApprove', null)
//            ->where('APResponseDate', null)
//            ->whereHas('approvalType', function ($subquery) {
//                $subquery->where('ATEntity', 'SO');
//            })->orderBy('APNo', 'DESC')->get();

        $query = ViewApproval::where('UserCode', $user->USCode)
            ->whereHas('approval', function ($q) {
                $q->where('APApprove', null)
                ->where('APResponseDate', null);
            })
            ->orderBy('ApprovalNo', 'DESC')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('APNo', function($row) {

                if($row->Department == 'JP'){
                    $route = route('perolehan.approval.view',[$row->approval->APNo]);
                }
                else{
                    $route = route('pelaksana.approval.view',[$row->approval->APNo]);
                }

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->approval->APNo.' </a>';

                return $result;

            })
            ->addColumn('APCD', function($row) {
                $carbonDatetime = Carbon::parse($row->approval->APCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i:s');

                return $formattedDate;
            })
            ->addColumn('Modul', function($row) {
                $modul = $this->dropdownService->modul();

                return $modul[$row->approval->approvalType->ATType];
            })
            ->addColumn('APRefNo', function($row) {

                return $row->approval->APRefNo;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','APNo', 'APRefNo', 'APCD', 'Modul'])
            ->make(true);
    }

    public function approvalHistoryDatatable(Request $request){

        $user = Auth::user();

//        $query = Approval::whereNotNull('APApprove')
//            ->whereNotNull('APResponseDate')
//            ->whereHas('approvalType', function ($subquery) {
//                $subquery->where('ATEntity', 'SO');
//            })->orderBy('APNo', 'DESC')->get();

        $query = ViewApproval::where('UserCode', $user->USCode)
            ->whereHas('approval', function ($q) {
                $q->whereNotNull('APApprove')
                    ->whereNotNull('APResponseDate');
            })
            ->orderBy('ApprovalNo', 'DESC')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('APNo', function($row) {

//                if($row->AP_ATCode == 'PTD-SM'){
                    $route = route('pelaksana.approval.view',[$row->approval->APNo]);
//                }
//                else{
//                    $route = '#';
//                }

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->approval->APNo.' </a>';

                return $result;

            })
            ->addColumn('APCD', function($row) {
                $carbonDatetime = Carbon::parse($row->approval->APCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i:s');

                return $formattedDate;
            })
            ->addColumn('APResponseDate', function($row) {
                $carbonDatetime = Carbon::parse($row->approval->APResponseDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i:s');

                return $formattedDate;
            })
            ->addColumn('APMB', function($row) {
                if($row->approval->APMB == 'SYSTEM'){
                    return 'SYSTEM';
                }
                else{
                    return $row->approval->byUser->USName;
                }
            })
            ->addColumn('APApprove', function($row) {
                if($row->approval->APApprove == 1){
                    return 'Diluluskan';
                }
                else{
                    return 'Tidak Diluluskan';
                }
            })
            ->addColumn('APRefNo', function($row) {

                return $row->approval->APRefNo;
            })
            ->addColumn('Modul', function($row) {
                $modul = $this->dropdownService->modul();

                return $modul[$row->approval->approvalType->ATType];
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','APNo', 'APRefNo', 'APCD', 'Modul', 'APMB','APResponseDate'])
            ->make(true);
    }

    public function approvalAuthUserDatatable(Request $request){

        $user = Auth::user();

//        $query = Approval::where('APApprove', null)
//            ->where('APResponseDate', null)
//            ->whereHas('approvalType', function ($subquery) {
//                $subquery->where('ATEntity', 'SO');
//            })->orderBy('APNo', 'DESC')->get();

        $query = ViewApprovalAuthUser::where('UserCode', $user->USCode)
            ->whereHas('approval', function ($q) {
                $q->where('APApprove', null)
                    ->where('APResponseDate', null);
            })
            ->orderBy('ApprovalNo', 'DESC')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('APNo', function($row) {

                $route = route('director.approval.view',[$row->approval->APNo]);
                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->approval->APNo.' </a>';

                return $result;

            })
            ->addColumn('APCD', function($row) {
                $carbonDatetime = Carbon::parse($row->approval->APCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i:s');

                return $formattedDate;
            })
            ->addColumn('Modul', function($row) {
                $modul = $this->dropdownService->modul();

                return $modul[$row->approval->approvalType->ATType];
            })
            ->addColumn('APRefNo', function($row) {

                return $row->approval->APRefNo;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','APNo', 'APRefNo', 'APCD', 'Modul'])
            ->make(true);
    }

    public function approvalAuthUserHistoryDatatable(Request $request){

        $user = Auth::user();

//        $query = Approval::whereNotNull('APApprove')
//            ->whereNotNull('APResponseDate')
//            ->whereHas('approvalType', function ($subquery) {
//                $subquery->where('ATEntity', 'SO');
//            })->orderBy('APNo', 'DESC')->get();

        $query = ViewApprovalAuthUser::where('UserCode', $user->USCode)
            ->whereHas('approval', function ($q) {
                $q->whereNotNull('APApprove')
                    ->whereNotNull('APResponseDate');
            })
            ->orderBy('ApprovalNo', 'DESC')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('APNo', function($row) {

//                if($row->AP_ATCode == 'PTD-SM'){
                $route = route('director.approval.view',[$row->approval->APNo]);
//                }
//                else{
//                    $route = '#';
//                }

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->approval->APNo.' </a>';

                return $result;

            })
            ->addColumn('APCD', function($row) {
                $carbonDatetime = Carbon::parse($row->approval->APCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i:s');

                return $formattedDate;
            })
            ->addColumn('APResponseDate', function($row) {
                $carbonDatetime = Carbon::parse($row->approval->APResponseDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i:s');

                return $formattedDate;
            })
            ->addColumn('APMB', function($row) {
                if($row->approval->APMB == 'SYSTEM'){
                    return 'SYSTEM';
                }
                else{
                    return $row->approval->byUser->USName;
                }
            })
            ->addColumn('APApprove', function($row) {
                if($row->approval->APApprove == 1){
                    return 'Diluluskan';
                }
                else{
                    return 'Tidak Diluluskan';
                }
            })
            ->addColumn('APRefNo', function($row) {

                return $row->approval->APRefNo;
            })
            ->addColumn('Modul', function($row) {
                $modul = $this->dropdownService->modul();

                return $modul[$row->approval->approvalType->ATType];
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','APNo', 'APRefNo', 'APCD', 'Modul', 'APMB','APResponseDate'])
            ->make(true);
    }
}
