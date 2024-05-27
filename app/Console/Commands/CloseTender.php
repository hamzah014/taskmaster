<?php
    namespace App\Console\Commands;
    use Illuminate\Console\Command;
    use App\Models\Tender;
    use App\Models\TenderProposal;
    use App\Models\TenderActivityLog;
    use App\Models\TenderProposalActivityLog;
    use Carbon\Carbon;
    class CloseTender extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
	protected $signature = 'Tender:Close';
        /**
         * The console command description.
         *
         * @var string
         */
	protected $description = 'Task to auto close tender';
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
            $date = carbon::now();

            $tenderData = Tender::where('TDClosingDate','<=',$date)->where('TD_TPCode','PA')->get();
            if(isset($tenderData) && count($tenderData)>0) {
                foreach ($tenderData as $x => $tender) {
                    $this->info('Process Record: '.($x+1).'/'.count($tenderData).' - Tender No:['.$tender->TDNo.']');

                    $tender->TD_TPCode = "CA";
                    $tender->save();

                    $tenderActivityLog = new TenderActivityLog();
                    $tenderActivityLog->TDAL_TDNo    = $tender->TDNo;
                    $tenderActivityLog->TDAL_TPCode  = $tender->TD_TPCode;
                    $tenderActivityLog->TDAL_USCode  = 'SYSTEM';
                    $tenderActivityLog->save();

                    $proposalData = TenderProposal::where('TP_TDNo',$tender->TDNo)->where('TP_TPPCode','PS')->get();
                    if(isset($proposalData) && count($proposalData)>0) {
                        foreach ($proposalData as $x => $proposal) {
                            $this->info('Process Record: '.($x+1).'/'.count($proposalData).' - Proposal No:['.$proposal->TPNo.']');
                            $proposal->TP_TPPCode = "02";
                            $proposal->save();

                            $proposalActivityLog = new TenderProposalActivityLog();
                            $proposalActivityLog->TPAL_TPNo    = $proposal->TPNo;
                            $proposalActivityLog->TP_TPPCode  = $proposal->TP_TPPCode;
                            $proposalActivityLog->TPAL_USCode  = 'SYSTEM';
                            $proposalActivityLog->save();
                        }
                    }
                }
            }


            $this->info('Close Tender has been processed successfully');
        }
    }
