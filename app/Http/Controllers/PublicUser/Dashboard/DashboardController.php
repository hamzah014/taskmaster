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
use Exception;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\FileAttach;
use App\Models\Staff;
use App\Models\WebSetting;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Session;
use Illuminate\Support\Facades\Config;
use Mail;


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

        $contractorCode = $contractor->CONo;

        $icType = 'RG-IC';
        $frType = 'RG-FR';
        $f9Type = 'RG-FORM9';

        $fileF9 = optional(FileAttach::where('FA_USCode',$contractorCode)
                ->where('FARefNo',$contractorCode)
                ->where('FAFileType',$f9Type)
                ->first());

        if ($contractor->COReg_StateCode == 'R'){//PERLIS
            $contractor->COReg_StateCode = 'MY-09';
        }
        elseif ($contractor->COReg_StateCode == 'K'){//KEDAH
            $contractor->COReg_StateCode = 'MY-02';
        }
        elseif ($contractor->COReg_StateCode == 'P'){//PP
            $contractor->COReg_StateCode = 'MY-07';
        }
        elseif ($contractor->COReg_StateCode == 'D'){//KEL
            $contractor->COReg_StateCode = 'MY-03';
        }
        elseif ($contractor->COReg_StateCode == 'T'){//TER
            $contractor->COReg_StateCode = 'MY-11';
        }
        elseif ($contractor->COReg_StateCode == 'A'){//PERAK
            $contractor->COReg_StateCode = 'MY-08';
        }
        elseif ($contractor->COReg_StateCode == 'B'){//SEL
            $contractor->COReg_StateCode = 'MY-10';
        }
        elseif ($contractor->COReg_StateCode == 'C'){//PAH
            $contractor->COReg_StateCode = 'MY-06';
        }
        elseif ($contractor->COReg_StateCode == 'N'){//NEG9
            $contractor->COReg_StateCode = 'MY-05';
        }
        elseif ($contractor->COReg_StateCode == 'M'){//MEL
            $contractor->COReg_StateCode = 'MY-04';
        }
        elseif ($contractor->COReg_StateCode == 'J'){//JHR
            $contractor->COReg_StateCode = 'MY-01';
        }
        elseif ($contractor->COReg_StateCode == 'X'){//SAB
            $contractor->COReg_StateCode = 'MY-12';
        }
        elseif ($contractor->COReg_StateCode == 'Y'){//SAR
            $contractor->COReg_StateCode = 'MY-13';
        }
        elseif ($contractor->COReg_StateCode == 'L'){//LAB
            $contractor->COReg_StateCode = 'MY-15';
        }
        elseif ($contractor->COReg_StateCode == 'W'){//WP
            $contractor->COReg_StateCode = 'MY-14';
        }
        elseif ($contractor->COReg_StateCode == 'U'){//PUT
            $contractor->COReg_StateCode = 'MY-16';
        }
        // elseif ($contractor->COReg_StateCode == 'Q'){//SG
        //     $contractor->COReg_StateCode = '';
        // }


        //BusCode
        if ($contractor->COBus_StateCode == 'R'){//PERLIS
            $contractor->COBus_StateCode = 'MY-09';
        }
        elseif ($contractor->COBus_StateCode == 'K'){//KEDAH
            $contractor->COBus_StateCode = 'MY-02';
        }
        elseif ($contractor->COBus_StateCode == 'P'){//PP
            $contractor->COBus_StateCode = 'MY-07';
        }
        elseif ($contractor->COBus_StateCode == 'D'){//KEL
            $contractor->COBus_StateCode = 'MY-03';
        }
        elseif ($contractor->COBus_StateCode == 'T'){//TER
            $contractor->COBus_StateCode = 'MY-11';
        }
        elseif ($contractor->COBus_StateCode == 'A'){//PERAK
            $contractor->COBus_StateCode = 'MY-08';
        }
        elseif ($contractor->COBus_StateCode == 'B'){//SEL
            $contractor->COBus_StateCode = 'MY-10';
        }
        elseif ($contractor->COBus_StateCode == 'C'){//PAH
            $contractor->COBus_StateCode = 'MY-06';
        }
        elseif ($contractor->COBus_StateCode == 'N'){//NEG9
            $contractor->COBus_StateCode = 'MY-05';
        }
        elseif ($contractor->COBus_StateCode == 'M'){//MEL
            $contractor->COBus_StateCode = 'MY-04';
        }
        elseif ($contractor->COBus_StateCode == 'J'){//JHR
            $contractor->COBus_StateCode = 'MY-01';
        }
        elseif ($contractor->COBus_StateCode == 'X'){//SAB
            $contractor->COBus_StateCode = 'MY-12';
        }
        elseif ($contractor->COBus_StateCode == 'Y'){//SAR
            $contractor->COBus_StateCode = 'MY-13';
        }
        elseif ($contractor->COBus_StateCode == 'L'){//LAB
            $contractor->COBus_StateCode = 'MY-15';
        }
        elseif ($contractor->COBus_StateCode == 'W'){//WP
            $contractor->COBus_StateCode = 'MY-14';
        }
        elseif ($contractor->COBus_StateCode == 'U'){//PUT
            $contractor->COBus_StateCode = 'MY-16';
        }
        // elseif ($contractor->COBus_StateCode == 'Q'){//SG
        //     $contractor->COBus_StateCode = '';
        // }

        $contractor['fileF9'] = $fileF9->FAGuidID ?? null;

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
        //    $update_contractor->COEmail            = $request->email;
           $update_contractor->COPICName          = $request->contact_name;
           $update_contractor->COPICPosition      = $request->contact_jawatan;
            $update_contractor->COPICEmail          = $request->contact_email;
           $update_contractor->COPICPhone         = $request->contact_hp_no;
           $update_contractor->COPICName2         = $request->contact_name2;
           $update_contractor->COPICPosition2     = $request->contact_jawatan2;
            $update_contractor->COPICEmail2        = $request->contact_email2;
           $update_contractor->COPICPhone2        = $request->contact_hp_no2;
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

            $autoNumber = new AutoNumber();
            $foundMatch = false;
            $foundMatch2 = false;

            $staffs = Staff::where('COST_CONo', $update_contractor->CONo)->get();

            foreach ($staffs as $staff) {
                if ($request->contact_email == $staff->COSTEmail || $request->contact_hp_no == $staff->COSTPhone) {

                    $staff->COSTName = $request->contact_name;
                    $staff->COSTPhone = $request->contact_hp_no;
                    $staff->COSTPosition = $request->contact_jawatan;
                    $staff->save();

                    $foundMatch = true;
                }

                if($request->contact_email2 == $staff->COSTEmail || $request->contact_hp_no2 == $staff->COSTPhone) {

                    $staff->COSTName = $request->contact_name2;
                    $staff->COSTPhone = $request->contact_hp_no2;
                    $staff->COSTPosition = $request->contact_jawatan2;
                    $staff->save();

                    $foundMatch2 = true;

                }
            }

            if (!$foundMatch) {
                $staffCode = $autoNumber->generateStaffCode();
                $newstaff = new Staff();
                $newstaff->COSTNo = $staffCode;
                $newstaff->COST_CONo = $update_contractor->CONo;
                $newstaff->COSTName = $request->contact_name;
                $newstaff->COSTPosition = $request->contact_jawatan;
                $newstaff->COSTPhone = $request->contact_hp_no;
                $newstaff->COSTEmail = $request->contact_email;
                $newstaff->save();

            }

            if (!$foundMatch2) {
                $staffCode2 = $autoNumber->generateStaffCode();
                $newstaff2 = new Staff();
                $newstaff2->COSTNo = $staffCode2;
                $newstaff2->COST_CONo = $update_contractor->CONo;
                $newstaff2->COSTName = $request->contact_name2;
                $newstaff2->COSTPosition = $request->contact_jawatan2;
                $newstaff2->COSTPhone = $request->contact_hp_no2;
                $newstaff2->COSTEmail = $request->contact_email2;
                $newstaff2->save();

            }

            if ($request->hasFile('dok_form9')) {

                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

                $dok_FAFileType		    = "RG-FORM9";

                $file = $request->file('dok_form9');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = FileAttach::where('FA_USCode',$user->USCode)
                                        ->where('FARefNo',$user->USCode)
                                        ->where('FAFileType',$fileCode)
                                        ->first();
                if ($fileAttach == null){
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB 		= $user->USCode;
                    $fileAttach->FAFileType 	= $fileCode;
                }else{

                    $filename   = $fileAttach->FAFileName;
                    $fileExt    = $fileAttach->FAFileExtension ;

                    Storage::disk('fileStorage')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);

                }
                $fileAttach->FARefNo     	    = $user->USCode;
                $fileAttach->FA_USCode     	    = $user->USCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = $user->USCode;
                $fileAttach->save();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.dashboard.permohonanSijil'),
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

        $certAppNo = Session::get('certAppNo');

        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::where('CANo', $certAppNo)->first();

        $webSetting = WebSetting::first();
        if ($certApp->CA_CATCode == 'NEW'){
            $certFee = (float) $webSetting->NewCertFee;
        }else{
            $certFee = (float) $webSetting->RenewCertFee;
        }
        $serviceFee = (float) $webSetting->ServiceFee;
        $subTotalFee = $certFee + $serviceFee;
        $taxFee = $subTotalFee * $webSetting->TaxPercent;
        $totalFee = $taxFee + $subTotalFee;

        return view('publicUser.dashboard.permohonanSijil',
            compact('contractor', 'certApp','certFee','serviceFee','subTotalFee','taxFee','totalFee')
        );
    }

