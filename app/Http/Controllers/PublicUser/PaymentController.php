<?php

namespace App\Http\Controllers\PublicUser;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\CertApplication;
use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
Use Illuminate\Support\Facades\Storage;
Use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Validator,Redirect,Response,File;
use RevenueMonster\SDK\Exceptions\ApiException;
use RevenueMonster\SDK\Exceptions\ValidationException;
use RevenueMonster\SDK\RevenueMonster;
use RevenueMonster\SDK\Request\PredictMykad;
use RevenueMonster\SDK\Request\VerifyFace;
use RevenueMonster\SDK\Request\WebPayment;
use RevenueMonster\SDK\Request\QRPay;
use RevenueMonster\SDK\Request\QuickPay;


class PaymentController extends Controller
{

	Public function createPayment(Request $request){

		$user = Auth::user();

		$certApp = CertApplication::WHERE('CANo',$request->certAppNo)->First();
		if ($certApp == null){
			return response()->json([
				'status'  => 'failed',
				'message' => 'Certificate Application not found!'
			]);
		}

		try {
            DB::beginTransaction();

			$dateInMills = Carbon::now()->timestamp;
			$paymentLogNo = 'PL'.$dateInMills;

			$paymentLog = new PaymentLog();
			$paymentLog->PLNo				= $paymentLogNo;
			$paymentLog->PL_CONo			= $user->USCode;
			$paymentLog->PLDesc				= 'Bayaran Sijil';
			$paymentLog->PL_PSCode			= '00';
			$paymentLog->PLPaymentFee		= $certApp->CATotalFee;
			$paymentLog->PLRefNo			= $certApp->CANo;
			$paymentLog->PL_PLTCode			= 'CERTAPP';
			$paymentLog->PLCB				= $user->USCode;
			$paymentLog->PLCD				= Carbon::now();
			$paymentLog->save();

			DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            throw $e;
        }


		//$fileLocation = Storage::disk('local')->path('rev_mon_private_key.pem');

		$fileLocation = Storage::disk('local')->path('rev_mon_private_key.pem');

		$rm = new RevenueMonster([
        	'clientId' => config::get('app.rm_clientid'),
        	'clientSecret' => config::get('app.rm_clientsecret'),
		  	'privateKey' => file_get_contents($fileLocation),
		  	'version' => 'stable',
		  	'isSandbox' => false,
		]);

		$redirectUrl = route('publicUser.updatePayment');
		$notifyUrl =  config::get('app.url').'api/paymentStatus';

		$title = 'Bayaran Sijil';
		$amount = 1;
		$detail = 'Bayaran Sijil';
		$checkOutID = '';
		$checkOutURL= '';

		// create Web payment
		try {
			$wp = new WebPayment;
			$wp->order->id 				= $paymentLog->PLNo;
			$wp->order->title 			= $title;
			$wp->order->currencyType 	= 'MYR';
			$wp->order->amount 			= $amount;
			$wp->order->detail 			= $detail;
			$wp->method 				= ["ALIPAY_CN","TNG_MY","SHOPEEPAY_MY","BOOST_MY","GRABPAY_MY","PRESTO_MY"];
			$wp->type 					= "WEB_PAYMENT";
			$wp->order->additionalData 	= $detail;
			$wp->storeId 				= config::get('app.rm_storeid'); //"1637227950434469890";
			$wp->redirectUrl 			= $redirectUrl;
			$wp->notifyUrl 				= $notifyUrl ;
			$wp->layoutVersion 			= 'v3';

			$response = $rm->payment->createWebPayment($wp);
			$checkOutID= $response->checkoutId; // Checkout ID
			$checkOutURL= $response->url; // Payment gateway url

			$paymentLog->PLCheckOutID = $checkOutID;
			$paymentLog->PLPaymentLink = $checkOutURL ;
			$paymentLog->Save();

		} catch(ApiException $e) {

		  //echo "statusCode : {$e->getCode()}, errorCode : {$e->getErrorCode()}, errorMessage : {$e->getMessage()}";
			return response()->json([
				'status'  => 'failed',
				'statusCode' => $e->getCode(),
				'errorCode' => $e->getErrorCode(),
				'message' => $e->getMessage(),
			]);
		} catch(ValidationException $e) {
		  	return response()->json([
				'status'  => 'failed',
				'message' => $e->getMessage(),
			]);
		} catch(Exception $e) {
		  	return response()->json([
				'status'  => 'failed',
				'message' => $e->getMessage(),
			]);
		}


		return Redirect::to($checkOutURL);
	}

