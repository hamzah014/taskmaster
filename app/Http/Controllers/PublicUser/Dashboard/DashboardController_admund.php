<?php

namespace App\Http\Controllers\PublicUser\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\CertApp;
use App\Models\Contractor;
use App\Models\PaymentLog;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\WebSetting;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

use RevenueMonster\SDK\Exceptions\ApiException;
use RevenueMonster\SDK\Exceptions\ValidationException;
use RevenueMonster\SDK\RevenueMonster;
use RevenueMonster\SDK\Request\WebPayment;

class DashboardController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function daftarSyarikat(){
        $negeri = $this->dropdownService->negeri();

        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();

        return view('publicUser.dashboard.daftarSyarikat',
            compact('negeri', 'contractor')
        );
    }

    public function updateSyarikat(Request $request){

        $messages = [
            'nama_syarikat.required'        => 'Ruangan Nama Syarikat diperlukan.',
            'ssm_no.required'               => 'Ruangan No. SSM Syarikat diperlukan.',
//            'ssm_no_new.required'           => 'Ruangan No. SSM Syarikat diperlukan.',
            'no_cukai.required'             => 'Ruangan No. Cukai diperlukan.',
//            'jenis_penyertaan.required'     => 'Ruangan Jenis Penyertaan diperlukan.',
//            'jenis_pendaftaran.required'    => 'Ruangan Jenis Pendaftaran diperlukan.',
//            'tarikh_ditubuhkan.required'    => 'Ruangan Tarikh Ditubuhkan diperlukan.',
//            'jenis_milikan_pejabat.required'=> 'Ruangan Jenis Milikan Pejabat diperlukan.',
            'hp_no.required'                => 'Ruangan No. Telefon Bimbit diperlukan.',
            'office_no.required'            => 'Ruangan No. Telefon Pejabat diperlukan.',
//            'fax_no.required'               => 'Ruangan No. Faks diperlukan.',
//            'parlimen_syarikat.required'    => 'Ruangan Parlimen Syarikat diperlukan.',
            'alamat1.required'              => 'Ruangan Alamat 1 (Alamat Pendaftaran) diperlukan.',
//            'alamat2.required'              => 'Ruangan Alamat 2 (Alamat Pendaftaran) diperlukan.',
            'poskod.required'               => 'Ruangan Poskod (Alamat Pendaftaran) diperlukan.',
            'bandar.required'               => 'Ruangan Bandar (Alamat Pendaftaran) diperlukan.',
            'negeri.required'               => 'Ruangan Negeri (Alamat Pendaftaran) diperlukan.',
            'business_alamat1.required'     => 'Ruangan Alamat 1 (Alamat Perniagaan) diperlukan.',
//            'business_alamat2.required'     => 'Ruangan Alamat 2 (Alamat Perniagaan) diperlukan.',
            'business_poskod.required'      => 'Ruangan Poskod (Alamat Perniagaan) diperlukan.',
            'business_bandar.required'      => 'Ruangan Bandar (Alamat Perniagaan) diperlukan.',
            'business_negeri.required'      => 'Ruangan Negeri (Alamat Perniagaan) diperlukan.',

        ];

        $validation = [
            'nama_syarikat'         => 'required|string',
            'ssm_no' 	            => 'required',
//            'ssm_no_new'            => 'required',
            'no_cukai' 	            => 'required',
//            'jenis_penyertaan'      => 'required',
//            'jenis_pendaftaran'     => 'required',
//            'tarikh_ditubuhkan'     => 'required',
//            'jenis_milikan_pejabat' => 'required',
            'hp_no'                 => 'required',
            'office_no'             => 'required',
//            'fax_no'                => 'required',
//            'parlimen_syarikat'     => 'required',
            'alamat1'               => 'required',
//            'alamat2'               => 'required',
            'poskod'                => 'required',
            'bandar'                => 'required',
            'negeri'                => 'required',
            'business_alamat1'      => 'required',
//            'business_alamat2'      => 'required',
            'business_poskod'       => 'required',
            'business_bandar'       => 'required',
            'business_negeri'       => 'required',
        ];
        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            //Update MSContractor
            $update_contractor = Contractor::where('CONo', $user->USCode)->first();
            $update_contractor->COName             = $request->nama_syarikat;
            $update_contractor->COCompNo           = $request->ssm_no;
            $update_contractor->COBusinessNo       = $request->ssm_no_new;
            $update_contractor->COTaxNo            = $request->no_cukai;
            $update_contractor->CORegAddr          = $request->alamat1;
            //        $update_contractor->CORegAddr2           = $request->alamat2;
            $update_contractor->CORegPostcode      = $request->poskod;
            $update_contractor->CORegCity          = $request->bandar;
            $update_contractor->COReg_StateCode    = $request->negeri;
            $update_contractor->COBusAddr          = $request->business_alamat1;
            //        $update_contractor->COBusAddr2           = $request->business_alamat2;
            $update_contractor->COBusPostcode      = $request->business_poskod;
            $update_contractor->COBusCity          = $request->business_bandar;
            $update_contractor->COBus_StateCode    = $request->business_negeri;
            $update_contractor->COPhone            = $request->hp_no;
            $update_contractor->COOfficePhone      = $request->office_no;
            $update_contractor->COFax             	= $request->fax_no;
//            $update_contractor->COEmail            = $request->email;
//            $update_contractor->COPICName          = $request->contact_name;
//            $update_contractor->COPICPosition      = $request->contact_jawatan;
//        $update_contractor->COPICICNo          = $request->email;
//            $update_contractor->COPICPhone         = $request->contact_hp_no;
//            $update_contractor->COPICName2         = $request->contact_name2;
//            $update_contractor->COPICPosition2     = $request->contact_jawatan2;
//        $update_contractor->COPICICNo2         = $request->email;
//            $update_contractor->COPICPhone2        = $request->contact_hp_no2;
//        $update_contractor->COCheckSSM         = $request->email;
//        $update_contractor->COCheckDREAMS      = $request->email;
//        $update_contractor->COCheckSPJ         = $request->email;
//        $update_contractor->COCheckCTOS        = $request->email;
//        $update_contractor->COCheckCIDB        = $request->email;
//        $update_contractor->COCheckDBKL        = $request->email;
//        $update_contractor->COScore            = $request->email;
//        $update_contractor->COTemporary        = $request->email;
            $update_contractor->COActive           = '1';
            $update_contractor->COMB               = $user->USCode;
            $update_contractor->save();

            //Update MSUser
            $update_user = User::where('USCode', $user->USCode)->first();
            $update_user->USName       = $request->nama_syarikat;
            $update_user->USMB         = $user->USCode;
            $update_user->save();

            $update_certApp = CertApp::where('CA_CONo', $update_contractor->CONo)->where('CAResult', null)->latest()->first();
            $update_certApp->CA_CONo = $user->USCode;
            $update_certApp->CA_CASCode = 'PAY';
            $update_certApp->CA_CAPCode = 'PAYMENT';
            $update_certApp->CAMB = $user->USCode;
            $update_certApp->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.index'),
                'message' => 'Maklumat syarikat telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat syarikat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }


    public function permohonanSijil(){

        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::where('CA_CONo', $contractor->CONo)->latest()->first();

		$webSetting = WebSetting::first();
		$certFee = $webSetting->NewCertFee;
		$taxFee = $certFee * $webSetting->TaxPercent /100;
		$totalFee = $taxFee + $certFee;

        return view('publicUser.dashboard.permohonanSijil',
            compact('contractor', 'certApp','certFee','taxFee','totalFee')
        );
    }

    public function resultpermohonanSijil($id){

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $contractor = Contractor::where('CONo', $user->USCode)->first();
            $certApp = CertApp::where('CA_CONo', $contractor->CONo)->where('CAResult', null)->latest()->first();

            if($id == 1){
                $CA_CASCode = 'RESUME';
                $CA_CAPCode = 'MOF';

                $autoNumber = new AutoNumber();
                $CACertNo = $autoNumber->generateCertAppNo();

                $certApp->CACertNo      = $CACertNo;
                $certApp->CAResultDate  = Carbon::now();
                $certApp->CAResultDate  = Carbon::now()->addMonth();
            }else{
                $CA_CASCode = 'REJECTED';
                $CA_CAPCode = 'RESULT';
                $CAResult   = 'REJECTED';
            }

            $certApp->CA_CASCode    = $CA_CASCode;
            $certApp->CA_CAPCode    = $CA_CAPCode;
            $certApp->CAResult      = $CAResult;
            $certApp->CAResultDate  = Carbon::now();
            $certApp->save();

            DB::commit();

            return redirect()->route('publicUser.index');

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat syarikat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function updatePermohonanSijil(Request $request){

		$user = Auth::user();
		$contractor = Contractor::where('CONo', $user->USCode)->first();
		$certApp = CertApp::where('CA_CONo', $contractor->CONo)->where('CAResult', null)->latest()->first();
		if ($certApp == null){
			return response()->json([
				'status'  => 'failed',
				'message' => 'Certificate Application not found!'
			]);
		}

		$webSetting = WebSetting::first();
		$certFee = $webSetting->NewCertFee;
		$taxFee = $certFee * $webSetting->TaxPercent /100;
		$totalFee = $taxFee + $certFee;

        try{
            DB::beginTransaction();

            //CHANGE TO FUNCTION AFTER PAYMENT GATEWAY
            $autoNumber = new AutoNumber();
            $PLNo = $autoNumber->generatePaymentLogNo();

            $paymentLog = new PaymentLog();
            $paymentLog->PLNo       	= $PLNo;
            $paymentLog->PL_CONo    	= $contractor->CONo;
            $paymentLog->PL_CANo    	= $certApp->CANo;
            $paymentLog->PLDesc     	= 'Bayaran Sijil';
			$paymentLog->PL_PSCode		= '00';
			$paymentLog->PLPaymentFee	= $totalFee;
			$paymentLog->PL_CANo		= $certApp->CANo;
			$paymentLog->PLCB			= $user->USCode;
            $paymentLog->PLActive   	= 1;
            $paymentLog->save();

            $certApp->CAFee         = $certFee;
            $certApp->CATaxFee      = $taxFee;
            $certApp->CATotalFee    = $totalFee;

            $certApp->CA_CASCode    = 'PAID';
            $certApp->CA_CAPCode    = 'RESULT';
            $certApp->CA_PLNo       = $PLNo;
            $certApp->save();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat syarikat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

		$fileLocation = Storage::disk('local')->path('rev_mon_private_key.pem');

		$rm = new RevenueMonster([
		  'clientId' => '1638183141441762397',
		  'clientSecret' => 'xXHNRLvwqWlTTBKtzJEJtFXfbXriyBWv',
		  'privateKey' => file_get_contents($fileLocation),
		  'version' => 'stable',
		  'isSandbox' => false,
		]);

		$redirectUrl = route('publicUser.dashboard.permohonanSijil.statusPembayaran');
		$notifyUrl =  Config('app.url').'/api/paymentStatus';

		$title = 'Bayaran Sijil Syarikat';
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
			$wp->storeId 				= "1637227950434469890";
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

            return response()->json([
				'error'  => '1',
				'statusCode' => $e->getCode(),
				'errorCode' => $e->getErrorCode(),
				'message' => $e->getMessage(),
            ], 400);
		} catch(ValidationException $e) {
            return response()->json([
				'error'  => '1',
				'message' => $e->getMessage(),
            ], 400);
		} catch(Exception $e) {
		  	return response()->json([
				'error'  => '1',
				'message' => $e->getMessage(),
            ], 400);
		}

		return response()->json([
			'success' => '1',
			'redirect' => $checkOutURL,
		]);
    }

	Public function updatePayment(Request $request){

		$status 		= $request->status;
		$paymentLogNo 	= $request->orderId;

		// Retrieve Web Payment Record
		$paymentLog = PaymentLog::Where('PLNo',$paymentLogNo)->First();
		if ($paymentLog == null) {
			return view('publicUser.dashboard.statusPembayaran',compact('certApp','data'));
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

			$paymentLog->PL_PSCode	= $paymentStatusCode;
			$paymentLog->PLMD		= Carbon::now();
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

		return view('publicUser.dashboard.statusPembayaran',compact('certApp','data'));
	}

    public function statusPembayaran(){

        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::where('CA_CONo', $contractor->CONo)->where('CAResult', null)->latest()->first();

        return view('publicUser.dashboard.statusPembayaran',
            compact('certApp')
        );
    }
}