// new code

    public function updatePermohonanSijil(Request $request){

        $certAppNo = Session::get('certAppNo');
        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::where('CANo', $certAppNo)->where('CAResult', null)->latest()->first();

        if ($certApp == null){
            return response()->json([
                'status'  => 'failed',
                'message' => 'Certificate Application not found!'
            ]);
        }

        $webSetting = WebSetting::first();
        if ($certApp->CA_CATCode == 'NEW'){
            $certFee = (float) $webSetting->NewCertFee;
        }else{
            $certFee = (float) $webSetting->RenewCertFee;
        }
        $serviceFee = (float) $webSetting->ServiceFee;
        $subTotalFee = $certFee + $serviceFee;
        $taxFee = $subTotalFee * $webSetting->TaxPercent /100;
        $totalFee = $taxFee + $subTotalFee;

        try{
            DB::beginTransaction();

            //CHANGE TO FUNCTION AFTER PAYMENT GATEWAY
            $autoNumber = new AutoNumber();
            $PLNo = $autoNumber->generatePaymentLogNo();

            $paymentLog = new PaymentLog();
            $paymentLog->PLNo       	= $PLNo;
            $paymentLog->PL_CONo    	= $contractor->CONo;
            $paymentLog->PLRefNo    	= $certApp->CANo;
			$paymentLog->PL_PLTCode		= 'CERTAPP';
            $paymentLog->PLDesc     	= 'Bayaran Sijil';
            $paymentLog->PL_PSCode		= '00';
            $paymentLog->PLPaymentFee	= $totalFee;
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

//		$fileLocation = Storage::disk('local')->path('rev_mon_private_key.pem');
//
//        $rm = new RevenueMonster([
//            'clientId' => config::get('app.rm_clientid'),
//            'clientSecret' => config::get('app.rm_clientsecret'),
//            'privateKey' => file_get_contents($fileLocation),
//            'version' => 'stable',
//            'isSandbox' => false,
//        ]);
//
//        $redirectUrl = route('publicUser.dashboard.permohonanSijil.statusPembayaran');
//        $notifyUrl =  Config('app.url').'/api/paymentStatus';
//
//        $title = 'Bayaran Sijil Syarikat';
//        $amount = 1;
//        $detail = 'Bayaran Sijil';
//        $checkOutID = '';
//        $checkOutURL= '';
//
//        // create Web payment
//        try {
//            $wp = new WebPayment;
//            $wp->order->id 				= $paymentLog->PLNo;
//            $wp->order->title 			= $title;
//            $wp->order->currencyType 	= 'MYR';
//            $wp->order->amount 			= $amount;
//            $wp->order->detail 			= $detail;
//            $wp->method 				= ["ALIPAY_CN","TNG_MY","SHOPEEPAY_MY","BOOST_MY","GRABPAY_MY","PRESTO_MY"];
//            $wp->type 					= "WEB_PAYMENT";
//            $wp->order->additionalData 	= $detail;
//			$wp->storeId 				= config::get('app.rm_storeid');
//            $wp->redirectUrl 			= $redirectUrl;
//            $wp->notifyUrl 				= $notifyUrl ;
//            $wp->layoutVersion 			= 'v3';
//
//            $response = $rm->payment->createWebPayment($wp);
//            $checkOutID= $response->checkoutId; // Checkout ID
//            $checkOutURL= $response->url; // Payment gateway url
//
//            $paymentLog->PLCheckOutID = $checkOutID;
//            $paymentLog->PLPaymentLink = $checkOutURL ;
//            $paymentLog->Save();
//
//        } catch(ApiException $e) {
//
//            return response()->json([
//                'error'  => '1',
//                'statusCode' => $e->getCode(),
//                'errorCode' => $e->getErrorCode(),
//                'message' => $e->getMessage(),
//            ], 400);
//        } catch(ValidationException $e) {
//            return response()->json([
//                'error'  => '1',
//                'message' => $e->getMessage(),
//            ], 400);
//        } catch(Exception $e) {
//            return response()->json([
//                'error'  => '1',
//                'message' => $e->getMessage(),
//            ], 400);
//        }
//
//        //SEND EMAIL FOR SUCCESS PAYMENT SIJIL AKAUN
        $tokenResult = $contractor->createToken('Personal Access Token');
        $token = $tokenResult->token;

        $emailLog = new EmailLog();
        $emailLog->ELCB 	= $contractor->CONo;
        $emailLog->ELType 	= 'Success Set Password';
        $emailLog->ELSentTo =  $contractor->COEmail;

        $emailData = array(
            'id' => $contractor->COID,
            'name'  => $contractor->COName ?? '',
            'email' => $contractor->COEmail,
            'domain' => config::get('app.url'),
            'token' => $token->id,
            'paymentLogNo' => $PLNo,
            'paymentAmount' =>number_format($amount?? 0,2, '.', ',') ,
            'now' => Carbon::now()->format('j F Y'),
        );

        try {
            Mail::send(['html' => 'email.certPaymentSuccessPublic'], $emailData, function($message) use ($emailData) {
                $message->to($emailData['email'] ,$emailData['name'])->subject('Status Pembayaran Sijil Akaun');
            });

            $emailLog->ELMessage = 'Success';
            $emailLog->ELSentStatus = 1;
        } catch (\Exception $e) {
            $emailLog->ELMessage = $e->getMessage();
            $emailLog->ELSentStatus = 2;
        }

        $emailLog->save();

        return response()->json([
            'success' => '1',
//            'redirect' => $checkOutURL,
            'redirect' => route('publicUser.dashboard.permohonanSijil.statusPembayaran', ['orderId'=>$PLNo, 'status' => 'SUCCESS']),
        ]);
    }

//old code

//    public function updatePermohonanSijil(Request $request){
//
//        try{
//            DB::beginTransaction();
//
//            $user = Auth::user();
//            $contractor = Contractor::where('CONo', $user->USCode)->first();
//            $certApp = CertApp::where('CA_CONo', $contractor->CONo)->where('CAResult', null)->latest()->first();
//
//            //CHANGE TO FUNCTION AFTER PAYMENT GATEWAY
//            $autoNumber = new AutoNumber();
//            $PLNo = $autoNumber->generatePaymentLogNo();
//
//            $paymentLog = new PaymentLog();
//            $paymentLog->PLNo       = $PLNo;
//            $paymentLog->PL_CONo    = $contractor->CONo;
//            $paymentLog->PLRefNo    = $certApp->CANo;
//            $paymentLog->PLDesc     = 'Bayaran Sijil';
//            $paymentLog->PLActive   = 1;
//            $paymentLog->save();
//
//
//
//            $certApp->CA_CASCode    = 'PAID';
//            $certApp->CA_CAPCode    = 'RESULT';
//            $certApp->CAFee         = 100;
//            $certApp->CATaxFee      = 6;
//            $certApp->CATotalFee    = 106;
//            $certApp->CA_PLNo       = $PLNo;
//            $certApp->save();
//
//
//
//            DB::commit();
//
//            return response()->json([
//                'success' => '1',
//                'redirect' => route('publicUser.dashboard.permohonanSijil.statusPembayaran'),
//            ]);
//
//        }catch (\Throwable $e) {
//            DB::rollback();
//
//            Log::info('ERROR', ['$e' => $e]);
//
//            return response()->json([
//                'error' => '1',
//                'message' => 'Maklumat syarikat tidak berjaya dikemaskini!'.$e->getMessage()
//            ], 400);
//        }
//    }

    Public function updatePayment(Request $request){

        $status 		= $request->status;
        $paymentLogNo 	= $request->orderId;
        $certAppNo = Session::get('certAppNo');

        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::where('CANo', $certAppNo)->where('CAResult', null)->first();

        $data = [];

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

        $certAppNo = Session::get('certAppNo');
        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::where('CANo', $certAppNo)->where('CAResult', null)->latest()->first();

        return view('publicUser.dashboard.statusPembayaran',
            compact('certApp')
        );
    }

    public function statusPermohonan(){

        $certAppNo = Session::get('certAppNo');
        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::where('CANo', $certAppNo)->first();
//        $certApp->CA_CASCode    = 'RESUME';
//        $certApp->CA_CAPCode      = 'MOF';
//        $certApp->save();

        if($certApp->CAResult == null){
            $status = 'PROSES';
        }
        else if($certApp->CAResult == 'APPROVED'){
            $status = 'APPROVED';
        }
        else if($certApp->CAResult == 'REJECTED'){
            $status = 'REJECTED';
        }
        else{
            $status = 'NONE';
        }

        return view('publicUser.dashboard.statusPermohonan',
            compact('status')
        );
    }

    public function approvedStatusPermohonan(){

        try {
            $certAppNo = Session::get('certAppNo');

            DB::beginTransaction();

            $user = Auth::user();
            $contractor = Contractor::where('CONo', $user->USCode)->first();
            $certApp = CertApp::where('CA_CONo', $certAppNo)->first();

            $certApp->CA_CASCode    = 'RESUME';
            $certApp->CA_CAPCode      = 'MOF';
            $certApp->save();

            DB::commit();


            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.index'),
                'message' => ''
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Approved status permohonan tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateMOF(Request $request){

        $certAppNo = Session::get('certAppNo');
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $contractor = Contractor::where('CONo', $user->USCode)->first();
            $certApp = CertApp::where('CANo', $certAppNo)->first();

            $certApp->CA_CASCode    = 'RESUME';
            $certApp->CA_CAPCode    = 'CIDB';
            $certApp->save();

            DB::commit();


            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.ppk.index', ['certAppNo' =>$certAppNo]),
                'message' => ''
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'MOF tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateCasCode(Request $request){

        $certAppNo = Session::get('certAppNo');
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $contractor = Contractor::where('CONo', $user->USCode)->first();
            $certApp = CertApp::where('CANo', $certAppNo)->first();

            $certApp->CA_CASCode    = 'RESUME';
            $certApp->CA_CAPCode    = 'MOF';
            $certApp->save();

            DB::commit();


            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.kkm.index', ['certAppNo' =>$certAppNo]),
                'message' => ''
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'MOF tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function updatePPK(Request $request){

        try {
            $certAppNo = Session::get('certAppNo');

            DB::beginTransaction();

            $user = Auth::user();
            $certApp = CertApp::where('CANo', $certAppNo)->first();

            $certApp->CA_CASCode    = 'RESUME';
            $certApp->CA_CAPCode    = 'STAFF';
            $certApp->save();

            DB::commit();


            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.kakitangan.index'),
                'message' => ''
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'PPK tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateKakitangan(Request $request){

        try {
            $certAppNo = Session::get('certAppNo');

            DB::beginTransaction();

            $certApp = CertApp::where('CANo', $certAppNo)->first();

            if($certApp->CA_PLNo != null){
                $certApp->CA_CASCode    = 'PAID';
                $certApp->CA_CAPCode    = 'RESULT';
            }
            else{
                $certApp->CA_CASCode    = 'PAY';
                $certApp->CA_CAPCode    = 'PAYMENT';
            }

            $certApp->save();

            DB::commit();


            return response()->json([
                'success' => '1',
//                'redirect' => route('publicUser.dashboard.paparSijil'),
                'redirect' => route('publicUser.dashboard.statusPermohonan'),
                'message' => ''
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Kakitangan tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateReturn(Request $request){

        try {
            $certAppNo = Session::get('certAppNo');

            DB::beginTransaction();

            $user = Auth::user();
            $certApp = CertApp::where('CANo', $certAppNo)->first();

            $certApp->CA_CASCode    = 'RESUME';
            $certApp->CA_CAPCode    = 'MOF';
            $certApp->CAResult     = null;
            $certApp->save();

            DB::commit();


            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.kkm.index'),
                'message' => ''
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'PPK tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateApprove(Request $request){

        try {
            $certAppNo = Session::get('certAppNo');

            DB::beginTransaction();

            $user = Auth::user();
            $certApp = CertApp::where('CANo', $certAppNo)->first();

            $certApp->CA_CASCode    = 'CERT';
            $certApp->CA_CAPCode    = 'CERT';
            $certApp->save();

            DB::commit();


            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.dashboard.paparSijil'),
                'message' => ''
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'PPK tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }


    public function paparSijil(){

        $certAppNo = Session::get('certAppNo');
        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $certApp = CertApp::where('CANo', $certAppNo)->first();

        $timestamp = strtotime($certApp->CAResultDate);
        $certApp->CAResultDate = date('d/m/Y H:i', $timestamp);

        $kod_bidang = $contractor->contractorMOF;
        //dd($contractor, $kod_bidang);

        return view('publicUser.dashboard.paparSijil',
            compact('certApp', 'contractor' , 'kod_bidang')
        );
    }

    public function updateComplete(){

        try {
            $certAppNo = Session::get('certAppNo');
            DB::beginTransaction();

            $user = Auth::user();
            $contractor = Contractor::where('CONo', $user->USCode)->first();
            $certApp = CertApp::where('CANo', $certAppNo)->first();

            $contractor->COCompleteEntry    = 1;
            $contractor->save();

            DB::commit();


            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.index'),
                'message' => ''
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Kakitangan tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function pembaharuan(){

        try {
            $user = Auth::user();
            $contractor = Contractor::where('CONo', $user->USCode)->first();

            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $CANo = $autoNumber->generateCertAppNo();

            $create_CA = new CertApp();
            $create_CA->CANo = $CANo;
            $create_CA->CA_CONo = $user->USCode;
            $create_CA->CA_CASCode = 'NEW';
            $create_CA->CA_CATCode = 'RENEW';
            $create_CA->CA_CAPCode = 'COMP';
            $create_CA->CAActive = 1;
            $create_CA->CACB = $user->USCode;
            $create_CA->save();

            $contractor->COCompleteEntry    = 0;
            $contractor->save();

            DB::commit();

            Session::put('certAppNo',$CANo);

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.dashboard.daftarSyarikat'),
                'message' => ''
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Kakitangan tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function permohonanSemula(){
        $certAppNo = Session::get('certAppNo');

        try {
            $user = Auth::user();
            $certApp = CertApp::where('CANo', $certAppNo)->first();

            $CA_CATCode = $certApp->CA_CATCode;

            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $CANo = $autoNumber->generateCertAppNo();

            $create_CA = new CertApp();
            $create_CA->CANo        = $CANo;
            $create_CA->CA_CONo     = $user->USCode;
            $create_CA->CA_CASCode  = 'NEW';
            $create_CA->CA_CATCode  = $CA_CATCode;
            $create_CA->CA_CAPCode  = 'COMP';
            $create_CA->CAActive    = 1;
            $create_CA->CACB = $user->USCode;
            $create_CA->save();

            DB::commit();

            Session::put('certAppNo',$CANo);

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.dashboard.daftarSyarikat'),
                'message' => ''
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Kakitangan tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function resultpermohonanSijil($id, $result){

        $certAppNo = Session::get('certAppNo');
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $contractor = Contractor::where('CONo', $user->USCode)->first();
            $certApp = CertApp::where('CANo', $id)->first();

            if($result == 1){
                $CA_CASCode = 'APPROVED';
//                $CA_CAPCode = 'MOF';
                $CAResult   = 'APPROVED';

                $autoNumber = new AutoNumber();
                $CACertNo = $autoNumber->generateCertNo();

                $certApp->CACertNo      = $CACertNo;
                $certApp->CACertStartDate  = Carbon::now();
                $certApp->CACertEndDate  = Carbon::now()->addYear();
            }else{
                $CA_CASCode = 'REJECTED';
//                $CA_CAPCode = 'RESULT';
                $CAResult   = 'REJECTED';
            }

            $certApp->CA_CASCode    = $CA_CASCode;
//            $certApp->CA_CAPCode    = $CA_CAPCode;
            $certApp->CAResult      = $CAResult;
            $certApp->CAResultDate  = Carbon::now();
            $certApp->save();

            DB::commit();

            return redirect()->route('osc.certApp.index');

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Result permohonan sijil tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }



}
