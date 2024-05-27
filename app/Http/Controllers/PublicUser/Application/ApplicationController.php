<?php

namespace App\Http\Controllers\PublicUser\Application;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\SSMCompany;
use App\Models\Contractor;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\TenderApplicationAuthSign;
use App\Models\TenderDetail;
use App\Models\TenderProposal;
use App\Models\TenderProposalDetail;
use App\Models\TenderProposalSpec;
use App\Models\TenderSpec;
use App\Models\WebSetting;
use App\Models\AutoNumber;
use App\Models\BlacklistProject;
use App\Models\FileAttach;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\SUbmission;
use App\Models\PaymentLog;
use App\Models\TenderProposalBLP;
use App\Models\TenderProposalDBKL;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator,Redirect,Response,File;
use App\Services\DropdownService;

use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use Session;
use Mail;

use RevenueMonster\SDK\Exceptions\ApiException;
use RevenueMonster\SDK\Exceptions\ValidationException;
use RevenueMonster\SDK\RevenueMonster;
use RevenueMonster\SDK\Request\WebPayment;
Use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }


    public function index(){
        return view('publicUser.application.index');
    }

    public function create($id){
        $tenderApplicationNo = $id;

        $tenderApplication = TenderApplication::where('TANo', $tenderApplicationNo)->first();

        $bankStatementBal = array(
            $tenderApplication->TABankStmtBalAmt1,
            $tenderApplication->TABankStmtBalAmt2,
            $tenderApplication->TABankStmtBalAmt3
        );

        $bank_name = $this->dropdownService->bank_name();
        $routeDownloadBorangKebenaran = "#";
        $routeDownloadBorangKebenaranBank = "#";
        $routeBankStatement = "#";
        $routeBorangKebenaran = "#";
        $routeBorangKebenaranBank = "#";

        $fileAttachDownloadBAF = FileAttach::where('FAFileType','PT-BAF')->first();
        if(!empty($fileAttachDownloadBAF)){
            $fileguid   = $fileAttachDownloadBAF->FAGuidID;
            $routeDownloadBorangKebenaranBank = route('file.view', ['fileGuid' => $fileguid]);
        }

        $fileAttachDownloadAF = FileAttach::where('FAFileType','PT-AF')->first();
        if(!empty($fileAttachDownloadAF)){
            $fileguid   = $fileAttachDownloadAF->FAGuidID;
            $routeDownloadBorangKebenaran = route('file.view', ['fileGuid' => $fileguid]);
        }

        if(!empty($tenderApplication->fileAttachBS)){
            $fileguid = $tenderApplication->fileAttachBS->FAGuidID;
            $routeBankStatement = route('file.view', ['fileGuid' => $fileguid]);
        }

        if(!empty($tenderApplication->fileAttachBAF)){
            $fileguid = $tenderApplication->fileAttachBAF->FAGuidID;
            $routeBorangKebenaranBank = route('file.view', ['fileGuid' => $fileguid]);
        }

        if(!empty($tenderApplication->fileAttachAF)){
            $fileguid = $tenderApplication->fileAttachAF->FAGuidID;
            $routeBorangKebenaran = route('file.view', ['fileGuid' => $fileguid]);
        }

        $tender = $tenderApplication->tender;
        $statementYear = $tender->TDBankStmtYear ?? Carbon::now();

        $bankDateArray = array();

        for($x = 0; $x < 3; $x++){

            $yourDate = Carbon::parse($statementYear);

            $date = $yourDate->addMonths($x)->toDateString();
            $bankDateArray[$x]['month'] = Carbon::parse($date)->format('F');
            $bankDateArray[$x]['year'] = Carbon::parse($date)->format('Y');

        }

        return view('publicUser.application.create',
            compact(
            'tenderApplication', 'tenderApplicationNo','bankDateArray','bankStatementBal',
            'bank_name','routeDownloadBorangKebenaran','routeDownloadBorangKebenaranBank','routeBankStatement','routeBorangKebenaran','routeBorangKebenaranBank'
            )
        );
    }

    public function storeSM(Request $request, $id){
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $wakil_id = $request->wakil_id;
            $wakil_ic_no = $request->wakil_ic_no;
            $wakil_nama = $request->wakil_nama;

            $tenderApp = TenderApplication::where('TANo', $request->taNo)->first();
            $tenderApp->TA_BankCode = $request->bank_name;
            $tenderApp->TABankAccNo = $request->bank_no;
            $tenderApp->save();

            $old_authorize_signs = TenderApplicationAuthSign::where('TAAS_TANo', $request->taNo)->get();

            foreach ($old_authorize_signs as $delete_authorize_sign){
                $authorize_sign_delete = TenderApplicationAuthSign::where('TAASID', $delete_authorize_sign->TAASID)->first();
                $authorize_sign_delete->delete();
            }

            foreach ($wakil_id as $key => $wakilId){
                // if($wakilId == 'new'){
                    $authorize_sign = new TenderApplicationAuthSign();
                // }
                // else{
                //     $authorize_sign = TenderApplicationAuthSign::where('TAASID', $wakilId)->first();
                // }
                $authorize_sign->TAAS_TANo = $request->taNo;
                $authorize_sign->TAASName = $wakil_nama[$key] ?? '';
                $authorize_sign->TAASIC = $wakil_ic_no[$key] ?? '';
                $authorize_sign->TAASCB = $user->USCode;
                $authorize_sign->save();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'data' 	 => $id
            ], 200);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Register account was failed!'.$e->getMessage()
            ], 400);
        }

    }

    public function storeSM2(Request $request, $id){

        $user = Auth::user();

        try {
            DB::beginTransaction();

            $wakil_id = $request->wakil_id;
            $wakil_ic_no = $request->wakil_ic_no;
            $wakil_nama = $request->wakil_nama;
            $bankBalance = $request->bankBalance;

            $tenderApp = TenderApplication::where('TANo', $request->taNo)->first();
            $tenderApp->TA_BankCode = $request->bank_name;
            $tenderApp->TABankAccNo = $request->bank_no;
            $tenderApp->TABankStmtBalAmt1 = $bankBalance[0];
            $tenderApp->TABankStmtBalAmt2 = $bankBalance[1];
            $tenderApp->TABankStmtBalAmt3 = $bankBalance[2];

            $totalBankBalance = array_sum($bankBalance) / 3;

            $tender = $tenderApp->tender;
            $contractAmt = $tender->TDContractAmt;

            $webSetting = WebSetting::first();
            $bankStatementBalRate = $webSetting->BankBalAvailablePercent;
            $bankScoreMax = $webSetting->OSCBankScoreMax;

            $minBankBal = $contractAmt * $bankStatementBalRate;

            if($totalBankBalance < $minBankBal){
                $tenderApp->TABankStmtBalScore = 0;
            }
            else{
                $tenderApp->TABankStmtBalScore = $bankScoreMax;

            }

            $tenderApp->save();

            $old_authorize_signs = TenderApplicationAuthSign::where('TAAS_TANo', $request->taNo)->get();

            foreach ($old_authorize_signs as $delete_authorize_sign){
                $authorize_sign_delete = TenderApplicationAuthSign::where('TAASID', $delete_authorize_sign->TAASID)->first();
                $authorize_sign_delete->delete();
            }

            foreach ($wakil_id as $key => $wakilId){
                $authorize_sign = new TenderApplicationAuthSign();
                $authorize_sign->TAAS_TANo = $request->taNo;
                $authorize_sign->TAASName = $wakil_nama[$key] ?? '';
                $authorize_sign->TAASIC = $wakil_ic_no[$key] ?? '';
                $authorize_sign->TAASCB = $user->USCode;
                $authorize_sign->save();
            }

            if ($request->hasFile('bankStatement')) {
                $file = $request->file('bankStatement');
                $fileType = 'TA-BS';
                $refNo = $request->taNo;

                $this->saveFile($file,$fileType,$refNo);
            }

            if($request->updateStatus == 1){
                $route = route('publicUser.application.create', [$request->taNo , 'flag' => 1]);
            }else{
                $route = route('publicUser.application.create', [$request->taNo]);
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => $route,
                'message' => 'Maklumat berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Register account was failed!'.$e->getMessage()
            ], 400);
        }

    }

    public function SubmissionDatatable(Request $request){

        $user = Auth::user();

        $query = TenderProposal::where('TP_CONo', $user->USCode)
                // ->where('SMActive', 1)
                // ->where('SMSubmit', 1)
                ->get();

        $statusSubmission = $this->dropdownService->statusSubmission();
        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('SMNo', function($row){

                // $route = route('publicUser.profil.kakitangan.edit',[$row->STNo, 'flag' => $flag]);
                $route = "#";
                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->SMNo.' </a>';

                return $result;
            })
            ->editColumn('SMCD', function($row) {

                // Create a Carbon instance from the MySQL datetime value
                $carbonDatetime = Carbon::parse($row->SMCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->editColumn('SMSubmit', function($row) use ($statusSubmission) {

                return $statusSubmission[$row->SMSubmit];

            })
            ->addColumn('action', function($row) {

                // $route = route('publicUser.profil.kakitangan.edit',[$row->STNo, 'flag' => $flag]);
                $route = "#";
                $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light btn gradient-45deg-indigo-light-blue">Edit</a>';

                return $result;


            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['SMNo','SMSubmit','SMCD','action'])
            ->make(true);
    }

    public function addCart($TDNo){

        $user = Auth::user();

        $tender = Tender::where('TDNo',$TDNo)->first();

        $tenderApp = TenderApplication::where('TA_TDNo',$TDNo)
        ->where('TA_TDANo',$tender->TD_TDANo)
        ->where('TAStatus','DRAFT')
        ->orderby('TAID','desc')
        ->first();

        if($tenderApp == null){

            $webSetting = WebSetting::first();
            $taxPercent = (float) $webSetting->TaxPercent;
            $serviceFee = (float) $webSetting->ServiceFee;

            $tender = Tender::where('TDNo',$TDNo)->first();
            $docAmt = (float) $tender->TDDocAmt;
            $totalAmt = (float) ($docAmt + $serviceFee);

            $taxAmt = (float) $totalAmt * $taxPercent / 100;
            $netTotalAmt =(float) $totalAmt + $taxAmt ;

            $autoNumber = new AutoNumber();
            $TANo = $autoNumber->generateTenderApplicationNo();

            $tenderApplication = new TenderApplication();
            $tenderApplication->TANo = $TANo;
            $tenderApplication->TA_TDNo = $TDNo;
            $tenderApplication->TA_TDANo = $tender->TD_TDANo;
            $tenderApplication->TA_CONo = $user->USCode;
            $tenderApplication->TAFee = $docAmt;
            $tenderApplication->TATaxFee = $taxAmt;
            $tenderApplication->TATotalFee = $netTotalAmt;
            $tenderApplication->TAActive = 1;
            $tenderApplication->TACB = $user->USCode;
            $tenderApplication->TAStatus = 'DRAFT';
            $tenderApplication->save();
        }else{
            $TANo = $tenderApp->TANo;
        }



        return response()->json([
            'status' => 'success',
            'redirect' => route('publicUser.application.create', $TANo),
            'message' => 'Record Successfully Saved'
        ]);

    }

    public function pay($id){

        $user = Auth::user();
        $tenderApp = TenderApplication::where('TANo',$id)->with('tender')->first();

        if($tenderApp == null){

            $autoNumber = new AutoNumber();
            $TANo = $autoNumber->generateTenderApplicationNo();
            $tenderApp = new TenderApplication();
        }else{
            $TANo = $tenderApp->TANo;
        }

        $webSetting = WebSetting::first();
        $taxPercent = (float) $webSetting->TaxPercent;
        $serviceFee = (float) $webSetting->ServiceFee;

        $tender = Tender::where('TDNo',$tenderApp->tender->TDNo)->first();

        $docAmt = isset($tender->tenderAdv) ? (float) $tender->tenderAdv->TDADocAmt : 0;
        $totalAmt = (float) ($docAmt + $serviceFee);

        $taxAmt = (float) $totalAmt * $taxPercent;
        $netTotalAmt =(float) $totalAmt + $taxAmt ;

        $tenderApp->TANo = $TANo;
        $tenderApp->TA_TDNo = $tenderApp->tender->TDNo;
        $tenderApp->TA_CONo = $user->USCode;
        $tenderApp->TAFee = $docAmt;
        $tenderApp->TATaxFee = $taxAmt;
        $tenderApp->TATotalFee = $netTotalAmt;
        $tenderApp->TAActive = 1;
        $tenderApp->TACB = $user->USCode;
        $tenderApp->TAStatus = 'DRAFT';
        $tenderApp->save();

        return view('publicUser.application.pay',
            compact('tenderApp','id','docAmt', 'serviceFee', 'totalAmt', 'taxPercent', 'taxAmt', 'netTotalAmt')
        );
    }


	Public function createPayment($TANo){
		$user = Auth::user();

		$tenderApp = TenderApplication::WHERE('TANo',$TANo)->First();
		if ($tenderApp == null){
			return response()->json([
				'status'  => 'failed',
				'message' => 'Tender Application not found!'
			]);
		}

		try {
            DB::beginTransaction();

//			$dateInMills = Carbon::now()->timestamp;
//			$paymentLogNo = 'PL'.$dateInMills;
            $autoNumber = new AutoNumber();
            $PLNo = $autoNumber->generatePaymentLogNo();

			$paymentLog = new PaymentLog();
			$paymentLog->PLNo			= $PLNo;
            $paymentLog->PL_CONo    	= $user->USCode;
			$paymentLog->PLRefNo		= $tenderApp->TANo;
			$paymentLog->PL_PLTCode		= 'TENDERAPP';
			$paymentLog->PLDesc			= 'Bayaran Dokumen';
			$paymentLog->PL_PSCode		= '00';
			$paymentLog->PLPaymentFee	= $tenderApp->TATotalFee;
			$paymentLog->PLCB			= $user->USCode;
			$paymentLog->PLCD			= Carbon::now();
            $paymentLog->PLActive   	= 1;
			$paymentLog->save();

			DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            throw $e;
        }

//		 $fileLocation = Storage::disk('local')->path('rev_mon_private_key.pem');
//
//        $rm = new RevenueMonster([
//            'clientId' => config::get('app.rm_clientid'),
//            'clientSecret' =>config::get('app.rm_clientsecret'),
//            'privateKey' => file_get_contents($fileLocation),
//            'version' => 'stable',
//            'isSandbox' => false,
//        ]);
//
//        $redirectUrl = route('publicUser.application.statusPembayaran');
//        $notifyUrl =  Config('app.url').'/api/paymentStatus';
//
//		$title = 'Bayaran Dokumen';
//		$amount = 1;
//		$detail = 'Bayaran Dokumen';
//		$checkOutID = '';
//		$checkOutURL= '';
//
//		// create Web payment
//		try {
//			 $wp = new WebPayment;
//			 $wp->order->id 			= $paymentLog->PLNo;
//			 $wp->order->title 			= $title;
//			 $wp->order->currencyType 	= 'MYR';
//			 $wp->order->amount 		= $amount;
//			 $wp->order->detail 		= $detail;
//			 $wp->method 				= ["ALIPAY_CN","TNG_MY","SHOPEEPAY_MY","BOOST_MY","GRABPAY_MY","PRESTO_MY"];
//			 $wp->type 					= "WEB_PAYMENT";
//			 $wp->order->additionalData = $detail;
//             $wp->storeId 				= config::get('app.rm_storeid');
//			 $wp->redirectUrl 			= $redirectUrl;
//			 $wp->notifyUrl 			= $notifyUrl ;
//			 $wp->layoutVersion 		= 'v3';
//
//			$response = $rm->payment->createWebPayment($wp);
//			$checkOutID= $response->checkoutId; // Checkout ID
//			$checkOutURL= $response->url; // Payment gateway url
//
//			$paymentLog->PLCheckOutID = $checkOutID;
//			$paymentLog->PLPaymentLink = $checkOutURL ;
//			$paymentLog->Save();
//
//		} catch(ApiException $e) {
//
//		  //echo "statusCode : {$e->getCode()}, errorCode : {$e->getErrorCode()}, errorMessage : {$e->getMessage()}";
//			return response()->json([
//				'status'  => 'failed',
//				'statusCode' => $e->getCode(),
//				'errorCode' => $e->getErrorCode(),
//				'message' => $e->getMessage(),
//			]);
//		} catch(ValidationException $e) {
//		  	return response()->json([
//				'status'  => 'failed',
//				'message' => $e->getMessage(),
//			]);
//		} catch(Exception $e) {
//		  	return response()->json([
//				'status'  => 'failed',
//				'message' => $e->getMessage(),
//			]);
//		}

        //SEND EMAIL FOR SUCCESS SET PASSWORD
        $contractor = Contractor::where('CONo',$user->USCode)->first();

        $emailLog = new EmailLog();
        $emailLog->ELCB 	= $contractor->CONo;
        $emailLog->ELType 	= 'Document Tender Purchase';
        $emailLog->ELSentTo =  $contractor->COEmail;

        $emailData = array(
            'id' => $contractor->COID,
            'name'  => $contractor->COName ?? '',
            'email' => $contractor->COEmail,
            'domain' => config::get('app.url'),
            'paymentLogNo' => $PLNo,
            'paymentAmount' =>number_format($amount?? 0,2, '.', ',') ,
            'now' => Carbon::now()->format('j F Y'),
        );

        try {
            Mail::send(['html' => 'email.docTenderPaymentSuccessPublic'], $emailData, function($message) use ($emailData) {
                $message->to($emailData['email'] ,$emailData['name'])->subject('Status Pembelian Dokumen Tender');
            });

            $emailLog->ELMessage = 'Success';
            $emailLog->ELSentStatus = 1;
        } catch (\Exception $e) {
            $emailLog->ELMessage = $e->getMessage();
            $emailLog->ELSentStatus = 2;
        }

        $emailLog->save();

        return Redirect::to(route('publicUser.application.statusPembayaran', ['orderId'=>$PLNo, 'status' => 'SUCCESS']));
	}


    public function statusPembayaran(Request $request){

        $user = Auth::user();

        $paymentLogNo = $request->orderId;
		$status = $request->status;

        $paymentLog = PaymentLog::WHERE('PLNo',$paymentLogNo)->First();
        $contractor = Contractor::where('CONo', $user->USCode)->first();
        $tenderApp = TenderApplication::where('TANo', $paymentLog->PLRefNo)->first();
        $tenderProp = TenderProposal::where('TP_TANo', $tenderApp->TANo)->first();


		if($status == 'SUCCESS' ){
			$paymentStatusCode='01';
		}elseif($status == 'FAILED' ){
			$paymentStatusCode='02';
		}elseif($status == 'CANCELLED'){
			$paymentStatusCode='03';
		}

		try {
			DB::beginTransaction();

			$paymentLog->PL_PSCode			= $paymentStatusCode;
			$paymentLog->PLMD				= Carbon::now();
			$paymentLog->save();

			//PAYMENT SUCCESS
			if ($status == 'SUCCESS') {

                $tenderApplication =  TenderApplication::where('TANo', $paymentLog->PLRefNo)->first();
				$tenderApplication->TA_PLNo 	= $paymentLogNo;
                $tenderApplication->TAStatus    = 'PAID';
                $tenderApplication->save();

                $proposal = TenderProposal::where('TP_TANo', $paymentLog->PLRefNo)
                ->where('TP_TDANo',$tenderApplication->TA_TDANo)
                ->first();

                if($proposal == null){
                    $autoNumber = new AutoNumber();
                    $tenderProposalNo = $autoNumber->generateTenderProposalNo();

                    $proposal = new TenderProposal();
                    $proposal->TPNo = $tenderProposalNo;
                    $proposal->TP_CONo = $tenderApplication->TA_CONo;
                    $proposal->TP_TDNo = $tenderApplication->TA_TDNo;
                    $proposal->TP_TDANo = $tenderApplication->TA_TDANo;
                    $proposal->TPBankStmtBalAmt1  = $tenderApplication->TABankStmtBalAmt1;
                    $proposal->TPBankStmtBalAmt2  = $tenderApplication->TABankStmtBalAmt2;
                    $proposal->TPBankStmtBalAmt3  = $tenderApplication->TABankStmtBalAmt3;
                    $proposal->TPBankStmtBalScore = $tenderApplication->TABankStmtBalScore;

                    $proposal->TP_TANo      = $paymentLog->PLRefNo;
                    $proposal->TP_TPPCode   = 'DF';
                    $proposal->TPTotalAmt   = 0;
                    $proposal->TPActive     = 1;
                    $proposal->TPCB         = $user->USCode;
                    $proposal->TPEvaluationStep = 0;
                    $proposal->save();

                    //CREATE-TENDERPROPOSALOSC - PENDING

                    $tenderProp = $proposal;

                    $tenderDetails = TenderDetail::where('TDD_TDNo', $tenderApplication->TA_TDNo)->get();
                    $i = 1;

                    foreach($tenderDetails as $tenderDetail){
                        $new_ProposalDetailNo = $proposal->TPNo.'-'.$formattedCounter = sprintf("%03d", $i);

                        $proposalDetail = TenderProposalDetail::where('TPDNo', $new_ProposalDetailNo)->first();

                        if(!$proposalDetail){
                            $proposalDetail = new TenderProposalDetail();
                            $proposalDetail->TPDNo = $new_ProposalDetailNo;
                        }

                        if($tenderDetail->TDDType == 'T'){
                            if(!in_array($tenderDetail->TDD_MTCode, ['DF'])){
                                $proposalDetail->TPDCompleteTE = 0;
                            }
                        }
                        if($tenderDetail->TDDType == 'F'){
                            if(!in_array($tenderDetail->TDD_MTCode, ['DF', 'BS', 'BL'])){
                                $proposalDetail->TPDCompleteFE = 0;
                            }
                        }

                        if($tenderDetail->TDDType == 'T,F'){
                            $proposalDetail->TPDCompleteTE = 0;
                            $proposalDetail->TPDCompleteFE = 0;
                        }

                        if(in_array($tenderDetail->TDD_MTCode, ['UF', 'BF'])){
                            $proposalDetail->TPDCompleteO = 0;
                        }

                        $proposalDetail->TPD_TPNo   = $proposal->TPNo;
                        $proposalDetail->TPD_TDDNo  = $tenderDetail->TDDNo;
                        $proposalDetail->TPDCB      = $user->USCode;
                        $proposalDetail->save();

                        if($tenderDetail->TDD_MTCode == 'SP'){
                            $tenderSpecs = TenderSpec::where('TDS_TDNo', $tenderDetail->TDD_TDNo)
                                ->where('TDS_TDDNo', $tenderDetail->TDDNo)
                                ->get();

                            $j = 1;

                            foreach ($tenderSpecs as $tenderSpec){
                                $new_ProposalDetailProposalNo = $proposalDetail->TPDNo.'-'.$formattedCounter = sprintf("%03d", $j);

                                $proposalSpec = TenderProposalSpec::where('TPS_TPNo', $proposal->TPNo)
                                    ->where('TPS_TPDNo', $proposalDetail->TPDNo)
                                    ->where('TPS_TDSNo', $tenderSpec->TDSNo)
                                    ->first();

                                if(!$proposalSpec){
                                    $proposalSpec = new TenderProposalSpec();
                                    $proposalSpec->TPSNo = $new_ProposalDetailProposalNo;
                                    $proposalSpec->TPS_TPNo = $proposal->TPNo;
                                    $proposalSpec->TPS_TPDNo = $proposalDetail->TPDNo;
                                    $proposalSpec->TPS_TDSNo = $tenderSpec->TDSNo;
                                    $proposalSpec->TPSCB = $user->USCode;
                                    $proposalSpec->save();
                                }

                                $j++;
                            }
                        }


                        $i++;
                    }


                    //GET BLACKLIST PROJECT and PAST PROJECT CLOSE
                    $contractor = $tenderApplication->contractor;
                    $CONo = $contractor->CONo;
                    $COSSM = $contractor->COCompNo;

                    $pastProjects = $contractor->projectClose;
                    $blackListProjects = BlacklistProject::where('BLPSSMNo',$COSSM)->get();

                    foreach($blackListProjects as $index => $blackListProject){

                        $tenderProposalBLP = new TenderProposalBLP();
                        $tenderProposalBLP->TPBLP_TPNo  = $tenderProposalNo;
                        $tenderProposalBLP->TPBLP_BLPNo = $blackListProject->BLPNo;
                        $tenderProposalBLP->save();

                    }

                    foreach($pastProjects as $index => $pastProject){

                        $tenderProposalDBKL = new TenderProposalDBKL();
                        $tenderProposalDBKL->TPDBKL_TPNo  = $tenderProposalNo;
                        $tenderProposalDBKL->TPDBKL_PTNo = $pastProject->PTNo;
                        $tenderProposalDBKL->save();

                    }

                }

                $this->sendNotification($tenderApplication);

			}

			DB::commit();


		} catch (\Throwable $e) {
			DB::rollback();
		   throw $e;
		}

        return view('publicUser.application.statusPembayaran', compact('tenderApp','tenderProp', 'paymentLog'));
    }

    public function payment($TANo){
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $tenderApplication =  TenderApplication::where('TANo', $TANo)->first();
            $tenderApplication->TAStatus = 'PAID';
            $tenderApplication->save();

            $proposal = TenderProposal::where('TP_TANo', $TANo)
            ->where('TP_TDANo',$tenderApplication->TA_TDANo)
            ->first();

            if(!$proposal){
                $autoNumber = new AutoNumber();
                $tenderProposalNo = $autoNumber->generateTenderProposalNo();

                $proposal = new TenderProposal();
                $proposal->TPNo = $tenderProposalNo;
                $proposal->TP_CONo = $tenderApplication->TA_CONo;
                $proposal->TP_TDNo = $tenderApplication->TA_TDNo;
                $proposal->TP_TDANo = $tenderApplication->TA_TDANo;
            }

            // $proposal->TPSeqNo = $tenderApplication->;

            $proposal->TP_TANo = $TANo;
            $proposal->TP_TPPCode = 'DF';
            $proposal->TPTotalAmt = 0;
            //$proposal->TPMinCapitalScore = $xxx;
            //$proposal->TPProjPrivateScore = $xxx;
            //$proposal->TPCurrentAssetAmt = $xxx;
            //$proposal->TPCurrentLiabilityAmt = $xxx;
            //$proposal->TPCashAmt = $xxx;
            //$proposal->TPAccrualAmt = $xxx;
            //$proposal->TPLiquidityRatio = $xxx;
            //$proposal->TPCurrentRatio = $xxx;
            //$proposal->TPLiquidityRatioScore = $xxx;
            //$proposal->TPCurrentRatioScore = $xxx;
            //$proposal->TPBankStmtAmt1 = $xxx;
            //$proposal->TPBankStmtAmt2 = $xxx;
            //$proposal->TPBankStmtAmt3 = $xxx;
            //$proposal->TPBankStmtScore = $xxx;
            //$proposal->TPDeclareDate = $xxx;
            //$proposal->TPIntegrityDate = $xxx;
            //$proposal->TPTechnicalScore = $xxx;
            //$proposal->TDTechnicalPass = $xxx;
            //$proposal->TPFinanceScore = $xxx;
            //$proposal->TDFinancePass = $xxx;
            //$proposal->TPOverallScore = $xxx;
            //$proposal->TPOverallPass = $xxx;
            $proposal->TPActive = 1;
            $proposal->TPCB = $user->USCode;
            //$proposal->TPCD = $xxx;
            //$proposal->TPMB = $xxx;
            $proposal->TPEvaluationStep = 0;
            $proposal->save();

            $tenderDetails = TenderDetail::where('TDD_TDNo', $tenderApplication->TA_TDNo)->get();
            // $autoNumber = new AutoNumber();
            // $tenderProposalNo = $autoNumber->generateTenderProposalDetailNo();
            $i = 1;

            foreach($tenderDetails as $tenderDetail){
                $new_ProposalDetailNo = $proposal->TPNo.'-'.$formattedCounter = sprintf("%03d", $i);

                $proposalDetail = TenderProposalDetail::where('TPDNo', $new_ProposalDetailNo)->first();

                if(!$proposalDetail){
                    $proposalDetail = new TenderProposalDetail();
                    $proposalDetail->TPDNo = $new_ProposalDetailNo;
                }

                $proposalDetail->TPD_TPNo = $proposal->TPNo;
                $proposalDetail->TPD_TDDNo = $tenderDetail->TDDNo;

                if($tenderDetail->TDDType == 'T'){
                    if(!in_array($tenderDetail->TDD_MTCode, ['DF'])){
                        $proposalDetail->TPDCompleteTE = 0;
                    }
                }
                if($tenderDetail->TDDType == 'F'){
                    if(!in_array($tenderDetail->TDD_MTCode, ['DF', 'BS', 'BL'])){
                        $proposalDetail->TPDCompleteFE = 0;
                    }
                }

                if($tenderDetail->TDDType == 'T,F'){
                    $proposalDetail->TPDCompleteTE = 0;
                    $proposalDetail->TPDCompleteFE = 0;
                }


                // $proposalDetail->TPDComplete = $xxx;
                // $proposalDetail->TPDVerify = $xxx;
                // $proposalDetail->TPDRemarkT = $xxx;
                // $proposalDetail->TPDRemarkF = $xxx;
                $proposalDetail->TPDCB = $user->USCode;
                // $proposalDetail->TPDCD = $xxx;
                // $proposalDetail->TPDMB = $xxx;
                // $proposalDetail->TPDMD = $xxx;
                $proposalDetail->save();

                if($tenderDetail->TDD_MTCode == 'SP'){
                    $tenderSpecs = TenderSpec::where('TDS_TDNo', $tenderDetail->TDD_TDNo)
                        ->where('TDS_TDDNo', $tenderDetail->TDDNo)
                        ->get();

                    $j = 1;

                    foreach ($tenderSpecs as $tenderSpec){
                        $new_ProposalDetailProposalNo = $proposalDetail->TPDNo.'-'.$formattedCounter = sprintf("%03d", $j);

                        $proposalSpec = TenderProposalSpec::where('TPS_TPNo', $proposal->TPNo)
                            ->where('TPS_TPDNo', $proposalDetail->TPDNo)
                            ->where('TPS_TDSNo', $tenderSpec->TDSNo)
                            ->first();

                        if(!$proposalSpec){
                            $proposalSpec = new TenderProposalSpec();
                            $proposalSpec->TPSNo = $new_ProposalDetailProposalNo;
                            $proposalSpec->TPS_TPNo = $proposal->TPNo;
                            $proposalSpec->TPS_TPDNo = $proposalDetail->TPDNo;
                            $proposalSpec->TPS_TDSNo = $tenderSpec->TDSNo;
                            $proposalSpec->TPSCB = $user->USCode;
                            $proposalSpec->save();
                        }

                        $j++;
                    }
                }


                $i++;
            }

            DB::commit();

            return redirect()->route('publicUser.proposal.index');

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Payment was failed!'.$e->getMessage()
            ], 400);
        }


    }


    public function resitPDF($PLNo){

        $data = PaymentLog::where('PLNo',$PLNo)->with('contractor','tenderApp')->first();

        $template = "RESIT";
        $download = false; //true for download or false for view
        $templateName = "TENDERAPP"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF',compact('data','template','templateName'));
        $response = $this->generatePDF($view,$download);

        return $response;
    }

    public function editTenderApp($TDNo){

        $user = Auth::user();

        $tender = Tender::where('TDNo',$TDNo)->first();

        $tenderApp = TenderApplication::where('TA_TDNo',$TDNo)
        ->where('TA_TDANo',$tender->TD_TDANo)
        ->where('TAStatus','DRAFT')
        ->where('TA_CONo', $user->USCode)
        ->first();

        if($tenderApp == null){
            $autoNumber = new AutoNumber();
            $TANo = $autoNumber->generateTenderApplicationNo();

            $tenderApplication = new TenderApplication();
            $tenderApplication->TANo        = $TANo;
            $tenderApplication->TA_TDNo     = $TDNo;
            $tenderApplication->TA_TDANo    = $tender->TD_TDANo;
            $tenderApplication->TA_CONo     = $user->USCode;
            $tenderApplication->TAFee       = 0;
            $tenderApplication->TATaxFee    = 0;
            $tenderApplication->TATotalFee  = 0;
            // $tenderApplication->TA_PLNo  = $aaa;
            $tenderApplication->TAActive    = 1;
            $tenderApplication->TACB        = $user->USCode;
            $tenderApplication->TAStatus    = 'DRAFT';
            $tenderApplication->save();
        }else{
            $TANo = $tenderApp->TANo;
        }



        return redirect()->route('publicUser.application.create',[$TANo]);

    }

    function sendNotification($tenderApplication){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $notiType = "TDP";

            //#NOTIF-017
            $title = "Pembelian Tender Dokumen - $tenderApplication->TA_TDNo";
            $desc = "Perhatian, pembelian tender dokumen bagi tender $tenderApplication->TA_TDNo telah berjaya.";

            $pelaksanaType = "OSC";

            $userOSC = User::where('USType',$pelaksanaType)->get();

            if(!empty($userOSC)){

                foreach ($userOSC as $osc) {

                    $notification = new Notification();
                    $notification->NO_RefCode = $osc->USCode;
                    $notification->NOType = $pelaksanaType;
                    $notification->NO_NTCode = $notiType;
                    $notification->NOTitle = $title;
                    $notification->NODescription = $desc;
                    $notification->NORead = 0;
                    $notification->NOSent = 1;
                    $notification->NOActive = 1;
                    $notification->NOCB = $user->USCode;
                    $notification->NOMB = $user->USCode;
                    $notification->save();


                }
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Maklumat notifikasi berjaya dihantar.',
            ], 400);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat notifikasi tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }



}
