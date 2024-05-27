<?php

namespace App\Http\Controllers\Perolehan\Letter;

use App\Helper\Custom;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Http\Controllers\Perolehan\Tender\TenderController;
use App\Models\MeetingNP;
use App\Models\ProjectTenderMilestone;
use App\Models\SSMCompany;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Dompdf\Dompdf;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Tender;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use App\Services\DropdownService;
use App\Models\TenderApplication;
use App\Models\TenderFormDetail;
use App\Models\TenderProcess;
use App\Models\TenderApplicationAuthSign;
use App\Models\TenderDetail;
use App\Models\TenderProposal;
use App\Models\TenderProposalDetail;
use App\Models\TenderProposalSpec;
use App\Models\FileAttach;
use App\Models\AutoNumber;
use App\Models\BoardMeeting;
use App\Models\BoardMeetingTender;
use App\Models\BoardMeetingProposal;
use App\Models\Contractor;
use App\Models\DigitalCert;
use App\Models\EmailLog;
use App\Models\LetterIntent;
use App\Models\LetterIntentDet;
use App\Models\LetterAcceptance;
use App\Models\LetterAcceptanceDet;
use App\Models\LetterAcceptanceDeduction;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\TemplateFile;
use App\Models\TemplateMilestone;
use App\Models\TenderPaymentDeduction;
use App\Models\WebSetting;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Config;

use Mail;

