<?php

namespace App\Console\Commands;

use App\Http\Controllers\SchedulerController;
use App\Models\AutoNumber;
use App\Services\DropdownService;
use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\ProjectMileStone;
use App\Models\WebSetting;
use Carbon\Carbon;

class MIlestone extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'Milestone:check';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Task to update overdue milestone';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
        $projects = Project::where('PT_PPCode', 'PS')->get();

        if(isset($projects) && count($projects)>0) {
            foreach ($projects as $x => $project) {
                $this->info('Process Record: '.($x+1).'/'.count($projects).' - Proposal No:['.$project->PTNo.']');
                $this->processMilestoneProject($project);
            }
            $this->info('Milestone overdue has been processed successfully');
        }
	}

    private function processMilestoneProject($project){
        $websetting = Websetting::first();
        $firstLevel = (float) $websetting->COMilestonePeriod1 ;
        $secondLevel = (float) $websetting->COMilestonePeriod2 ;

        $firstLevelProject = (float) $websetting->ProjMilestonePeriod1 ;
        $secondLevelProject = (float) $websetting->ProjMilestonePeriod2 ;

        $projectMilestone = ProjectMilestone::where('PM_PTNo', $project->PTNo)
            ->where('PMCompleteDate', null)
            ->orderBy('PMSeq')
            ->first();

        if($projectMilestone){
            $now = Carbon::now();

            if($projectMilestone->PMRefType == 'PJ'){
                $proposeStartDate = Carbon::parse($projectMilestone->PMProposeStartDate);
                $proposeEndDate = Carbon::parse($projectMilestone->PMProposeEndDate);

                $startDate = Carbon::parse($projectMilestone->PMStartDate);
                $endDate = Carbon::parse($projectMilestone->PMEndDate);

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

                            $pmAfterProposeStartDate = Carbon::parse($projectMilestoneAfter->PMProposeStartDate);
                            $pmAfterProposeEndDate = Carbon::parse($projectMilestoneAfter->PMProposeEndDate);

                            $projectMilestoneAfter->PMPriority = $priority;
                            $projectMilestoneAfter->save();

//                            dd($projectMilestone->PMStartDate, $pmAfterProposeStartDate,  $pmAfterProposeStartDate->copy()->addDays($sumOfPMLateDay));
                        }
                    }
//                        dd($now .'is between'. $startDate.' and '.$EndDate);
                }
                else {
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

                            $pmAfterProposeStartDate = Carbon::parse($projectMilestoneAfter->PMProposeStartDate);
                            $pmAfterProposeEndDate = Carbon::parse($projectMilestoneAfter->PMProposeEndDate);

                            $projectMilestoneAfter->PMPriority = $priority;
                            $projectMilestoneAfter->PMStartDate = $pmAfterProposeStartDate->copy()->addDays($sumOfPMLateDay);
                            $projectMilestoneAfter->PMEndDate = $pmAfterProposeEndDate->copy()->addDays($sumOfPMLateDay);
                            $projectMilestoneAfter->save();

//                            dd($projectMilestone->PMStartDate, $pmAfterProposeStartDate,  $pmAfterProposeStartDate->copy()->addDays($sumOfPMLateDay));
                        }

                        $projectEndDate = Carbon::parse($project->PTEndDate);

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

        return true;
    }
}
