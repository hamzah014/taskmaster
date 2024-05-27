<?php
    namespace App\Console\Commands;
    use Illuminate\Console\Command;
    use App\Models\PaymentLog;
    use Carbon\Carbon;
    class CancelPendingPayment extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
	protected $signature = 'Payment:CancelPendingPayment';
        /**
         * The console command description.
         *
         * @var string
         */
	protected $description = 'Task to auto cancel pending payment';
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
            $date = carbon::now()->addDays(-1);
            $paymentLogData = PaymentLog::where('PL_PSCode','00')->whereDate('PLCD','<',$date)->get();
            if(isset($paymentLogData) && count($paymentLogData)>0) {
                foreach ($paymentLogData as $x => $paymentLog) {
                    $this->info('Process Record: '.($x+1).'/'.count($paymentLogData).' - Payment Log No:['.$paymentLog->PLNo.']');
                    $paymentLog->PL_PSCode = "02";
                    $paymentLog->save();
                }
            }
            $this->info('Payment data has been processed successfully');
        }
    }
