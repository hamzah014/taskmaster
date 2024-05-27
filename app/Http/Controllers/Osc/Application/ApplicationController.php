<?php
namespace App\Http\Controllers\Osc\Application;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Http\Requests;
use App\Models\Contractor;
use App\Models\ContractorComp;
use App\Models\ContractorCompOfficer;
use App\Models\Project;
use App\Models\TenderProposalProcess;
use App\Models\TenderProposal;
use App\Models\TenderProposalBLP;
use App\Models\TenderProposalComp;
use App\Models\TenderProposalCompOfficer;
use App\Models\TenderProposalCompShareholder;
use App\Models\TenderProposalCIDB;
use App\Models\TenderProposalCIDBGrade;
use App\Models\TenderProposalCTOS;
use App\Models\TenderProposalCTOSCharges;
use App\Models\TenderProposalCTOSFinancial;
use App\Models\TenderProposalNews;
use App\Models\TenderProposalOSC;
use App\Models\TenderProposalCTOSLoan;
use App\Models\TenderProposalCTOSLegalCase;
use App\Models\TenderProposalCTOSLoanDet;
use App\Models\TenderProposalDBKL;
use App\Models\WebSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use App\Services\DropdownService;
use mikehaertl\wkhtmlto\Pdf;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Calculation\Web;

class ApplicationController extends Controller
{
    public function __construct(DropdownService $dropdownService){
        $this->dropdownService = $dropdownService;
        //$this->username = $this->findUsername();
    }
    public function index(){
        return view('osc.application.index'
        );
    }
    public function view($id){
        $bank_status = $this->dropdownService->bank_status();
        $submission_status = $this->dropdownService->statusSubmission();
        $bank_name = $this->dropdownService->bank_name();
        $negeri = $this->dropdownService->negeri();
        $proposalInsolvensiStatus = $this->dropdownService->proposalInsolvensiStatus();
        $proposalBankStatus = $this->dropdownService->proposalBankStatus();
        $remark = $this->dropdownService->remark_type();
        $yt = $this->dropdownService->yt();

        $webSetting = new WebSetting();

        $news = [
            [
                'description' => 'ABC SDN BHD MENDAPAT AWARD SYARIKAT TERHEBAT',
                'url' => 'http://abc.com.my',
                'qr' => 'https://www.w3schools.com/images/w3schools_green.jpg',
            ],
            [
                'description' => 'ABC TERSEDAP DI HARTAMAS',
                'url' => 'http://sinar-harian.com.my',
                'qr' => 'https://api.qrserver.com/v1/create-qr-code/?size=185x185&ecc=L&qzone=1&data=http%3A%2F%2Fexample.com%2F',
            ],
            [
                'description' => 'BURSA SAHAM ABC SDN BHD MENINGKAT 50%',
                'url' => 'http://abc.com.my',
                'qr' => 'C:/Users/User/Pictures/qr.png',
            ]
        ];
        $proposal = TenderProposal::with('tender','contractor','tenderApplication')->where('TPNo',$id)->first();
        $oscProposal = TenderProposal::with('tender','contractor','tenderApplication')->where('TPNo',$id)->first();

        $proposalOSC = TenderProposalOSC::where('TPO_TPNo', $id)->first();
        

        $totalScore = TenderProposalOSC::select('TPOTotalScore')->where('TPO_TPNo', $id)->first();
        $totalScoreMax = $webSetting->OSCTotalScoreMax;


        $scoreData = [
            'Bank' => [
                'score' => $proposalOSC->TPOBankScore ?? null,
            ],
            'SSM' => [
                'score' => $proposalOSC->TPOSSMScore ?? null,
            ],
            'CIDB' => [
                'score' => $proposalOSC->TPOCIDBScore ?? null,
            ],
            'CTOS' => [
                'score' => $proposalOSC->TPOCTOSScore ?? null,
            ],
            'Pengalaman Projek DBKL' => [
                'score' => $proposalOSC->TPODBKLScore ?? null,
            ],
            'Pengalaman Projek BLP' => [
                'score' => $proposalOSC->TPOBLPScore ?? null,
            ],
            'Insolvensi	' => [
                'score' => $proposalOSC->TPOMDIScore ?? null,
            ],
            'Berita' => [
                'score' => $proposalOSC->TPONewsScore ?? null,
            ],

        ];

        $tenderProposalComp = TenderProposalComp::select('TRTenderProposalComp.*','SSMCSDesc AS TPC_companyStatusDesc','SSMCTDesc AS TPC_companyTypeDesc',
                                                    'SSMSCDesc AS TPC_statusOfCompanyDesc','RA.SSMSTDesc AS TPC_ra_stateDesc',
                                                    'BA.SSMSTDesc AS TPC_ba_stateDesc','BS.SSMSTDesc AS TPC_bs_auditFirmStateDesc')
                                                    ->leftjoin('SSM_COMPANY_STATUS','SSMCSCode','TPC_companyStatus','' )
                                                    ->leftjoin('SSM_COMPANY_TYPE','SSMCTCode','TPC_companyType' )
                                                    ->leftjoin('SSM_STATUS_OF_COMPANY','SSMSCCode','TPC_statusOfCompany' )
                                                    ->leftjoin('SSM_STATE AS RA','RA.SSMSTCode','TPC_ra_state' )
                                                    ->leftjoin('SSM_STATE AS BA','BA.SSMSTCode','TPC_ba_state' )
                                                    ->leftjoin('SSM_STATE AS BS','BS.SSMSTCode','TPC_bs_auditFirmState' )
                                                    ->where('TPC_TPNo', $id)->first();
        $tenderProposalCompOfficer = TenderProposalCompOfficer::select('TRTenderProposalCompOfficer.*','SSMSTDesc AS TPCO_stateDesc')
                                                                ->leftjoin('SSM_STATE','SSMSTCode','TPCO_state' )
                                                                ->where('TPCO_TPNo', $id)->get();
        $tenderProposalCompShareholder = TenderProposalCompShareholder::where('TPCS_TPNo', $id)->get();
        $tenderProposalCIDB = TenderProposalCIDB::where('TPCIDB_TPNo', $id)->first();
        $tenderProposalCIDBGrade = TenderProposalCIDBGrade::where('TPCIDBG_TPNo', $id)->orderby('TPCIDBGGrade','asc')->orderby('TPCIDBGCategoryCode','asc')->orderby('TPCIDBGSpecCode','asc')->get();
        $contractor = $proposal->contractor;

        $tenderApp = $proposal->tenderApplication;

        //dd($tenderApp->fileAttachBS->FAGuidID  , $tenderApp->fileAttachDownloadBAF->FAGuidID);
        $tenderProposalNews = TenderProposalNews::where('TPNews_TPNo', $id)->orderby('TPNews_publishedAt','asc')->get();

        if(!$proposal->tenderProposalOSC){
            $tenderProposalOSC = $this->createProposalOSC($proposal->TPNo);
        }


        $pengalamanprojek = Project::where('PTStatus', 'CLOSE')->take(3)->get();
        $tender = null;
        $tenderProposal = null;

        foreach ($pengalamanprojek as $project) {
            $tenderProposal = $project->tenderProposal;

            $tender = $tenderProposal->tender ?? null;

        }

        $ctos = TenderProposalCTOS::where('TPCTOS_TPNo',$id)->first();

        if (!isset($ctos)) {
            $ctos = null;
        }else{

            if (!isset($ctos->secure_totalLimit)) {
                $ctos->secure_totalLimit = "-";
            }
            if (!isset($ctos->secure_averageArrears)) {
                $ctos->secure_averageArrears = "-";
            }
            if (!isset($ctos->unsecure_totalLimit)) {
                $ctos->unsecure_totalLimit = "-";
            }
            if (!isset($ctos->unsecure_averageArrears)) {
                $ctos->unsecure_averageArrears = "-";
            }

        }

        $ctosloan = TenderProposalCTOSLoan::where('TPCL_TPNo',$id)->get();
        // $ctosloandet = $ctosloan->CTOSLoanDet;
        $ctosloandet = null;

        $legalcase = TenderProposalCTOSLegalCase::where('TPCLS_TPNo',$id)->first();

        $ctosloandet = TenderProposalCTOSLoanDet::where('TPCLD_TPNo',$id)->first();

        $totalOutstanding = TenderProposalCTOSLoanDet::where('TPCLD_TPNo', $id)->sum('TPCLD_position_balance');
        $totalCreditLimit = TenderProposalCTOSLoan::where('TPCL_TPNo', $id)->sum('TPCL_Limit');
        $totalinstAmount = TenderProposalCTOSLoanDet::where('TPCLD_TPNo', $id)->sum('TPCLD_inst_amount');
        $totalLimit = $totalCreditLimit + $totalinstAmount;

        $startDate = Carbon::now();
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = [
                'noMonth' => $startDate->format('m'),
                'fullMonth' => $startDate->format('M'),
                'year' => $startDate->format('Y'),
            ];

            $startDate->subMonth(); // Use subMonth() to subtract a month
        }

