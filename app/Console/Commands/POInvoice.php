<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Http\Controllers\Api\EBSController;

class POInvoice extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'EBS:POInvoice';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Task to update PO & invoice from EBS';

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
        $ebs = new EBSController();
		$projectData = Project::whereNotNull('PTContractNo')->whereNull('PTClosedDate')->get();

        if(isset($projectData) && count($projectData)>0) {
            foreach ($projectData as $x => $project) {
                $this->info('Process Record: '.($x+1).'/'.count($projectData).' - Project No:['.$project->PTNo.']');
               $ebs->processPOInvoice($project->PTNo, $this);
            }
        }

        $this->info('PO Invoice data has been updated successfully');
	}
}
