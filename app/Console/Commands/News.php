<?php

    namespace App\Console\Commands;

    use Illuminate\Console\Command;
    use App\Models\AutoNumber;
    use App\Models\TenderProposal;
    use App\Models\TenderProposalNews;
    use App\Models\IntegrationLog;
    use App\Models\Contractor;
    use App\Http\Controllers\Api\NewsController;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Config;

    class News extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
	protected $signature = 'OSC:News';

        /**
         * The console command description.
         *
         * @var string
         */
	protected $description = 'Task to pull Tender Proposal News';

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
            $news = new NewsController();
            $tenderProposalData = TenderProposal::where('TPCheckNews','N')->get();

            if(isset($tenderProposalData) && count($tenderProposalData)>0) {
                foreach ($tenderProposalData as $x => $tenderProposal) {
                    $this->info('Process Record: '.($x+1).'/'.count($tenderProposalData).' - Proposal No:['.$tenderProposal->TPNo.']');

                    $contractor = Contractor::where('CONo',$tenderProposal->TP_CONo)->first();
                    if ($contractor!= null){
                        $news->processGoogleNews($contractor, $tenderProposal,$this); //Process
                    }
                }
            }


            $this->info('News data has been processed successfully');
        }



    }
