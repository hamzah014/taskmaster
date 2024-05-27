<?php

namespace App\Console\Commands;

use App\Models\Contractor;
use Illuminate\Console\Command;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Support\Facades\Config;

class Register extends Command
{

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'Register:check';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Task to pull SSM & Face Verification';

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
        $register = new RegisterController();
        $contractorData = Contractor::where('COStatus','PAID')
                                        ->whereNotNull('COBusinessNo')
                                        ->where(
                                            function($query) {
                                              return $query
                                                     ->whereNull('COIntegrateResult')
                                                     ->orWhereIn('COIntegrateResult', ['PENDING','KIV','WIP']);
                                             })
                                        ->get();

         $this->info('Process Record: '.count($contractorData));
        if(isset($contractorData) && count($contractorData)>0) {
            foreach ($contractorData as $x => $contractor) {
                $this->info('Process Record: '.($x+1).'/'.count($contractorData).' - Contractor No:['.$contractor->CONo.']');
               $register->ssmRegister($contractor->CONo, $this);
            }
        }

        $this->info('SSM data has been processed successfully');
	}

}
