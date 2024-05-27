<?php

namespace App\Http\Controllers;

use App\Models\ExtensionOfTime;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Tender;
use App\Models\TenderProposal;
use App\Models\TenderProposalDetail;
use App\Models\VariantOrder;
use App\Models\WebSetting;
use Carbon\Carbon;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Controller;
use App\Models\FileAttach;
use App\Helper\Custom;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Auth;
use Image;
use Imagick;
use App\Models\AutoNumber;
use App\Models\CertApp;
use App\Models\Contractor;
use App\Models\IntegrateSSMLog;
use App\Models\EmailLog;
use App\Services\DropdownService;
use Mail;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class SchedulerController extends Controller{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        $now = Carbon::now();

        return view('scheduler.index',
            compact('now')
        );
    }

    public function startModel(Request $request){

        if(isset($request->contractorNo)){
            $contractorData = Contractor::where('COStatus','PAID')
                                            ->where('COBusinessNo',$request->contractorNo)
                                            ->where(
                                                function($query) {
                                                  return $query
                                                         ->whereNull('COVerifyResult')
                                                         ->orWhere('COVerifyResult', 'KIV');
                                                 })
                                            ->get();
        }
        else{
            $contractorData = Contractor::where('COStatus','PAID')
                                            ->whereNotNull('COBusinessNo')
                                            ->where(
                                                function($query) {
                                                  return $query
                                                         ->whereNull('COVerifyResult')
                                                         ->orWhere('COVerifyResult', 'KIV');
                                                 })
                                            ->get();
        }

        $register = new RegisterController();
        // $this->info('Process Record: '.count($contractorData));
        if(isset($contractorData) && count($contractorData)>0) {
            foreach ($contractorData as $x => $contractor) {
               // $this->info('Process Record: '.($x+1).'/'.count($contractorData).' - Contractor No:['.$contractor->CONo.']');
               $register->ssmRegister($contractor->CONo);
            }
        }
        return $contractorData;
    }


    public function startMilestone(Request $request){
        $result = 0;

        try {
            DB::beginTransaction();

            $websetting = Websetting::first();
            $firstLevel = (float) $websetting->COMilestonePeriod1 ;
            $secondLevel = (float) $websetting->COMilestonePeriod2 ;

            $firstLevelProject = (float) $websetting->ProjMilestonePeriod1 ;
            $secondLevelProject = (float) $websetting->ProjMilestonePeriod2 ;

            if(isset($request->projectNo)){
                $projects = Project::where('PTNo', $request->projectNo)->get();
            }
            else{
                $projects = Project::get();
            }

            foreach ($projects as $project){

                $projectMilestone = ProjectMilestone::where('PM_PTNo', $project->PTNo)
                    ->where('PMCompleteDate', null)
                    ->orderBy('PMSeq')
                    ->first();
                if($projectMilestone){

                    if(isset($request->tarikh)){

                        $now = \Carbon\Carbon::parse($request->tarikh);
                    }
                    else{
                        $now = Carbon::now();
                    }

                    if($projectMilestone->PMRefType == 'PJ'){
                        $proposeStartDate = \Carbon\Carbon::parse($projectMilestone->PMProposeStartDate);
                        $proposeEndDate = \Carbon\Carbon::parse($projectMilestone->PMProposeEndDate);

                        $startDate = \Carbon\Carbon::parse($projectMilestone->PMStartDate);
                        $endDate = \Carbon\Carbon::parse($projectMilestone->PMEndDate);

                        if ($now->isBetween($startDate, $endDate)) {
                            if($now > $proposeEndDate) {//lewat
                                $daysNowDiffStart = $startDate->diffInDays($now) + 1;
                                $daysNowDiffProposeEnd = $proposeEndDate->diffInDays($now);
                                $PMLateDay = $daysNowDiffStart - $projectMilestone->PMWorkDay;

                                if($daysNowDiffProposeEnd >= $firstLevel) {
                                    if($daysNowDiffProposeEnd >= $secondLevel) {
                                        $priority = 2;
                                    }
                                    else{
                                        $priority = 1;
                                    }
                                }
                                else {
                                    $priority = 0;
                                }
                                if($projectMilestone->PM_PMSCode == 'D'){
                                    $projectMilestone->PM_PMSCode = 'N';
                                }
                                $projectMilestone->PMProposeLateDay = $daysNowDiffProposeEnd;
                                $projectMilestone->PMPriority = $priority;
                                $projectMilestone->save();

                                $projectMilestoneAfters = ProjectMilestone::where('PM_PTNo', $project->PTNo)
                                    ->where('PMCompleteDate', null)
                                    ->where('PMSeq', '>', $projectMilestone->PMSeq)
                                    ->where('PMRefType', 'PJ')
                                    ->orderBy('PMSeq')
                                    ->get();

                                foreach ($projectMilestoneAfters as $projectMilestoneAfter){

                                    $pmAfterProposeStartDate = \Carbon\Carbon::parse($projectMilestoneAfter->PMProposeStartDate);
                                    $pmAfterProposeEndDate = \Carbon\Carbon::parse($projectMilestoneAfter->PMProposeEndDate);

                                    $projectMilestoneAfter->PMPriority = $priority;
                                    $projectMilestoneAfter->save();

//                            dd($projectMilestone->PMStartDate, $pmAfterProposeStartDate,  $pmAfterProposeStartDate->copy()->addDays($sumOfPMLateDay));
                                }
                            }
//                        dd($now .'is between'. $startDate.' and '.$EndDate);
                        } else {
                            if($now > $endDate){//lewat
//                            dd($now .'is NOT between and larger'. $startDate.' and '.$EndDate);

                                $daysNowDiffStart = $startDate->diffInDays($now) + 1;
                                $daysNowDiffProposeEnd = $proposeEndDate->diffInDays($now);
                                $PMLateDay = $daysNowDiffStart - $projectMilestone->PMWorkDay;

                                if($daysNowDiffProposeEnd >= $firstLevel) {
                                    if($daysNowDiffProposeEnd >= $secondLevel) {
                                        $priority = 2;
                                    }
                                    else{
                                        $priority = 1;
                                    }
                                }
                                else {
                                    $priority = 0;
                                }

                                $progress = $projectMilestone->PMWorkDay / $daysNowDiffStart;

                                if($progress > 1){
                                    $progress = 1;
                                }
                                if($projectMilestone->PM_PMSCode == 'D'){
                                    $projectMilestone->PM_PMSCode = 'N';
                                }
                                $projectMilestone->PMLateDay = $PMLateDay;
                                $projectMilestone->PMProposeLateDay = $daysNowDiffProposeEnd;
                                $projectMilestone->PMPriority = $priority;
                                $projectMilestone->PMProgress = number_format($progress ?? 0,4, '.', '');
                                $projectMilestone->PMEndDate = $now;
                                $projectMilestone->save();

                                $sumOfPMLateDay = ProjectMilestone::where('PM_PTNo', $project->PTNo)
                                    ->where('PMRefType', 'PJ')
                                    ->orderBy('PMSeq')
                                    ->sum('PMLateDay');

                                $sumOfPMWorkDay = ProjectMilestone::where('PM_PTNo', $project->PTNo)
                                    ->where('PMRefType', 'PJ')
                                    ->orderBy('PMSeq')
                                    ->sum('PMWorkDay');

                                $projectMilestoneAfters = ProjectMilestone::where('PM_PTNo', $project->PTNo)
                                    ->where('PMCompleteDate', null)
                                    ->where('PMSeq', '>', $projectMilestone->PMSeq)
                                    ->where('PMRefType', 'PJ')
                                    ->orderBy('PMSeq')
                                    ->get();

                                foreach ($projectMilestoneAfters as $projectMilestoneAfter){

                                    $pmAfterProposeStartDate = \Carbon\Carbon::parse($projectMilestoneAfter->PMProposeStartDate);
                                    $pmAfterProposeEndDate = \Carbon\Carbon::parse($projectMilestoneAfter->PMProposeEndDate);

                                    $projectMilestoneAfter->PMPriority = $priority;
                                    $projectMilestoneAfter->PMStartDate = $pmAfterProposeStartDate->copy()->addDays($sumOfPMLateDay);
                                    $projectMilestoneAfter->PMEndDate = $pmAfterProposeEndDate->copy()->addDays($sumOfPMLateDay);
                                    $projectMilestoneAfter->save();

//                            dd($projectMilestone->PMStartDate, $pmAfterProposeStartDate,  $pmAfterProposeStartDate->copy()->addDays($sumOfPMLateDay));
                                }

                                $projectEndDate = \Carbon\Carbon::parse($project->PTEndDate);

                                if($sumOfPMLateDay >= $firstLevelProject) {
                                    if($sumOfPMLateDay >= $secondLevelProject) {
                                        $priorityP = 2;
                                    }
                                    else{
                                        $priorityP = 1;
                                    }
                                }
                                else {
                                    $priorityP = 0;
                                }

                                $progressP = number_format($sumOfPMWorkDay ?? 0,2, '.', '') / (number_format($sumOfPMWorkDay ?? 0,2, '.', '') + number_format($sumOfPMLateDay ?? 0,2, '.', ''));
//                        $progressP = 183.00/184.00;

                                if($progressP > 1){
                                    $progressP = 1;
                                }

                                $project->PTEstimateEndDate = $projectEndDate->copy()->addDays($sumOfPMLateDay);
                                $project->PTLateDay = $sumOfPMLateDay;
                                $project->PTPriority = $priorityP;
                                $project->PTProgress = number_format($progressP ?? 0,4, '.', '');
                                $project->save();

                            }else{
//                            dd($now .'is NOT between and less'. $startDate.' and '.$EndDate);
                            }
                        }
                    }
                    else if($projectMilestone->PMRefType == 'EOT'){

                    }
                    else if($projectMilestone->PMRefType == 'VO'){

                    }
                    else{

                    }
                }
            }

            DB::commit();

            return $result;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Scheduler Tidak Berjaya!'.$e->getMessage()
            ], 400);
        }
    }

    public function calculateCompleteMilestone(Request $request){

        $user = Auth::user();

        $result = 0;
        if(isset($request->dateNow)){
            $now = \Carbon\Carbon::parse($request->dateNow);//testing according to current date
        }
        else{
            $now = Carbon::now();
        }

        try {
            DB::beginTransaction();
            $PMNo     = $request->PMNo;
            $dateComplete     = \Carbon\Carbon::parse($request->dateComplete);
            $PMSCode = "C";

            $websetting = Websetting::first();
            $firstLevel = (float) $websetting->COMilestonePeriod1 ;
            $secondLevel = (float) $websetting->COMilestonePeriod2 ;

            $firstLevelProject = (float) $websetting->ProjMilestonePeriod1 ;
            $secondLevelProject = (float) $websetting->ProjMilestonePeriod2 ;

            $milestone = ProjectMileStone::where('PMNo',$PMNo)->where('PMRefType', 'PJ')->first();
            $milestone->PM_PMSCode = $PMSCode;
            $milestone->PMCompleteDate = $request->dateComplete;
            $milestone->PMEndDate = $request->dateComplete;
            $milestone->PMPriority = 3;
            $milestone->PMApproveDate = $now;
            $milestone->PMApproveBy = $user->USCode;

            $proposeStartDate = \Carbon\Carbon::parse($milestone->PMProposeStartDate);
            $proposeEndDate = \Carbon\Carbon::parse($milestone->PMProposeEndDate);

            $startDate = \Carbon\Carbon::parse($milestone->PMStartDate);
            $endDate = \Carbon\Carbon::parse($milestone->PMEndDate);

            $daysCompleteDiffStart = $startDate->diffInDays($dateComplete) + 1;
            $daysCompleteDiffProposeStart = $proposeStartDate->diffInDays($dateComplete) + 1;
            $daysCompleteDiffProposeEnd = $proposeEndDate->diffInDays($dateComplete);
            $PMLateDay = $daysCompleteDiffStart - $milestone->PMWorkDay;

            $progress = $milestone->PMWorkDay / $daysCompleteDiffStart;

            if($progress > 1){
                $progress = 1;
            }

            $milestone->PMLateDay = $PMLateDay;
            $milestone->PMComplete = 1;
            $milestone->PMCompleteDay = $daysCompleteDiffStart;
            $milestone->PMProposeLateDay = $daysCompleteDiffProposeEnd;
            $milestone->PMProgress = number_format($progress ?? 0,4, '.', '');
            $milestone->save();

            $sumOfPMLateDay = ProjectMilestone::where('PM_PTNo', $milestone->PM_PTNo)
                ->where('PMRefType', 'PJ')
                ->orderBy('PMSeq')
                ->sum('PMLateDay');

            $sumOfPMWorkDay = ProjectMilestone::where('PM_PTNo', $milestone->PM_PTNo)
                ->where('PMRefType', 'PJ')
                ->orderBy('PMSeq')
                ->sum('PMWorkDay');

            $projectMilestoneAfters = ProjectMilestone::where('PM_PTNo', $milestone->PM_PTNo)
                ->where('PMRefType', 'PJ')
                ->where('PMCompleteDate', null)
                ->where('PMSeq', '>', $milestone->PMSeq)
                ->orderBy('PMSeq')
                ->get();

            $MilestoneAfterCurrentCompleteFirst = ProjectMilestone::where('PM_PTNo', $milestone->PM_PTNo)
                ->where('PMRefType', 'PJ')
                ->where('PMCompleteDate', null)
                ->where('PMSeq', '>', $milestone->PMSeq)
                ->orderBy('PMSeq')
                ->first();

            $pmAfterCurrentCompleteFirstProposeStartDate = \Carbon\Carbon::parse($MilestoneAfterCurrentCompleteFirst->PMProposeStartDate);
            $pmAfterCurrentCompleteFirstProposeEndDate = \Carbon\Carbon::parse($MilestoneAfterCurrentCompleteFirst->PMProposeEndDate);

            $MilestoneAfterCurrentCompleteFirst->PMStartDate = $pmAfterCurrentCompleteFirstProposeStartDate->copy()->addDays($sumOfPMLateDay);
            $MilestoneAfterCurrentCompleteFirst->PMEndDate = $pmAfterCurrentCompleteFirstProposeEndDate->copy()->addDays($sumOfPMLateDay);
            $MilestoneAfterCurrentCompleteFirst->save();

            $MilestoneAfterCurrentCompleteFirstStartDate = \Carbon\Carbon::parse($MilestoneAfterCurrentCompleteFirst->PMStartDate);

            $daysNowDiffStart = $MilestoneAfterCurrentCompleteFirstStartDate->diffInDays($now) + 1;

            $PMLateDay = $daysNowDiffStart - $MilestoneAfterCurrentCompleteFirst->PMWorkDay;

            if($PMLateDay >= $firstLevel) {
                if($PMLateDay >= $secondLevel) {
                    $priority = 2;
                }
                else{
                    $priority = 1;
                }
            }
            else {
                $priority = 0;
            }

            foreach ($projectMilestoneAfters as $projectMilestoneAfter){

                $pmAfterProposeStartDate = \Carbon\Carbon::parse($projectMilestoneAfter->PMProposeStartDate);
                $pmAfterProposeEndDate = \Carbon\Carbon::parse($projectMilestoneAfter->PMProposeEndDate);

                $projectMilestoneAfter->PMPriority = $priority;
                $projectMilestoneAfter->PMStartDate = $pmAfterProposeStartDate->copy()->addDays($sumOfPMLateDay);
                $projectMilestoneAfter->PMEndDate = $pmAfterProposeEndDate->copy()->addDays($sumOfPMLateDay);
                $projectMilestoneAfter->save();
            }

            $project = Project::where('PTNo', $milestone->PM_PTNo)->first();

            $projectEndDate = \Carbon\Carbon::parse($project->PTEndDate);

            if($sumOfPMLateDay >= $firstLevelProject) {
                if($sumOfPMLateDay >= $secondLevelProject) {
                    $priorityP = 2;
                }
                else{
                    $priorityP = 1;
                }
            }
            else {
                $priorityP = 0;
            }

            $progressP = number_format($sumOfPMWorkDay ?? 0,2, '.', '') / (number_format($sumOfPMWorkDay ?? 0,2, '.', '') + number_format($sumOfPMLateDay ?? 0,2, '.', ''));
//                        $progressP = 183.00/184.00;

            if($progressP > 1){
                $progressP = 1;
            }

            $project->PTEstimateEndDate = $projectEndDate->copy()->addDays($sumOfPMLateDay);
            $project->PTLateDay = $sumOfPMLateDay;
            $project->PTPriority = $priorityP;
            $project->PTProgress = number_format($progressP ?? 0,4, '.', '');
            $project->save();

            DB::commit();

            return $result;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Milestone Selesai Tidak Berjaya!'.$e->getMessage()
            ], 400);
        }
    }

    public function calculateEOTMilestone($meetingEOT){
        $meetingEOT->dateNow = '2024-01-07';

        $result = 0;
        try {
            DB::beginTransaction();

            $websetting = Websetting::first();
            $firstLevel = (float) $websetting->COMilestonePeriod1 ;
            $secondLevel = (float) $websetting->COMilestonePeriod2 ;

            $firstLevelProject = (float) $websetting->ProjMilestonePeriod1 ;
            $secondLevelProject = (float) $websetting->ProjMilestonePeriod2 ;

//            $milestoneChoose2 =ProjectMilestone::where('PMNo', 'PT00000019-002')->first();
            $milestoneChoose2 =ProjectMilestone::where('PMNo', $meetingEOT->ME_PMNo)
                ->where('PMRefType', 'PJ')
                ->first();
//            $milestones = ProjectMilestone::where('PMNo', '>=', 'PT00000019-002')
            $milestones = ProjectMilestone::where('PMNo', '>=', $meetingEOT->ME_PMNo)
                ->where('PM_PTNo', $milestoneChoose2->PM_PTNo)
                ->where('PMRefType', 'PJ')
                ->get();
            foreach ($milestones as $key => $milestone){
                $milestoneChoose = ProjectMilestone::where('PMNo', $milestone->PMNo)
                    ->where('PMRefType', 'PJ')
                    ->first();

                if($milestoneChoose->PMCompleteDate == null){
                    if($milestoneChoose->PM_PMSCode == 'N'){
                        if($key >= 1){
                            $milestonebefore = ProjectMilestone::where('PMNo', '<', $milestoneChoose->PMNo)
                                ->where('PM_PTNo', $milestoneChoose2->PM_PTNo)
                                ->where('PMRefType', 'PJ')
                                ->orderBy('PMNo', 'DESC')
                                ->first();

                            $milestoneBeforeProposeEndDate = \Carbon\Carbon::parse($milestonebefore->PMProposeEndDate);
                            $milestoneBeforeEndDate = \Carbon\Carbon::parse($milestonebefore->PMEndDate);

                            $milestoneChoose->PMProposeStartDate = $milestoneBeforeProposeEndDate->copy()->addDays(1);
                            $milestoneChoose->PMStartDate = $milestoneBeforeEndDate->copy()->addDays(1);
                        }
                        else{
                            $milestoneChoose->PMWorkDay = $milestoneChoose->PMWorkDay+$meetingEOT->MEWorkday;
                        }

                        $milestoneChoose->PMProposeEndDate = Carbon::parse($milestoneChoose->PMProposeStartDate)->copy()->addDays($milestoneChoose->PMWorkDay -1);
                        $endDate = \Carbon\Carbon::parse($milestoneChoose->PMEndDate);
                        $PMEndDate = Carbon::parse($milestoneChoose->PMStartDate)->copy()->addDays($milestoneChoose->PMWorkDay);

                        if($endDate > $PMEndDate){

                        }
                        else if($endDate < $PMEndDate){
                            $milestoneChoose->PMEndDate = $PMEndDate;
                        }
                        else{

                        }

                        $proposeStartDate = \Carbon\Carbon::parse($milestoneChoose->PMProposeStartDate);
                        $proposeEndDate = \Carbon\Carbon::parse($milestoneChoose->PMProposeEndDate);

                        $startDate = \Carbon\Carbon::parse($milestoneChoose->PMStartDate);
                        $endDate = \Carbon\Carbon::parse($milestoneChoose->PMEndDate);

                        if(isset($meetingEOT->dateNow)){
                            $now = \Carbon\Carbon::parse($meetingEOT->dateNow);//testing according to current date
                        }
                        else{
                            $now = Carbon::now();
                        }

                        $daysCompleteDiffStart = $startDate->diffInDays($now) + 1;
                        $daysCompleteDiffProposeStart = $proposeStartDate->diffInDays($now) + 1;
                        $daysCompleteDiffProposeEnd = $proposeEndDate->diffInDays($now);
                        if($milestoneChoose->PMProposeEndDate > $now){
                            $daysCompleteDiffProposeEnd = -$daysCompleteDiffProposeEnd;
                        }
                        $PMLateDay = $daysCompleteDiffStart - $milestoneChoose->PMWorkDay;

                        $progress = $milestoneChoose->PMWorkDay / $daysCompleteDiffStart;

                        $milestoneChoose->PMLateDay = $PMLateDay;

                        if($PMLateDay >= $firstLevel) {
                            if($PMLateDay >= $secondLevel) {
                                $priority = 2;
                            }
                            else{
                                $priority = 1;
                            }
                        }
                        else {
                            $priority = 0;
                        }

                        $milestoneChoose->PMPriority = $priority;
                        $milestoneChoose->PMProposeLateDay = $daysCompleteDiffProposeEnd;
                        $milestoneChoose->PMProgress = number_format($progress ?? 0,4, '.', '');
                        $milestoneChoose->save();
                    }
                    else{

                        if($key >= 1){
//                            $milestones = ProjectMilestone::where('PMNo', '>=', 'PT00000019-002')
                            $milestonebefore = ProjectMilestone::where('PMNo', '<', $milestoneChoose->PMNo)
                                ->where('PM_PTNo', $milestoneChoose2->PM_PTNo)
                                ->where('PMRefType', 'PJ')
                                ->orderBy('PMNo', 'DESC')
                                ->first();

                            if($milestonebefore->PM_PMSCode == 'N'){
                            }
                            else{
                                $milestonebefore->PMPriority = 0;
                            }

                            $milestoneBeforeProposeEndDate = \Carbon\Carbon::parse($milestonebefore->PMProposeEndDate);
                            $milestoneBeforeEndDate = \Carbon\Carbon::parse($milestonebefore->PMEndDate);

                            $milestoneChoose->PMPriority = $milestonebefore->PMPriority;
                            $milestoneChoose->PMProposeStartDate = $milestoneBeforeProposeEndDate->copy()->addDays(1);
                            $milestoneChoose->PMStartDate = $milestoneBeforeEndDate->copy()->addDays(1);
                        }
                        else{
                            $milestoneChoose->PMWorkDay = $milestoneChoose->PMWorkDay+$meetingEOT->MEWorkday;
                        }

                        $milestoneChoose->PMProposeEndDate = Carbon::parse($milestoneChoose->PMProposeStartDate)->copy()->addDays($milestoneChoose->PMWorkDay - 1);
                        $endDate = \Carbon\Carbon::parse($milestoneChoose->PMEndDate);
                        $milestoneChoose->PMEndDate = Carbon::parse($milestoneChoose->PMStartDate)->copy()->addDays($milestoneChoose->PMWorkDay - 1);
                        $milestoneChoose->save();
                    }
                }
                else{
                    if($key >= 1){
                        $milestonebefore = ProjectMilestone::where('PMNo', '<', $milestoneChoose->PMNo)
                            ->where('PM_PTNo', $milestoneChoose2->PM_PTNo)
                            ->where('PMRefType', 'PJ')
                            ->orderBy('PMNo', 'DESC')
                            ->first();

                        $milestoneBeforeProposeEndDate = \Carbon\Carbon::parse($milestonebefore->PMProposeEndDate);
                        $milestoneBeforeEndDate = \Carbon\Carbon::parse($milestonebefore->PMEndDate);

                        $milestoneChoose->PMProposeStartDate = $milestoneBeforeProposeEndDate->copy()->addDays(1);
                        $milestoneChoose->PMStartDate = $milestoneBeforeEndDate->copy()->addDays(1);
                    }
                    else{
                        $milestoneChoose->PMWorkDay = $milestoneChoose->PMWorkDay+$meetingEOT->MEWorkday;
                    }

                    $milestoneChoose->PMProposeEndDate = Carbon::parse($milestoneChoose->PMProposeStartDate)->copy()->addDays($milestoneChoose->PMWorkDay-1);

                    $proposeStartDate = \Carbon\Carbon::parse($milestoneChoose->PMProposeStartDate);
                    $proposeEndDate = \Carbon\Carbon::parse($milestoneChoose->PMProposeEndDate);

                    $startDate = \Carbon\Carbon::parse($milestoneChoose->PMStartDate);
                    $endDate = \Carbon\Carbon::parse($milestoneChoose->PMEndDate);

                    $completeDate = \Carbon\Carbon::parse($milestoneChoose->PMCompleteDate);

                    $daysCompleteDiffStart = $startDate->diffInDays($completeDate) + 1;
                    $daysCompleteDiffProposeStart = $proposeStartDate->diffInDays($completeDate) + 1;
                    $daysCompleteDiffProposeEnd = $proposeEndDate->diffInDays($completeDate);
                    if($milestoneChoose->PMProposeEndDate > $completeDate){
                        $daysCompleteDiffProposeEnd = -$daysCompleteDiffProposeEnd;
                    }
                    $PMLateDay = $daysCompleteDiffStart - $milestoneChoose->PMWorkDay;

                    $progress = $milestoneChoose->PMWorkDay / $daysCompleteDiffStart;

                    if($progress > 1){
                        $progress = 1;
                    }

                    $milestoneChoose->PMLateDay = $PMLateDay;
                    $milestoneChoose->PMComplete = 1;
                    $milestoneChoose->PMCompleteDay = $daysCompleteDiffStart;
                    $milestoneChoose->PMProposeLateDay = $daysCompleteDiffProposeEnd;
                    $milestoneChoose->PMProgress = number_format($progress ?? 0,4, '.', '');
                    $milestoneChoose->save();


                }
            }

            $project = Project::where('PTNo', $milestoneChoose2->PM_PTNo)->first();

            $project->PTEndDate = Carbon::parse($project->PTEndDate)->copy()->addDays($meetingEOT->MEWorkday);

            $projectEndDate = \Carbon\Carbon::parse($project->PTEndDate);

            $sumOfPMLateDay = ProjectMilestone::where('PM_PTNo', $milestoneChoose2->PM_PTNo)
                ->where('PMRefType', 'PJ')
                ->orderBy('PMSeq')
                ->sum('PMLateDay');

            $sumOfPMWorkDay = ProjectMilestone::where('PM_PTNo', $milestoneChoose2->PM_PTNo)
                ->where('PMRefType', 'PJ')
                ->orderBy('PMSeq')
                ->sum('PMWorkDay');

            if($sumOfPMLateDay >= $firstLevelProject) {
                if($sumOfPMLateDay >= $secondLevelProject) {
                    $priorityP = 2;
                }
                else{
                    $priorityP = 1;
                }
            }
            else {
                $priorityP = 0;
            }

            $progressP = number_format($sumOfPMWorkDay ?? 0,2, '.', '') / (number_format($sumOfPMWorkDay ?? 0,2, '.', '') + number_format($sumOfPMLateDay ?? 0,2, '.', ''));
//                        $progressP = 183.00/184.00;

            if($progressP > 1){
                $progressP = 1;
            }

            $project->PTEstimateEndDate = $projectEndDate->copy()->addDays($sumOfPMLateDay);
            $project->PTLateDay = $sumOfPMLateDay;
            $project->PTPriority = $priorityP;
            $project->PTProgress = number_format($progressP ?? 0,4, '.', '');
            $project->save();

//            $milestonesX = ProjectMilestone::where('PM_PTNo', $milestoneChoose2->PM_PTNo)
//                ->where('PMRefType', 'PJ')
//                ->get();
//
//            dd($milestonesX);

            DB::commit();

            return $result;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Lanjutan masa Tidak Berjaya!'.$e->getMessage()
            ], 400);
        }
    }

    public function calculateNewEOTMilestone($meeting, $module){
        $result = 0;
        try {
            DB::beginTransaction();
            $user = Auth::user();

            if($module == 'EOT'){
                $eot = ExtensionOfTime::where('EOTNo', $meeting->ME_EOTNo)->first();

                $latest = ProjectMileStone::where('PM_PTNo', $eot->EOT_PTNo)
                    ->orderBy('PMNo','desc')
                    ->first();

                if($latest){
                    $PMNo = $this->increment3digit($latest->PMNo);
                    $PMSeq = $latest->PMSeq + 1;
                }
                else{
                    $PMNo = $eot->EOT_PTNo.'-'.$formattedCounter = sprintf("%03d", 1);
                    $PMSeq = 1;
                }

                $proposeEndDate = Carbon::parse($meeting->MEStartDate)->copy()->addDays($meeting->MEWorkday -1);

                $PMRefNo = $meeting->ME_EOTNo;
                $PTNo = $eot->EOT_PTNo;
                $PMDesc = $eot->EOTDesc;
                $PMWorkDay = $meeting->MEWorkday;
                $proposeStartDate = \Carbon\Carbon::parse($meeting->MEStartDate);

                $eot->EOTApproveWorkDay = $PMWorkDay;
                $eot->EOTApproveStartDate = $proposeStartDate;
                $eot->EOTApproveEndDate = $proposeEndDate;

                if($proposeStartDate > Carbon::now()){
                    $eot->EOTEstimateEndDate = $proposeEndDate;
                    $eot->EOTPriority = 0;
                    $eot->EOTProgress = 1;
                    $eot->EOTLateDay = 0;
                }
                else{
                    if(Carbon::now()->format('d/m/Y')>$proposeEndDate->format('d/m/Y')){
                        //CALCULATE
                        $websetting = Websetting::first();
                        $firstLevel = (float) $websetting->COMilestonePeriod1 ;
                        $secondLevel = (float) $websetting->COMilestonePeriod2 ;

                        $daysNowDiffProposeEnd = $proposeEndDate->diffInDays(Carbon::now());
                        $daysCompleteDiffStart = $proposeStartDate->diffInDays(Carbon::now()) + 1;

                        $progress = $PMWorkDay / $daysCompleteDiffStart;

                        if($daysNowDiffProposeEnd >= $firstLevel) {
                            if($daysNowDiffProposeEnd >= $secondLevel) {
                                $priority = 2;
                            }
                            else{
                                $priority = 1;
                            }
                        }
                        else {
                            $priority = 0;
                        }

                        $eot->EOTEstimateEndDate = Carbon::now();
                        $eot->EOTPriority = $priority;
                        $eot->EOTProgress = $progress;
                        $eot->EOTLateDay = $daysNowDiffProposeEnd;
                    }
                    else{
                        $eot->EOTEstimateEndDate = $proposeEndDate;
                        $eot->EOTPriority = 0;
                        $eot->EOTProgress = 1;
                        $eot->EOTLateDay = 0;
                    }
                }
                $eot->save();

            }
            else{
                $vo = VariantOrder::where('VONo', $meeting->MV_VONo)->first();

                $latest = ProjectMileStone::where('PM_PTNo', $vo->VO_PTNo)
                    ->orderBy('PMNo','desc')
                    ->first();

                if($latest){
                    $PMNo = $this->increment3digit($latest->PMNo);
                    $PMSeq = $latest->PMSeq + 1;
                }
                else{
                    $PMNo = $vo->VO_PTNo.'-'.$formattedCounter = sprintf("%03d", 1);
                    $PMSeq = 1;
                }
                $proposeEndDate = Carbon::parse($meeting->MVStartDate)->copy()->addDays($meeting->MVWorkday -1);

                $PMRefNo = $meeting->MV_VONo;
                $PTNo = $vo->VO_PTNo;
                $PMDesc = $vo->VODesc;
                $PMWorkDay = $meeting->MVWorkday;
                $proposeStartDate = $meeting->MVStartDate;

                $vo->VOApproveWorkDay = $PMWorkDay;
                $vo->VOApproveStartDate = $proposeStartDate;
                $vo->VOApproveEndDate = $proposeEndDate;

                if($proposeStartDate > Carbon::now()){
                    $vo->VOEstimateEndDate = $proposeEndDate;
                    $vo->VOPriority = 0;
                    $vo->VOProgress = 1;
                    $vo->VOLateDay = 0;
                }
                else{
                    if(Carbon::now()->format('d/m/Y')>$proposeEndDate->format('d/m/Y')){
                        //CALCULATE
                        $websetting = Websetting::first();
                        $firstLevel = (float) $websetting->COMilestonePeriod1 ;
                        $secondLevel = (float) $websetting->COMilestonePeriod2 ;

                        $daysNowDiffProposeEnd = $proposeEndDate->diffInDays(Carbon::now());
                        $daysCompleteDiffStart = $proposeStartDate->diffInDays(Carbon::now()) + 1;

                        $progress = $PMWorkDay / $daysCompleteDiffStart;

                        if($daysNowDiffProposeEnd >= $firstLevel) {
                            if($daysNowDiffProposeEnd >= $secondLevel) {
                                $priority = 2;
                            }
                            else{
                                $priority = 1;
                            }
                        }
                        else {
                            $priority = 0;
                        }

                        $vo->VOEstimateEndDate = Carbon::now();
                        $vo->VOPriority = $priority;
                        $vo->VOProgress = $progress;
                        $vo->VOLateDay = $daysNowDiffProposeEnd;
                    }
                    else{
                        $vo->VOEstimateEndDate = $proposeEndDate;
                        $vo->VOPriority = 0;
                        $vo->VOProgress = 1;
                        $vo->VOLateDay = 0;
                    }
                }
                $vo->save();
            }

            $newData = new ProjectMilestone();
            $newData->PMNo                  = $PMNo;
            $newData->PMRefType             = $module;
            $newData->PMRefNo               = $PMRefNo;
            $newData->PM_PTNo               = $PTNo;
            $newData->PMSeq                 = $PMSeq;
            $newData->PMDesc                = $PMDesc;
            $newData->PMWorkDay             = $PMWorkDay;
            $newData->PMWorkPercent         = 100;
            $newData->PMClaimInd            = 1;
            $newData->PMProposeStartDate    = $proposeStartDate;
            $newData->PMProposeEndDate      = $proposeEndDate;
            $newData->PMStartDate           = $proposeStartDate;
            $newData->PMEndDate             = $proposeEndDate;
            $newData->PMPriority            = 0;
            $newData->PMProgress            = 1;
            $newData->PMActive              = 1;
            $newData->PM_PMSCode            = 'D';
            $newData->PMCB                  = $user->USCode;
            $newData->PMMB                  = $user->USCode;
            $newData->save();

            DB::commit();

            return $result;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Tambah Milestone Lanjutan Masa Tidak Berjaya!'.$e->getMessage()
            ], 400);
        }

    }

    public function increment3digit($no){
        $prefix = substr($no, 0, 11);   // "TD"
//        $suffix = substr($no, -4);     // "-121"

        // Extract last 3 digits
        $last3Digits = substr($no, -3, 3); // "001"

        // Convert to integer, increment, and format with leading zeros
        $incrementedLast3Digits = str_pad((int)$last3Digits + 1, 3, "0", STR_PAD_LEFT);

        // Combine all parts
        $incrementedno = $prefix . $incrementedLast3Digits;

        return $incrementedno;
    }

    public function closeAds(Request $request){

        try {

            DB::beginTransaction();

            if($request->tdNo != null){
                $tender = Tender::where('TDNo', $request->tdNo)->first();
                if($tender){
                    $tender->TD_TPCode = 'CA';
                    $tender->save();

                    $tenderProposalDrafs = TenderProposal::where('TP_TDNo', $request->tdNo)->where('TP_TPPCode', 'DF')->get();

                    foreach ($tenderProposalDrafs as $tenderProposalDraf){
                        $tenderProposalDraf->TP_TPPCode = 'CL';
                        $tenderProposalDraf->save();
                    }
                }
                else{
                    return response()->json([
                        'error' => '1',
                        'message' => 'Tender tidak berjaya dikemaskini!'
                    ], 400);
                }
            }

            DB::commit();

            return true;


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Buka Peti tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

}