	Public function updatePayment(Request $request){

        $messages = [
            'orderId.required' 	=> trans('message.param.orderID.required'),
        ];

        $validation = [
            'orderId' 	=> 'required',
        ];

        $validator = Validator::make($request->all(), $validation, $messages);


		$data = array(
			'redirectURL' 		=> '',
			'paymentStatusCode' => '00',
		);

		if ($validator->fails()) {
			return view('paymentResult', compact('data'));
		}

		$status 		= $request->status;
		$paymentLogNo 	= $request->orderId;

		// Retrieve Web Payment Record
		$paymentLog = PaymentLog::Where('PLNo',$paymentLogNo)->First();
		if ($paymentLog == null) {
			return view('paymentResult', compact('data'));
		}

		$paymentStatusCode = '';

		if($status == 'SUCCESS' ){
			$paymentStatusCode='01';
		}elseif($status == 'FAILED' ){
			$paymentStatusCode='02';
		}elseif($status == 'CANCELLED'){
			$paymentStatusCode='03';
		}

		try {
			DB::beginTransaction();

			//UPDATE PAYMENT LOG
			$paymentLog = PaymentLog::WHERE('PLNo',$paymentLogNo)->First();
			if ($paymentLog == null){
				log::error('Record Payment Log Not Found! '.$paymentLogNo);
				return '';
			}

			$paymentLog->PL_PSCode			= $paymentStatusCode;
			$paymentLog->PLMD				= Carbon::now();
			$paymentLog->save();

			//PAYMENT SUCCESS
			if ($status == 'SUCCESS') {

				/*
				$certApp->CA_CASCode	= 'PAID'; //LATEST STATUS
				$certApp->CA_CAPCode	= 'RESULT'; //NEXT PROCESS
				$certApp->CA_PLNo		= $paymentLog->PLNo;
				$certApp->Save();

				*/
			}

			DB::commit();

		} catch (\Throwable $e) {
			DB::rollback();
		   throw $e;
		}

		$data = array(
			'paymentLogNo' 		=> $paymentLog->PLNo ?? '',
			'paymentStatusCode' => $paymentLog->PL_PSCode ?? '',
			'paymentFee' 		=> (float) $paymentLog->PaymentFee,
			'redirectURL' 		=> "",
		);

        return view('paymentResult', compact('data'));
	}

	Public function paymentStatus(Request $request){

		log::info('paymentStatus');
		log::info($request);

		$data=$request->data;
		$status = $data['status'];
		$paymentLogNo = $data['order']['id'];

		$refID = $data['referenceId'];
		$transID = $data['transactionId'];
		$method = $data['method'] ;

		if($status == 'SUCCESS' ){
			$paymentStatusCode='01';
		}elseif($status == 'FAILED' ){
			$paymentStatusCode='02';
		}elseif($status == 'CANCELLED'){
			$paymentStatusCode='03';
		}

		try {
			DB::beginTransaction();

			//UPDATE PAYMENT LOG
			$paymentLog = PaymentLog::WHERE('PLNo',$paymentLogNo)->First();
			if ($paymentLog == null){
				log::error('Record Payment Log Not Found! '.$paymentLogNo);
				return '';
			}

			$paymentLog->PLReferenceID		= $refID ?? '';
			$paymentLog->PLTransactionID	= $transID ?? '';
			$paymentLog->PLMethod			= $method ?? '';
			$paymentLog->PL_PSCode			= $paymentStatusCode;
			$paymentLog->PLMD				= Carbon::now();
			$paymentLog->save();

			//PAYMENT SUCCESS
			if ($status == 'SUCCESS') {

				/*
				$certApp->CA_CASCode	= 'PAID'; //LATEST STATUS
				$certApp->CA_CAPCode	= 'RESULT'; //NEXT PROCESS
				$certApp->CA_PLNo		= $paymentLog->PLNo;
				$certApp->Save();

				*/
			}

			DB::commit();

			//$user = Users::WHERE('UserID',$paymentLog->CreateID)->First();

			//$this->sendPushNotification($notification->NotificationID, $user->Locale);


		} catch (\Throwable $e) {
			DB::rollback();
		   throw $e;
		}

		echo "RECEIVEOK";
	}

}
