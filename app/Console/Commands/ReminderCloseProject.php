<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\WebSetting;
use Carbon\Carbon;

class ReminderCloseProject extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'ReminderCloseProject:check';

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
        $remindProjEndDatePercent = (float) $websetting->RemindProjEndDatePercent ;

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
        if($remindProjEndDatePercent != null && $remindProjEndDatePercent != 0){
            if(isset($projects) && count($projects)>0) {
                foreach ($projects as $x => $project) {
                    $this->info('Process Record: '.($x+1).'/'.count($projects).' - Proposal No:['.$project->PTNo.']');

                    $proposeEndDate = Carbon::parse($project->PTEstimateEndDate);
//                $this->info('$proposeEndDate '.$proposeEndDate);

                    $startDate = Carbon::parse($project->PTStartDate);
//                $this->info('$startDate '.$startDate);
                    $endDate = Carbon::parse($project->PTEndDate);
//                $this->info('$endDate '.$endDate);
                    if($now >= $startDate){
                        $startDiffEnd = $startDate->diffInDays($endDate) + 1;
                        $startDiffNow = $startDate->diffInDays($now) + 1;

                        $daySentNotification = ceil($remindProjEndDatePercent * $startDiffEnd);

                        if($startDiffNow >= $daySentNotification){
                            $exist_notification = Notification::where('NO_RefCode', $project->PTNo)
                                ->where('NO_NTCode', 'RM-CP')
                                ->first();

                            if($exist_notification){
                                $this->info('Already sent notification');
                            }
                            else{
                                //HAMZAH-TAMBAH INSERT TO TABLE NOTIFICATION (NO_NTCode == RM-CP)

                                $this->info('Notification sent NOID: ');
                            }
                        }
                        else{
                            $this->info('Still below the setting amount');
                        }
                    }
                    else{
                        $this->info('Project Start Date Not come yet');
                    }
//                    $this->info('');
                }
                $this->info('Reminder Close Project has been processed successfully');
            }
        }
        else{
            $this->info('Please check setting MSWebsetting.RemindProjEndDatePercent');
        }
    }
}
