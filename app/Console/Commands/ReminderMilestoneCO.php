<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\ProjectMileStone;
use App\Models\WebSetting;
use Carbon\Carbon;

class ReminderMilestoneCO extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'ReminderMilestoneCO:check';

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
//        $this->PTNo  = $this->ask('Project Number (PT00000001)');
        $this->PTNo  = null;
//        $this->dateNow  = $this->ask('Date Now (2023-12-01)');
        $this->dateNow  = null;
        $this->process();

	}

    private function process(){
        $websetting = Websetting::first();
        $firstLevel = (float) $websetting->COMilestonePeriod1 ;
        $secondLevel = (float) $websetting->COMilestonePeriod2 ;

        if($this->dateNow == null){
            $now = Carbon::now();
        }
        else{
            $now = Carbon::parse($this->dateNow);
        }

        $PTNo = $this->PTNo;

        $projects = Project::where('PT_PPCode', 'PS')
            ->when($PTNo, function ($q) use ($PTNo) {
                $q->where("PTNo", $PTNo);
            })
            ->get();

        if(isset($projects) && count($projects)>0) {
            foreach ($projects as $x => $project) {
                $this->info('Process Record: '.($x+1).'/'.count($projects).' - Proposal No:['.$project->PTNo.']');

                $projectMilestone = ProjectMilestone::where('PM_PTNo', $project->PTNo)
                    ->where('PMCompleteDate', null)
                    ->orderBy('PMSeq')
                    ->first();

                if($projectMilestone){
                    if($projectMilestone->PMRefType == 'PJ'){
                        $proposeStartDate = Carbon::parse($projectMilestone->PMProposeStartDate);
                        $proposeEndDate = Carbon::parse($projectMilestone->PMProposeEndDate);
//                        $this->info('$proposeEndDate '.$proposeEndDate);

                        $startDate = Carbon::parse($projectMilestone->PMStartDate);
//                        $this->info('$startDate '.$startDate);
                        $endDate = Carbon::parse($projectMilestone->PMEndDate);
//                        $this->info('$endDate '.$endDate);

                        if($now > $endDate){

                            $daysNowDiffStart = $startDate->diffInDays($now) + 1;
//                            $this->info('$daysNowDiffStart '.$daysNowDiffStart);
                            $daysNowDiffProposeEnd = $proposeEndDate->diffInDays($now);
//                            $this->info('$daysNowDiffProposeEnd '.$daysNowDiffProposeEnd);

                            if($daysNowDiffProposeEnd >= $firstLevel) {
                                if($daysNowDiffProposeEnd >= $secondLevel) {
                                    $exist_notification = Notification::where('NO_RefCode', $projectMilestone->PMNo)
                                        ->where('NO_NTCode', 'RM-PM-L2')
                                        ->first();

                                    if($exist_notification){
                                        $this->info('Already sent notification');
                                    }
                                    else{
                                        //HAMZAH-TAMBAH INSERT TO TABLE NOTIFICATION (NO_NTCode == RM-PM-L2)

                                        $this->info('Notification sent NOID: ');
                                    }
                                }
                                else{
                                    $exist_notification = Notification::where('NO_RefCode', $projectMilestone->PMNo)
                                        ->where('NO_NTCode', 'RM-PM-L1')
                                        ->first();

                                    if($exist_notification){
                                        $this->info('Already sent notification');
                                    }
                                    else{
                                        //HAMZAH-TAMBAH INSERT TO TABLE NOTIFICATION (NO_NTCode == RM-PM-L1)

                                        $this->info('Notification sent NOID: ');
                                    }
                                }
                            }
                        }
                        else{
                            $this->info('On Schedule');
                        }
                    }
                    else if($projectMilestone->PMRefType == 'EOT'){

                    }
                    else if($projectMilestone->PMRefType == 'VO'){

                    }
                    else{

                    }
                }
                else{
                    $this->info('No Project Milestone to process');
                }

                $this->info('');
            }
            $this->info('Reminder Milestone Contractor has been processed successfully');
        }
    }
}