        $ctosCharges = TenderProposalCTOSCharges::where('TPCC_TPNo' , $id)->get();

        $ctosFinancials = TenderProposalCTOSFinancial::where('TPCF_TPNo' , $id)->get();

        $projectDBKL = TenderProposalDBKL::where('TPDBKL_TPNo' , $id)->get();

        $projectBLP = TenderProposalBLP::where('TPBLP_TPNo' , $id)->get();

        $webSetting = WebSetting::first();


        if($tenderProposalComp){
            if ($tenderProposalComp->TPC_ma_state == 'R'){//PERLIS
                $tenderProposalComp->TPC_ma_state = 'MY-09';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'K'){//KEDAH
                $tenderProposalComp->TPC_ma_state = 'MY-02';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'P'){//PP
                $tenderProposalComp->TPC_ma_state = 'MY-07';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'D'){//KEL
                $tenderProposalComp->TPC_ma_state = 'MY-03';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'T'){//TER
                $tenderProposalComp->TPC_ma_state = 'MY-11';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'A'){//PERAK
                $tenderProposalComp->TPC_ma_state = 'MY-08';;
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'B'){//SEL
                $tenderProposalComp->TPC_ma_state = 'MY-10';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'C'){//PAHANG
                $tenderProposalComp->TPC_ma_state = 'MY-06';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'N'){//N9
                $tenderProposalComp->TPC_ma_state = 'MY-05';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'M'){//MEL
                $tenderProposalComp->TPC_ma_state = 'MY-04';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'J'){//JHR
                $tenderProposalComp->TPC_ma_state = 'MY-01';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'X'){//SAB
                $tenderProposalComp->TPC_ma_state = 'MY-12';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'Y'){//SAR
                $tenderProposalComp->TPC_ma_state = 'MY-13';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'L'){//LAB
                $tenderProposalComp->TPC_ma_state = 'MY-15';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'W'){//WP
                $tenderProposalComp->TPC_ma_state = 'MY-14';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'U'){//PERLIS
                $tenderProposalComp->TPC_ma_state = 'MY-16';
            }
    
            //TPC_PA_STATE
            if ($tenderProposalComp->TPC_pa_state == 'R'){//PERLIS
                $tenderProposalComp->TPC_pa_state = 'MY-09';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'K'){//KEDAH
                $tenderProposalComp->TPC_pa_state = 'MY-02';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'P'){//PP
                $tenderProposalComp->TPC_pa_state = 'MY-07';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'D'){//KEL
                $tenderProposalComp->TPC_pa_state = 'MY-03';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'T'){//TER
                $tenderProposalComp->TPC_pa_state = 'MY-11';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'A'){//PERAK
                $tenderProposalComp->TPC_pa_state = 'MY-08';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'B'){//SEL
                $tenderProposalComp->TPC_pa_state = 'MY-10';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'C'){//PAHANG
                $tenderProposalComp->TPC_pa_state = 'MY-06';
            }
            elseif ( $tenderProposalComp->TPC_pa_state == 'N'){//N9
                $tenderProposalComp->TPC_pa_state = 'MY-05';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'M'){//MEL
                $tenderProposalComp->TPC_pa_state = 'MY-04';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'J'){//JHR
                $tenderProposalComp->TPC_pa_state = 'MY-01';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'X'){//SAB
                $tenderProposalComp->TPC_pa_state = 'MY-12';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'Y'){//SAR
                $tenderProposalComp->TPC_pa_state = 'MY-13';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'L'){//LAB
                $tenderProposalComp->TPC_pa_state = 'MY-15';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'W'){//WP
                $tenderProposalComp->TPC_pa_state = 'MY-14';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'U'){//PERLIS
                $tenderProposalComp->TPC_pa_state = 'MY-16';
            }
    
            //TPC_OL_STATE
            if ($tenderProposalComp->TPC_ol_state == 'R'){//PERLIS
                $tenderProposalComp->TPC_ol_state = 'MY-09';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'K'){//KEDAH
                $tenderProposalComp->TPC_ol_state = 'MY-02';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'P'){//PP
                $tenderProposalComp->TPC_ol_state = 'MY-07';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'D'){//KEL
                $tenderProposalComp->TPC_ol_state = 'MY-03';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'T'){//TER
                $tenderProposalComp->TPC_ol_state = 'MY-11';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'A'){//PERAK
                $tenderProposalComp->TPC_ol_state = 'MY-08';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'B'){//SEL
                $tenderProposalComp->TPC_ol_state = 'MY-10';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'C'){//PAHANG
                $tenderProposalComp->TPC_ol_state = 'MY-06';
            }
            elseif ( $tenderProposalComp->TPC_ol_state == 'N'){//N9
                $tenderProposalComp->TPC_ol_state = 'MY-05';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'M'){//MEL
                $tenderProposalComp->TPC_ol_state = 'MY-04';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'J'){//JHR
                $tenderProposalComp->TPC_ol_state = 'MY-01';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'X'){//SAB
                $tenderProposalComp->TPC_ol_state = 'MY-12';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'Y'){//SAR
                $tenderProposalComp->TPC_ol_state = 'MY-13';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'L'){//LAB
                $tenderProposalComp->TPC_ol_state = 'MY-15';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'W'){//WP
                $tenderProposalComp->TPC_ol_state = 'MY-14';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'U'){//PERLIS
                $tenderProposalComp->TPC_ol_state = 'MY-16';
            }

        }

        $tender = $proposal->tender;
        $statementYear = $tender->TDBankStmtYear ?? Carbon::now();

        $bankDateArray = array();

        for($x = 0; $x < 3; $x++){

            $yourDate = Carbon::parse($statementYear);

            $date = $yourDate->addMonths($x)->toDateString();
            $bankDateArray[$x]['month'] = Carbon::parse($date)->format('F');
            $bankDateArray[$x]['year'] = Carbon::parse($date)->format('Y');

        }

