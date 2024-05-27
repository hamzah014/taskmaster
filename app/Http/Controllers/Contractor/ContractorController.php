<?php

namespace App\Http\Controllers\Contractor;

use App\Helper\Custom;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Models\FileAttach;
use App\Models\LetterAcceptanceDeduction;
use App\Models\LetterIntent;
use App\Models\ProjectMilestone;
use App\Models\TemplateFile;
use App\Models\TenderProposalSpec;
use App\Models\VariantOrder;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\AutoNumber;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Tender;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use Spatie\PdfToText\Pdf;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;
use Dompdf\Dompdf;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PaymentLog;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectBudget;
use App\Models\ProjectBudgetYear;
use App\Models\InvoicePayment;
use App\Models\Contractor;
use App\Models\LetterAcceptance;
use App\Models\SuratArahanKerja;
use App\Models\TenderApplication;
use App\Models\TenderProposal;
use App\Models\WebSetting;
use App\Services\DropdownService;

class ContractorController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        Session::put('page', 'contractor') ;
        $negeri = $this->dropdownService->negeri();
        $jenis_projek = $this->dropdownService->jenis_projek();
        $department = $this->dropdownService->department();
        $mileStoneStatus = $this->dropdownService->mileStoneStatus();
        $claimProcessC = $this->dropdownService->claimProcess();
        $claimApprovalProcess = $this->dropdownService->claimApprovalProcess();
        $meetingLocation = $this->dropdownService->meetingLocation();

        $projectNo = Session::get('project');

        $project = Project::where('PTNo',$projectNo)->first();

        if( in_array($project->PT_PPCode, ['MLI', 'LIS', 'LIA', 'LIR', 'LIM', 'LIMS', 'MLA', 'LA'])){
            $letterIntent = LetterIntent::where('LINo', $project->tenderProposal->letterIntent->LINo)
                ->first();

            $li_date = Carbon::parse($letterIntent->LIDate)->format('Y-m-d');
            $li_time = Carbon::parse($letterIntent->LITime)->format('H:i');

            $acceptStatus = $this->dropdownService->acceptStatus();

            return view('contractor.LetterIntent.view',
                compact('letterIntent','li_date', 'li_time','acceptStatus','meetingLocation')
            );
        }
        else if( in_array($project->PT_PPCode, ['LAS', 'LAA', 'LAR', 'LAV', 'LAC', 'SAK', 'SAK-RQ', 'KO-RQ'])){
            $webSetting = WebSetting::first();

            $SSTDigitalSign = $webSetting->SSTDigitalSign;

            $letterAccept = LetterAcceptance::where('LANo', $project->tenderProposal->letterAcceptance->LANo)->first();

            $tenderProposal = $letterAccept->tenderProposal;
            $acceptStatus = $this->dropdownService->acceptStatus();

            $yn = $this->dropdownService->yt();

            $tender = $letterAccept->tenderProposal->tender;
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


            // $routerLetterALA = '#';
            // $routerLetterCU = '#';
            // $routerLetterSBDL = '#';

            $routerLetterALA = route('contractor.printLetter', ['id' => $projectNo, 'code' => 'LA-ALA']);
            $routerLetterCU = route('contractor.printLetter', ['id' => $projectNo, 'code' => 'LA-CU']);
            $routerLetterSBDL = route('contractor.printLetter', ['id' => $projectNo, 'code' => 'LA-SBDL']);

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


            $letterAcceptDeduction = LetterAcceptanceDeduction::select('TRLetterAcceptanceDeduction.*','PDTDesc')
                ->leftjoin('MSPaymentDeductionType','PDTCode','LAP_PDTCode')
                ->where('LAP_LANo', $project->tenderProposal->letterAcceptance->LANo)->get();

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

            $commentLog = $letterAccept->commentLog;
            $commentLog->each(function ($comment) {
                $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
            });

            return view('contractor.letterAccept.view',
                compact('tenderProposal','acceptStatus','yn','pentadbir','letterAccept','letterAcceptDeduction',
                    'tender','tenderProject_type','tempohSah','tenderJabatan','startDate','endDate','templates','tenderProposalSpec','paymentDeductType',
                    'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms', 'tenderDocuments', 'tenderDetails',
                    'routerLetterALA','routerLetterCU','routerLetterSBDL', 'commentLog', 'project', 'SSTDigitalSign')
            );
        }
        else if(in_array($project->PT_PPCode, ['KO', 'PS', 'CP-RQ', 'CP'])){
            $SAK = SuratArahanKerja::where('SAKNo' , $project->PT_SAKNo)->first();

            $project['SAKDate'] = isset($SAK->SAKDate) ? Carbon::parse($SAK->SAKDate)->format('d/m/Y') : null;

            $projectMileStone = ProjectMileStone::where('PMRefType','PJ')->where('PM_PTNo',$projectNo)->get();
            $eotMileStone = ProjectMileStone::where('PMRefType','EOT')->where('PM_PTNo',$projectNo)->get();
            $voMileStone = ProjectMileStone::where('PMRefType','VO')->where('PM_PTNo',$projectNo)->get();

            if($projectMileStone != null){
                foreach($projectMileStone as $pms){
                    $pms['startDate'] = Carbon::parse($pms->PMStartDate)->format('d/m/Y');
                    $pms['endDate'] = Carbon::parse($pms->PMEndDate)->format('d/m/Y');
                    $pms['milestoneStatus'] = $mileStoneStatus[$pms->PM_PMSCode] ?? "";

                    if(count($pms->projectClaimM) > 0){
                        foreach($pms->projectClaimM as $projectClaimM){
                            if($projectClaimM->PC_PCPCode != 'DF'){
                                $pms['claimStatus'] = $projectClaimM->PCNo . ' - ' . $claimProcessC[$projectClaimM->PC_PCPCode];
                            }
                            else{
//                            $pms['claimStatus'] = '<a href></a>';
                            }
//                        foreach($projectClaimM->claimMeetingDetD as $cmd){
//                            //
//                        }
                        }

                    }
                    else{
//                    $route = route('contractor.claim.create', [$pms->PMNo]);
//                    $pms['claimStatus'] = 'Belum Tuntut'.'<br><a href="'.$route.'">buat tuntutan</a>';
                    }

                }
                $totalWorkDays = $projectMileStone->sum('PMWorkDay');
                $project['totalWorkDay'] = $totalWorkDays;
            }

            $tender = $project->tenderProposal->tender ?? '';
            $tender['projectType'] = $jenis_projek[$tender->TD_PTCode];
            $tender['department'] = $department[$tender->TD_DPTCode];

            $tenderProposal = $project->tenderProposal;

            $contractor = $project->contractor;
            $contractor['state'] = $negeri[$contractor->COReg_StateCode] ?? '';

            $letterAccept = $tenderProposal->letterAcceptance;
            $letterAccept['SSTDate'] = Carbon::parse($letterAccept->LAConfirmDate)->format('d/m/Y');

            $sumOfVOAmount = VariantOrder::where('VO_PTNo', $projectNo)
                ->sum('VOAmount');

            $totalWorkDaysEOT = 0;

            if(count($project->eotApprove) > 0){
                foreach( $project->eotApprove as $eot){
                    $totalWorkDaysEOT += $eot->meetingEot->MEWorkday;
                }
            }

            $milestone = ProjectMilestone::where('PM_PTNo', $projectNo)
//            ->where('PMClaimInd', 1)
//            ->whereHas('projectClaim', function ($query) {
//                $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
//            })
                ->get(['PMDesc', 'PMNo'])
                ->pluck('PMDesc', 'PMNo');

            $currentYear = Carbon::now()->year;

            $projectBudget = ProjectBudget::where('PB_PTNo', $projectNo)->first();

            $projectBudgetAmt = ProjectBudgetYear::where('PBY_PTNo', $projectNo)
                ->where('PBYContractNo',$project->PTContractNo)
                ->where('PBYYear', $currentYear)
                ->sum('PBYBudgetAmt');


            $totalClaimYearly = InvoicePayment::where('IVP_PTNo' , $projectNo)->whereYear('IVPDate',$currentYear)->sum('IVPAmtPaid');

            $totalClaim = InvoicePayment::where('IVP_PTNo' , $projectNo)->sum('IVPAmtPaid');

            $budgetBalance = $projectBudgetAmt - $totalClaimYearly;

            $totalClaim = ProjectClaim::where('PC_PCPCode', 'PD')->where('PC_PTNo' , $projectNo)->sum('PCTotalAmt');

            //dd($id, $projectBudget, $totalClaim , $totalClaimYearly);


            $projectDetails = [];

            if ($tender) {
                $completePercentage = $project->projectMilestone->where('PM_PMSCode', 'C')->sum('PMWorkPercent') / 100;
                $remainingPercentage = $project->projectMilestone->where('PM_PMSCode', 'D')->sum('PMWorkPercent') / 100;
                $progressPercentage = $project->projectMilestone->where('PM_PMSCode', 'N')->sum('PMWorkPercent') / 100;

                $projectDetails[] = [
                    'project' => $tender->TDTitle,
                    'completePercentage' => $completePercentage,
                    'remainingPercentage' => $remainingPercentage,
                    'progressPercentage' => $progressPercentage,
                ];
            }

            $sak = SuratArahanKerja::where('SAKNo', $project->PT_SAKNo)->first();

            $websetting = Websetting::first();

            $days = ceil($websetting->LateProjPercentEOT * $project['totalWorkDay']);

            $integerValue = (int)$project->PTLateDay;

            if($integerValue >= $days){
                $lateFlag = true;
            }
            else{
                $lateFlag = false;
            }

            return view('contractor.index', compact('project','contractor','tender','tenderProposal','projectMileStone','eotMileStone','voMileStone','claimApprovalProcess',
                'letterAccept', 'claimProcessC', 'sumOfVOAmount', 'totalWorkDaysEOT', 'milestone' , 'projectBudgetAmt' , 'projectBudget', 'totalClaimYearly' , 'totalClaim',
                'projectDetails','budgetBalance' , 'sak', 'lateFlag'));
        }

    }

    public function updateStatusLetterIntent(Request $request){

        $messages = [
            'submitStatus.required'   => 'Status penerimaan diperlukan.',
        ];

        $validation = [
            'submitStatus' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $id = $request->LINo;
            $submitStatus = $request->submitStatus;

            $dateNow = Carbon::now()->format('Y-m-d');

            $letterIntent = LetterIntent::where('LINo',$id)->first();
            $letterIntent->LIStatus = $submitStatus;
            $letterIntent->LIResponseDate = $dateNow;
            $letterIntent->save();

            $trpcode = "";

            $project = Project::where('PT_TPNo', $letterIntent->LI_TPNo)->first();

            if($submitStatus == 'APPROVE'){

                 $trpcode = "LIA";
//                $trpcode = "LIA-RQ";
                 $project->PT_PPCode = 'LIA';
//                $project->PT_PPCode = 'LIA-RQ';

//                $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//                $approvalController->storeApproval($letterIntent->LINo, 'LI-RP');

            }
            else if($submitStatus == 'REJECT'){

                $trpcode = "LIR";
                $project->PT_PPCode = 'LIR';

            }
            $project->save();

            $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LI_TPNo)->first();
            $tenderProposal->TP_TRPCode = $trpcode;
            $tenderProposal->save();

//            $this->sendLetterNotification($letterIntent,'LI'); HAMZAH TENGOK SINI
//
            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.index'),
                'message' => 'Maklumat surat niat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat surat niat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function updateStatusLetterAccept(Request $request){

        $messages = [
            'submitStatus.required'   => 'Status penerimaan diperlukan.',
            //TBR - #LAMPIRAN-NOTWORKING
//            'lampiran.required'       => 'Bahagian lampiran perlukan diselesaikan.',
//            'lampiran.*.accepted'       => 'Bahagian lampiran perlukan diselesaikan.',
        ];

        $validation = [
            'submitStatus' => 'required',
//            'lampiran' => 'required',
//            'lampiran.*' => 'accepted',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $id = $request->acceptNo;
            $submitStatus = $request->submitStatus;

            $dateNow = Carbon::now()->format('Y-m-d');

            $letterIntent = LetterAcceptance::where('LANo',$id)->first();
            $letterIntent->LAStatus = $submitStatus;
            $letterIntent->LAResponseDate = $dateNow;
            $letterIntent->save();

            $project = Project::where('PT_TPNo', $letterIntent->LA_TPNo)->first();

            $trpcode = "";

            if($submitStatus == 'APPROVE'){

                 $trpcode = "LAA";
                 $project->PT_PPCode = 'LAA';

//                $trpcode = "LAA-RQ";
//                $project->PT_PPCode = 'LAA-RQ';

//                $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//                $approvalController->storeApproval($letterIntent->LANo, 'LA-RP');

            }else if($submitStatus == 'REJECT'){

                $trpcode = "LAR";
                $project->PT_PPCode = 'LAR';

            }
            $project->save();

            $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LA_TPNo)->first();
            $tenderProposal->TP_TRPCode = $trpcode;
            $tenderProposal->save();

//            $this->sendLetterNotification($letterIntent,'LA');HAMZAH TENGOK SINI

            $contractor = $tenderProposal->contractor;

            $phoneNo = $contractor->COPhone;
            $title = $tenderProposal->tender->TDTitle;
            $refNo = $tenderProposal->tender->TDNo;

//            $custom = new Custom();
//            $sendWSACPTSST = $custom->sendWhatsappLetter('sst_accept_notice',$phoneNo,$title,$refNo);//PENGESAHANWSLETTER
            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.index'),
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

    public function tenderSelect(Request $request){
/*

        $messages = [
            'project.required'  => 'Sila pilih projek',
        ];

        $validation = [
            'project'  => 'required',
        ];

        $request->validate($validation, $messages);

        Session::put('project', $request->project) ;

        $userLogin = Session::get('userLogin');
        $userPassword = Session::get('userPassword');

		if (Auth::attempt(['USCode' => $userLogin, 'password' => $userPassword, 'USActive' => 1])) {

			Session::put('page', 'contractor');

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.index'),
                'message' => '',
            ]);
		}
        else{
            return response()->json([
                'error' => '1',
                'message' => 'Unable login to contractor',
            ], 400);
        }*/

        Session::put('project', $request->project) ;

        return response()->json([
            'success' => '1',
            'redirect' => route('contractor.index'),
            'message' => '',
        ]);

    }

    public function tenderIndex(){

        Session::put('page', 'contractor');

        $userLogin = Session::get('userLogin');
        $userPassword = Session::get('userPassword');

        if ($userLogin == null){
            $userLogin = "CO00000030";
            $userPassword = "111111";
            Session::put('userLogin', $userLogin);
            Session::put('userPassword', $userPassword);
        }

		if (Auth::attempt(['USCode' => $userLogin, 'password' => $userPassword, 'USActive' => 1])) {

		}

        $project = Tender::join('TRTenderProposal','TP_TDNo','TDNo')
                            ->join('TRProject','PT_TPNo','TPNo')
                            ->where('PT_CONo',Auth::user()->USCode)
                            ->orderby('PTNo', 'desc')
                            ->get()->pluck('TDTitle', 'PTNo');

        return view('contractor.tenderIndex', compact('project'));

    }


    public function notification($id){
        return view('contractor.notification');
    }

    public function allNotification(){
        return view('contractor.all_notification');
    }

    public function test(Request $request){
        // $x = "<p>cdc&nbsp;</p><p style=\"padding-left: 80px;\">w<strong>dcce</strong></p><p><strong>&nbsp;dsvwd</strong></p><table style=\"border-collapse: collapse; width: 100.022%; height: 44.7916px;\" border=\"1\"><tbody><tr style=\"height: 22.3958px;\"><td style=\"width: 32.5961%; height: 22.3958px;\">dcdfc</td><td style=\"width: 32.5961%; height: 22.3958px;\"><strong>cece</strong></td><td style=\"width: 32.5969%; height: 22.3958px;\">e</td></tr><tr style=\"height: 22.3958px;\"><td style=\"width: 32.5961%; height: 22.3958px;\">&nbsp;</td><td style=\"width: 32.5961%; height: 22.3958px;\"><strong>ccece</strong></td><td style=\"width: 32.5969%; height: 22.3958px;\">ceerce</td></tr></tbody></table><p>&nbsp;</p>";

        // $invoice_no = [
        //     0 => 'C00001',
        //     1 => 'C00002',
        // ];
        // $pdf = new Pdf('C:/laragon/usr/bin/pdftotext/test_a.pdf');
        // $text = $pdf->text();
        // $text = Pdf::getText('test_a.pdf', 'C:/laragon/usr/bin/pdftotext');

        if ($request->ajax()) {

            $query = Customer::select('CSCode', 'CSName')->get();
            return DataTables::of($query)
                ->addColumn('tarikh', function($row) {
                    return $row->CSCode;
                })
                ->addColumn('tajuk', function($row) {
                    return $row->CSName;
                })
                ->rawColumns(['tarikh','tajuk'])
                ->make(true);
        }

        return view('contractor.test');
    }

    public function testPost(Request $request){

        dd($request->input('content'));

        return view('contractor.test', compact('invoice_no'));
    }

//    public function testspatie(){
//
//        $invoice_no = [
//            0 => 'C00001',
//            1 => 'C00002',
//        ];
//
//        try {
//            $file_path = 'C:/Users/User/Downloads/test_a.pdf';
//
//            if (Storage::disk('local')->exists($file_path)) {
//                if (Storage::disk('local')->isReadable($file_path)) {
//                    dd(1);
//                    echo "Read permissions are set for the file.";
//                } else {
//                    dd(2);
//                    echo "Read permissions are not set for the file.";
//                }
//            } else {
//                dd(3);
//                echo "File does not exist.";
//            }
//
//
//            $pdf = new Pdf();
//            $text = $pdf->getText('test_a.pdf', 'C:/Users/User/Downloads');
//
//            // Process the extracted text as needed
////            echo $text;
//
//            dd($text);
//        } catch (Spatie\PdfToText\Exceptions\PdfNotFound $exception) {
//            // Handle the exception (e.g., display an error message)
//            echo "PDF file not found.";
//        } catch (Exception $exception) {
//            // Handle other exceptions
//            echo "An error occurred: " . $exception->getMessage();
//        }
//
////        $text = (new Pdf('C:/Users/User/Downloads'))
////            ->setPdf('test_a.pdf')
////            ->setOptions(['layout', 'r 96'])
////            ->text();
////
////        dd($text);
//
//        return view('contractor.test', compact('invoice_no'));
////        return view('contractor.test');
//    }

//    public function addRow(Request $request){
//        if ($request->ajax()) {
//            return view('contractor.test2');
//        }
//    }


    public function resitPDF($id){

        //GET DATA FROM PAYMENTLOG
        //get data - contractor - PLCONo
        //certApp where PLNo - PLCANo - get total from this table

        // Data to pass to the Blade view
        $data = PaymentLog::where('PLNo',$id)->with('contractor','certApp')->first();

        // Render the Blade view as a string using View::make()
        $view = View::make('publicUser.transaksi.resitPDF',compact('data'));
        $output = $view->render();

        // Generate the PDF
        $pdf = new Dompdf();
        $pdf->loadHtml($output);
        $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation (portrait or landscape)
        $pdf->render();

        // Set this variable to true to force download, or false to display in the browser
        $forceDownload = true;

        // Generate the response with appropriate headers
        $response = new Response($pdf->output());
        $response->headers->set('Content-Type', 'application/pdf');

        if ($forceDownload) {
            // Generate the response with 'attachment' header for download
            $response->headers->set('Content-Disposition', 'attachment; filename="Resit_Pembayaran.pdf"');
        } else {
            // Generate the response with 'inline' header for display in the browser
            $response->headers->set('Content-Disposition', 'inline');
        }

        return $response;



        // return view('publicUser.transaksi.resitPDF',
        // compact('data')
        // );


    }

    public function claimDatatable(Request $request){

        $project = $request->idProject;

        //$purchaseOrder = PurchaseOrder::with('invoices')->where('PO_PTNo' , $project->PTNo)->get();
        //$query = PurchaseOrder::where('PO_PTNo', $request->idProject)->get();

        $query = ProjectClaim::join('TRProjectMilestone','PC_PMNo', 'PMNo', 'IVRefNo','IVAmtPaid')
                                ->join('TRProjectInvoice','PCI_PCNo','PCNo')
                                ->leftjoin('MSProjectClaimProcess','PCPCode','PC_PCPCode')
                                ->leftjoin("TRInvoice",function($join){
                                    $join->on("TRInvoice.IVRefNo","TRProjectInvoice.PCIInvNo")
                                        ->on("TRInvoice.IV_PTNo","TRProjectMilestone.PM_PTNo");
                                })
                                ->where('PM_PTNo', $project)
                                ->orderby('PCID','desc')
                                ->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PCSubmittedDate', function ($row) {
                $result = isset($row->PCSubmittedDate) ? Carbon::parse($row->PCSubmittedDate)->format('d/m/Y') : null;

                return $result;
            })
            ->editColumn('PCIInvDate', function ($row) {
                $result = isset($row->PCIInvDate) ? Carbon::parse($row->PCIInvDate)->format('d/m/Y') : null;

                return $result;
            })
            ->editColumn('PCIInvAmt', function ($row) {
                $result = number_format($row->PCIInvAmt ?? 0, 2, '.', ',');
                return $result;
            })
            ->addColumn('InvoiceStatus', function ($row) {
                if ($row->IVRefNo != null ){
                    $result = '<i class="ki-solid ki-check-square text-success fs-2"></i>';
                }else{
                   $result = '<i class="ki-solid ki-cross-square text-danger fs-2"></i>';
                }
                return $result;
            })
            ->addColumn('PaidStatus', function ($row) {
                if ($row->IVAmtPaid > 0){
                    $result = '<i class="ki-solid ki-check-square text-success fs-2"></i>';
                }else{
                    $result = '<i class="ki-solid ki-cross-square text-danger fs-2"></i>';
                }
                return $result;
            })
            ->with(['count' => 0])
            ->setRowId('indexNo')
            ->rawColumns(['InvoiceStatus','PaidStatus'])
            ->make(true);

    }

    public function generateSST($id){

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

        $qrCode = QRcode::size(80)->generate($letterAccept->LANo);

        $template = "LETTER";
        $download = false; //true for download or false for view
        $templateName = "ACCEPTANCE"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName','letterAccept' , 'COState', 'department' , 'responseDate' ,
        'dateExp' , 'bondPercent' , 'bondInWords'  , 'insurance' , 'insuranceInWords' , 'qrCode')
        );
        $response = $this->generatePDF($view,$download);

        return $response;
    }

    public function generateSAK($id){
        $webSetting = WebSetting::first();

        $currentYear = Carbon::now()->year;

        $project = Project::where('PTNo',$id)->first();

        $departmentCode = $project->tenderProposal->tender->TD_DPTCode;

        $project['SAKDate'] = isset($project->PTSAKDate) ? Carbon::parse($project->PTSAKDate)->format('d/m/Y') : null;

        $contractor = Contractor::where('CONo', $project->PT_CONo)->first();

        $letterAccept = LetterAcceptance::where('LANo' , $project->PT_LANo)->first();

        $responseDate = \Carbon\Carbon::parse($letterAccept->LAResponseDate)->format('d/m/Y');

        $bondPercent = $webSetting->SSTBondPercent * 100;

        $bondAmt = $letterAccept->LATotalAmount * $webSetting->SSTBondPercent;

        $bondInWords = $this->DigitToWords($bondAmt);

        $insurance  = $webSetting->SSTInsuranceAmt;

        $insuranceInWords = $this->DigitToWords($insurance);

        $contractAmtWord = $this->DigitToWords($letterAccept->LAContractAmt);

        $discAmtWord = $this->DigitToWords($letterAccept->LADiscPercent);

        $taxAmtWord = $this->DigitToWords($letterAccept->LATaxAmt);

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$project->contractor->COReg_StateCode];

        $sak =SuratArahanKerja::where('SAKNo', $project->PT_SAKNo)->first();

        $qrCode = QRcode::size(80)->generate($letterAccept->LANo);

        $template = "SAK";
        $download = false; //true for download or false for view
        $templateName = "SAK"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName', 'COState', 'project' , 'letterAccept', 'responseDate' , 'bondAmt' , 'contractAmtWord' , 'discAmtWord',
                'taxAmtWord' , 'bondPercent' , 'bondInWords'  , 'insurance' , 'insuranceInWords' , 'bondPercent' , 'currentYear'
                , 'departmentCode' , 'sak' , 'qrCode')
        );
        $response = $this->generatePDF($view,$download);

        // $output = $view->render();
        // $pdf = new Dompdf();
        // $pdf->set_option('chroot', public_path());
        // $pdf->loadHtml($output);
        // $pdf->setPaper('A4', 'portrait');
        // $pdf->render();

        // $temporaryFilePath = storage_path('app/tmp/sak.pdf');

        // file_put_contents($temporaryFilePath, $pdf->output());

        // return $temporaryFilePath;

        return $response;
    }

    public function printLetter($id , $type){

        $user = Auth::user();

        $webSetting = WebSetting::first();

        $project = Project::where('PTNo',$id)->first();

        $letterAccept = LetterAcceptance::where('LANo', $project->PT_LANo)->first();

        $contractor =  $letterAccept->tenderProposal->contractor;

        $tender = $letterAccept->tenderProposal->tender;

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$letterAccept->tenderProposal->contractor->COReg_StateCode];

        $qrCode = QRcode::size(80)->backgroundColor(255, 255, 0, 0)->generate($letterAccept->LANo);

        $la_date = Carbon::parse($letterAccept->LAResponseDate)->format('d/m/Y');

        $template = "LETTER";
        $download = true; //true for download or false for view

        if ( $type == 'LA-ALA'){
            $templateName = "AKUAN PENGESAHAN";
        }
        if ( $type == 'LA-SBDL'){
            $templateName = "AKUAN PEMBIDA BERJAYA";
        }
        if ( $type == 'LA-CU'){
            $templateName = "AKUJANJI SYARIKAT";
        }
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName' , 'letterAccept' , 'qrCode' , 'la_date' , 'contractor' , 'COState' , 'project' , 'tender')
        );
        $response = $this->generatePDF($view,$download , $templateName);

        return $response;

    }

    public function viewSAK($id){
        $projectNo = Session::get('project');

        $sak = SuratArahanKerja::where('SAKNo' , $id)->first();

        $project = Project::where('PT_SAKNo', $sak->SAKNo)->first();

        $fileSAK = $this->saveSAK($project->PTNo);

        if($fileSAK == false){
            $file = null;
        }else{
            $file = $fileSAK;
        }

        return view('contractor.sak.view',
            compact('projectNo' , 'project' , 'sak' , 'file')
        );
    }

    public function uploadSAK(Request $request){
        
        $messages = [
            'dokumen.file'     => 'Lampiran Dokumen harus berupa file.',
        ];

        $validation = [
            'dokumen'          => 'required|file',
        ];

        $request->validate($validation, $messages);

        $sakNo = $request->SAKNo;

        $sak = SuratArahanKerja::where('SAKNo' , $sakNo)->first();

        //$temporaryFilePath = $this->generateSAK($request->SAKNo);

        try {

            DB::beginTransaction();

            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');

                $fileType = 'SAK-U';
                $refNo = $sakNo;

                $fileAttach = FileAttach::where('FARefNo',$refNo)
                    ->where('FAFileType',$fileType)
                    ->first();
                if ($fileAttach != null){
                    $filename   = $fileAttach->FAFileName;
                    $fileExt    = $fileAttach->FAFileExtension ;

                    $returnval = Storage::disk('fileStorage')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);
                    $fileAttach->delete();
                }

                $this->saveFile($file,$fileType,$refNo);
            }

            $status = "UPLOAD";
            $sak->SAKStatus = $status;
            $sak->save();

             //$approvalController = new ApprovalController(new DropdownService(), new AutoNumber());

             //$approvalController->storeApproval($sak->SAKNo, 'SAK-RP');

            // if (file_exists($temporaryFilePath)) {

            //     $file = $temporaryFilePath;
            //     $fileType = 'SAK-DC';
            //     $refNo = $sakNo;

            //     $fileAttach = FileAttach::where('FARefNo',$refNo)
            //         ->where('FAFileType',$fileType)
            //         ->first();
            //     if ($fileAttach != null){
            //         $filename   = $fileAttach->FAFileName;
            //         $fileExt    = $fileAttach->FAFileExtension ;

            //         $returnval = Storage::disk('fileStorage')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);
            //         $fileAttach->delete();
            //     }

            //     $this->saveFile($file,$fileType,$refNo);

            // }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.sak.viewSAK' , $sakNo),
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

    public function saveSAK($id){
        $webSetting = WebSetting::first();

        $currentYear = Carbon::now()->year;

        $project = Project::where('PTNo',$id)->first();

        $departmentCode = $project->tenderProposal->tender->TD_DPTCode;

        $project['SAKDate'] = isset($project->PTSAKDate) ? Carbon::parse($project->PTSAKDate)->format('d/m/Y') : null;

        $contractor = Contractor::where('CONo', $project->PT_CONo)->first();

        $letterAccept = LetterAcceptance::where('LANo' , $project->PT_LANo)->first();

        $responseDate = \Carbon\Carbon::parse($letterAccept->LAResponseDate)->format('d/m/Y');

        $bondPercent = $webSetting->SSTBondPercent * 100;

        $bondAmt = $letterAccept->LATotalAmount * $webSetting->SSTBondPercent;

        $bondInWords = $this->DigitToWords($bondAmt);

        $insurance  = $webSetting->SSTInsuranceAmt;

        $insuranceInWords = $this->DigitToWords($insurance);

        $contractAmtWord = $this->DigitToWords($letterAccept->LAContractAmt);

        $discAmtWord = $this->DigitToWords($letterAccept->LADiscPercent);

        $taxAmtWord = $this->DigitToWords($letterAccept->LATaxAmt);

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$project->contractor->COReg_StateCode];

        $sak =SuratArahanKerja::where('SAKNo', $project->PT_SAKNo)->first();

        $qrCode = QRcode::size(80)->generate($letterAccept->LANo);

        $template = "SAK";
        $download = false; //true for download or false for view
        $templateName = "SAK"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF'
        // return view ('general.templatePDF'
        ,compact('template','templateName', 'COState', 'project' , 'letterAccept', 'responseDate' , 'bondAmt' , 'contractAmtWord' , 'discAmtWord',
                'taxAmtWord' , 'bondPercent' , 'bondInWords'  , 'insurance' , 'insuranceInWords' , 'bondPercent' , 'currentYear'
                , 'departmentCode' , 'sak' , 'qrCode')
        );
        // $response = $this->generatePDF($view,$download);

        try {

            $output = $view->render();
            $pdf = new Dompdf();
            $pdf->set_option('chroot', public_path());
            $pdf->loadHtml($output);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();

            $temporaryFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'SURAT_ARAHAN_KERJA.pdf';

            file_put_contents($temporaryFilePath, $pdf->output());

            if (file_exists($temporaryFilePath)) {

                $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();
                $file = $temporaryFilePath;
                $fileType = 'SAK';
                $refNo = $sak->SAKNo;

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  'SURAT_ARAHAN_KERJA.pdf';
                $newFileExt = pathinfo($temporaryFilePath, PATHINFO_EXTENSION);
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $fileType;

                $fileContent = file_get_contents($file);

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = FileAttach::where('FARefNo',$refNo)
                    ->where('FAFileType',$fileType)
                    ->first();

                if ($fileAttach == null){
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB 		= Auth::user()->USCode;
                    $fileAttach->FAFileType 	= $fileCode;
                }else{

                $filename   = $fileAttach->FAFileName;
                $fileExt    = $fileAttach->FAFileExtension ;

                Storage::disk('fileStorage')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);

                }
                $fileAttach->FARefNo     	    = $refNo;
                $fileAttach->FA_USCode     	    = Auth::user()->USCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = Auth::user()->USCode;
                $fileAttach->save();

                return $fileAttach->FAGuidID;
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
