<?php

use Illuminate\Database\Seeder;
use \App\Models\CmnRunningNo;
use \App\Models\ParkingTransactionHeader;
use \App\Models\ParkingTransactionDetail;
use \App\Models\PaymentOperator;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $getName =[
            'PARKING',
            'MERCHANT',
            'JOMPARKING',
            'FLEXI PARKING',
            'TOUCH N GO E-WALLET',
            'BOOST',
            'MCASH',

        ];
        foreach($getName as $names) {
            $cmnRunningNo = new CmnRunningNo();
            $uniqueOPPId = $cmnRunningNo->generateRunningNo('11', 'OPP');

            $operator = new PaymentOperator();
            $operator->operator_id = $uniqueOPPId;
            $operator->name = $names;
            $operator->operator_user_id = '';
            $operator->client_id = '';
            $operator->client_secret = '';
            $operator->sequence = '0';
            $operator->is_active = '1';
            $operator->created_by = '1';
            $operator->save();
        }

//        ezklsmartpark
        foreach (range(1, 20) as $indexHeader) {
            $cmnRunningNo = new CmnRunningNo();
            $uniqueId = $cmnRunningNo->generateRunningNo('7','PTH');
            $uniqueTransId = $cmnRunningNo->generateRunningNo('1','PT');
            $uniquePaymentId = $cmnRunningNo->generateRunningNo('0','OP');
            $uniqueOperatorId = $cmnRunningNo->generateRunningNo('4','OPS');
            if(strlen($indexHeader) == 1) {
                $code = '128'.$indexHeader;
            } else if(strlen($indexHeader) == 2) {
                $code = '20'.$indexHeader;
            } else {
                $code = $indexHeader;
            }
            $operator = PaymentOperator::orderBy('created_at', 'ASC')->get();
            foreach($operator as $operators) {
                $parking = new ParkingTransactionHeader();
                $parking->reference_no = $uniqueId;
                $parking->payment_no = $uniquePaymentId;
                $parking->user_id = $indexHeader;
                $parking->partner_id = $indexHeader;
                $parking->location_id = $indexHeader;
                $parking->car_id = $indexHeader;
                $parking->car_plate_no = 'ABC' . $code;
                $parking->car_model = '';
                $parking->car_desc = '';
                $parking->point = '100';
                $parking->operator_id = $operators->id;
                $parking->zone_id = '1';
                $parking->monthly_pass_id = '0';
                $parking->status = '1';
                $parking->message_last_post = \Carbon\Carbon::now();
                $parking->activity_date_deadline = \Carbon\Carbon::today();
                $parking->transaction_date = \Carbon\Carbon::now();
                $parking->save();

                $parkingDetails = new ParkingTransactionDetail();
                $parkingDetails->p_transaction_header_id = $parking->id;
                $parkingDetails->operator_no = $uniqueOperatorId;
                $parkingDetails->transaction_no = $uniqueTransId;
                $parkingDetails->amount = '4.00';
                $parkingDetails->time_in = \Carbon\Carbon::create(2021, 6, 1, 8, 0, 0);
                $parkingDetails->time_out = \Carbon\Carbon::create(2021, 6, 1, 18, 0, 0);
                $parkingDetails->duration = '9';
                $parkingDetails->merchant_user_id = $indexHeader;
                $parkingDetails->extend_session = '';
                $parkingDetails->retry_limit = '';
                $parkingDetails->receive_status = '';
                $parkingDetails->currency_id = '';
                $parkingDetails->origin = '';
                $parkingDetails->description = '';
                $parkingDetails->save();
            }

        }
    }
}