        return view('osc.application.view',
        compact('bank_status', 'submission_status', 'bank_name','bankDateArray',
            'negeri','proposalBankStatus','proposalInsolvensiStatus','yt',
            'news','proposal','contractor','tenderApp','ctos',
            'tenderProposalComp','tenderProposalCompOfficer','tenderProposalCompShareholder',
            'tenderProposalCIDB','tenderProposalCIDBGrade','pengalamanprojek','tenderProposal','tender','tenderProposalNews' ,
            'ctosloan' , 'ctosloandet' , 'legalcase' , 'totalOutstanding' , 'totalinstAmount' , 'totalLimit','months' , 
            'remark' , 'ctosCharges' , 'ctosFinancials' , 'projectDBKL' , 'projectBLP' , 'webSetting' , 'oscProposal' , 'scoreData')
        );
    }

    public function updateInsolvensiStatus(Request $request,$id){
        $messages = [
            'insolvensiStatus.required'   => 'Status insolvensi diperlukan.',
        ];

        $validation = [
            'insolvensiStatus' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tenderProposalOSC = TenderProposalOSC::where('TPO_TPNo',$id)->first();
            $tenderProposalOSC->TPO_PISCode = $request->insolvensiStatus;

            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');
                $fileType = 'TPO-MDI';
                $refNo = $id;
                $this->saveFile($file,$fileType,$refNo);
            }

            $tenderProposalOSC->save();

            if($request->updateStatus == 1){

                $tenderProposal = TenderProposal::where('TPNo',$id)->first();
                $tenderProposal->TPCheckMDI = 'Y';
                $tenderProposal->save();

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('osc.application.view', [$id, 'flag2' => 7]) ,
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function updateBankStatus(Request $request,$id){

        $messages = [
            'bank_status.required'   => 'Status bank diperlukan.',
        ];

        $validation = [
            'bank_status' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tenderProposalOSC = TenderProposalOSC::where('TPO_TPNo',$id)->first();
            $tenderProposalOSC->TPO_PBSCode = $request->bank_status;
            $tenderProposalOSC->save();

            if($request->updateStatus == 1){

                $tenderProposal = TenderProposal::where('TPNo',$id)->first();
                $tenderProposal->TPCheckBank = 'Y';
                $tenderProposal->save();

            }


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('osc.application.view', [$id, 'flag2' => 1]) ,
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function updateTPO(Request $request,$id){
        $messages = [
            'score_bank.required'    => 'Markah Bank diperlukan.',
            'score_bank.max'         => 'Markah Bank tidak boleh melebihi :max.',
            'remark_bank.required'   => 'Catatan Bank diperlukan.',
            'score_ssm.required'     => 'Markah SSM diperlukan.',
            'score_ssm.max'          => 'Markah SSM tidak boleh melebihi :max.',
            'remark_ssm.required'    => 'Catatan SSM diperlukan.',
            // 'score_dreams.required'  => 'Markah PPK diperlukan.',
            // 'score_dreams.max'       => 'Markah PPK tidak boleh melebihi :max.',
            // 'remark_dreams.required' => 'Catatan PPK ? Dreams diperlukan.',
            'score_ctos.required'    => 'Markah CTOS diperlukan.',
            'score_ctos.max'         => 'Markah CTOS tidak boleh melebihi :max.',
            'remark_ctos.required'   => 'Catatan CTOS diperlukan.',
            'score_cidb.required'    => 'Markah CIDB diperlukan.',
            'score_cidb.max'         => 'Markah CIDB tidak boleh melebihi :max.',
            'remark_cidb.required'   => 'Catatan CIDB diperlukan.',
            'score_news.required'    => 'Markah Berita diperlukan.',
            'score_news.max'         => 'Markah Berita tidak boleh melebihi :max.',
            'remark_news.required'   => 'Catatan Berita diperlukan.',
            'score_total.required'   => 'Jumlah markah diperlukan.',
            'score_total.max'        => 'Jumlah markah tidak boleh melebihi :max.',
            'remark_all.required'    => 'Catatan diperlukan.',
            'RTbank.required'        => 'Catatan Bank Diperlukan',
            'RTSSM.required'        => 'Catatan SSM Diperlukan',
            'RTCIDB.required'        => 'Catatan CIDB Diperlukan',
            'RTCTOS.required'        => 'Catatan CTOS Diperlukan',
            'RTDBKL.required'        => 'Catatan DBKL Diperlukan',
            'RTMOF.required'        => 'Catatan MOF Diperlukan',
            'RTMDI.required'        => 'Catatan Insolvensi Diperlukan',
            'RTNews.required'        => 'Catatan Berita Diperlukan',
            
        ];

        $validation = [
            'score_bank'     => 'required',
            'score_ssm'      => 'required',
            'score_ctos'     => 'required',
            'score_cidb'     => 'required',
            'score_dbkl'     => 'required',
            'score_mof'     => 'required',
            'score_mdi'     => 'required',
            'score_news'     => 'required',
            'score_total'    => 'required',
        ];


        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tenderProposalOSC = TenderProposalOSC::where('TPO_TPNo',$id)->first();
            $tenderProposalOSC->TPOBankScore    = $request->score_bank;
            $tenderProposalOSC->TPOBank_RTCode  = $request->RTBank;
            $tenderProposalOSC->TPOBankRemark   = $request->remark_RTBank;
            $tenderProposalOSC->TPOSSMScore     = $request->score_ssm;
            $tenderProposalOSC->TPOSSM_RTCode  = $request->RTSSM;
            $tenderProposalOSC->TPOSSMRemark    = $request->remark_RTSSM;
            // $tenderProposalOSC->TPODREAMSScore  = $request->score_dreams;
            // $tenderProposalOSC->TPODREAMSRemark = $request->remark_dreams;
            $tenderProposalOSC->TPOCIDBScore    = $request->score_cidb;
            $tenderProposalOSC->TPOCIDB_RTCode  = $request->RTCIDB;
            $tenderProposalOSC->TPOCIDBRemark   = $request->remark_RTCIDB;
            $tenderProposalOSC->TPOCTOSScore    = $request->score_ctos;
            $tenderProposalOSC->TPOCTOS_RTCode  = $request->RTCTOS;
            $tenderProposalOSC->TPOCTOSRemark   = $request->remark_RTCTOS;
            $tenderProposalOSC->TPODBKLScore    = $request->score_dbkl;
            $tenderProposalOSC->TPODBKL_RTCode  = $request->RTDBKL;
            $tenderProposalOSC->TPODBKLRemark   = $request->remark_RTDBKL;
            $tenderProposalOSC->TPOBLPScore    = $request->score_mof;
            $tenderProposalOSC->TPOBLP_RTCode  = $request->RTMOF;
            $tenderProposalOSC->TPOBLPRemark   = $request->remark_RTMOF;
            $tenderProposalOSC->TPOMDIScore    = $request->score_mdi;
            $tenderProposalOSC->TPOMDI_RTCode  = $request->RTMDI;
            $tenderProposalOSC->TPOMDIRemark   = $request->remark_RTMDI;
            $tenderProposalOSC->TPONewsScore    = $request->score_news;
            $tenderProposalOSC->TPONews_RTCode  = $request->RTNews;
            $tenderProposalOSC->TPONewsRemark   = $request->remark_RTNews;
            $tenderProposalOSC->TPOTotalScore   = $request->score_total;
            $tenderProposalOSC->TPORemark       = $request->remark_all;
            $tenderProposalOSC->save();

            if($request->updateStatus == 1){

                $tenderProposal = TenderProposal::where('TPNo',$id)->first();
                $tenderProposal->TPCompleteOSC = 1;
                $tenderProposal->TPOSCScore = $request->score_total;
//                $tenderProposal->TPOSCPass = 1;
                $tenderProposal->save();

            }


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('osc.application.view', [$id, 'flag' => 1]) ,
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function newsModal($id){
        $news = TenderProposalNews::where('TPNewsID', $id)->first();
        $data = array(
            'title'         => $news->TPNews_title ?? '',
            'description'   => $news->TPNews_description ?? '',
            'content'       => $news->TPNews_content ?? '',
            'url'           => $news->TPNews_url ?? '',
            'image'         => $news->TPNews_urlToImage ?? '',
            'publishAt'     => isset($news->TPNews_publishedAt) ? carbon::parse($news->TPNews_publishedAt)->format('d/m/Y H:i:s') : '',
            'source'        => $news->TPNews_source_name ?? '',
            'author'        => $news->TPNews_author ?? '',
        );
        return response()->json([
            'status' => 'success',
			'data' 	 => $data
        ]);
    }
    //{{--Working Code Datatable with indexNo--}}
    public function cadanganDatatable(Request $request){
        //$query = TenderProposal::where('TP_TPPCode', 'SB')->with('tender','contractor')->get();
        $query = TenderProposal::leftjoin('TRTender','TDNo', 'TP_TDNo')
                                ->leftjoin('MSContractor','CONo','TP_CONo')
                                ->whereIn('TP_TPPCode', ['DF','SB'])
                                ->orderby('TPNo','desc')
                                ->get();
        $count = 0;
        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {
                $count++;
                return $count;
            })
            ->addColumn('TPNo', function($row) {
                $route = route('osc.application.view',[$row->TPNo] );
                $result = '<a href="'.$route.'">'.$row->TPNo.'</a>';
                return $result;
            })
            ->editColumn('TPSubmitDate', function($row) {
                $carbonDatetime = Carbon::parse($row->TPSubmitDate);
                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');
                return $formattedDate;
            })
            ->editColumn('status', function($row) {
                $tenderstatus = TenderProposalProcess::where('TPPCode',$row->TP_TPPCode)->first();

                $result = $tenderstatus->TPPDesc;

                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo', 'TPNo', 'status'])
            ->make(true);
    }

    public function cetak($id){
        $yt = $this->dropdownService->yt();
        $negeri = $this->dropdownService->negeri();
        $proposalInsolvensiStatus = $this->dropdownService->proposalInsolvensiStatus();

        $user = Auth::user();

        $webSetting = WebSetting::first();

        $proposal = TenderProposal::with('tender','contractor','tenderApplication')->where('TPNo',$id)->first();

        $tenderProposalComp = TenderProposalComp::select('TRTenderProposalComp.*','SSMCSDesc AS TPC_companyStatusDesc','SSMCTDesc AS TPC_companyTypeDesc','SSMSCDesc AS TPC_statusOfCompanyDesc','RA.SSMSTDesc AS TPC_ra_stateDesc','BA.SSMSTDesc AS TPC_ba_stateDesc')
                                                    ->leftjoin('SSM_COMPANY_STATUS','SSMCSCode','TPC_companyStatus','' )
                                                    ->leftjoin('SSM_COMPANY_TYPE','SSMCTCode','TPC_companyType' )
                                                    ->leftjoin('SSM_STATUS_OF_COMPANY','SSMSCCode','TPC_statusOfCompany' )
                                                    ->leftjoin('SSM_STATE AS RA','RA.SSMSTCode','TPC_ra_state' )
                                                    ->leftjoin('SSM_STATE AS BA ','BA.SSMSTCode','TPC_ba_state' )
                                                    ->where('TPC_TPNo', $id)->first();
        $tenderProposalCompOfficer = TenderProposalCompOfficer::select('TRTenderProposalCompOfficer.*','SSMSTDesc AS TPCO_stateDesc')
                                                                ->leftjoin('SSM_STATE','SSMSTCode','TPCO_state' )
                                                                ->where('TPCO_TPNo', $id)->get();
        
        $tenderProposalCIDB = TenderProposalCIDB::where('TPCIDB_TPNo', $id)->first();
        $tenderProposalCIDBGrade = TenderProposalCIDBGrade::where('TPCIDBG_TPNo', $id)->orderby('TPCIDBGGrade','asc')->orderby('TPCIDBGCategoryCode','asc')->orderby('TPCIDBGSpecCode','asc')->get();

        $companyNo = $tenderProposalComp->TPC_companyNo ?? '';

        $profitLossCompany = TenderProposalComp::where('TPC_companyNo', $companyNo)->take(5)->orderBy('TPC_pl_financialYearEndDate', 'desc')->get();

        $tenderProposalNews = TenderProposalNews::where('TPNews_TPNo', $id)->orderby('TPNews_publishedAt','asc')->get();

        $pengalamanprojek = Project::with('tenderProposal')->where('PTStatus', 'CLOSE')->take(3)->get();

        $projectDBKL = TenderProposalDBKL::where('TPDBKL_TPNo' , $id)->get();

        $projectBLP = TenderProposalBLP::where('TPBLP_BLPNo' , $id)->get();

        $ssmScore = TenderProposalOSC::select('TPOSSMScore')->where('TPO_TPNo', $id)->get();
        $bankScore = TenderProposalOSC::select('TPOBankScore')->where('TPO_TPNo', $id)->get();
        $ctosScore = TenderProposalOSC::select('TPOCTOSScore')->where('TPO_TPNo', $id)->get();
        $cidbScore = TenderProposalOSC::select('TPOCIDBScore')->where('TPO_TPNo', $id)->get();
        $dbklScore = TenderProposalOSC::select('TPODBKLScore')->where('TPO_TPNo', $id)->get();
        $mofScore = TenderProposalOSC::select('TPOBLPScore')->where('TPO_TPNo', $id)->get();
        $mdiScore = TenderProposalOSC::select('TPOMDIScore')->where('TPO_TPNo', $id)->get();
        $newsScore = TenderProposalOSC::select('TPONewsScore')->where('TPO_TPNo', $id)->get();

        $totalScore = TenderProposalOSC::select('TPOTotalScore')->where('TPO_TPNo', $id)->first();
        $totalScoreMax = $webSetting->OSCTotalScoreMax;


        $scoreData = [
            'SSM' => [
                'scoreMax' => $webSetting->OSCSSMScoreMax,
                'score' => $ssmScore->sum('TPOSSMScore'),
            ],
            'Bank' => [
                'scoreMax' => $webSetting->OSCBankScoreMax,
                'score' => $bankScore->sum('TPOBankScore'),
            ],
            'CTOS' => [
                'scoreMax' => $webSetting->OSCCTOSScoreMax,
                'score' => $ctosScore->sum('TPOCTOSScore'),
            ],
            'CIDB' => [
                'scoreMax' => $webSetting->OSCCIDBScoreMax,
                'score' => $cidbScore->sum('TPOCIDBScore'),
            ],
            'DBKL' => [
                'scoreMax' => $webSetting->OSCDBKLScoreMax,
                'score' => $cidbScore->sum('TPODBKLScore'),
            ],
            'MOF' => [
                'scoreMax' => $webSetting->OSCBLPScoreMax,
                'score' => $cidbScore->sum('TPOBLPScore'),
            ],
            'MDI' => [
                'scoreMax' => $webSetting->OSCDMDIScoreMax,
                'score' => $cidbScore->sum('TPOMDIScore'),
            ],
            'News' => [
                'scoreMax' => $webSetting->OSCNewsScoreMax,
                'score' => $newsScore->sum('TPONewsScore'),
            ],
            'Total' => [
                'scoreMax' => $totalScoreMax,
                'score' => $totalScore->sum('TPOTotalScore'),
            ],
        ];

        foreach ($pengalamanprojek as $project) {
            $tenderProposal = $project->tenderProposal;

            $tender = $tenderProposal->tender ?? null;

        }

        $tenderProposalCompShareholder = TenderProposalCompShareholder::where('TPCS_TPNo', $id)->get();
        $tenderProposalCIDB = TenderProposalCIDB::where('TPCIDB_TPNo', $id)->first();
        $tenderProposalCIDBGrade = TenderProposalCIDBGrade::where('TPCIDBG_TPNo', $id)->get();
        $contractor = $proposal->contractor;
        $tenderApp = $proposal->tenderApplication;

        $ctosloan = TenderProposalCTOSLoan::where('TPCL_TPNo',$id)->get();
        // $ctosloandet = $ctosloan->CTOSLoanDet;
        $ctosloandet = null;

        $legalcase = TenderProposalCTOSLegalCase::where('TPCLS_TPNo',$id)->first();

        $ctosloandett = TenderProposalCTOSLoanDet::where('TPCLD_TPNo',$id)->first();

        $totalOutstanding = TenderProposalCTOSLoanDet::where('TPCLD_TPNo', $id)->sum('TPCLD_position_balance');

        $totalCreditLimit = TenderProposalCTOSLoan::where('TPCL_TPNo', $id)->sum('TPCL_Limit');

        $totalinstAmount = TenderProposalCTOSLoanDet::where('TPCLD_TPNo', $id)->sum('TPCLD_inst_amount');

        $totalLimit = $totalCreditLimit + $totalinstAmount;

        $startDate = Carbon::now();
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = [
                'noMonth' => $startDate->format('m'),
                'fullMonth' => $startDate->format('M'),
                'year' => $startDate->format('Y'),
            ];

            $startDate->subMonth(); // Use subMonth() to subtract a month
        }

        // $ctos = TenderProposalCTOS::first();

        $ctos = TenderProposalCTOS::where('TPCTOS_TPNo',$id)->first();

        if (!isset($ctos)) {
            $ctos = new TenderProposalCTOS;
        }

        if (!isset($ctos->secure_totalLimit)) {
            $ctos->secure_totalLimit = "-";
        }
        if (!isset($ctos->secure_averageArrears)) {
            $ctos->secure_averageArrears = "-";
        }
        if (!isset($ctos->unsecure_totalLimit)) {
            $ctos->unsecure_totalLimit = "-";
        }
        if (!isset($ctos->unsecure_averageArrears)) {
            $ctos->unsecure_averageArrears = "-";
        }

        $ctosCharges = TenderProposalCTOSCharges::where('TPCC_TPNo' , $id)->get();

        $ctosFinancials = TenderProposalCTOSFinancial::where('TPCF_TPNo' , $id)->get();

        $officer = ContractorCompOfficer::where('COCO_CONo' , $proposal->TP_CONo)->get();

        $tpOfficer = TenderProposalCompOfficer::where('TPCO_TPNo' , $id)->get();

        $findings = ''; // Initialize findings variable

        foreach ($officer as $officerRecord) {
            if ($officerRecord->COCO_designationCode == 'D') {

                if ($tpOfficer->isNotEmpty() && $tpOfficer->contains('TPCO_designationCode', 'D') && $tpOfficer->contains('TPCO_name', $officerRecord->COCO_name)) {
                    $findings = 'Pengarah yang di daftarkan mempunyai padanan dengan maklumat SSM.';
                } else {
                    $findings = 'Pengarah tidak mempunyai padanan dengan maklumat SSM.';
                }
            }
        }

        if($tenderProposalComp){
            if ($tenderProposalComp->TPC_ma_state == 'R'){//PERLIS
                $tenderProposalComp->TPC_ma_state = 'MY-09';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'K'){//KEDAH
                $tenderProposalComp->TPC_ma_state = 'MY-02';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'P'){//PP
                $tenderProposalComp->TPC_ma_state = 'MY-07';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'D'){//KEL
                $tenderProposalComp->TPC_ma_state = 'MY-03';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'T'){//TER
                $tenderProposalComp->TPC_ma_state = 'MY-11';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'A'){//PERAK
                $tenderProposalComp->TPC_ma_state = 'MY-08';;
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'B'){//SEL
                $tenderProposalComp->TPC_ma_state = 'MY-10';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'C'){//PAHANG
                $tenderProposalComp->TPC_ma_state = 'MY-06';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'N'){//N9
                $tenderProposalComp->TPC_ma_state = 'MY-05';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'M'){//MEL
                $tenderProposalComp->TPC_ma_state = 'MY-04';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'J'){//JHR
                $tenderProposalComp->TPC_ma_state = 'MY-01';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'X'){//SAB
                $tenderProposalComp->TPC_ma_state = 'MY-12';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'Y'){//SAR
                $tenderProposalComp->TPC_ma_state = 'MY-13';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'L'){//LAB
                $tenderProposalComp->TPC_ma_state = 'MY-15';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'W'){//WP
                $tenderProposalComp->TPC_ma_state = 'MY-14';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'U'){//PERLIS
                $tenderProposalComp->TPC_ma_state = 'MY-16';
            }
    
            //TPC_PA_STATE
            if ($tenderProposalComp->TPC_pa_state == 'R'){//PERLIS
                $tenderProposalComp->TPC_pa_state = 'MY-09';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'K'){//KEDAH
                $tenderProposalComp->TPC_pa_state = 'MY-02';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'P'){//PP
                $tenderProposalComp->TPC_pa_state = 'MY-07';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'D'){//KEL
                $tenderProposalComp->TPC_pa_state = 'MY-03';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'T'){//TER
                $tenderProposalComp->TPC_pa_state = 'MY-11';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'A'){//PERAK
                $tenderProposalComp->TPC_pa_state = 'MY-08';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'B'){//SEL
                $tenderProposalComp->TPC_pa_state = 'MY-10';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'C'){//PAHANG
                $tenderProposalComp->TPC_pa_state = 'MY-06';
            }
            elseif ( $tenderProposalComp->TPC_pa_state == 'N'){//N9
                $tenderProposalComp->TPC_pa_state = 'MY-05';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'M'){//MEL
                $tenderProposalComp->TPC_pa_state = 'MY-04';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'J'){//JHR
                $tenderProposalComp->TPC_pa_state = 'MY-01';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'X'){//SAB
                $tenderProposalComp->TPC_pa_state = 'MY-12';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'Y'){//SAR
                $tenderProposalComp->TPC_pa_state = 'MY-13';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'L'){//LAB
                $tenderProposalComp->TPC_pa_state = 'MY-15';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'W'){//WP
                $tenderProposalComp->TPC_pa_state = 'MY-14';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'U'){//PERLIS
                $tenderProposalComp->TPC_pa_state = 'MY-16';
            }
    
            //TPC_OL_STATE
            if ($tenderProposalComp->TPC_ol_state == 'R'){//PERLIS
                $tenderProposalComp->TPC_ol_state = 'MY-09';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'K'){//KEDAH
                $tenderProposalComp->TPC_ol_state = 'MY-02';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'P'){//PP
                $tenderProposalComp->TPC_ol_state = 'MY-07';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'D'){//KEL
                $tenderProposalComp->TPC_ol_state = 'MY-03';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'T'){//TER
                $tenderProposalComp->TPC_ol_state = 'MY-11';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'A'){//PERAK
                $tenderProposalComp->TPC_ol_state = 'MY-08';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'B'){//SEL
                $tenderProposalComp->TPC_ol_state = 'MY-10';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'C'){//PAHANG
                $tenderProposalComp->TPC_ol_state = 'MY-06';
            }
            elseif ( $tenderProposalComp->TPC_ol_state == 'N'){//N9
                $tenderProposalComp->TPC_ol_state = 'MY-05';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'M'){//MEL
                $tenderProposalComp->TPC_ol_state = 'MY-04';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'J'){//JHR
                $tenderProposalComp->TPC_ol_state = 'MY-01';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'X'){//SAB
                $tenderProposalComp->TPC_ol_state = 'MY-12';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'Y'){//SAR
                $tenderProposalComp->TPC_ol_state = 'MY-13';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'L'){//LAB
                $tenderProposalComp->TPC_ol_state = 'MY-15';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'W'){//WP
                $tenderProposalComp->TPC_ol_state = 'MY-14';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'U'){//PERLIS
                $tenderProposalComp->TPC_ol_state = 'MY-16';
            }
        }

        $template = "OSC";
        $download = false; //true for download or false for view
        $templateName = "BACKGROUND";
 /*
        $pdf = \PDF::loadView('osc.application.templatePDF',compact('template','templateName','proposal' , 'contractor' , 'tenderProposalComp','months'
        , 'tenderProposalCompOfficer' , 'tenderProposalCompShareholder', 'pengalamanprojek' , 'scoreData' , 'totalScore' , 'totalScoreMax'
        , 'ctosloan' , 'ctosloandet' , 'legalcase' , 'totalOutstanding' , 'totalinstAmount' , 'totalLimit'
        , 'ctos' , 'yt'));
        $pdf->setOption('enable-javascript', true);
        $pdf->setOption('javascript-delay', 5000);
        $pdf->setOption('enable-smart-shrinking', true);
        $pdf->setOption('no-stop-slow-scripts', true);
        return $pdf->download('chart.pdf');
*/
        //$view = View::make('osc.application.templatePDF'
        return view('osc.application.templatePDF'
        ,compact('template','templateName','proposal' , 'contractor' , 'tenderProposalComp','months'
        , 'tenderProposalCompOfficer' , 'tenderProposalCompShareholder', 'pengalamanprojek' , 'scoreData' , 'totalScore' , 'totalScoreMax'
        , 'ctosloan' , 'ctosloandet' , 'legalcase' , 'totalOutstanding' , 'totalinstAmount' , 'totalLimit' , 'negeri'
        , 'ctos' , 'yt' , 'profitLossCompany' , 'ctosCharges' , 'ctosFinancials', 'tenderProposalNews' , 'proposalInsolvensiStatus'
        , 'tenderProposalCIDB' , 'tenderProposalCIDBGrade' , 'projectDBKL' , 'officer' , 'tpOfficer' , 'findings' , 'projectBLP')
        );
        //$response = $this->generateReport($view,$download);

        //return $response;
    }

    public function download($id){
        $yt = $this->dropdownService->yt();

        $user = Auth::user();

        $proposal = TenderProposal::with('tender','contractor','tenderApplication')->where('TPNo',$id)->first();

        $tenderProposalComp = TenderProposalComp::where('TPC_TPNo', $id)->first();

        $tenderProposalCompOfficer = TenderProposalCompOfficer::where('TPCO_TPNo', $id)->get();

        $pengalamanprojek = Project::with('tenderProposal')->where('PTStatus', 'CLOSE')->take(3)->get();

        $ssmScore = TenderProposalOSC::select('TPOSSMScoreMax' , 'TPOSSMScore')->where('TPO_TPNo', $id)->get();

        $bankScore = TenderProposalOSC::select('TPOBankScoreMax' , 'TPOBankScore')->where('TPO_TPNo', $id)->get();

        $dreamScore = TenderProposalOSC::select('TPODREAMSScoreMax' , 'TPODREAMSScore')->where('TPO_TPNo', $id)->get();

        $ctosScore = TenderProposalOSC::select('TPOCTOSScoreMax' , 'TPOCTOSScore')->where('TPO_TPNo', $id)->get();

        $cidbScore = TenderProposalOSC::select('TPOCIDBScoreMax' , 'TPOCIDBScore')->where('TPO_TPNo', $id)->get();

        $newsScore = TenderProposalOSC::select('TPONewsScoreMax' , 'TPONewsScore')->where('TPO_TPNo', $id)->get();

        $totalScore = TenderProposalOSC::select('TPOTotalScore')->where('TPO_TPNo', $id)->first();
        $totalScoreMax = TenderProposalOSC::select('TPOTotalScoreMax')->where('TPO_TPNo', $id)->first();


        $scoreData = [
            'SSM' => [
                'scoreMax' => $ssmScore->sum('TPOSSMScoreMax'),
                'score' => $ssmScore->sum('TPOSSMScore'),
            ],
            'Bank' => [
                'scoreMax' => $bankScore->sum('TPOBankScoreMax'),
                'score' => $bankScore->sum('TPOBankScore'),
            ],
            'Dreams' => [
                'scoreMax' => $dreamScore->sum('TPODREAMSScoreMax'),
                'score' => $dreamScore->sum('TPODREAMSScore'),
            ],
            'CTOS' => [
                'scoreMax' => $ctosScore->sum('TPOCTOSScoreMax'),
                'score' => $ctosScore->sum('TPOCTOSScore'),
            ],
            'CIDB' => [
                'scoreMax' => $cidbScore->sum('TPOCIDBScoreMax'),
                'score' => $cidbScore->sum('TPOCIDBScore'),
            ],
            'News' => [
                'scoreMax' => $newsScore->sum('TPONewsScoreMax'),
                'score' => $newsScore->sum('TPONewsScore'),
            ],
            'Total' => [
                'scoreMax' => $totalScore->sum('TPOTotalScoreMax'),
                'score' => $totalScore->sum('TPOTotalScore'),
            ],
        ];

        foreach ($pengalamanprojek as $project) {
            $tenderProposal = $project->tenderProposal;

            $tender = $tenderProposal->tender ?? null;

        }

        $tenderProposalCompShareholder = TenderProposalCompShareholder::where('TPCS_TPNo', $id)->get();
        $tenderProposalCIDB = TenderProposalCIDB::where('TPCIDB_TPNo', $id)->first();
        $tenderProposalCIDBGrade = TenderProposalCIDBGrade::where('TPCIDBG_TPNo', $id)->get();
        $contractor = $proposal->contractor;
        $tenderApp = $proposal->tenderApplication;

        $ctosloan = TenderProposalCTOSLoan::where('TPCL_TPNo',$id)->get();
        // $ctosloandet = $ctosloan->CTOSLoanDet;
        $ctosloandet = null;

        $legalcase = TenderProposalCTOSLegalCase::where('TPCLS_TPNo',$id)->first();

        //dd($legalcase);

        //dd($ctosloandet);
        $ctosloandett = TenderProposalCTOSLoanDet::where('TPCLD_TPNo',$id)->first();

        $totalOutstanding = TenderProposalCTOSLoanDet::where('TPCLD_TPNo', $id)->sum('TPCLD_position_balance');

        $totalCreditLimit = TenderProposalCTOSLoan::where('TPCL_TPNo', $id)->sum('TPCL_Limit');

        $totalinstAmount = TenderProposalCTOSLoanDet::where('TPCLD_TPNo', $id)->sum('TPCLD_inst_amount');

        $totalLimit = $totalCreditLimit + $totalinstAmount;

        $startDate = Carbon::now();
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = [
                'noMonth' => $startDate->format('m'),
                'fullMonth' => $startDate->format('M'),
                'year' => $startDate->format('Y'),
            ];

            $startDate->subMonth(); // Use subMonth() to subtract a month
        }

        $ctos = TenderProposalCTOS::first();

        if (!isset($ctos)) {
            $ctos = new TenderProposalCTOS;
        }

        if (!isset($ctos->secure_totalLimit)) {
            $ctos->secure_totalLimit = "-";
        }
        if (!isset($ctos->secure_averageArrears)) {
            $ctos->secure_averageArrears = "-";
        }
        if (!isset($ctos->unsecure_totalLimit)) {
            $ctos->unsecure_totalLimit = "-";
        }
        if (!isset($ctos->unsecure_averageArrears)) {
            $ctos->unsecure_averageArrears = "-";
        }

        // dd($months);

        //dd($ctosloan);

        //dd($ctosloan, $ctosloandet);

        //dd($ctosloandet);

        //dd($ctosloan, $ctosloandet);


        $template = "OSC";
        $download = false; //true for download or false for view
        $templateName = "BACKGROUND";
        //$view = View::make('osc.application.templatePDF'
        //return view('osc.application.templatePDF'
        //,compact('template','templateName','proposal' , 'contractor' , 'tenderProposalComp','months'
        //, 'tenderProposalCompOfficer' , 'tenderProposalCompShareholder', 'pengalamanprojek' , 'scoreData' , 'totalScore' , 'totalScoreMax'
        //, 'ctosloan' , 'ctosloandet' , 'legalcase' , 'totalOutstanding' , 'totalinstAmount' , 'totalLimit'
        //, 'ctos' , 'yt')
        //);
        //$response = $this->generateReport($view,$download);

        //return $response;
        $render =  view('osc.application.templatePDF' ,compact('template','templateName','proposal' , 'contractor' , 'tenderProposalComp','months'
        , 'tenderProposalCompOfficer' , 'tenderProposalCompShareholder', 'pengalamanprojek' , 'scoreData' , 'totalScore' , 'totalScoreMax'
        , 'ctosloan' , 'ctosloandet' , 'legalcase' , 'totalOutstanding' , 'totalinstAmount' , 'totalLimit'
        , 'ctos' , 'yt'))->render();


        $pdf = new Pdf;
        $pdf->addPage($render);
        $pdf->setOptions(['enable-javascript' => true]);
        $pdf->setOptions(['javascript-delay' => 5000]);
        $pdf->setOptions(['enable-smart-shrinking' => true]);
        $pdf->setOptions(['no-stop-slow-scripts' => true]);
        $pdf->saveAs(public_path('report.pdf'));

        return response()->download(public_path('report.pdf'));
    }

    public function createProposalOSC($proposalNo){
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $proposalOSC = new TenderProposalOSC();
            $proposalOSC->TPO_TPNo = $proposalNo;
            $proposalOSC->TPOCB     = $user->USCode;
            $proposalOSC->TPOMB     = $user->USCode;

            $proposalOSC->save();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat OSC tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }



    }

    public function cetak2(Request $request){
        $id = $request->id;
        $base64 = $request->based64;

        $imageGraph = preg_replace('#^data:image/\w+;base64,#i', '', $base64);


        $yt = $this->dropdownService->yt();
        $negeri = $this->dropdownService->negeri();
        $proposalInsolvensiStatus = $this->dropdownService->proposalInsolvensiStatus();

        $user = Auth::user();

        $webSetting = WebSetting::first();

        $proposal = TenderProposal::with('tender','contractor','tenderApplication')->where('TPNo',$id)->first();
        $oscProposal = TenderProposal::with('tender','contractor','tenderApplication')->where('TPNo',$id)->first();


        $tenderProposalComp = TenderProposalComp::select('TRTenderProposalComp.*','SSMCSDesc AS TPC_companyStatusDesc','SSMCTDesc AS TPC_companyTypeDesc','SSMSCDesc AS TPC_statusOfCompanyDesc','RA.SSMSTDesc AS TPC_ra_stateDesc','BA.SSMSTDesc AS TPC_ba_stateDesc')
                                                    ->leftjoin('SSM_COMPANY_STATUS','SSMCSCode','TPC_companyStatus','' )
                                                    ->leftjoin('SSM_COMPANY_TYPE','SSMCTCode','TPC_companyType' )
                                                    ->leftjoin('SSM_STATUS_OF_COMPANY','SSMSCCode','TPC_statusOfCompany' )
                                                    ->leftjoin('SSM_STATE AS RA','RA.SSMSTCode','TPC_ra_state' )
                                                    ->leftjoin('SSM_STATE AS BA ','BA.SSMSTCode','TPC_ba_state' )
                                                    ->where('TPC_TPNo', $id)->first();
        $tenderProposalCompOfficer = TenderProposalCompOfficer::select('TRTenderProposalCompOfficer.*','SSMSTDesc AS TPCO_stateDesc')
                                                                ->leftjoin('SSM_STATE','SSMSTCode','TPCO_state' )
                                                                ->where('TPCO_TPNo', $id)->get();
        
        $tenderProposalCIDB = TenderProposalCIDB::where('TPCIDB_TPNo', $id)->first();
        $tenderProposalCIDBGrade = TenderProposalCIDBGrade::where('TPCIDBG_TPNo', $id)->orderby('TPCIDBGGrade','asc')->orderby('TPCIDBGCategoryCode','asc')->orderby('TPCIDBGSpecCode','asc')->get();

        $companyNo = $tenderProposalComp->TPC_companyNo ?? '';

        $profitLossCompany = TenderProposalComp::where('TPC_companyNo', $companyNo)->take(5)->orderBy('TPC_pl_financialYearEndDate', 'desc')->get();

        $tenderProposalNews = TenderProposalNews::where('TPNews_TPNo', $id)->orderby('TPNews_publishedAt','asc')->get();

        $pengalamanprojek = Project::with('tenderProposal')->where('PTStatus', 'CLOSE')->take(3)->get();

        $projectDBKL = TenderProposalDBKL::where('TPDBKL_TPNo' , $id)->get();

        $projectBLP = TenderProposalBLP::where('TPBLP_BLPNo' , $id)->get();

        $ssmScore = TenderProposalOSC::select('TPOSSMScore')->where('TPO_TPNo', $id)->get();
        $bankScore = TenderProposalOSC::select('TPOBankScore')->where('TPO_TPNo', $id)->get();
        $ctosScore = TenderProposalOSC::select('TPOCTOSScore')->where('TPO_TPNo', $id)->get();
        $cidbScore = TenderProposalOSC::select('TPOCIDBScore')->where('TPO_TPNo', $id)->get();
        $dbklScore = TenderProposalOSC::select('TPODBKLScore')->where('TPO_TPNo', $id)->get();
        $mofScore = TenderProposalOSC::select('TPOBLPScore')->where('TPO_TPNo', $id)->get();
        $mdiScore = TenderProposalOSC::select('TPOMDIScore')->where('TPO_TPNo', $id)->get();
        $newsScore = TenderProposalOSC::select('TPONewsScore')->where('TPO_TPNo', $id)->get();

        $totalScore = TenderProposalOSC::select('TPOTotalScore')->where('TPO_TPNo', $id)->first();
        $totalScoreMax = $webSetting->OSCTotalScoreMax;


        $scoreData = [
            'SSM' => [
                'scoreMax' => $webSetting->OSCSSMScoreMax,
                'score' => $ssmScore->sum('TPOSSMScore'),
            ],
            'Bank' => [
                'scoreMax' => $webSetting->OSCBankScoreMax,
                'score' => $bankScore->sum('TPOBankScore'),
            ],
            'CTOS' => [
                'scoreMax' => $webSetting->OSCCTOSScoreMax,
                'score' => $ctosScore->sum('TPOCTOSScore'),
            ],
            'CIDB' => [
                'scoreMax' => $webSetting->OSCCIDBScoreMax,
                'score' => $cidbScore->sum('TPOCIDBScore'),
            ],
            'DBKL' => [
                'scoreMax' => $webSetting->OSCDBKLScoreMax,
                'score' => $cidbScore->sum('TPODBKLScore'),
            ],
            'MOF' => [
                'scoreMax' => $webSetting->OSCBLPScoreMax,
                'score' => $cidbScore->sum('TPOBLPScore'),
            ],
            'MDI' => [
                'scoreMax' => $webSetting->OSCDMDIScoreMax,
                'score' => $cidbScore->sum('TPOMDIScore'),
            ],
            'News' => [
                'scoreMax' => $webSetting->OSCNewsScoreMax,
                'score' => $newsScore->sum('TPONewsScore'),
            ],

        ];

        foreach ($pengalamanprojek as $project) {
            $tenderProposal = $project->tenderProposal;

            $tender = $tenderProposal->tender ?? null;

        }

        $tenderProposalCompShareholder = TenderProposalCompShareholder::where('TPCS_TPNo', $id)->get();
        $tenderProposalCIDB = TenderProposalCIDB::where('TPCIDB_TPNo', $id)->first();
        $tenderProposalCIDBGrade = TenderProposalCIDBGrade::where('TPCIDBG_TPNo', $id)->get();
        $contractor = $proposal->contractor;
        $tenderApp = $proposal->tenderApplication;

        $ctosloan = TenderProposalCTOSLoan::where('TPCL_TPNo',$id)->get();
        // $ctosloandet = $ctosloan->CTOSLoanDet;
        $ctosloandet = null;

        $legalcase = TenderProposalCTOSLegalCase::where('TPCLS_TPNo',$id)->first();

        $ctosloandett = TenderProposalCTOSLoanDet::where('TPCLD_TPNo',$id)->first();

        $totalOutstanding = TenderProposalCTOSLoanDet::where('TPCLD_TPNo', $id)->sum('TPCLD_position_balance');

        $totalCreditLimit = TenderProposalCTOSLoan::where('TPCL_TPNo', $id)->sum('TPCL_Limit');

        $totalinstAmount = TenderProposalCTOSLoanDet::where('TPCLD_TPNo', $id)->sum('TPCLD_inst_amount');

        $totalLimit = $totalCreditLimit + $totalinstAmount;

        $startDate = Carbon::now();
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = [
                'noMonth' => $startDate->format('m'),
                'fullMonth' => $startDate->format('M'),
                'year' => $startDate->format('Y'),
            ];

            $startDate->subMonth(); // Use subMonth() to subtract a month
        }

        // $ctos = TenderProposalCTOS::first();

        $ctos = TenderProposalCTOS::where('TPCTOS_TPNo',$id)->first();

        if (!isset($ctos)) {
            $ctos = new TenderProposalCTOS;
        }

        if (!isset($ctos->secure_totalLimit)) {
            $ctos->secure_totalLimit = "-";
        }
        if (!isset($ctos->secure_averageArrears)) {
            $ctos->secure_averageArrears = "-";
        }
        if (!isset($ctos->unsecure_totalLimit)) {
            $ctos->unsecure_totalLimit = "-";
        }
        if (!isset($ctos->unsecure_averageArrears)) {
            $ctos->unsecure_averageArrears = "-";
        }

        $ctosCharges = TenderProposalCTOSCharges::where('TPCC_TPNo' , $id)->get();

        $ctosFinancials = TenderProposalCTOSFinancial::where('TPCF_TPNo' , $id)->get();

        $officer = ContractorCompOfficer::where('COCO_CONo' , $proposal->TP_CONo)->get();

        $tpOfficer = TenderProposalCompOfficer::where('TPCO_TPNo' , $id)->get();

        $findings = ''; // Initialize findings variable

        foreach ($officer as $officerRecord) {
            if ($officerRecord->COCO_designationCode == 'D') {

                if ($tpOfficer->isNotEmpty() && $tpOfficer->contains('TPCO_designationCode', 'D') && $tpOfficer->contains('TPCO_name', $officerRecord->COCO_name)) {
                    $findings = 'Pengarah yang di daftarkan mempunyai padanan dengan maklumat SSM.';
                } else {
                    $findings = 'Pengarah tidak mempunyai padanan dengan maklumat SSM.';
                }
            }
        }

        if($tenderProposalComp){
            if ($tenderProposalComp->TPC_ma_state == 'R'){//PERLIS
                $tenderProposalComp->TPC_ma_state = 'MY-09';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'K'){//KEDAH
                $tenderProposalComp->TPC_ma_state = 'MY-02';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'P'){//PP
                $tenderProposalComp->TPC_ma_state = 'MY-07';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'D'){//KEL
                $tenderProposalComp->TPC_ma_state = 'MY-03';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'T'){//TER
                $tenderProposalComp->TPC_ma_state = 'MY-11';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'A'){//PERAK
                $tenderProposalComp->TPC_ma_state = 'MY-08';;
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'B'){//SEL
                $tenderProposalComp->TPC_ma_state = 'MY-10';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'C'){//PAHANG
                $tenderProposalComp->TPC_ma_state = 'MY-06';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'N'){//N9
                $tenderProposalComp->TPC_ma_state = 'MY-05';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'M'){//MEL
                $tenderProposalComp->TPC_ma_state = 'MY-04';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'J'){//JHR
                $tenderProposalComp->TPC_ma_state = 'MY-01';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'X'){//SAB
                $tenderProposalComp->TPC_ma_state = 'MY-12';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'Y'){//SAR
                $tenderProposalComp->TPC_ma_state = 'MY-13';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'L'){//LAB
                $tenderProposalComp->TPC_ma_state = 'MY-15';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'W'){//WP
                $tenderProposalComp->TPC_ma_state = 'MY-14';
            }
            elseif ($tenderProposalComp->TPC_ma_state == 'U'){//PERLIS
                $tenderProposalComp->TPC_ma_state = 'MY-16';
            }
    
            //TPC_PA_STATE
            if ($tenderProposalComp->TPC_pa_state == 'R'){//PERLIS
                $tenderProposalComp->TPC_pa_state = 'MY-09';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'K'){//KEDAH
                $tenderProposalComp->TPC_pa_state = 'MY-02';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'P'){//PP
                $tenderProposalComp->TPC_pa_state = 'MY-07';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'D'){//KEL
                $tenderProposalComp->TPC_pa_state = 'MY-03';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'T'){//TER
                $tenderProposalComp->TPC_pa_state = 'MY-11';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'A'){//PERAK
                $tenderProposalComp->TPC_pa_state = 'MY-08';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'B'){//SEL
                $tenderProposalComp->TPC_pa_state = 'MY-10';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'C'){//PAHANG
                $tenderProposalComp->TPC_pa_state = 'MY-06';
            }
            elseif ( $tenderProposalComp->TPC_pa_state == 'N'){//N9
                $tenderProposalComp->TPC_pa_state = 'MY-05';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'M'){//MEL
                $tenderProposalComp->TPC_pa_state = 'MY-04';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'J'){//JHR
                $tenderProposalComp->TPC_pa_state = 'MY-01';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'X'){//SAB
                $tenderProposalComp->TPC_pa_state = 'MY-12';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'Y'){//SAR
                $tenderProposalComp->TPC_pa_state = 'MY-13';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'L'){//LAB
                $tenderProposalComp->TPC_pa_state = 'MY-15';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'W'){//WP
                $tenderProposalComp->TPC_pa_state = 'MY-14';
            }
            elseif ($tenderProposalComp->TPC_pa_state == 'U'){//PERLIS
                $tenderProposalComp->TPC_pa_state = 'MY-16';
            }
    
            //TPC_OL_STATE
            if ($tenderProposalComp->TPC_ol_state == 'R'){//PERLIS
                $tenderProposalComp->TPC_ol_state = 'MY-09';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'K'){//KEDAH
                $tenderProposalComp->TPC_ol_state = 'MY-02';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'P'){//PP
                $tenderProposalComp->TPC_ol_state = 'MY-07';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'D'){//KEL
                $tenderProposalComp->TPC_ol_state = 'MY-03';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'T'){//TER
                $tenderProposalComp->TPC_ol_state = 'MY-11';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'A'){//PERAK
                $tenderProposalComp->TPC_ol_state = 'MY-08';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'B'){//SEL
                $tenderProposalComp->TPC_ol_state = 'MY-10';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'C'){//PAHANG
                $tenderProposalComp->TPC_ol_state = 'MY-06';
            }
            elseif ( $tenderProposalComp->TPC_ol_state == 'N'){//N9
                $tenderProposalComp->TPC_ol_state = 'MY-05';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'M'){//MEL
                $tenderProposalComp->TPC_ol_state = 'MY-04';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'J'){//JHR
                $tenderProposalComp->TPC_ol_state = 'MY-01';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'X'){//SAB
                $tenderProposalComp->TPC_ol_state = 'MY-12';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'Y'){//SAR
                $tenderProposalComp->TPC_ol_state = 'MY-13';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'L'){//LAB
                $tenderProposalComp->TPC_ol_state = 'MY-15';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'W'){//WP
                $tenderProposalComp->TPC_ol_state = 'MY-14';
            }
            elseif ($tenderProposalComp->TPC_ol_state == 'U'){//PERLIS
                $tenderProposalComp->TPC_ol_state = 'MY-16';
            }
        }

        $template = "OSC";
        $download = true; //true for download or false for view
        $templateName = "BACKGROUND";
 /*
        $pdf = \PDF::loadView('osc.application.templatePDF',compact('template','templateName','proposal' , 'contractor' , 'tenderProposalComp','months'
        , 'tenderProposalCompOfficer' , 'tenderProposalCompShareholder', 'pengalamanprojek' , 'scoreData' , 'totalScore' , 'totalScoreMax'
        , 'ctosloan' , 'ctosloandet' , 'legalcase' , 'totalOutstanding' , 'totalinstAmount' , 'totalLimit'
        , 'ctos' , 'yt'));
        $pdf->setOption('enable-javascript', true);
        $pdf->setOption('javascript-delay', 5000);
        $pdf->setOption('enable-smart-shrinking', true);
        $pdf->setOption('no-stop-slow-scripts', true);
        return $pdf->download('chart.pdf');
*/
        $view = View::make('osc.application.templatePDF'
        //return view('osc.application.templatePDF'
        ,compact('template','templateName','proposal' , 'contractor' , 'tenderProposalComp','months' , 'imageGraph'
        , 'tenderProposalCompOfficer' , 'tenderProposalCompShareholder', 'pengalamanprojek' , 'scoreData' , 'totalScore' , 'totalScoreMax'
        , 'ctosloan' , 'ctosloandet' , 'legalcase' , 'totalOutstanding' , 'totalinstAmount' , 'totalLimit' , 'negeri'
        , 'ctos' , 'yt' , 'profitLossCompany' , 'ctosCharges' , 'ctosFinancials', 'tenderProposalNews' , 'proposalInsolvensiStatus'
        , 'tenderProposalCIDB' , 'tenderProposalCIDBGrade' , 'projectDBKL' , 'officer' , 'tpOfficer' , 'findings' , 'projectBLP' , 'oscProposal')
        );
        // $response = $this->generateReport($view,$download);

        //return $response;

        try {

            $headerHtml = '<div class="header" style="position: fixed; top: 0; width: 100%;">
                <div class="row">
                    <div class="col m1">
                        <img src="' . public_path('assets/images/logo/logo.png') . '" alt="" style="width:70px;margin-top:-20px;">
                    </div>
                    <div class="col m1 right">
                        <img src="' . public_path('assets/images/logo/SPEED.png') . '" alt="" style="width:130px;margin-left:-80px;margin-top:-20px;">
                    </div>
                </div>
            </div>';

            $contentHtml = $view->render();

            $html = $headerHtml . $contentHtml;

            //$output = $view->render();
            $pdf = new Dompdf();
            $pdf->set_option('chroot', public_path());
            $pdf->loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();

            $temporaryFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testing.pdf';

            file_put_contents($temporaryFilePath, $pdf->output());

            if (file_exists($temporaryFilePath)) {

                $file = $temporaryFilePath;

                $fileContent = file_get_contents($file);

				$base64File = base64_encode($fileContent);

				return $base64File;
            }else{
                return false;
            }

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }


}