class AcceptLetterController extends Controller
{
    // public function __construct(DropdownService $dropdownService, TenderController $tenderController)
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        return view('perolehan.letter.acceptLetter.index');
    }

    public function listIntentLetter(){

        return view('perolehan.letter.acceptLetter.listIntentLetter');
    }

    public function create($id){

        $user = Auth::user();

        $tender = Tender::whereHas('tenderProposal', function($query){
            $query->where('TP_TPPCode','SB');
        })
        ->get()->pluck('TDTitle','TDNo')->map(function ($item, $key) {
            $code = Tender::where('TDNo', $key)->value('TDNo');
            return  $code . " - " . $item;
        });

        $tenderProposal = TenderProposal::where('TPNo',$id)->first();

        $meetingNP = null;

        if($tenderProposal->meetingNP){

//            $meetingNP = $tenderProposal->meetingNP->last();
            $meetingNP = MeetingNP::where('MNP_TPNo',$id)->where('MNP_MSCode', 'D')->orderBy('MNPID','DESC')->first();

        }

        if($tenderProposal->letterIntent){

            $letterIntent = $tenderProposal->letterIntent;

            $li_date = Carbon::parse($letterIntent->LIDate)->format('Y-m-d');
            $li_time = Carbon::parse($letterIntent->LITime)->format('H:i');
            $tenderProposal = $letterIntent->tenderProposal;
            $tender =$letterIntent->tenderProposal->tender;

        }else{
            $letterIntent = null;

            $li_date = null;
            $li_time = null;
            $tender = $tenderProposal->tender;
        }

        $acceptStatus = $this->dropdownService->acceptStatus();

        $yn = $this->dropdownService->yt();

        $jenis_projek = $this->dropdownService->jenis_projek();
        $tenderProject_type = $jenis_projek[$tender->TD_PTCode];

        $department = $this->dropdownService->department();
        $tenderJabatan = $department[$tender->TD_DPTCode];


        // Convert the date strings to DateTime objects
        $startDate = Carbon::parse($tender->TDPublishDate);
        $endDate = Carbon::parse($tender->TDClosingDate);

        // Calculate the difference between the two dates
        $tempohSah = $startDate->diff($endDate);

        $pentadbir = User::where('USName','!=','')->get()->pluck('USName','USCode');

        //LAMPIRAN
        $templates = TemplateFile::where('TF_TTCode','LA')
                    ->get();

        //SPEC DETAIL
        $tenderProposalSpec = TenderProposalSpec::where('TPS_TPNo',$tenderProposal->TPNo)
                                ->whereHas('tenderSpec', function ($query) {
                                    $query->where('TDStockInd', 1);
                                })
                                ->get();

        // $tender = Tender::where('TDNo', $id)->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $yn = $this->dropdownService->yn();

        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod = $tender->TDPublishPeriod;

        $tenderDocuments = [];
        $tenderDetails = $tender->tenderDetailD;

        $template_milestone = $this->dropdownService->template_milestone();

        return view('perolehan.letter.acceptLetter.create',
        compact('tenderProposal','letterIntent','li_date','li_time','acceptStatus','yn','pentadbir','meetingNP',
        'tender','tenderProject_type','tempohSah','tenderJabatan','startDate','endDate','templates' , 'tenderDetails')
        );
    }

    public function createNoLI($id){

        $user = Auth::user();

        $tender = Tender::whereHas('tenderProposal', function($query){
            $query->where('TP_TPPCode','SB');
        })
        ->get()->pluck('TDTitle','TDNo')->map(function ($item, $key) {
            $code = Tender::where('TDNo', $key)->value('TDNo');
            return  $code . " - " . $item;
        });

        $tenderProposal = TenderProposal::where('TPNo',$id)->first();

        if($tenderProposal->letterIntent){

            $letterIntent = $tenderProposal->letterIntent;

            $li_date = Carbon::parse($letterIntent->LIDate)->format('Y-m-d');
            $li_time = Carbon::parse($letterIntent->LITime)->format('H:i');

        }else{
            $letterIntent = null;

            $li_date = null;
            $li_time = null;
        }

        $acceptStatus = $this->dropdownService->acceptStatus();
        $yn = $this->dropdownService->yt();

        $tender = $tenderProposal->tender;
        $jenis_projek = $this->dropdownService->jenis_projek();
        $tenderProject_type = $jenis_projek[$tender->TD_PTCode];

        $department = $this->dropdownService->department();
        $tenderJabatan = $department[$tender->TD_DPTCode];


        // Convert the date strings to DateTime objects
        $startDate = Carbon::parse($tender->TDPublishDate);
        $endDate = Carbon::parse($tender->TDClosingDate);

        // Calculate the difference between the two dates
        $tempohSah = $startDate->diff($endDate);

        $pentadbir = User::where('USName','!=','')->get()->pluck('USName','USCode');

        //LAMPIRAN
        $templates = TemplateFile::where('TF_TTCode','LA')
                    ->get();

        return view('perolehan.letter.acceptLetter.create',
        compact('tenderProposal','letterIntent','li_date','li_time','acceptStatus','yn','pentadbir',
        'tender','tenderProject_type','tempohSah','tenderJabatan','startDate','endDate','templates')
        );
    }

    public function view($id){

        $user = Auth::user();
        $webSetting = WebSetting::first();

        $SSTDigitalSign = $webSetting->SSTDigitalSign;

        $letterAccept = LetterAcceptance::where('LANo',$id)->first();
        // $letterIntent = LetterIntent::where('LINo',$id)->first();

        if($letterAccept->letterIntent){

            $letterIntent = $letterAccept->letterIntent;

            $li_date = Carbon::parse($letterIntent->LIDate)->format('Y-m-d');
            $li_time = Carbon::parse($letterIntent->LITime)->format('H:i');

        }else{
            $letterIntent = null;

            $li_date = null;
            $li_time = null;
        }

        $tenderProposal = $letterAccept->tenderProposal;
        $acceptStatus = $this->dropdownService->acceptStatus();

        $yn = $this->dropdownService->yt();

        $tender = $letterAccept->tenderProposal->tender;
        $jenis_projek = $this->dropdownService->jenis_projek();
        $tenderProject_type = $jenis_projek[$tender->TD_PTCode];

        $department = $this->dropdownService->department();
        $tenderJabatan = $department[$tender->TD_DPTCode];

        //#TBR-TBC
        // Convert the date strings to DateTime objects
        $startDate = Carbon::parse($tender->TDPublishDate);
        $endDate = Carbon::parse($tender->TDClosingDate);

        // Calculate the difference between the two dates
        $tempohSah = $startDate->diff($endDate);

        $pentadbir = User::where('USName','!=','')->get()->pluck('USName','USCode');

        //LAMPIRAN
        $templates = TemplateFile::where('TF_TTCode','LA')
                    ->get();

        // $routerLetterALA = '#';
        // $routerLetterCU = '#';
        // $routerLetterSBDL = '#';

        $routerLetterALA = route('perolehan.letter.generateLetter', ['id' => $letterAccept->LANo, 'code' => 'LA-ALA-DC']);
        $routerLetterCU = route('perolehan.letter.generateLetter', ['id' => $letterAccept->LANo, 'code' => 'LA-CU-DC']);
        $routerLetterSBDL = route('perolehan.letter.generateLetter', ['id' => $letterAccept->LANo, 'code' => 'LA-SBDL-DC']);

        // $fileAttachDownloadALA = FileAttach::where('FAFileType','LA-ALA')->first();
        // if(!empty($fileAttachDownloadALA)){
        //     $fileguid   = $fileAttachDownloadALA->FAGuidID;
        //     $routerLetterALA = route('file.download', ['fileGuid' => $fileguid]);
        // }

        // $fileAttachDownloadCU = FileAttach::where('FAFileType','LA-CU')->first();
        // if(!empty($fileAttachDownloadCU)){
        //     $fileguid   = $fileAttachDownloadCU->FAGuidID;
        //     $routerLetterCU = route('file.download', ['fileGuid' => $fileguid]);
        // }

        // $fileAttachDownloadSBDL = FileAttach::where('FAFileType','LA-SBDL')->first();
        // if(!empty($fileAttachDownloadSBDL)){
        //     $fileguid   = $fileAttachDownloadSBDL->FAGuidID;
        //     $routerLetterSBDL = route('file.download', ['fileGuid' => $fileguid]);
        // }

        //SPEC DETAIL
        $tenderProposalSpec = TenderProposalSpec::where('TPS_TPNo',$tenderProposal->TPNo)
                                ->whereHas('tenderSpec', function ($query) {
                                    $query->where('TDStockInd', 1);
                                })
                                ->get();

        //DEDUCTION FORM
        $paymentDeductType = $this->dropdownService->paymentDeductType();
        $letterAcceptDeduction = $letterAccept->letterAcceptanceDeduct ??  null;

        // $tender = Tender::where('TDNo', $id)->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $yn = $this->dropdownService->yn();

        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod = $tender->TDPublishPeriod;

        $tenderDocuments = [];
        $tenderDetails = $tender->tenderDetailD;

        $template_milestone = $this->dropdownService->template_milestone();

        $commentLog = $letterAccept->commentLog;
        $commentLog->each(function ($comment) {
            $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
        });

        return view('perolehan.letter.acceptLetter.view',
        compact('tenderProposal','letterIntent','li_date','li_time','acceptStatus','yn','pentadbir','letterAccept','letterAcceptDeduction',
            'tender','tenderProject_type','tempohSah','tenderJabatan','startDate','endDate','templates','tenderProposalSpec','paymentDeductType',
            'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms', 'tenderDocuments', 'tenderDetails', 'template_milestone',
            'routerLetterALA','routerLetterCU','routerLetterSBDL', 'commentLog', 'SSTDigitalSign')
        );
    }

    public function store(Request $request){

        $messages = [
            'title.required'            => 'Tajuk diperlukan.',
            'amountCBP.required'        => 'Sila letak 0 sekiranya tiada amaun.',
            // 'totalAmount.required'      => 'Lokasi diperlukan.',
            'insuranJaminan.required'   => 'Insurans/Jaminan diperlukan.',
            'bonPelaksanaan.required'   => 'Bon Pelaksanaan diperlukan.',
            'semakanLulus.required'     => 'Semakan Kelulusan diperlukan.',
            'slim.required'             => 'Program SL1M diperlukan.',
//            'pentadbir.required'        => 'Sila pilih pentadbir.',
            'perjanjian.required'       => 'Perlukan Perjanjian diperlukan.',
        ];

        $validation = [
            'title' => 'required',
            'amountCBP' => 'required',
            // 'totalAmount' => 'required',
            'insuranJaminan' => 'required',
            'bonPelaksanaan' => 'required',
            'semakanLulus' => 'required',
            'slim' => 'required',
//            'pentadbir' => 'required',
            'perjanjian' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $tenderController = new TenderController(new DropdownService(), new AutoNumber());

            $tenderProposal = TenderProposal::where('TPNo', $request->loa_TPNo)->first();

            $autoNumber = new AutoNumber();
            $LANo              = $autoNumber->generateLetterAcceptance();
            $intentNo          = $request->intentNo;
            $tenderNo          = $request->tenderNo;
            $loa_TPNo          = $request->loa_TPNo;
            $acceptStatus      = 'NEW';
            $title             = $request->title;
            $contractAmt         = $request->contractAmt;
            $amountCBP         = $request->amountCBP;
            $totalAmount       = $request->totalAmount;
            $insuranJaminan    = $request->insuranJaminan;
            $bonPelaksanaan    = $request->bonPelaksanaan;
            $semakanLulus      = $request->semakanLulus;
            $slim              = $request->slim;
//            $pentadbir         = $request->pentadbir;
            $perjanjian        = $request->perjanjian;

            if($tenderProposal->TPTotalAmt != $request->contractAmt){
                $totalPercent = (($tenderProposal->TPTotalAmt - $request->contractAmt) / $tenderProposal->TPTotalAmt) * 100;
            }
            else{
                $totalPercent = 0;
            }

            $letterAcccept = new LetterAcceptance();

            $letterAcccept->LANo            = $LANo           ;
            $letterAcccept->LA_LINo         = $intentNo       ;
            $letterAcccept->LA_TPNo         = $loa_TPNo       ;
            $letterAcccept->LAStatus        = $acceptStatus   ;
            $letterAcccept->LATitle         = $title          ;
            $letterAcccept->LAContractPeriod = $request->contractPeriod ?? 0;
            // $letterAcccept->LAContractAmt    = $request->contractAmt ?? 0;
            $letterAcccept->LAOriginalAmt    = $tenderProposal->TPTotalAmt ?? 0;
            $letterAcccept->LADiscPercent    = number_format($totalPercent, 2, '.', '');
            $letterAcccept->LAContractAmt    = $request->contractAmt ?? 0;
            $letterAcccept->LATaxAmt        = $amountCBP      ;
            $letterAcccept->LATotalAmount   = $totalAmount    ;
            $letterAcccept->LAInsurans      = $insuranJaminan ;
            $letterAcccept->LABon           = $bonPelaksanaan ;
            $letterAcccept->LAApproval      = $semakanLulus   ;
            $letterAcccept->LASLIM          = $slim           ;
//            $letterAcccept->LA_USCode     = $pentadbir      ;
            $letterAcccept->LAAgreement     = $perjanjian     ;

            $letterAcccept->LACB = $user->USCode;
            $letterAcccept->LAMB = $user->USCode;
            $letterAcccept->save();

            $project = Project::where('PT_TPNo', $loa_TPNo)->first();
            $project->PT_LANo = $LANo;
            $project->PT_PPCode = 'MLA';
            $project->save();


            //LAMPIRAN INSERT AS DEFAULT
            $templates = TemplateFile::where('TF_TTCode','LA')
                        ->where('TFActive','1')
                        ->get();

            foreach($templates as $index => $template){

                $index++;

                $tempTitle = $template->TFTitle;
                $tempMTCode = $template->TF_MTCode;

                $latest = LetterAcceptanceDet::where('LAD_LANo',$LANo)
                            ->orderBy('LADNo','desc')
                            ->first();

                if($latest){
                    $LADNo = $tenderController->increment3digit($latest->LADNo);
                }
                else{
                    $LADNo = $LANo.'-'.$formattedCounter = sprintf("%03d", 1);
                }


                $letterAccceptDet = new LetterAcceptanceDet();
                $letterAccceptDet->LADNo = $LADNo;
                $letterAccceptDet->LAD_LANo = $LANo;
                $letterAccceptDet->LADSeq = $index;
                $letterAccceptDet->LAD_MTCode = $tempMTCode;
                $letterAccceptDet->LADTitle = $tempTitle;
                $letterAccceptDet->LADCompleteP = 0;

                if($tempMTCode == 'DF'){
                    $letterAccceptDet->LADCompleteU = 1;

                }else{
                    $letterAccceptDet->LADCompleteU = 0;

                }

                $letterAccceptDet->LADCB = $user->USCode;
                $letterAccceptDet->LADMB = $user->USCode;
                $letterAccceptDet->save();

            }


            //BAYARAN INSERT AS DEFAULT
            $templatePayments = TenderPaymentDeduction::where('TDP_TDNo',$tenderNo)
                                ->get();

            foreach($templatePayments as $index => $templatePay){

                $index++;

                $tempTDPDesc = $templatePay->TDPDesc;
                $tempPDTCode = $templatePay->TDP_PDTCode;
                $tempTDPAmt  = $templatePay->TDPAmt;
                $tempTDPTermDesc  = $templatePay->TDPTermDesc;

                $letterAccceptDeduct = new LetterAcceptanceDeduction();
                $letterAccceptDeduct->LAP_LANo = $LANo;
                $letterAccceptDeduct->LAPSeq = $index;
                $letterAccceptDeduct->LAP_PDTCode = $tempPDTCode;
                $letterAccceptDeduct->LAPDesc = $tempTDPDesc;
                $letterAccceptDeduct->LAPAmt = $tempTDPAmt;
                $letterAccceptDeduct->LAPTermDesc = $tempTDPTermDesc;
                $letterAccceptDeduct->LAPCB = $user->USCode;
                $letterAccceptDeduct->LAPMB = $user->USCode;
                $letterAccceptDeduct->save();

            }

            //save gs
            //then simpan masuk table dc.
            $generateGS = $this->generateGS($letterAcccept->LANo);

            $imagePath = public_path('assets/images/chop/dbkl_chop.png');
            $imageContent = file_get_contents($imagePath);
            $stamp = base64_encode($imageContent);

            $autoNumber = new AutoNumber();

            $digitalCert = new DigitalCert();
            $digitalCert->DCNo = $autoNumber->generateDCNo();
            $digitalCert->DCRefNo = $letterAcccept->LANo;
            $digitalCert->DC_DCTCode = 'LA-LA';
            $digitalCert->DCProcess = 0;
            $digitalCert->DCProcessDate = Carbon::now();
            $digitalCert->DCCB = Auth::user()->USCode;
            $digitalCert->save();

            //then sceduler run DC == 0,
            //once DC==1 , then generate -DC , then bila -DC ada, button papar untuk
            //4 file keluar.
            //baru boleh HANTAR.

            // INI UNTUK DIGITALCERT AFTER APPROVAL
            // try{
            //     $custom = new Custom();
            //     $jsonArray = $custom->generateDigitalCert($generatePDF, $stamp , $letterAcccept->LANo , 'LA-DC');

            // }catch (\Exception $e) {

            //     return response()->json([
            //         'error' => '1',
            //         'message' => $e->getMessage()
            //     ], 400);
            // }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.letter.accept.view',[$LANo,'flag'=>3]),
                'message' => 'Maklumat surat setuju terima berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat surat setuju terima tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }
    }

    public function detailAdd($id){

        $jenis_doc = $this->dropdownService->jenis_docD();

        $letterAccept = LetterAcceptance::where('LANo',$id)->first();

        return view('perolehan.letter.acceptLetter.detailAdd',
        compact('jenis_doc','letterAccept')
        );
    }

    public function detailUpdate(Request $request){
        $messages = [
            'fileType.required' => 'Jenis Dokumen diperlukan.',
            'title.required'    => 'Tajuk Kandungan diperlukan.',
        ];

        $validation = [
            'fileType' => 'required',
            'title' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $tenderController = new TenderController(new DropdownService(), new AutoNumber());

            $user = Auth::user();
            $fileType = $request->fileType;
            $title = $request->title;
            $acceptNo = $request->acceptNo;
            $intentNo = $request->intentNo;

            $latest = LetterAcceptanceDet::where('LAD_LANo',$acceptNo)
                        ->orderBy('LADNo','desc')
                        ->first();

            if($latest){
                $LADNo = $tenderController->increment3digit($latest->LADNo);
                $LADSeq = $latest->LADSeq + 1;
            }
            else{
                $LADNo = $acceptNo.'-'.$formattedCounter = sprintf("%03d", 1);
                $LADSeq = 1;
            }


            $letterAccceptDet = new LetterAcceptanceDet();
            $letterAccceptDet->LADNo = $LADNo;
            $letterAccceptDet->LAD_LANo = $acceptNo;
            $letterAccceptDet->LADSeq = $LADSeq;
            $letterAccceptDet->LAD_MTCode = $fileType;
            $letterAccceptDet->LADTitle = $title;

            $letterAccceptDet->LADCB = $user->USCode;
            $letterAccceptDet->LADMB = $user->USCode;
            $letterAccceptDet->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.letter.accept.view',[$acceptNo,'flag'=>4]),
                'message' => 'Maklumat lampiran berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat lampiran tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }
    }

    public function detailDelete($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $letterAcceptDet = LetterAcceptanceDet::where('LADNo',$id)->first();

            $LANo = $letterAcceptDet->LAD_LANo;

            $letterAcccept = LetterAcceptance::where('LANo',$LANo)->first();

            $currentAcceptDet = LetterAcceptanceDet::where('LAD_LANo',$LANo)
                        ->get();
            $seq = 0;
            foreach($currentAcceptDet as $current){

                if($id == $current->LADNo){
                    $current->delete();
                }else{

                    $seq++;
                    $current->LADSeq = $seq;
                    $current->save();

                }

            }

            DB::commit();

            return redirect()->route('perolehan.letter.accept.view', [$letterAcccept->LANo,'flag' => 4]);

            // return response()->json([
            //     'success' => '1',
            //     'redirect' => route('perolehan.letter.accept.view',[$intentNo,'flag'=>4]),
            //     'message' => 'Maklumat lampiran berjaya ditambah.'
            // ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat lampiran tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }
    }

    public function paymentDeductionUpdate(Request $request){

        $messages = [
            // 'paymentType.required'    => 'Jenis potongan diperlukan.',
            // 'paymentDesc.required' => 'Maklumat potongan diperlukan.',
            // 'paymentAmount.required'    => 'Amaun potongan diperlukan.',
        ];

        $validation = [
            // 'paymentType' => 'required',
            // 'paymentDesc' => 'required',
            // 'paymentAmount' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $intentNo               = $request->intentNo;
            $acceptNo               = $request->acceptNo;


            if(!empty($request->detailId)){

                $paymentType            = $request->paymentType;
                $detailId               = $request->detailId;
                $paymentDesc            = $request->paymentDesc;
                $paymentAmount          = $request->paymentAmount;
                $paymentKlausa          = $request->paymentKlausa;

                $oldPaymentDeduct = LetterAcceptanceDeduction::where('LAP_LANo',$acceptNo)->get();

                //DELETE PAYMENT DEDUCTION
                foreach ($oldPaymentDeduct as $oPaymentDeduct) {
                    if (!in_array($oPaymentDeduct->LAPID, $detailId)) {
                        // DELETE
                        $oPaymentDeduct->delete();

                    }
                }

                //CHECK PAYMENT DEDUCTION DATA
                $count = 0;
                $sequence = 0;

                foreach($detailId as $did){

                    $type = $paymentType[$count];
                    $desc = $paymentDesc[$count];
                    $amount = $paymentAmount[$count];
                    $termDesc = $paymentKlausa[$count];

                    if($did == "new")
                        $did = 0;

                    $sequence++;
                    $existPaymentDeduct = LetterAcceptanceDeduction::where('LAP_LANo',$acceptNo)
                                        ->where('LAPID',$did)
                                        ->first();


                    if(empty($existPaymentDeduct)){
                        //INSERT
                        $newPaymentDeduct = new LetterAcceptanceDeduction();
                        $newPaymentDeduct->LAP_LANo       = $acceptNo;
                        $newPaymentDeduct->LAPSeq         = $sequence;
                        $newPaymentDeduct->LAP_PDTCode    = $type;
                        $newPaymentDeduct->LAPDesc        = $desc;
                        $newPaymentDeduct->LAPAmt         = $amount;
                        $newPaymentDeduct->LAPTermDesc    = $termDesc;

                        $newPaymentDeduct->LAPCB         = $user->USCode;
                        $newPaymentDeduct->LAPMB         = $user->USCode;
                        $newPaymentDeduct->save();

                    }else{
                        //UPDATE
                        $existPaymentDeduct->LAPSeq         = $sequence;
                        $existPaymentDeduct->LAP_PDTCode    = $type;
                        $existPaymentDeduct->LAPDesc        = $desc;
                        $existPaymentDeduct->LAPAmt         = $amount;
                        $existPaymentDeduct->LAPTermDesc    = $termDesc;
                        $existPaymentDeduct->LAPMB         = $user->USCode;
                        $existPaymentDeduct->save();
                    }


                    $count++;


                }

            }

            if($request->updateStatus == '1'){

                $letterAccept = LetterAcceptance::where('LANo',$acceptNo)->first();

                $letterAccept->LAStatus = "SUBMIT";
                $letterAccept->save();

                $trpcode = "LA";
                $tenderProposal = TenderProposal::where('TPNo',$letterAccept->LA_TPNo)->first();
                $tenderProposal->TP_TRPCode = $trpcode;
                $tenderProposal->save();

                $project = Project::where('PT_TPNo', $tenderProposal->TPNo)
                    ->where('PT_TDNo', $tenderProposal->tender->TDNo)
                    ->first();

                $project->PT_PPCode = 'LAS';
                $project->save();

                $tender = $tenderProposal->tender;

                // if($tenderProposal->tender->TDLOI != 1){
                if($tender->meetingTender->BMTLOI != 1){
                    $contractor = Contractor::where('CONo',$tenderProposal->TP_CONo)->first();

                    $emailLog = new EmailLog();
                    $emailLog->ELCB 	= $user->USCode;
                    $emailLog->ELType 	= 'Activate Code';
                    // Send Email
                    $emailData = array(
                        'id' => $contractor->CONo,
                        'name'  => $contractor->COName ?? '',
                        'email' => $contractor->COEmail,
                        'activationCode' => $project->PTActivationCode ?? '',
                        'domain' => config('app.url'),
                        'projectCode' => $project->PTNo,
                    );

                    try {
                        Mail::send(['html' => 'email.newUserProject'], $emailData, function($message) use ($emailData) {
                            $message->to($emailData['email'] ,$emailData['name'])->subject('Pengaktifan Akaun Projek');
                        });

                        $emailLog->ELMessage = 'Success';
                        $emailLog->ELSentStatus = 1;
                        $emailLog->save();
                    } catch (\Exception $e) {
                        $emailLog->ELMessage = $e->getMessage();
                        $emailLog->ELSentStatus = 2;
                        $emailLog->save();
                        return response()->json([
                            'error' => '1',
                            'message' => 'Penghantaran email gagal!'.$e->getMessage()
                        ], 400);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.letter.accept.view',[$acceptNo,'flag'=>5]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateSubmit(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $acceptNo               = $request->acceptNo;

            $letterAccept = LetterAcceptance::where('LANo',$acceptNo)->first();
//            $letterAccept->LAStatus = "SUBMIT";
//            $letterAccept->save();

//            $trpcode = "LA";
            $tenderProposal = TenderProposal::where('TPNo',$letterAccept->LA_TPNo)->first();
//            $tenderProposal->TP_TRPCode = $trpcode;
//            $tenderProposal->save();

            $project = Project::where('PT_TPNo', $tenderProposal->TPNo)
                ->where('PT_TDNo', $tenderProposal->tender->TDNo)
                ->first();

//            $project->PT_PPCode = 'LAS-RQ';
            $project->PT_PPCode = 'LAS';
            $project->save();

            $this->processSentActivation($acceptNo);

//            $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//            $approvalController->storeApproval($acceptNo, 'LA-SM');
            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.letter.accept.view',[$acceptNo,'flag'=>5]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function processSentActivation($id){
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $acceptNo               = $id;

            $letterAccept = LetterAcceptance::where('LANo',$acceptNo)->first();
            $letterAccept->LAStatus = "SUBMIT";
            $letterAccept->save();

            $trpcode = "LA";
            $tenderProposal = TenderProposal::where('TPNo',$letterAccept->LA_TPNo)->first();
            $tenderProposal->TP_TRPCode = $trpcode;
            $tenderProposal->save();

            $project = Project::where('PT_TPNo', $tenderProposal->TPNo)
                ->where('PT_TDNo', $tenderProposal->tender->TDNo)
                ->first();

            $contractor = Contractor::where('CONo',$tenderProposal->TP_CONo)->first();

            $custom = new Custom();
            $phoneNo = $contractor->COPhone;
            $title = $tenderProposal->tender->TDTitle;
            $refNo = $tenderProposal->tender->TDNo;
            $refNo2 = $project->PTNo;
            $refNo3 = $project->PTActivationCode;

            $tender = $tenderProposal->tender;

            // if($tenderProposal->tender->TDLOI != 1){
            if($tender->meetingTender->BMTLOI != 1){

                $emailLog = new EmailLog();
                $emailLog->ELCB 	= $user->USCode;
                $emailLog->ELType 	= 'Activate Code';
                // Send Email
                $emailData = array(
                    'id' => $contractor->CONo,
                    'name'  => $contractor->COName ?? '',
                    'email' => $contractor->COEmail,
                    'activationCode' => $project->PTActivationCode ?? '',
                    'domain' => config('app.url'),
                    'projectCode' => $project->PTNo,
                );

                try {
                    Mail::send(['html' => 'email.newUserProject'], $emailData, function($message) use ($emailData) {
                        $message->to($emailData['email'] ,$emailData['name'])->subject('Pengaktifan Akaun Projek');
                    });

                    $emailLog->ELMessage = 'Success';
                    $emailLog->ELSentStatus = 1;
                    $emailLog->save();
                } catch (\Exception $e) {
                    $emailLog->ELMessage = $e->getMessage();
                    $emailLog->ELSentStatus = 2;
                    $emailLog->save();
                    return response()->json([
                        'error' => '1',
                        'message' => 'Penghantaran email gagal!'.$e->getMessage()
                    ], 400);
                }

//                $sendWSPTActive = $custom->sendWhatsappLetter('active_project_notice',$phoneNo,$title,$refNo,$refNo2,$refNo3); //CONTOHWSLETTER
            }



//            $sendWSSST = $custom->sendWhatsappLetter('sst_notice',$phoneNo,$title,$refNo); //CONTOHWSLETTER
            DB::commit();

            return true;
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return false;
        }
    }

    public function edit($id){

        $user = Auth::user();
        $letterIntent = LetterIntent::where('LINo', $id)
                        ->first();

        $li_date = Carbon::parse($letterIntent->LIDate)->format('d/m/Y');
        $li_time = Carbon::parse($letterIntent->LITime)->format('H:i');

        return view('perolehan.letter.intentLetter.edit',
                compact('letterIntent','li_date','li_time')
        );
    }

    public function update(Request $request){

        $messages = [
            'title.required'            => 'Tajuk diperlukan.',
            'amountCBP.required'        => 'Sila letak 0 sekiranya tiada amaun.',
            // 'totalAmount.required'      => 'Lokasi diperlukan.',
            'insuranJaminan.required'   => 'Insurans/Jaminan diperlukan.',
            'bonPelaksanaan.required'   => 'Bon Pelaksanaan diperlukan.',
            'semakanLulus.required'     => 'Semakan Kelulusan diperlukan.',
            'slim.required'             => 'Program SL1M diperlukan.',
//            'pentadbir.required'        => 'Sila pilih pentadbir.',
            'perjanjian.required'       => 'Perlukan Perjanjian diperlukan.',
//            'lampiran.required'       => 'Bahagian lampiran perlukan diselesaikan.',
//            'lampiran.*.accepted'       => 'Bahagian lampiran perlukan diselesaikan.',
        ];

        $validation = [
            'title' => 'required',
            'amountCBP' => 'required',
            // 'totalAmount' => 'required',
            'insuranJaminan' => 'required',
            'bonPelaksanaan' => 'required',
            'semakanLulus' => 'required',
            'slim' => 'required',
//            'pentadbir' => 'required',
            'perjanjian' => 'required',
//            'lampiran' => 'required',
//            'lampiran.*' => 'accepted',

        ];
        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $intentNo          = $request->intentNo;
            $LANo              = $request->acceptNo;
            $title             = $request->title;
            $contractAmt       = $request->contractAmt;
            $amountCBP         = $request->amountCBP;
            $totalAmount       = $request->totalAmount;
            $insuranJaminan    = $request->insuranJaminan;
            $bonPelaksanaan    = $request->bonPelaksanaan;
            $semakanLulus      = $request->semakanLulus;
            $slim              = $request->slim;
//            $pentadbir         = $request->pentadbir;
            $perjanjian        = $request->perjanjian;
            $sendNow           = $request->sendNow;

            $letterAccept = LetterAcceptance::where('LANo',$LANo)->first();
            $letterAccept->LATitle          = $title          ;
            $letterAccept->LAContractAmt    = $contractAmt    ;
            $letterAccept->LATaxAmt         = $amountCBP      ;
            $letterAccept->LATotalAmount    = $totalAmount    ;
            $letterAccept->LAInsurans       = $insuranJaminan ;
            $letterAccept->LABon            = $bonPelaksanaan ;
            $letterAccept->LAApproval       = $semakanLulus   ;
            $letterAccept->LASLIM           = $slim           ;
            $letterAccept->LAContractPeriod = $request->contractPeriod ?? 0 ;
//            $letterAccept->LA_USCode     = $pentadbir      ;
            $letterAccept->LAAgreement   = $perjanjian     ;

            if($sendNow == '1'){
                $letterAccept->LAStatus = "SUBMIT";

                $trpcode = "LA";
                $tenderProposal = TenderProposal::where('TPNo',$letterAccept->LA_TPNo)->first();
                $tenderProposal->TP_TRPCode = $trpcode;
                $tenderProposal->save();
            }

            $letterAccept->LAMB = $user->USCode;
            $letterAccept->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.letter.accept.view',[$LANo,'flag'=>2]),
                'message' => 'Maklumat surat setuju terima berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat surat setuju terima tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function printLetter($id){

        $user = Auth::user();

        $webSetting = WebSetting::first();

        $letterAccept = LetterAcceptance::where('LANo',$id)->first();

        $tenderProposal  = TenderProposal::where('TPNo' , $letterAccept->LA_TPNo)->first();

        $tenderApplication = TenderApplication::where('TANo' , $tenderProposal->TP_TANo)->first();

        $contractor = Contractor::where('CONo', $tenderApplication->TA_CONo)->first();

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$letterAccept->tenderProposal->contractor->COReg_StateCode];

        $dpt = $this->dropdownService->department();
        $department = $dpt[$tenderApplication->tender->TD_DPTCode];

        $responseDate = \Carbon\Carbon::parse($letterAccept->LAResponseDate)->format('d/m/Y');

        $dateExp = \Carbon\Carbon::parse($letterAccept->LAResponseDate)
                    ->addDays($webSetting->SSTExpDay)
                    ->format('d/m/Y');

        $bondPercent = $letterAccept->LATotalAmount * $webSetting->SSTBondPercent;

        $bondInWords = $this->DigitToWords($bondPercent);

        $insurance  = $webSetting->SSTInsuranceAmt;

        $insuranceInWords = $this->DigitToWords($insurance);

        $contractAddress = $contractor->CORegAddr . ", " . $contractor->CORegPostcode . ", " . $contractor->CORegCity . ", " . $COState ;

        $qrvalue = $letterAccept->LANo . " | " . $contractor->COName . " | " . $contractAddress . " | " . $tenderProposal->tender->TDTitle;

        $qrCode = QRcode::size(80)->generate($qrvalue);

        $name = "SURAT SETUJU TERIMA";

        $template = "LETTER";
        $download = true; //true for download or false for view
        $templateName = "ACCEPTANCE"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName','letterAccept' , 'COState', 'department' ,
        'responseDate' ,'dateExp' , 'bondPercent' , 'bondInWords'  , 'insurance' , 'insuranceInWords' , 'qrCode')
        );
        $response = $this->generatePDF($view,$download,$name);

        return $response;

    }

    public function updateStatus($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $dateNow = Carbon::now()->format('d/m/Y');

            $letterAccept = LetterAcceptance::where('LANo',$id)->first();
            $letterAccept->LAStatus = "SUBMIT";
            $letterAccept->LASubmitBy = $user->USCode;
            $letterAccept->LASubmitDate = $dateNow;
            $letterAccept->LAMB = $user->USCode;
            $letterAccept->save();

            $this->sendNotification($letterAccept,'S');

            DB::commit();

            return redirect()->route('perolehan.letter.accept.index');

            // return response()->json([
            //     'success' => '1',
            //     'redirect' => route('perolehan.letter.intent.index'),
            //     'message' => 'Maklumat surat niat berjaya dikemaskini.'
            // ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat surat niat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function letterAcceptDatatable(Request $request){

        $user = Auth::user();

        $query = LetterAcceptance::select('TRLetterAcceptance.*','TP_TDNo','TDTitle')
                                    ->leftjoin('TRTenderProposal','TPNo', 'LA_TPNo')
                                    ->leftjoin('TRTender','TP_TDNo', 'TDNo')
                                    ->orderBy('LANo', 'DESC')->get();

        $letterStatus = $this->dropdownService->letterStatus();

        return DataTables::of($query)
            ->editColumn('LANo', function($row) {

                $route = route('perolehan.letter.accept.view',[$row->LANo]);
                $result = '<a href="'.$route.'">'.$row->LANo.'</a>';

                return $result;
            })
            ->addColumn('status', function($row) use ($letterStatus) {

                return $letterStatus[$row->LAStatus];
            })
            ->editColumn('LAMD', function($row) {
                return [
                    'display' => e(carbon::parse($row->LAMD)->format('d/m/Y h:ia')),
                    'timestamp' => carbon::parse($row->LAMD)->timestamp
                ];

            })
            ->addColumn('action', function($row) {

                $routePrint = route('perolehan.letter.accept.printLetter',[$row->LANo]);

                $result = '&nbsp<a target="_blank" href="'.$routePrint.'" class="new modal-trigger waves-effect waves-light btn btn-light-primary"><i class="material-icons left">print</i>Cetak</a>';

                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['status', 'action','LANo'])
            ->make(true);
    }

    public function letterIntentDatatable(Request $request){

        $user = Auth::user();

        $query = LetterIntent::doesntHave('letterAcceptance')
                ->whereHas('tenderProposal',function($query){
                    $query->where('TP_TRPCode','LIA');
                })
                ->get();

        $letterStatus = $this->dropdownService->letterStatus();
        $count = 0;

        return DataTables::of($query)
            ->editColumn('LINo', function($row) {

                $route = route('perolehan.letter.intent.edit',[$row->LINo]);
                $result = '<a href="'.$route.'">'.$row->LINo.'</a>';

                return $result;
            })
            ->editColumn('LIDate', function($row) {
                $formattedDate = null;
                if($row->LIDate != null){
                    $carbonDatetime = Carbon::parse($row->LIDate);
                    $formattedDate = $carbonDatetime->format('d/m/Y');
                }

                return $formattedDate;
            })
            ->editColumn('LITime', function($row) {
                $formattedDate = null;
                if($row->LITime != null){
                    $carbonDatetime = Carbon::parse($row->LITime);
                    $formattedDate = $carbonDatetime->format('h:i A');
                }

                return $formattedDate;
            })
            ->addColumn('status', function($row) use ($letterStatus) {

                return $letterStatus[$row->LIStatus];
            })
            ->addColumn('action', function($row) {


                if(isset($row->letterAcceptance)){
                    $route = route('perolehan.letter.accept.view',[$row->letterAcceptance->LANo]);

                    $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light btn btn-light-primary">Lihat Surat Setuju Terima</a>';

                }else{
                    $route = route('perolehan.letter.accept.create',[$row->LINo]);

                    $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light btn btn-primary">Sedia Surat Setuju Terima</a>';

                }
                return $result;
            })
            ->rawColumns(['status', 'action','LINo'])
            ->make(true);
    }

    public function updateStatusProposal(Request $request){

        $messages = [
            'LANo.required'                 => 'LANo diperlukan.',
//            'templateMilestone.required'    => 'LANo diperlukan.',
        ];

        $validation = [
            'LANo'              => 'required',
//            'templateMilestone' => 'required',

        ];
        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tenderController = new TenderController(new DropdownService(), new AutoNumber());

            $LANo = $request->LANo;

            $letterAcccept = LetterAcceptance::where('LANo',$LANo)->first();
            $letterAcccept->LAStatus = 'CONFIRM';
            $letterAcccept->LAConfirmDate = Carbon::now();
            $letterAcccept->LAConfirmBy = $user->USCode;
//            $letterAcccept->LA_TMCode = $request->templateMilestone;
            $letterAcccept->save();

            $tenderPropNo = $letterAcccept->tenderProposal->TPNo;


            $trpcode = "LAC";
            $tenderProposal = TenderProposal::where('TPNo',$tenderPropNo)->first();
            $tenderProposal->TP_TRPCode = $trpcode;
            $tenderProposal->save();

            $project = Project::where('PT_TPNo', $tenderPropNo)->first();
            $project->PT_PPCode = 'LAC';
            $project->save();

            $projectTenderMilestones = ProjectTenderMilestone::where('PTDM_PTDNo', $tenderProposal->tender->TD_PTDNo)
                ->orderBy('PTDMSeq')->get();

            foreach ($projectTenderMilestones as $projectTenderMilestone){
                $latest = ProjectMilestone::where('PM_PTNo',$project->PTNo)
                    ->orderBy('PMNo','desc')
                    ->first();

                if($latest){
                    $PMNo = $tenderController->increment3digit($latest->PMNo);
                }
                else{
                    $PMNo = $project->PTNo.'-'.$formattedCounter = sprintf("%03d", 1);
                }

                $projectMilestone = new ProjectMilestone();
                $projectMilestone->PMNo = $PMNo;
                $projectMilestone->PMRefType = 'PJ';
                $projectMilestone->PMRefNo = $project->PTNo;
                $projectMilestone->PM_PTNo = $project->PTNo;
                $projectMilestone->PMSeq = $projectTenderMilestone->PTDMSeq;
                $projectMilestone->PMDesc = $projectTenderMilestone->PTDMDesc;
                $projectMilestone->PMWorkPercent = $projectTenderMilestone->PTDMWorkPercent;
                $projectMilestone->PMWorkDay = $projectTenderMilestone->PTDMWorkDay;
                $projectMilestone->PMClaimInd = $projectTenderMilestone->PTDMClaimable;
                $projectMilestone->PMEstimateAmt = $projectTenderMilestone->PTDEstimateAmt;
                $projectMilestone->PMCB = $user->USCode;
                $projectMilestone->save();
            }
//            $contractorCode = $tenderProposal->TP_CONo;
//            $letterAccceptNo = $letterAcccept->LANo;
//            $projectPSCode = 'N'; //refer ProjectMilestoneStatus
//            $projectStatus = 'NEW';
//            $activationCode = $this->createActivationCode();
//
//            $dateNow = Carbon::now();
//
//            $tender = $tenderProposal->tender;
//            $projectType = $tender->TD_PTCode;
//            $contractPeriodInMonths  = $tender->TDContractPeriod;

//            //CREATE PROJECT
//            $autoNumber                     = new AutoNumber();
//            $projectNo                      = $autoNumber->generateProjectNo();
//            $project                        = new Project();
//            $project->PTNo                  = $projectNo;
//            $project->PTCode                = $projectNo;
//            $project->PT_CONo               = $contractorCode;
//            $project->PT_TPNo               = $tenderPropNo;
//            $project->PT_LANo               = $letterAccceptNo;
//            $project->PTActivationCode      = $activationCode;
//            $project->PTActivationDate      = $dateNow;
//            $project->PTActivationSent      = 1;
//            $project->PT_PSCode             = $projectPSCode;
//            $project->PTStatus              = $projectStatus;
//            $project->PTPriority            = 0;
//            $project->PTProgress            = 1;
//            $project->PTCB                  = $user->USCode;
//
//            $project->save();
//
//
//            // Calculate the total number of days by adding the contract period in months to $dateNow
//            $dateAfterContractPeriod = $dateNow->copy()->addMonths($contractPeriodInMonths);
//
//            // Calculate the difference in days between $dateNow and $dateAfterContractPeriod
//            $totalDays = $dateNow->diffInDays($dateAfterContractPeriod);
//
//
//            //LAMPIRAN INSERT AS DEFAULT
//            $templatesMilestone = TemplateMilestone::where('TMCode',$request->templateMilestone)
//                        ->first();
//
//            foreach($templatesMilestone->templateMilestoneDet as $index => $template){
//
//                $index++;
//
//                $tempTitle = $template->TMDDesc;
//                $tempSeq = $template->TMDSeq;
//                $tempPercent = $template->TMDPercent;
//
//                $workDay = round($totalDays * ($tempPercent / 100));
//
//                $latest = ProjectMilestone::where('PM_PTNo',$projectNo)
//                            ->orderBy('PMNo','desc')
//                            ->first();
//
//                if($latest){
//                    $PMNo = $this->tenderController->increment3digit($latest->PMNo);
//                }
//                else{
//                    $PMNo = $projectNo.'-'.$formattedCounter = sprintf("%03d", 1);
//                }
//
//                $projectMilestone = new ProjectMilestone();
//                $projectMilestone->PMNo = $PMNo;
//                $projectMilestone->PMRefType = 'PJ';
//                $projectMilestone->PMRefNo = $projectNo;
//                $projectMilestone->PM_PTNo = $projectNo;
//                $projectMilestone->PMSeq = $tempSeq;
//                $projectMilestone->PMDesc = $tempTitle;
//                $projectMilestone->PMWorkDay = $workDay;
//                $projectMilestone->PMCB = $user->USCode;
//                $projectMilestone->save();
//
//            }
//

           $contractor = Contractor::where('CONo',$project->PT_CONo)->first();

           $this->sendMailSSTStatus($contractor);


            // $this->sendNotification($letterAcccept,'D');

            DB::commit();

//            return redirect()->route('perolehan.letter.accept.index');

             return response()->json([
                 'success' => '1',
                 'redirect' => route('perolehan.letter.accept.index'),
                 'message' => 'Projek telah berjaya dicipta berjaya dikemaskini.'
             ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat surat niat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function templateLADatatable(Request $request){
        $query = TemplateFile::where('TF_TTCode', 'LA')
                ->whereIn('TF_MTCode', ['DF'])
                ->get();

        $letterAcceptNo = $request->input('letterAcceptNo');

        return datatables()->of($query)
            ->editColumn('TF_TTCode', function($row){
                $jenis_doc = $this->dropdownService->jenis_doc();
                return $jenis_doc[$row->TF_MTCode] ?? "";
            })
            ->addColumn('action', function($row) use($letterAcceptNo){
                $route = route('perolehan.letter.accept.add.lampiranTemplate', [$letterAcceptNo, $row->TFNo]);

                $result = '<a href="'.$route.'" class="btn btn-light-primary"><i class="material-icons">add_circle_outline</i></a>';

                return $result;
            })
            ->rawColumns(['TF_TTCode', 'action'])
            ->make(true);
    }

    public function addLampiranTemplate($id, $tno){ //id = LANo,  tno = templateNo/TFNo

        $templateFile = null;

        if($tno !== 0){
            $templateFile = TemplateFile::where('TFNo', $tno)->first();
        }

        $letterAcceptance = LetterAcceptance::where('LANo',$id)->first();
        $jenis_doc = $this->dropdownService->jenis_docDF();

        return view('perolehan.letter.acceptLetter.includes.chooseTemplate',
                compact('templateFile','letterAcceptance','jenis_doc')
        );

    }

    public function storeLampiranTemplate(Request $request, $id){

        $messages = [
            'title.required'        => 'Tajuk dokumen diperlukan.',
            'jenis_doc.required'            => 'Jenis dokumen diperlukan.',
        ];

        $validation = [
            'title' => 'required',
            'jenis_doc' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tenderController = new TenderController(new DropdownService(), new AutoNumber());

            $letterAcceptNo    = $request->letterAcceptNo;
            $title             = $request->title;
            $jenis_doc         = $request->jenis_doc;

            $letterAccept = LetterAcceptance::where('LANo',$letterAcceptNo)->first();
            $LANo = $letterAccept->LANo;

            $latest = LetterAcceptanceDet::where('LAD_LANo',$LANo)
                        ->orderBy('LADNo','desc')
                        ->first();

            $latestSequenceNumber = LetterAcceptanceDet::where('LAD_LANo', $LANo)
                        ->max('LADSeq');

            if($latest){
                $LADNo = $tenderController->increment3digit($latest->LADNo);
                $index = $latestSequenceNumber + 1;
            }
            else{
                $LADNo = $LANo.'-'.$formattedCounter = sprintf("%03d", 1);
                $index = 1;
            }


            $letterAccceptDet = new LetterAcceptanceDet();
            $letterAccceptDet->LADNo = $LADNo;
            $letterAccceptDet->LAD_LANo = $LANo;
            $letterAccceptDet->LADSeq = $index;
            $letterAccceptDet->LAD_MTCode = $jenis_doc;
            $letterAccceptDet->LADTitle = $title;

            $letterAccceptDet->LADCB = $user->USCode;
            $letterAccceptDet->LADMB = $user->USCode;
            $letterAccceptDet->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.letter.accept.view',[$letterAccept->LANo,'flag'=>4]),
                'message' => 'Maklumat lampiran berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat lampiran tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    function sendNotification($letterAccept,$status){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $tenderProposal = TenderProposal::where('TPNo',$letterAccept->LA_TPNo)->first();
            $tender = Tender::where('TDNo',$tenderProposal->TP_TDNo)->first();

            if($status == 'S'){ //LA HANTAR

                $notiType = "LA";

                //##NOTIF-031
                //SEND NOTIFICATION TO PIC - PELAKSANA,PEROLEHAN
                $tenderPIC = $tender->tenderPIC_PT;

                $title = "Penganjuran Surat Setuju Terima - $letterAccept->LANo";
                $desc = "Perhatian, surat setuju terima $letterAccept->LANo bagi cadangan $tenderProposal->TPNo telah berjaya dihantar.";

                if(!empty($tenderPIC)){

                    foreach($tenderPIC as $pic){

                        if($pic->TPICType == 'T'){
                            $pelaksanaType = "SO";

                        }else if($pic->TPICType == 'P'){
                            $pelaksanaType = "PO";

                        }

                        $notification = new Notification();
                        $notification->NO_RefCode = $pic->TPIC_USCode;
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


            }
            else if($status == 'D'){ //LA SELESAI

                $notiType = "LAC";

                //##NOTIF-034
                //SEND NOTIFICATION TO PIC - PELAKSANA,PEROLEHAN
                $tenderPIC = $tender->tenderPIC;

                $title = "Proses Surat Setuju Terima Selesai - $letterAccept->LANo";
                $desc = "Perhatian, surat setuju terima $letterAccept->LANo bagi cadangan $tenderProposal->TPNo telah berjaya dihantar dan sudah selesai.";

                if(!empty($tenderPIC)){

                    foreach($tenderPIC as $pic){

                        if($pic->TPICType == 'T'){
                            $pelaksanaType = "SO";

                            $notification = new Notification();
                            $notification->NO_RefCode = $pic->TPIC_USCode;
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

                        }else if($pic->TPICType == 'P'){
                            $pelaksanaType = "PO";

                            $notification = new Notification();
                            $notification->NO_RefCode = $pic->TPIC_USCode;
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

                        }elseif(isset($pic->userSV) && $pic->userSV !== null){
                            //SEND TO BOSS
                            $pelaksanaType = "SO";

                            $notification = new Notification();
                            $notification->NO_RefCode = $pic->TPIC_USCode;
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

                }

                //##NOTIF-035
                //SEND NOTIFICATION TO PUBLIC USER
                $pelaksanaType = "PU";

                $title2 = "Pemberitahuan Status Surat Setuju Terima - $letterAccept->LANo";
                $desc2 = "Tahniah, surat setuju terima $letterAccept->LANo bagi cadangan $tenderProposal->TPNo telah berjaya diproses dan selesai. Maklumat projek telah dihantar melalu E-mel anda yang telah berdaftar.";

                $contractorNo = $tenderProposal->TP_CONo;

                $notification = new Notification();
                $notification->NO_RefCode = $contractorNo;
                $notification->NOType = $pelaksanaType;
                $notification->NO_NTCode = $notiType;
                $notification->NOTitle = $title2;
                $notification->NODescription = $desc2;
                $notification->NORead = 0;
                $notification->NOSent = 1;
                $notification->NOActive = 1;
                $notification->NOCB = $user->USCode;
                $notification->NOMB = $user->USCode;
                $notification->save();

            }


            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Maklumat notifikasi berjaya dihantar.',
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat notifikasi tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function generateLetter($id , $type){

        $user = Auth::user();

        // $webSetting = WebSetting::first();

        // $project = Project::where('PTNo',$id)->first();

        $letterAccept = LetterAcceptance::where('LANo', $id)->first();

        $contractor =  $letterAccept->tenderProposal->contractor;

        $tender = $letterAccept->tenderProposal->tender;

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$letterAccept->tenderProposal->contractor->COReg_StateCode];

        $la_date = Carbon::parse($letterAccept->LAResponseDate)->format('d/m/Y');

        $template = "LETTER";
        $download = true; //true for download or false for view

        if ( $type == 'LA-ALA-DC'){
            $templateName = "AKUAN PENGESAHAN";
            $qrCode = QRcode::size(80)
            ->backgroundColor(255, 255, 0, 0)
                    ->generate($letterAccept->LANo);
        }
        if ( $type == 'LA-SBDL-DC'){
            $templateName = "AKUAN PEMBIDA BERJAYA";
            $qrCode = QRcode::size(80)
            ->backgroundColor(255, 255, 0, 0)
                    ->generate($letterAccept->LANo);
        }
        if ( $type == 'LA-CU-DC'){
            $templateName = "AKUJANJI SYARIKAT";
            $qrCode = QRcode::size(80)
            ->backgroundColor(255, 255, 0, 0)
                    ->generate($letterAccept->LANo);
        }
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName' , 'letterAccept' , 'qrCode' , 'contractor', 'la_date' , 'tender' , 'COState')
        );
        $response = $this->generatePDF($view,$download , $templateName);

        return $response;

    }

    private function sendMailSSTStatus($contractor){

        // $paymentLog = PaymentLog::where('PLNo',$paymentLogNo)->first();


        $emailLog = new EmailLog();
        $emailLog->ELCB 	= $contractor->CONo;
        $emailLog->ELType 	= 'SST';
        $emailLog->ELSentTo =  $contractor->COEmail;
        // Send Email

        $tokenResult = $contractor->createToken('Personal Access Token');
        $token = $tokenResult->token;

        $emailData = array(
            'id' => $contractor->COID,
            'name'  => $contractor->COName ?? '',
            'email' => $contractor->COEmail,
            'domain' => config::get('app.url'),
            'token' => $token->id,
            'now' => Carbon::now()->format('j F Y'),
            'contractor' => $contractor,
        );

        try {
            Mail::send(['html' => 'email.setSuccessSSTContractor'], $emailData, function($message) use ($emailData) {
                $message->to($emailData['email'] ,$emailData['name'])->subject('Status Surat Setuju Terima');
            });

            $emailLog->ELMessage = 'Success';
            $emailLog->ELSentStatus = 1;
        } catch (\Exception $e) {
            $emailLog->ELMessage = $e->getMessage();
            $emailLog->ELSentStatus = 2;
        }

        $emailLog->save();

    }

    //GENERATE AFTER APPROVAL
    public function generateGS($id){

        $user = Auth::user();

        $webSetting = WebSetting::first();

        $letterAccept = LetterAcceptance::where('LANo',$id)->first();

        $tenderProposal  = TenderProposal::where('TPNo' , $letterAccept->LA_TPNo)->first();

        $tenderApplication = TenderApplication::where('TANo' , $tenderProposal->TP_TANo)->first();

        $contractor = Contractor::where('CONo', $tenderApplication->TA_CONo)->first();

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$letterAccept->tenderProposal->contractor->COReg_StateCode];

        $dpt = $this->dropdownService->department();
        $department = $dpt[$tenderApplication->tender->TD_DPTCode];

        $responseDate = \Carbon\Carbon::parse($letterAccept->LAResponseDate)->format('d/m/Y');

        $dateExp = \Carbon\Carbon::parse($letterAccept->LAResponseDate)
                    ->addDays($webSetting->SSTExpDay)
                    ->format('d/m/Y');

        $bondPercent = $letterAccept->LATotalAmount * $webSetting->SSTBondPercent;

        $bondInWords = $this->DigitToWords($bondPercent);

        $insurance  = $webSetting->SSTInsuranceAmt;

        $insuranceInWords = $this->DigitToWords($insurance);

        $contractAddress = $contractor->CORegAddr . ", " . $contractor->CORegPostcode . ", " . $contractor->CORegCity . ", " . $COState ;

        $qrvalue = $letterAccept->LANo . " | " . $contractor->COName . " | " . $contractAddress . " | " . $tenderProposal->tender->TDTitle;

        $qrCode = QRcode::size(80)->generate($qrvalue);

        $name = "SURAT_SETUJU_TERIMA";

        $template = "LETTER";
        $download = true; //true for download or false for view
        $templateName = "ACCEPTANCE"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName','letterAccept' , 'COState', 'department' ,
        'responseDate' ,'dateExp' , 'bondPercent' , 'bondInWords'  , 'insurance' , 'insuranceInWords' , 'qrCode')
        );
        // $response = $this->generatePDF($view,$download,$name);

        try {

            $output = $view->render();
            $pdf = new Dompdf();
            $pdf->set_option('chroot', public_path());
            $pdf->loadHtml($output);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();

            $temporaryFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'PERMOHONAN_LESEN.pdf';

            file_put_contents($temporaryFilePath, $pdf->output());

            if (file_exists($temporaryFilePath)) {

                $autoNumber = new AutoNumber();

                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
                $file = $temporaryFilePath;
                $fileType = 'LA-GS';
                $refNo = $letterAccept->LANo;

                $fileContent = file_get_contents($file);

                $result = $this->saveFile(new \Illuminate\Http\UploadedFile($file, 'SURAT_SETUJU_TERIMA.pdf'), $fileType, $refNo);

				//$base64File = base64_encode($fileContent);

                return true;

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

        //$generatePDF = $this->generate($view, $fileType, $id, $name);

        // return $response;

    }


}
