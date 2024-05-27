<?php

namespace App\Http\Controllers\Perolehan\Tender;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralNotificationController;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Models\AutoNumber;
use App\Models\FileAttach;
use App\Models\Project;
use App\Models\SSMCompany;
use App\Models\TemplateFile;
use App\Models\TemplateSpecHeader;
use App\Models\TenderDetail;
use App\Models\TenderMOF;
use App\Models\TenderPaymentDeduction;
use App\Models\TenderPIC;
use App\Models\TenderProposal;
use App\Models\TenderProposalDetail;
use App\Models\TenderSiteBrief;
use App\Models\TenderSpec;
use App\Models\TenderProposalOSC;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\ProjectTender;
use App\Models\Tender;
use App\Models\TenderAdv;
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
use App\Models\WebSetting;
use Illuminate\Support\Facades\Storage;

class TenderController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        $template = [
            0 => 'Template Tender Pembangunan 2023',
            1 => 'Template Tender Penyelenggaraan 2023',
            2 => 'Template Tender One Off 2023',
        ];

        return view('perolehan.tender.index', compact('template'));
    }

    public function create($projectTenderNo = null){

        $projectTender = null;
        $tender = null;

        if($projectTenderNo !== null){

            $projectTender = ProjectTender::where('PTDNo',$projectTenderNo)->first();
        }

        $jenis = $this->dropdownService->tender_sebutharga();
        $syarat_khas = $this->dropdownService->yt();
        $kod_bidang = $this->dropdownService->kod_bidang();
        $wajib =  $this->dropdownService->wt();
        $jenis_projek = $this->dropdownService->jenis_projek();
        $jenis_dokumen = $this->dropdownService->jenis_dokumen();
        $format_fail = $this->dropdownService->format_fail();
        $yn = $this->dropdownService->yn();
        $taklimat = $this->dropdownService->taklimat();
        $paymentDeductType = $this->dropdownService->paymentDeductType();
        $department = $this->dropdownService->department();
        $user = $this->dropdownService->user();
        $peranan = $this->dropdownService->peranan();
        $gred = $this->dropdownService->gred();
        $jenis_docA = $this->dropdownService->jenis_docA();

        $listTenderAdv = null;
        $tenderAdv = null;

        return view('perolehan.tender.create',
            compact('jenis', 'syarat_khas', 'kod_bidang', 'wajib', 'jenis_projek', 'jenis_dokumen', 'format_fail',
                'yn', 'taklimat', 'department','projectTender','tender' , 'user' , 'peranan', 'gred','jenis_docA',
                'listTenderAdv','tenderAdv', 'paymentDeductType'
                )
        );
    }

    public function edit($id){

        $jenis = $this->dropdownService->tender_sebutharga();
        $syarat_khas = $this->dropdownService->yt();
        $kod_bidang = $this->dropdownService->kod_bidang();
        $wajib =  $this->dropdownService->wt();
        $jenis_projek = $this->dropdownService->jenis_projek();
        $jenis_dokumen = $this->dropdownService->jenis_dokumen();
        $format_fail = $this->dropdownService->format_fail();
        $yn = $this->dropdownService->yn();
        $taklimat = $this->dropdownService->taklimat();
        $paymentDeductType = $this->dropdownService->paymentDeductType();
        $department = $this->dropdownService->department();
        $user = $this->dropdownService->user();
        $peranan = $this->dropdownService->peranan();
        $gred = $this->dropdownService->gred();
        $jenis_docA = $this->dropdownService->jenis_docA();

        $tender = Tender::where('TDNo', $id)->first();
        $array_tenderMOF= [];

        if(count($tender->tenderMOF) > 0){
            foreach($tender->tenderMOF as $tenderMOF) {
                array_push($array_tenderMOF, $tenderMOF->TDM_MOFCode);
            }
        }

        $dokumens = TenderDetail::where('TDD_TDNo', $tender->TDNo)
            ->where('TDDType', 'LIKE' ,'%D%')
            ->orderBy('TDDSeq')
            ->get();

        foreach ($dokumens as $dokumen){
            $dokumen['fileAttachDownloadPD'] = null;

            $fileAttachDownloadPD = FileAttach::where('FARefNO', $dokumen->TDDNo)->where('FAFileType', 'TD-PD')->first();
            if($fileAttachDownloadPD){
                $dokumen['fileAttachDownloadPD'] = $fileAttachDownloadPD;
            }
            // $fileAttachDownloadPD = FileAttach::where('FARefNO', $dokumen->TDDNo)->first();
            // if($fileAttachDownloadPD){
            //     $dokumen['fileAttachDownloadPD'] = $fileAttachDownloadPD;
            // }
        }

        $teknikals = TenderDetail::where('TDD_TDNo', $tender->TDNo)
            ->where('TDDType', 'LIKE' ,'%T%')
            ->orderBy('TDDSeq')
            ->get();

        foreach ($teknikals as $teknikal){
            $teknikal['fileAttachTD'] = null;

             $fileAttachDownloadPD = FileAttach::where('FARefNO', $teknikal->TDDNo)->where('FAFileType', 'TD-DD')->first();
             if($fileAttachDownloadPD){
                 $teknikal['fileAttachDownloadPD'] = $fileAttachDownloadPD;
             }
            // $fileAttachDownloadPD = FileAttach::where('FARefNO', $teknikal->TDDNo)->first();
            // if($fileAttachDownloadPD){
            //     $teknikal['fileAttachDownloadPD'] = $fileAttachDownloadPD;
            // }
        }

        $kewangans = TenderDetail::where('TDD_TDNo', $tender->TDNo)
            ->where('TDDType', 'LIKE' ,'%F%')
            ->orderBy('TDDSeq')
            ->get();

        foreach ($kewangans as $kewangan){
            $kewangan['fileAttachTD'] = null;

            $fileAttachDownloadPD = FileAttach::where('FARefNO', $kewangan->TDDNo)->where('FAFileType', 'TD-DD')->first();
            // $fileAttachDownloadPD = FileAttach::where('FARefNO', $kewangan->TDDNo)->first();
            if($fileAttachDownloadPD){
                $kewangan['fileAttachDownloadPD'] = $fileAttachDownloadPD;
            }
        }

        $tender->sahTarikh = Carbon::parse($tender->TDProposalValidDate)->format('Y-m-d');

        $project = Project::where('PT_TDNo', $id)->first();

        $listTenderAdv = null;
        $tenderAdv = TenderAdv::where('TDANo',$tender->TD_TDANo)->orderBy('TDAID','DESC')->first();

        if($tenderAdv){

            $listTenderAdv = TenderAdv::where('TDA_TDNo',$id)
            ->where('TDANo', '!=' ,$tenderAdv->TDANo)
            ->orderBy('TDAID','DESC')
            ->get();

        }

        return view('perolehan.tender.create',
            compact('jenis', 'syarat_khas', 'kod_bidang', 'wajib', 'jenis_projek', 'jenis_dokumen', 'format_fail',
                'yn', 'taklimat', 'tender', 'array_tenderMOF', 'dokumens', 'teknikals', 'kewangans', 'paymentDeductType', 'department',
                'user', 'peranan', 'gred','jenis_docA', 'project',
                'listTenderAdv','tenderAdv'
            )
        );
    }

    public function view($id){
        $tender = Tender::where('TDNo', $id)->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $yn = $this->dropdownService->yn();
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod = $tender->TDPublishPeriod;

        $tenderDocuments = [];
        $tenderDetails = $tender->tenderDetail;

        return view('perolehan.tender.view', compact('tender','TD_TCCode', 'TDPublishDate', 'TDPublishPeriod',
            'TDSpecialTerms', 'TDTerms', 'tenderDocuments', 'tenderDetails'));
    }

    public function check($id){
        $jenis = $this->dropdownService->tender_sebutharga();
        $jenis_projek =  $this->dropdownService->jenis_projek();
        $status = $this->dropdownService->status();
        $dokumen_lampiran = $this->dropdownService->dokumen_lampiran();
        $wajib = $this->dropdownService->wt();
        $kod_bidang = $this->dropdownService->kod_bidang();

        return view('perolehan.tender.check',
            compact('jenis','jenis_projek', 'status', 'dokumen_lampiran', 'wajib', 'kod_bidang')
        );
    }

    public function indexV2(){
        $template = [
            0 => 'Template Tender Pembangunan 2023',
            1 => 'Template Tender Penyelenggaraan 2023',
            2 => 'Template Tender One Off 2023',
        ];

        return view('perolehan.tender.indexV2', compact('template'));
    }

    public function tenderDatatable(Request $request){
        $query = Tender::orderBy('TDID', 'DESC')->get();

        return datatables()->of($query)
            ->editColumn('TD_TCCode', function($row) {
                $tender_sebutharga = $this->dropdownService->tender_sebutharga();
                return $tender_sebutharga[$row->TD_TCCode];
            })
            ->editColumn('TDNo', function($row) {

                //if( $rowstatus == 'Draf')
                // $route = route('perolehan.tender.create', [$row->TDNo]);
                //else
                // $route = route('perolehan.tender.view', [$row->TDNo]);
                $route = route('perolehan.tender.edit', [$row->TDNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->TDNo.' </a>';

                return $result;
            })
            ->editColumn('TDPublishDate', function($row) {
                $carbonDatetime = Carbon::parse($row->TDPublishDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->editColumn('TDClosingDate', function($row) {
                $carbonDatetime = Carbon::parse($row->TDClosingDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->editColumn('TDSiteBrief', function($row) {
                $yn = $this->dropdownService->yn();
                return $yn[$row->TDSiteBrief];
            })
            ->editColumn('TDDocAmt', function($row) {

                return isset($row->TDDocAmt) ? "RM " . $row->TDDocAmt : "RM 0";
            })
            ->editColumn('TD_TPCode', function($row) {
                if(isset($row->TD_TPCode)){
                    $tenderProcess = $this->dropdownService->tenderProcess();
                    return $tenderProcess[$row->TD_TPCode] ?? null;
                }

                return null;

            })
            ->addColumn('Action', function($row) {

                $route = route('publicUser.application.addCart', [$row->TDNo]);

                $result = '<button data-id="'.$row->TDNo.'" class="add-to-cart new modal-trigger waves-effect waves-light btn gradient-45deg-indigo-light-blue">Pilih Dokumen</a>';

                return $result;
            })
            ->rawColumns(['TD_TCCode','TDPublishDate', 'TDClosingDate', 'TDSiteBrief', 'TDDocAmt', 'Action', 'TDNo', 'TD_TPCode'])
            ->make(true);
    }

    public function storeMaklumatTender(Request $request){

        try {
            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $webSetting = WebSetting::first();

            $currentTenderNo = $request->currentTenderNo;

            $tarikh_taklimats = $request->tarikh_taklimat;
            $tempat_kumpuls = $request->tempat_kumpul;
            $taklimats = $request->taklimat;
            $pegawais = $request->pegawai;
            $max_orangs = $request->max_orang;
            $keterangans = $request->keterangan;
            $bankStatementDate = $request->bankStatementDate;

            if( $currentTenderNo !== '0'){

                $tdNo = $this->duplicateTender($currentTenderNo);

                $tender = Tender::where('TDNo',$tdNo)->first();

            }
            else{

                $tdNo = $autoNumber->generateTenderNo();

                $tender = new Tender();
                $tender->TDNo           = $tdNo;

                $TDANo = $this->autoNumber->generateTenderAdvNo();

                $newTenderAdv = new TenderAdv();
                $newTenderAdv->TDANo = $TDANo;
                $newTenderAdv->TDA_TDNo = $tdNo;
                $newTenderAdv->TDARev = 1;
                $newTenderAdv->TDADocAmt = 0;
                $newTenderAdv->save();

                $tender->TD_TDANo            = $TDANo;

                if($request->projectTenderNo !== 0){
                    $ptdno = $request->projectTenderNo;
                    $projectTender = ProjectTender::where('PTDNo',$ptdno)->first();
                    $projectTender->PTD_PTSCode = 'TD';
                    $projectTender->PTD_TDNo = $tdNo;
                    $projectTender->save();

                }

                $templateFiles = TemplateFile::where('TFAutoCreate', 1)
                    ->where('TF_TTCode', 'TD')
                    ->orderBy('TFAutoSeq')->get();
                $request->tDDType = 'D';

                foreach ($templateFiles as $templateFile){
                    $latest = TenderDetail::where('TDD_TDNo', $tdNo)->orderBy('TDDNo', 'desc')
                        ->first();

                    if($latest){
                        $tddNo = $this->increment3digit($latest->TDDNo);
                        $allTD = TenderDetail::where('TDD_TDNo', $tdNo)
                            ->where('TDDType', 'LIKE', '%D%')
                            ->get();
                        $seq = count($allTD)+1;
                    }
                    else{
                        $tddNo = $tdNo.'-'.$formattedCounter = sprintf("%03d", 1);
                        $seq = 1;
                    }

                    $tenderDetail = new TenderDetail();
                    $tenderDetail->TDDNo        = $tddNo;
                    $tenderDetail->TDD_TDNo     = $tdNo;
                    $tenderDetail->TDDSeq       = $seq;
                    $tenderDetail->TDD_MTCode   = $templateFile->TF_MTCode;
                    $tenderDetail->TDDCB        = Auth::user()->USCode;
                    $tenderDetail->TDDCompleteT = 0;
                    $tenderDetail->TDDType      = $request->tDDType;
                    $tenderDetail->TDDTitle     = $templateFile->TFTitle;

                    $tenderFileAttach = FileAttach::where('FARefNo' , $templateFile->TFNo)->first();

                    if($tenderFileAttach){
                        //get the current template file
                        $folderPath	 = $tenderFileAttach->FAFilePath;
                        $newFileName = $tenderFileAttach->FAFileName;
                        $newFileExt	 = $tenderFileAttach->FAFileExtension;

                        $filePath = $folderPath;

                        $tenderfileContents = Storage::disk('fileStorage')->get($filePath);

                        //$filecontent = file_get_contents($tenderfileContents->getRealPath());

                        //recreate template file
                        $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

                        $folderPath = Carbon::now()->format('ymd');
                        $newFileName = strval($generateRandomSHA256);

                        Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName , $tenderfileContents );

                        $newTenderFileAttach = new FileAttach();
                        $newTenderFileAttach->FA_USCode      = Auth::user()->USCode;
                        $newTenderFileAttach->FARefNo        = $tenderDetail->TDDNo;
                        // $newTenderFileAttach->FAFileType     = $tenderDetail->TDD_MTCode;
                        if($request->tDDType == 'D'){
                            $newTenderFileAttach->FAFileType     = 'TD-PD';

                        }
                        else if($request->tDDType == 'T') {
                            $newTenderFileAttach->FAFileType = 'TD-DD';
                            $tenderDetail->TDDCompleteT = 1;
                        }
                        else if($request->tDDType == 'F'){
                            $newTenderFileAttach->FAFileType     = 'TD-DD';
                            $tenderDetail->TDDCompleteF = 1;
                        }
                        else{
                            $newTenderFileAttach->FAFileType     = $tenderDetail->TDD_MTCode;
                        }

                        $newTenderFileAttach->FAFilePath	 = $folderPath.'\\'.$newFileName;
                        $newTenderFileAttach->FAFileName     = $tenderFileAttach->FAFileName;

                        $newTenderFileAttach->FAOriginalName = $tenderFileAttach->FAOriginalName;
                        $newTenderFileAttach->FAFileExtension= $tenderFileAttach->FAFileExtension;
                        $newTenderFileAttach->FAActive       = $tenderFileAttach->FAActive;
                        $newTenderFileAttach->FAMB 			 = Auth::user()->USCode;

                        //to check fill is uploaded and can 'Papar'
                        $tenderDetail->TDDCompleteT = 1;

                        $newTenderFileAttach->save();
                    }

                    $tenderDetail->save();
                }

            }

            $tender->TDTitle                = $request->nama_Tender;
            $tender->TDContractAmt          = 0;
            $tender->TD_TCCode              = $request->jenis;
            $tender->TD_PTCode              = $request->jenis_projek;
            $tender->TDSpecialTerms         = isset($request->syarat_khas) ? 1 : 0;
            $tender->TDSiteBrief            = count($request->tarikh_taklimat) > 0 ? 1 : 0;
            $tender->TD_DPTCode             = $request->jabatan;
            $tender->TDContractNo           = $request->contractNo;
            $tender->TDContractAmt          = $request->contractAmount;
            $tender->TDContractPeriod       = $request->contractPeriod;
            $tender->TDProposalValidDay     = $request->proposalValid;
            // $tender->TDLOI                  = $request->proposalLOI;
            $tender->TD_TPCode              = 'DF';
            $tender->TD_PTDNo               = $request->projectTenderNo;
            $tender->TDBankScoreMax         = $webSetting->OSCBankScoreMax;
            $tender->TDSSMScoreMax          = $webSetting->OSCSSMScoreMax;
            $tender->TDCIDBScoreMax         = $webSetting->OSCCIDBScoreMax;
            $tender->TDCTOSScoreMax         = $webSetting->OSCCTOSScoreMax;
            $tender->TDDBKLScoreMax         = $webSetting->OSCDBKLScoreMax;
            $tender->TDBLPScoreMax          = $webSetting->OSCBLPScoreMax;
            $tender->TDMDIScoreMax          = $webSetting->OSCDMDIScoreMax;
            $tender->TDNewsScoreMax         = $webSetting->OSCNewsScoreMax;
            $tender->TDTotalScoreMax        = $webSetting->OSCTotalScoreMax;
            $tender->TD_CIDBCode            = $request->gred;
            $tender->TDBankStmtYear         = $request->bankStatementDate;

            $tender->save();

            //UPDATE TENDER MOF
            $kodbidangs = $request->kod_bidang;
            foreach($kodbidangs as $kodbidang){
                $new_tenderMOF = new TenderMOF();
                $new_tenderMOF->TDM_TDNo = $tdNo;
                $new_tenderMOF->TDM_MOFCode = $kodbidang;
                $new_tenderMOF->save();
            }

            //UPDATE TAKLIMAT TAPAK
            if(count($tarikh_taklimats) > 0){
                foreach ($tarikh_taklimats as $key => $tarikh_taklimat){

                    if($tarikh_taklimat != null){

                        $fixedDatetime = str_replace('T', ' ', $tarikh_taklimat);
                        $parsedDatetime = Carbon::parse($fixedDatetime)->format('Y-m-d H:i');

                        $tenderSiteBrief = new TenderSiteBrief();
                        $tenderSiteBrief->TDS_TDNo      = $tdNo;
                        $tenderSiteBrief->TDSType       = $taklimats[$key];
                        $tenderSiteBrief->TDSDate       = $parsedDatetime;
                        $tenderSiteBrief->TDSDesc       = $keterangans[$key];
                        $tenderSiteBrief->TDSLocation   = $tempat_kumpuls[$key];
                        $tenderSiteBrief->TDSOfficer    = $pegawais[$key];
                        $tenderSiteBrief->TDSMaxAttend  = $max_orangs[$key];
                        $tenderSiteBrief->save();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', $tdNo),
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

    public function duplicateTender($id){


        try {
            DB::beginTransaction();

            $tdNo = $this->autoNumber->generateTenderNo();
            $tender = Tender::where('TDNo',$id)->first();
            $oriTDNo = $tender->TDNo;

            $tenderNew = new Tender();
            $tenderNew->TDNo                      = $tdNo;
            $tenderNew->TDTitle                = $tender->TDTitle;
            $tenderNew->TDContractAmt          = $tender->TDContractAmt;
            $tenderNew->TD_TCCode              = $tender->TD_TCCode;
            $tenderNew->TD_PTCode              = $tender->TD_PTCode;
            $tenderNew->TDSpecialTerms         = $tender->TDSpecialTerms;
            $tenderNew->TDSiteBrief            = $tender->TDSiteBrief;
            $tenderNew->TD_DPTCode             = $tender->TD_DPTCode;
            $tenderNew->TDContractNo           = $tender->TDContractNo;
            $tenderNew->TDContractAmt          = $tender->TDContractAmt;
            $tenderNew->TDContractPeriod       = $tender->TDContractPeriod;
            $tenderNew->TDProposalValidDay     = $tender->TDProposalValidDa;
            // $tenderNew->TDLOI                  = $tender->TDLOI;
            $tenderNew->TD_TPCode              = $tender->TD_TPCode;
            $tenderNew->TD_PTDNo               = $tender->TD_PTDNo;
            $tenderNew->TDBankScoreMax         = $tender->TDBankScoreMax;
            $tenderNew->TDSSMScoreMax          = $tender->TDSSMScoreMax;
            $tenderNew->TDCIDBScoreMax         = $tender->TDCIDBScoreMax;
            $tenderNew->TDCTOSScoreMax         = $tender->TDCTOSScoreMax;
            $tenderNew->TDDBKLScoreMax         = $tender->TDDBKLScoreMax;
            $tenderNew->TDBLPScoreMax          = $tender->TDBLPScoreMax;
            $tenderNew->TDMDIScoreMax          = $tender->TDMDIScoreMax;
            $tenderNew->TDNewsScoreMax         = $tender->TDNewsScoreMax;
            $tenderNew->TDTotalScoreMax        = $tender->TDTotalScoreMax;
            $tenderNew->TD_CIDBCode            = $tender->TD_CIDBCode;
            $tenderNew->TDBankStmtYear         = $tender->TDBankStmtYear;

            $TDANo = $this->autoNumber->generateTenderAdvNo();

            $newTenderAdv = new TenderAdv();
            $newTenderAdv->TDANo = $TDANo;
            $newTenderAdv->TDA_TDNo = $tdNo;
            $newTenderAdv->TDARev = 1;
            $newTenderAdv->TDADocAmt = 0;
            $newTenderAdv->save();

            $tenderNew->TD_TDANo = $TDANo;
            $tenderNew->save();

            //copy TenderDetail
            $tenderDetails = $tender->tenderDetail;

            foreach($tenderDetails as $indexTD => $tenderDetail){

                $latest = TenderDetail::where('TDD_TDNo', $tdNo)->orderBy('TDDNo', 'desc')
                    ->first();

                if($latest){
                    $tddNo = $this->increment3digit($latest->TDDNo);
                    $allTD = TenderDetail::where('TDD_TDNo', $tdNo)
                        ->where('TDDType', 'LIKE', '%D%')
                        ->get();
                    $seq = count($allTD)+1;
                }
                else{
                    $tddNo = $tdNo.'-'.$formattedCounter = sprintf("%03d", 1);
                    $seq = 1;
                }

                $newTenderDetail = new TenderDetail();
                $newTenderDetail->TDDNo        = $tddNo;
                $newTenderDetail->TDD_TDNo     = $tdNo;
                $newTenderDetail->TDDSeq       = $seq;
                $newTenderDetail->TDD_MTCode   = $tenderDetail->TDD_MTCode;
                $newTenderDetail->TDDCB        = Auth::user()->USCode;
                $newTenderDetail->TDDCompleteT = 0;
                $newTenderDetail->TDDType      = $tenderDetail->TDDType;
                $newTenderDetail->TDDTitle     = $tenderDetail->TDDTitle;

                //copy FileAttach
                $fileAttachs = $tenderDetail->fileAttachAll;

                foreach($fileAttachs as $indexFA => $fileAttach){

                    //get the current template file
                    $folderPath	 = $fileAttach->FAFilePath;
                    $newFileName = $fileAttach->FAFileName;
                    $newFileExt	 = $fileAttach->FAFileExtension;

                    $filePath = $folderPath;

                    $tenderfileContents = Storage::disk('fileStorage')->get($filePath);

                    //recreate template file
                    $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

                    $folderPath = Carbon::now()->format('ymd');
                    $newFileName = strval($generateRandomSHA256);

                    Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName , $tenderfileContents );

                    $newTenderFileAttach = new FileAttach();
                    $newTenderFileAttach->FA_USCode      = Auth::user()->USCode;
                    $newTenderFileAttach->FARefNo        = $tddNo;
                    $newTenderFileAttach->FAFileType     = $tenderDetail->TDD_MTCode;

                    $newTenderFileAttach->FAFilePath	 = $folderPath.'\\'.$newFileName;
                    $newTenderFileAttach->FAFileName     = $fileAttach->FAFileName;
                    $newTenderFileAttach->FAOriginalName = $fileAttach->FAOriginalName;
                    $newTenderFileAttach->FAFileExtension= $fileAttach->FAFileExtension;
                    $newTenderFileAttach->FAActive       = $fileAttach->FAActive;
                    $newTenderFileAttach->FAMB 			 = Auth::user()->USCode;

                    //to check fill is uploaded and can 'Papar'
                    $newTenderDetail->TDDCompleteT = 1;

                    $newTenderFileAttach->save();
                }

                $newTenderDetail->save();

                $oriTenderSpecs = TenderSpec::where('TDS_TDNo',$oriTDNo)
                ->where('TDS_TDDNo',$tenderDetail->TDDNo)
                ->get();

                $tdsNoSeq = 1;

                foreach($oriTenderSpecs as $indexOSpec => $tenderSpec){

                    $tdsNo = $tddNo.'-'.$formattedCounter = sprintf("%03d", $tdsNoSeq);

                    $newTenderSpec = new TenderSpec();
                    $newTenderSpec->TDSNo                   = $tdsNo;
                    $newTenderSpec->TDS_TDNo                = $tdNo;
                    $newTenderSpec->TDS_TDDNo               = $tddNo;
                    $newTenderSpec->TDSSeq                  = $tenderSpec->TDSSeq;
                    $newTenderSpec->TDStockInd              = $tenderSpec->TDStockInd;
                    $newTenderSpec->TDSDesc                 = $tenderSpec->TDSDesc;
                    $newTenderSpec->TDSQty                  = $tenderSpec->TDSQty;
                    $newTenderSpec->TDS_UMCode              = $tenderSpec->TDS_UMCode;
                    $newTenderSpec->TDSRespondType          = $tenderSpec->TDSRespondType;
                    $newTenderSpec->TDSEstimatePrice        = $tenderSpec->TDSEstimatePrice;
                    $newTenderSpec->TDSTotalEstimatePrice   = $tenderSpec->TDSTotalEstimatePrice;
                    $newTenderSpec->TDSScoreMax             = $tenderSpec->TDSScoreMax;
                    $newTenderSpec->TDSCB                   = Auth::user()->USCode;
                    $newTenderSpec->save();

                    $tdsNoSeq++;

                }

            }

            //copy TenderPIC
            $tenderPICs = $tender->tenderPIC;

            foreach($tenderPICs as $indexTPIC => $tenderPIC){

                $newTenderPIC = new TenderPIC();
                $newTenderPIC->TPIC_TDNo   = $tdNo;
                $newTenderPIC->TPIC_USCode = $tenderPIC->TPIC_USCode;
                $newTenderPIC->TPICType    = $tenderPIC->TPICType;
                $newTenderPIC->TPICCB = Auth::user()->USCode;
                $newTenderPIC->save();

            }

            //copy TenderPaymentDeduction
            $tenderPaymentDeductions = $tender->tenderPaymentDeduction;

            foreach($tenderPaymentDeductions as $indexTPayD => $tenderPaymentDeduction){

                $newTenderPaymentDeduction = new TenderPaymentDeduction();
                $newTenderPaymentDeduction->TDP_TDNo       = $tdNo;
                $newTenderPaymentDeduction->TDP_PDTCode    = $tenderPaymentDeduction->TDP_PDTCode;
                $newTenderPaymentDeduction->TDPDesc        = $tenderPaymentDeduction->TDPDesc;
                $newTenderPaymentDeduction->TDPAmt         = $tenderPaymentDeduction->TDPAmt;
                $newTenderPaymentDeduction->TDPTermDesc    = $tenderPaymentDeduction->TDPTermDesc;
                $newTenderPaymentDeduction->TDPCB          = Auth::user()->USCode;
                $newTenderPaymentDeduction->save();

            }

            // //copy TenderMOF
            // $tenderMOFs = $tender->tenderMOF;

            // foreach($tenderMOFs as $indexTMOF => $tenderMOF){

            //     $newTenderMOF = new TenderMOF();
            //     $newTenderMOF->TDM_TDNo = $tdNo;
            //     $newTenderMOF->TDM_MOFCode = $tenderMOF->TDM_MOFCode;
            //     $newTenderMOF->save();

            // }

            // //copy TenderSiteBrief
            // $tenderSiteBriefs = $tender->tenderSiteBriefs;

            // foreach($tenderSiteBriefs as $indexSB => $tenderSiteBrief){


            //     $newTenderSiteBrief = new TenderSiteBrief();
            //     $newTenderSiteBrief->TDS_TDNo      = $tdNo;
            //     $newTenderSiteBrief->TDSType       = $tenderSiteBrief->TDSType;
            //     $newTenderSiteBrief->TDSDate       = $tenderSiteBrief->TDSDate;
            //     $newTenderSiteBrief->TDSDesc       = $tenderSiteBrief->TDSDesc;
            //     $newTenderSiteBrief->TDSLocation   = $tenderSiteBrief->TDSLocation;
            //     $newTenderSiteBrief->TDSOfficer    = $tenderSiteBrief->TDSOfficer;
            //     $newTenderSiteBrief->TDSMaxAttend  = $tenderSiteBrief->TDSMaxAttend;
            //     $newTenderSiteBrief->save();

            // }

            DB::commit();

            return $tdNo;

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function updateMaklumatTender(Request $request){
        try {
            DB::beginTransaction();

            $tarikh_taklimats = $request->tarikh_taklimat;
            $tempat_kumpuls = $request->tempat_kumpul;
            $taklimats = $request->taklimat;
            $pegawais = $request->pegawai;
            $max_orangs = $request->max_orang;
            $keterangans = $request->keterangan;
            $keterangans = $request->keterangan;

            $tender = Tender::where('TDNo', $request->noTender)->first();
            $tender->TDTitle        = $request->nama_Tender;
            $tender->TDContractAmt  = 0;
            $tender->TD_TCCode      = $request->jenis;
            $tender->TD_PTCode      = $request->jenis_projek;
            $tender->TDSpecialTerms = isset($request->syarat_khas) ? 1 : 0;
            $tender->TDSiteBrief    = $request->has('tarikh_taklimat') && count($request->tarikh_taklimat) > 0 ? 1 : 0;
            $tender->TDTerms        = $request->syarat_khas;
            $tender->TD_DPTCode     = $request->jabatan;
            $tender->TDContractNo           = $request->contractNo;
            $tender->TDContractAmt          = $request->contractAmount;
            $tender->TDContractPeriod       = $request->contractPeriod;
            // $tender->TDProposalValidDate    = $request->proposalDate;
            $tender->TDProposalValidDay     = $request->proposalValid;
            // $tender->TDLOI                  = $request->proposalLOI;
            $tender->TD_CIDBCode            = $request->gred;
            $tender->TDBankStmtYear         = $request->bankStatementDate;
            $tender->save();

            $old_tenderSiteBrief = TenderSiteBrief::where('TDS_TDNo', $tender->TDNo)->get();
            foreach ($old_tenderSiteBrief as $tenderSiteBrief){
                $tenderSiteBrief->delete();
            }
            if($request->tarikh_taklimat){
                if(count($tarikh_taklimats) > 0 ){
                    foreach ($tarikh_taklimats as $key => $tarikh_taklimat){
                        //dd($tarikh_taklimat);
                        if($tarikh_taklimat != null) {
                            $parsedDatetime = Carbon::createFromFormat('Y-m-d\TH:i', $tarikh_taklimat);
                            $formattedDatetime = $parsedDatetime->format('Y-m-d H:i:s');

                            $tenderSiteBrief = new TenderSiteBrief();
                            $tenderSiteBrief->TDS_TDNo = $tender->TDNo;
                            $tenderSiteBrief->TDSType = $taklimats[$key];
                            $tenderSiteBrief->TDSDate = $formattedDatetime;
                            $tenderSiteBrief->TDSDesc = $keterangans[$key];
                            $tenderSiteBrief->TDSLocation = $tempat_kumpuls[$key];
                            $tenderSiteBrief->TDSOfficer = $pegawais[$key];
                            $tenderSiteBrief->TDSMaxAttend = $max_orangs[$key];
                            $tenderSiteBrief->save();

                        }
                    }
                }
            }


            $old_tenderMOF = TenderMOF::where('TDM_TDNo', $request->noTender)->get();
            $kodbidangs = $request->kod_bidang;

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_tenderMOF) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_tenderMOF as $otenderMOF){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($kodbidangs as $kodbidang){
                        if($otenderMOF->TDM_MOFCode == $kodbidang){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $otenderMOF->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($kodbidangs as $kodbidang){
                    $exist_tenderMOF = TenderMOF::where('TDM_TDNo', $request->noTender)->where('TDM_MOFCode', $kodbidang)->first();
                    if(!$exist_tenderMOF){
                        $new_tenderMOF = new TenderMOF();
                        $new_tenderMOF->TDM_TDNo = $request->noTender;
                        $new_tenderMOF->TDM_MOFCode = $kodbidang;
                        $new_tenderMOF->save();
                    }
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                }
            }
            else{
                foreach($kodbidangs as $kodbidang){
                    $new_tenderMOF = new TenderMOF();
                    $new_tenderMOF->TDM_TDNo = $request->noTender;
                    $new_tenderMOF->TDM_MOFCode = $kodbidang;
                    $new_tenderMOF->save();
                }
            }
            //END HERE

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', $tender->TDNo),
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

    public function updatePengiklananTender(Request $request){

        $startDate = Carbon::parse($request->tarikh_mula);
        $endDate = Carbon::parse($request->tarikh_tamat)->addHours(12);


        $tempoh = $startDate->diff($endDate);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $updateStatus = $request->updateStatus;

            // $tender = Tender::where('TDNo', $request->noTender)->first();
            // $tender->TDFileNo = $request->no_file_tender;
            // $tender->TDDocAmt = $request->harga_dokumen;
            // $tender->TDPublishDate = $request->tarikh_mula;
            // $tender->TDPublishPeriod = $tempoh->days;
            // $tender->TDClosingDate = $endDate;
            // $tender->TDProposalValidDate = $request->tarikh_tamat;
            // $tender->TDLocation = $request->tempat_hantar;
            // $tender->save();

            $tenderAdv = TenderAdv::where('TDAID',$request->noTDA)->first();
            $tenderAdv->TDAFileNo = $request->no_file_tender;
            $tenderAdv->TDADocAmt = $request->harga_dokumen;
            $tenderAdv->TDAPublishDate = $request->tarikh_mula;
            $tenderAdv->TDAPublishPeriod = $tempoh->days;
            $tenderAdv->TDAClosingDate = $endDate;
            $tenderAdv->TDALocation = $request->tempat_hantar;
            $tenderAdv->save();

            if($updateStatus == 1){
                $tender = Tender::where('TDNo',$request->noTender)->first();

                //check pic
                if(count($tender->tenderPIC) < 1){

                    return response()->json([
                        'error' => 1,
                        'redirect' => route('perolehan.tender.edit', [$request->noTender, 'flag' => 7]),
                        'message' => 'Sila lantik sekurang-kurangnya seorang seabgai pegawai yang terlibat.'
                    ],400);

                }

                //check teknikal
                $teknikals = TenderDetail::where('TDD_TDNo', $request->noTender)
                ->where('TDDType', 'LIKE' ,'%T%')
                ->where('TDDCompleteT', 0)
                ->get();

                if(count($teknikals) > 0){

                    return response()->json([
                        'error' => 1,
                        'redirect' => route('perolehan.tender.edit', [$request->noTender, 'flag' => 7]),
                        'message' => 'Sila lengkapkan maklumat cadangan teknikal.'
                    ],400);

                }

                //check kewangan
                $kewangans = TenderDetail::where('TDD_TDNo', $request->noTender)
                ->where('TDDType', 'LIKE' ,'%F%')
                ->where('TDDCompleteF', 0)
                ->get();

                if(count($kewangans) > 0){

                    return response()->json([
                        'error' => 1,
                        'redirect' => route('perolehan.tender.edit', [$request->noTender, 'flag' => 7]),
                        'message' => 'Sila lengkapkan maklumat cadangan kewangan.'
                    ],400);

                }

                //check doc tender
                $dokumens = TenderDetail::where('TDD_TDNo', $request->noTender)
                ->where('TDDType', 'LIKE' ,'%D%')
                ->where('TDDCompleteT', 0)
                ->where(function($query){
                    $query->whereNotIn('TDD_MTCode',['CF', 'SPF', 'BQF']);
                })
                ->get();

                if(count($dokumens) > 0){

                    return response()->json([
                        'error' => 1,
                        'redirect' => route('perolehan.tender.edit', [$request->noTender, 'flag' => 7]),
                        'message' => 'Sila lengkapkan maklumat dokumen tender.'
                    ],400);

                }

                $webSetting = WebSetting::first();

                if($webSetting->TenderAdvApproval == 1){
                    $submitStatus = 'PA';
//                    $submitStatus = 'PA-RQ';

//                    $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());

//                    $approvalController->storeApproval($request->noTender, 'TD-SM');
                }
                else{
                    $submitStatus = 'PA';
                }

                $tender = Tender::where('TDNo',$request->noTender)->first();
                $tender->TD_TPCode = $submitStatus;
                $tender->TDMB = $user->USCode;
                $tender->save();

                // $this->sendNotification($tender,'S');

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->noTender, 'flag' => 7]),
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

    public function updatePotonganTender(Request $request){
        try {
            DB::beginTransaction();

            if(isset($request->idTDP) && isset($request->jenisPotongan) && isset($request->desc) && isset($request->amount) && isset($request->descTerm)){

                $tender = Tender::where('TDNo', $request->noTender)->first();

                $old_tenderPaymentDeduction = TenderPaymentDeduction::where('TDP_TDNo', $request->noTender)->get();

                $idTDPs = $request->idTDP;
                $jenisPotongans = $request->jenisPotongan;
                $descs = $request->desc;
                $amounts = $request->amount;
                $descTerms = $request->descTerm;

                // ARRAY UPDATE MULTIPLE ROW
                if(count($old_tenderPaymentDeduction) > 0){
                    //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                    foreach($old_tenderPaymentDeduction as $otenderPaymentDeduction){
                        $exist = 0;
                        //CHECING WUJUD 3 DLM 5
                        foreach($idTDPs as $idTDP){
                            if($otenderPaymentDeduction->TDPID == $idTDP){
                                $exist = 1;
                            }
                        }

                        //DELETE 2
                        if($exist == 0){
                            $otenderPaymentDeduction->delete();
                        }
                    }

                    //ADD NEW 4,5,6
                    foreach($idTDPs as $key => $idTDP){
                        $new_tenderPaymentDeduction = TenderPaymentDeduction::where('TDP_TDNo', $request->noTender)->where('TDPID', $idTDP)->first();
                        if(!$new_tenderPaymentDeduction){
                            $new_tenderPaymentDeduction = new TenderPaymentDeduction();
                            $new_tenderPaymentDeduction->TDP_TDNo = $request->noTender;
                            $new_tenderPaymentDeduction->TDPCB = Auth::user()->USCode;
                        }
                        //KALAU NK EDIT 1, 3 TAMBAH ELSE
                        $new_tenderPaymentDeduction->TDP_PDTCode = $jenisPotongans[$key];
                        $new_tenderPaymentDeduction->TDPDesc = $descs[$key];
                        $new_tenderPaymentDeduction->TDPAmt = $amounts[$key];
                        $new_tenderPaymentDeduction->TDPTermDesc = $descTerms[$key];
                        $new_tenderPaymentDeduction->save();
                    }
                }
                else{
                    foreach($idTDPs as $key => $idTDP){
                        $new_tenderPaymentDeduction = new TenderPaymentDeduction();
                        $new_tenderPaymentDeduction->TDP_TDNo = $request->noTender;
                        $new_tenderPaymentDeduction->TDP_PDTCode = $jenisPotongans[$key];
                        $new_tenderPaymentDeduction->TDPDesc = $descs[$key];
                        $new_tenderPaymentDeduction->TDPAmt = $amounts[$key];
                        $new_tenderPaymentDeduction->TDPTermDesc = $descTerms[$key];
                        $new_tenderPaymentDeduction->TDPCB = Auth::user()->USCode;
                        $new_tenderPaymentDeduction->save();
                    }
                }
                // END HERE

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$tender->TDNo, 'flag' => 5]),
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

    public function updateCancelProject(Request $request){
        try {
            DB::beginTransaction();

            $tender = Tender::where('TDNo', $request->tdno)->first();

            if($tender->TD_TPCode == 'IM'){
                return response()->json([
                    'error' => '1',
                    'message' => 'Tender Tidak Boleh Dibatalkan. Sila Keluarkan Tender Dari Mesyuarat Tender Board!'
                ], 400);
            }
            else{
                if ($request->hasFile('reasonStatement')) {

                    $file = $request->file('reasonStatement');
                    $fileType		    = "CT";
                    $refNo		        =  $request->tdno;

                    $this->saveFile($file,$fileType,$refNo);
                }

                $tender->TDCancelRemark = $request->remark;
                // $tender->TDCancelDate = Carbon::now();
                if($tender->TD_TPCode == 'DF'){
                    $tender->TD_TPCode = "DF";
                }
                else if($tender->TD_TPCode == 'PA'){
                    $tender->TD_TPCode = "PA";
                }
                else if($tender->TD_TPCode == 'CA'){
                    $tender->TD_TPCode = "CA";
                }
                else if($tender->TD_TPCode == 'OT'){
                    $tender->TD_TPCode = "OT";
                }
                else if($tender->TD_TPCode == 'ES'){
                    $tender->TD_TPCode = "ES";
                }
                else{
                    $tender->TD_TPCode = "DF";
                }
                $tender->save();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$tender->TDNo]),
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

    public function addDokumen($id, $templateCode, $indexNo, $tddType = 'D'){

        if($tddType == 'T'){
            $jenis_doc = $this->dropdownService->jenis_docT();
        }
        else if($tddType == 'F'){
            $jenis_doc = $this->dropdownService->jenis_docF();
        }
        else{
            $jenis_doc = $this->dropdownService->jenis_docDF();
        }
        $template_header = $this->dropdownService->template_header();

        $tender = Tender::where('TDNo', $id)->first();

        $templateFile = TemplateFile::where('TFNo', $templateCode)->first();

        if ($tddType == 'T'){
            $tddType = 'T';
            $flag = 2;
        }
        else if ($tddType == 'F'){
            $tddType = 'F';
            $flag = 3;
        }
        else{
            $tddType = 'D';
            $flag = 4;
        }

        $key = $indexNo;

        return view('perolehan.tender.new.chooseTemplate',
            compact('tender', 'templateFile', 'jenis_doc', 'key', 'indexNo', 'flag', 'tddType', 'template_header')
        );
    }

    public function dokumenTenderDetail(Request $request){
        try {
            DB::beginTransaction();

            $latest = TenderDetail::where('TDD_TDNo', $request->idTender)->orderBy('TDDNo', 'desc')
                ->first();

            if($latest){
                $tddNo = $this->increment3digit($latest->TDDNo);
                $allTD = TenderDetail::where('TDD_TDNo', $request->idTender)
                    ->where('TDDType', 'LIKE', '%'.$request->tDDType.'%')
                    ->get();
                $seq = count($allTD)+1;
            }
            else{
                $tddNo = $request->idTender.'-'.$formattedCounter = sprintf("%03d", 1);
                $seq = 1;
            }

            $tenderDetail = new TenderDetail();
            $tenderDetail->TDDNo        = $tddNo;
            $tenderDetail->TDD_TDNo     = $request->idTender;
            $tenderDetail->TDDSeq       = $seq;
            $tenderDetail->TDD_MTCode   = $request->jenis_doc;
            $tenderDetail->TDDCB        = Auth::user()->USCode;

            if(in_array($request->jenis_doc, ['UF', 'BS'])){
                // $tenderDetail->TDDComplete = 1;
            }
            else{
                if($request->tDDType == 'T' || $request->tDDType == 'D'){
                    $tenderDetail->TDDCompleteT = 0;
                }
                if($request->tDDType == 'F'){
                    $tenderDetail->TDDCompleteF = 0;
                }
                // if($request->jenis_doc == 'CF' || $request->jenis_doc == 'BQF' || $request->jenis_doc == 'SPF'){
                //     $tenderDetail->TDDCompleteT = 1;
                // }
            }

            if(in_array($request->jenis_doc, ['UF', 'BF'])){
                $tenderDetail->TDDCompleteO = 0;
            }

            if(isset($request->title_spec)){
                $templateSpecHeader = TemplateSpecHeader::where('TSHNo', $request->title_spec)->first();

                $request->title = $templateSpecHeader->TSHTitle;
                $request->tDDType = 'T,F';

                $tdsNoSeq = 1;

                foreach($templateSpecHeader->templateSpecDetail as $templateSpecDetail){
                    $tdsNo = $tddNo.'-'.$formattedCounter = sprintf("%03d", $tdsNoSeq);

                    $tenderSpec = new TenderSpec();
                    $tenderSpec->TDSNo = $tdsNo;
                    $tenderSpec->TDS_TDNo = $request->idTender;
                    $tenderSpec->TDS_TDDNo = $tddNo;
                    $tenderSpec->TDSSeq = $templateSpecDetail->TSDSeq;
                    $tenderSpec->TDStockInd = $templateSpecDetail->TSDStockInd;
                    $tenderSpec->TDSDesc = $templateSpecDetail->TSDDesc;
                    $tenderSpec->TDSQty = $templateSpecDetail->TSDQty;
                    $tenderSpec->TDS_UMCode = $templateSpecDetail->TSD_UMCode;
                    $tenderSpec->TDSRespondType = $templateSpecDetail->TSDRespondType;
                    $tenderSpec->TDSEstimatePrice = $templateSpecDetail->TSDEstimatePrice;
                    $tenderSpec->TDSTotalEstimatePrice = $templateSpecDetail->TSDTotalEstimatePrice;
                    $tenderSpec->TDSScoreMax = $templateSpecDetail->TSDScoreMax;
                    $tenderSpec->TDSCB = Auth::user()->USCode;
                    $tenderSpec->save();

                    $tdsNoSeq++;
                }
            }
            $tenderDetail->TDDType      = $request->tDDType;
            $tenderDetail->TDDTitle     = $request->title;

            //check FileAttach
            $templateCode = $request->templateFileNo;

            $tenderFileAttach = FileAttach::where('FARefNo' , $templateCode)->first();

            // foreach ($tenderFileAttachs as $tenderFileAttach){
                if($tenderFileAttach){
                    //get the current template file
                    $folderPath	 = $tenderFileAttach->FAFilePath;
                    $newFileName = $tenderFileAttach->FAFileName;
                    $newFileExt	 = $tenderFileAttach->FAFileExtension;

                    $filePath = $folderPath;

                    $tenderfileContents = Storage::disk('fileStorage')->get($filePath);

                    //$filecontent = file_get_contents($tenderfileContents->getRealPath());

                    //recreate template file
                    $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

                    $folderPath = Carbon::now()->format('ymd');
                    $newFileName = strval($generateRandomSHA256);

                    Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName , $tenderfileContents );

                    $newTenderFileAttach = new FileAttach();
                    $newTenderFileAttach->FA_USCode      = Auth::user()->USCode;
                    $newTenderFileAttach->FARefNo        = $tenderDetail->TDDNo;
                    // $newTenderFileAttach->FAFileType     = $tenderDetail->TDD_MTCode;
                    if($request->tDDType == 'D'){
                        $newTenderFileAttach->FAFileType     = 'TD-PD';

                    }
                    else if($request->tDDType == 'T') {
                        $newTenderFileAttach->FAFileType = 'TD-DD';
                        $tenderDetail->TDDCompleteT = 1;
                    }
                    else if($request->tDDType == 'F'){
                        $newTenderFileAttach->FAFileType     = 'TD-DD';
                        $tenderDetail->TDDCompleteF = 1;
                    }
                    else{
                        $newTenderFileAttach->FAFileType     = $tenderDetail->TDD_MTCode;
                    }

                    $newTenderFileAttach->FAFilePath	 = $folderPath.'\\'.$newFileName;
                    $newTenderFileAttach->FAFileName     = $tenderFileAttach->FAFileName;

                    $newTenderFileAttach->FAOriginalName = $tenderFileAttach->FAOriginalName;
                    $newTenderFileAttach->FAFileExtension= $tenderFileAttach->FAFileExtension;
                    $newTenderFileAttach->FAActive       = $tenderFileAttach->FAActive;
                    $newTenderFileAttach->FAMB 			 = Auth::user()->USCode;

                    //to check fill is uploaded and can 'Papar'
                    $tenderDetail->TDDCompleteT = 1;

                    $newTenderFileAttach->save();
                }

            // }

            $tenderDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>4]),
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

    public function addIndexTenderDetail($id, $templateCode ,$key, $tddType = 'D'){
        if($tddType == 'T'){
            $jenis_doc = $this->dropdownService->jenis_docT();
        }
        else if($tddType == 'F'){
            $jenis_doc = $this->dropdownService->jenis_docF();
        }
        else{
            $jenis_doc = $this->dropdownService->jenis_docDF();
        }
        $template_header = $this->dropdownService->template_header();

        $tenderDetail = TenderDetail::where('TDDNo', $id)->first();

        if($tenderDetail == null){
            $tenderDetailTDD_TDNo = substr($id, 0, 10);
        }
        else{
            $tenderDetailTDD_TDNo =$tenderDetail->TDD_TDNo;
        }
        $tender = Tender::where('TDNo', $tenderDetailTDD_TDNo)->first();

        $templateFile = TemplateFile::where('TFNo', $templateCode)->first();

        if ($tddType == 'T'){
            $tddType = 'T';
            $flag = 2;
        }
        else if ($tddType == 'F'){
            $tddType = 'F';
            $flag = 3;
        }
        else{
            $tddType = 'D';
            $flag = 4;
        }

        return view('perolehan.tender.new.chooseTemplate',
            compact('tender', 'templateFile', 'tenderDetail', 'jenis_doc', 'key', 'flag', 'tddType', 'template_header')
        );

    }

    public function storeIndexTenderDetail(Request $request){
        try {
            DB::beginTransaction();

            $seqNo = $request->key;

            $latest = TenderDetail::where('TDD_TDNo', $request->idTender)
                ->orderBy('TDDNo', 'desc')->first();
            $tddNo = $this->increment3digit($latest->TDDNo);

            //if spesifikasi seq set to 1
            if(isset($request->title_spec)){
                $seqNo = 0;

                $tenderDetailAfters = TenderDetail::where('TDDSeq', '>', $seqNo)
                    ->where('TDDType', 'LIKE', '%'.$request->tDDType.'%')
                    ->get();

                foreach ($tenderDetailAfters as $tenderDetailAfter){
                    $tenderDetailAfter->TDDSeq = $tenderDetailAfter->TDDSeq + 1;
                    $tenderDetailAfter->save();
                }

                $seq = $seqNo;
            }
            else{
                $tenderDetailAfters = TenderDetail::where('TDDSeq', '>', $seqNo)
                    ->where('TDDType', 'LIKE', '%'.$request->tDDType.'%')
                    ->get();

                foreach ($tenderDetailAfters as $tenderDetailAfter){
                    $tenderDetailAfter->TDDSeq = $tenderDetailAfter->TDDSeq + 1;
                    $tenderDetailAfter->save();
                }

                $seq = $seqNo;
            }

            $tenderDetail = new TenderDetail();
            $tenderDetail->TDDNo = $tddNo;
            $tenderDetail->TDD_TDNo     = $request->idTender;
            $tenderDetail->TDDSeq       = $seq+1;
            $tenderDetail->TDD_MTCode   = $request->jenis_doc;
            $tenderDetail->TDDCB        = Auth::user()->USCode;

            if(in_array($request->jenis_doc, ['UF', 'BS'])){
                // $tenderDetail->TDDComplete = 1;
            }
            else{
                if($request->tDDType == 'T' || $request->tDDType == 'D'){
                    $tenderDetail->TDDCompleteT = 0;
                }
                if($request->tDDType == 'F'){
                    $tenderDetail->TDDCompleteF = 0;
                }

            }

            if(in_array($request->jenis_doc, ['UF', 'BF'])){
                $tenderDetail->TDDCompleteO = 0;
            }

            if(isset($request->title_spec)){
                $templateSpecHeader = TemplateSpecHeader::where('TSHNo', $request->title_spec)->first();

                $request->title = $templateSpecHeader->TSHTitle;
                $request->tDDType = 'T,F';

                $tdsNoSeq = 1;

                foreach($templateSpecHeader->templateSpecDetail as $templateSpecDetail){
                    $tdsNo = $tddNo.'-'.$formattedCounter = sprintf("%03d", $tdsNoSeq);

                    $tenderSpec = new TenderSpec();
                    $tenderSpec->TDSNo = $tdsNo;
                    $tenderSpec->TDS_TDNo = $request->idTender;
                    $tenderSpec->TDS_TDDNo = $tddNo;
                    $tenderSpec->TDSSeq = $templateSpecDetail->TSDSeq;
                    $tenderSpec->TDStockInd = $templateSpecDetail->TSDStockInd;
                    $tenderSpec->TDSDesc = $templateSpecDetail->TSDDesc;
                    $tenderSpec->TDSQty = $templateSpecDetail->TSDQty;
                    $tenderSpec->TDS_UMCode = $templateSpecDetail->TSD_UMCode;
                    $tenderSpec->TDSRespondType = $templateSpecDetail->TSDRespondType;
                    $tenderSpec->TDSEstimatePrice = $templateSpecDetail->TSDEstimatePrice;
                    $tenderSpec->TDSTotalEstimatePrice = $templateSpecDetail->TSDTotalEstimatePrice;
                    $tenderSpec->TDSScoreMax = $templateSpecDetail->TSDScoreMax;
                    $tenderSpec->TDSCB = Auth::user()->USCode;
                    $tenderSpec->save();

                    $tdsNoSeq++;
                }
            }

            $tenderDetail->TDDType      = $request->tDDType;
            $tenderDetail->TDDTitle     = $request->title;

            //check FileAttach
            $templateCode = $request->templateFileNo;

            $tenderFileAttachs = FileAttach::where('FARefNo' , $templateCode)->get();

            foreach ($tenderFileAttachs as $tenderFileAttach){
                if($tenderFileAttach){
                    //get the current template file
                    $folderPath	 = $tenderFileAttach->FAFilePath;
                    $newFileName = $tenderFileAttach->FAFileName;
                    $newFileExt	 = $tenderFileAttach->FAFileExtension;

                    $filePath = $folderPath;

                    $tenderfileContents = Storage::disk('fileStorage')->get($filePath);

                    //$filecontent = file_get_contents($tenderfileContents->getRealPath());

                    //recreate template file
                    $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

                    $folderPath = Carbon::now()->format('ymd');
                    $newFileName = strval($generateRandomSHA256);

                    Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName , $tenderfileContents );

                    $newTenderFileAttach = new FileAttach();
                    $newTenderFileAttach->FA_USCode      = Auth::user()->USCode;
                    $newTenderFileAttach->FARefNo        = $tenderDetail->TDDNo;
                    // $newTenderFileAttach->FAFileType     = $tenderDetail->TDD_MTCode;
                    if($request->tDDType == 'D'){
                        $newTenderFileAttach->FAFileType     = 'TD-PD';

                    }
                    else if($request->tDDType == 'T') {
                        $newTenderFileAttach->FAFileType = 'TD-DD';
                        $tenderDetail->TDDCompleteT = 1;
                    }
                    else if($request->tDDType == 'F'){
                        $newTenderFileAttach->FAFileType     = 'TD-DD';
                        $tenderDetail->TDDCompleteF = 1;
                    }
                    else{
                        $newTenderFileAttach->FAFileType     = $tenderDetail->TDD_MTCode;
                    }

                    $newTenderFileAttach->FAFilePath	 = $folderPath.'\\'.$newFileName;
                    $newTenderFileAttach->FAFileName     = $tenderFileAttach->FAFileName;

                    $newTenderFileAttach->FAOriginalName = $tenderFileAttach->FAOriginalName;
                    $newTenderFileAttach->FAFileExtension= $tenderFileAttach->FAFileExtension;
                    $newTenderFileAttach->FAActive       = $tenderFileAttach->FAActive;
                    $newTenderFileAttach->FAMB 			 = Auth::user()->USCode;

                    //to check fill is uploaded and can 'Papar'

                    $newTenderFileAttach->save();
                }

            }

            $tenderDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>$request->flag]),
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

    public function deleteTenderDetail($id, $flag){

        try {
            DB::beginTransaction();

            $tenderDetail = TenderDetail::where('TDDNo', $id)->first();
            $tenderID = $tenderDetail->TDD_TDNo;

            $tenderFA = FileAttach::where('FARefNo' , $id)->first();

            if($tenderDetail->TDD_MTCode == 'SP'){

                $tenderSpecs = TenderSpec::where('TDS_TDNo', $tenderDetail->TDD_TDNo)
                    ->get();

                foreach($tenderSpecs as $tenderSpec){
                    $tenderSpec->delete();
                }

                $tenderDetailAfters = TenderDetail::where('TDD_TDNo', $tenderDetail->TDD_TDNo)
                    ->where('TDDSeq', '>', $tenderDetail->TDDSeq)
                    ->where(function ($query) {
                        $query->where('TDDType', 'T')
                            ->orWhere('TDDType', 'F');
                    })
                    ->get();
            }
            else{
                $tenderDetailAfters = TenderDetail::where('TDD_TDNo', $tenderDetail->TDD_TDNo)
                    ->where('TDDSeq', '>', $tenderDetail->TDDSeq)
                    ->where('TDDType', 'LIKE', '%'.$tenderDetail->TDDType.'%')
                    ->get();
            }

            foreach ($tenderDetailAfters as $tenderDetailAfter){
                $tenderDetailAfter->TDDSeq = $tenderDetailAfter->TDDSeq - 1;
                $tenderDetailAfter->save();
            }

            $tenderDetail->delete();
            if($tenderFA){
                $tenderFA->delete();
            }

            DB::commit();

            return redirect()->route('perolehan.tender.edit', [$tenderID, 'flag' =>4] );

        }catch (\Throwable $e) {
            DB::rollback();

            return redirect()->route('perolehan.tender.edit', [$tenderID, 'flag' =>4] );
        }
    }

    public function increment3digit($no){
        $prefix = substr($no, 0, 11);   // "TD"
        // $suffix = substr($no, -4);     // "-121"

        // Extract last 3 digits
        $last3Digits = substr($no, -3, 3); // "001"

        // Convert to integer, increment, and format with leading zeros
        $incrementedLast3Digits = str_pad((int)$last3Digits + 1, 3, "0", STR_PAD_LEFT);

        // Combine all parts
        $incrementedno = $prefix . $incrementedLast3Digits;

        return $incrementedno;
    }

    public function increment3digitSpec($no){
        $prefix = substr($no, 0, 15);   // "TD"
        // $suffix = substr($no, -4);     // "-121"

        // Extract last 3 digits
        $last3Digits = substr($no, -3, 3); // "001"

        // Convert to integer, increment, and format with leading zeros
        $incrementedLast3Digits = str_pad((int)$last3Digits + 1, 3, "0", STR_PAD_LEFT);

        // Combine all parts
        $incrementedno = $prefix . $incrementedLast3Digits;

        return $incrementedno;
    }

    public function templateDatatable(Request $request){
        $query = TemplateFile::where('TF_TTCode', 'TD')
            ->whereHas('mechanismType', function ($query) {
                $query->where('MT_DUInd', 1);
            })
            ->orderBy('TFTitle')
            ->get();

        // $query = TemplateFile::where('TF_TTCode', 'TD')->whereIn('TF_MTCode', ['DF', 'BQF', 'CF', 'SPF'])->get();
        $idtender = 0;
        $key = 0;

        if($request->input('idTender') != null){
            $idtender = $request->input('idTender');
        }

        if($request->input('key')){
            $key = $request->input('key');
        }

        return datatables()->of($query)
            ->editColumn('TF_TTCode', function($row) use($request) {
                $jenis_doc = $this->dropdownService->jenis_docDF();
                return $jenis_doc[$row->TF_MTCode];
            })
            ->addColumn('action', function($row) use($idtender, $key) {
                $route = route('perolehan.tender.addIndex.tenderDetail', [$idtender, $row->TFNo, $key, 'D']);

                $result = '<a href="'.$route.'" class="btn btn-sm btn-light-primary"><i class="ki-duotone ki-plus fs-2"></i></a>';

                return $result;
            })
            ->rawColumns(['TF_TTCode', 'action'])
            ->make(true);
    }

    public function templateTeknikalDatatable(Request $request){
        $query = TemplateFile::where('TF_TTCode', 'TD')->whereIn('TF_MTCode', ['BF', 'DF', 'UF', 'SP'])->get();

        $idtender = 0;
        $key = 0;

        if($request->input('idTender') != null){
            $idtender = $request->input('idTender');
        }

        if($request->input('key')){
            $key = $request->input('key');
        }

        return datatables()->of($query)
            ->editColumn('TF_TTCode', function($row) use($request) {
                $jenis_doc = $this->dropdownService->jenis_doc();
                return $jenis_doc[$row->TF_MTCode];
            })
            ->addColumn('action', function($row) use($idtender, $key) {
                $route = route('perolehan.tender.addIndex.tenderDetail', [$idtender, $row->TFNo, $key, 'T']);

                $result = '<a href="'.$route.'" class="btn btn-sm btn-light-primary"><i class="ki-duotone ki-plus fs-2"></i></a>';

                return $result;
            })
            ->rawColumns(['TF_TTCode', 'action'])
            ->make(true);
    }

    public function templateSpecTeknikalDatatable(Request $request){
        // $query = TemplateFile::where('TF_TTCode', 'TD')->whereIn('TF_MTCode', ['BF', 'DF', 'UF', 'SP'])->get();

        $idtender = 0;
        $key = 0;

        if($request->input('idTender') != null){
            $idtender = $request->input('idTender');
            //TBR add checking if there are with department
        }

        if($request->input('key')){
            $key = $request->input('key');
        }

        $query = TemplateSpecHeader::
            orderBy('TSHNo', 'DESC')->get();

        return datatables()->of($query)
            ->addColumn('action', function($row) use($idtender, $key) {
                $route = route('perolehan.tender.add.spec.teknikal', [$idtender, $row->TSHNo, $key, 'T']);

                $result = '<a href="'.$route.'" class="btn btn-sm btn-light-primary"><i class="ki-duotone ki-plus fs-2"></i></a>';

                return $result;
            })
            ->rawColumns(['TF_TTCode', 'action'])
            ->make(true);

        // return datatables()->of($query)
        //     ->editColumn('TF_TTCode', function($row) use($request) {
        //         $jenis_doc = $this->dropdownService->jenis_doc();
        //         return $jenis_doc[$row->TF_MTCode];
        //     })
        //     ->addColumn('action', function($row) use($idtender, $key) {
        //         $route = route('perolehan.tender.addIndex.tenderDetail', [$idtender, $row->TFNo, $key, 'T']);

        //         $result = '<a href="'.$route.'" class="btn btn-light-primary"><i class="material-icons">add_circle_outline</i></a>';

        //         return $result;
        //     })
        //     ->rawColumns(['TF_TTCode', 'action'])
        //     ->make(true);
    }

    public function templateSpecTeknikalOldDatatable(Request $request){

        $idtender = 0;
        $key = 0;

        if($request->input('idTender') != null){
            $idtender = $request->input('idTender');
            //TBR add checking if there are with department
        }

        if($request->input('key')){
            $key = $request->input('key');
        }

        $query = TenderDetail::where('TDD_MTCode', 'SP')
            ->whereHas('tender', function ($query) {
                $query->where('TD_TPCode', '!=', 'DF');
            })
            ->orderBy('TDDNo', 'DESC')
            ->get();

        return datatables()->of($query)
            ->addColumn('TDNo', function($row) use($idtender, $key) {
                $result = $row->tender->TDNo;

                return $result;
            })
            ->addColumn('TDTitle', function($row) use($idtender, $key) {
                $result = $row->tender->TDTitle;

                return $result;
            })
            ->addColumn('action', function($row) use($idtender, $key) {
                $route = route('perolehan.tender.add.specOld.teknikal', [$idtender, $row->TDDNo, $key, 'T']);

                $result = '<a href="'.$route.'" class="btn btn-sm btn-light-primary"><i class="ki-duotone ki-plus fs-2"></i></a>';

                return $result;
            })
            ->rawColumns(['TDNo', 'TDTitle', 'action'])
            ->make(true);
    }

    public function templateTeknikalSpecDatatable(Request $request){
        $query = TemplateFile::where('TF_TTCode', 'TD')->whereIn('TF_MTCode', ['BF', 'DF', 'UF', 'SP'])->get();
        $idtender = 0;
        $key = 0;

        if($request->input('idTender') != null){
            $idtender = $request->input('idTender');
        }

        if($request->input('key')){
            $key = $request->input('key');
        }

        return datatables()->of($query)
            ->editColumn('TF_TTCode', function($row) use($request) {
                $jenis_doc = $this->dropdownService->jenis_doc();
                return $jenis_doc[$row->TF_MTCode];
            })
            ->addColumn('action', function($row) use($idtender, $key) {
                $route = route('perolehan.tender.addIndex.tenderDetail', [$idtender, $row->TFNo, $key, 'T']);

                $result = '<a href="'.$route.'" class="btn btn-sm btn-light-primary"><i class="ki-duotone ki-plus fs-2"></i></a>';

                return $result;
            })
            ->rawColumns(['TF_TTCode', 'action'])
            ->make(true);
    }

    public function templateFinanceDatatable(Request $request){
        $query = TemplateFile::where('TF_TTCode', 'TD')
            ->whereHas('mechanismType', function ($query) {
                $query->where('MT_FSInd', 1);
            })
            ->get();

        $idtender = 0;
        $key = 0;

        if($request->input('idTender') != null){
            $idtender = $request->input('idTender');
        }

        if($request->input('key')){
            $key = $request->input('key');
        }

        return datatables()->of($query)
            ->editColumn('TF_TTCode', function($row) use($request) {
                $jenis_doc = $this->dropdownService->jenis_doc();
                return $jenis_doc[$row->TF_MTCode];
            })
            ->addColumn('action', function($row) use($idtender, $key) {
                $route = route('perolehan.tender.addIndex.tenderDetail', [$idtender, $row->TFNo, $key, 'F']);

                $result = '<a href="'.$route.'" class="btn btn-sm btn-light-primary"><i class="ki-duotone ki-plus fs-2"></i></a>';

                return $result;
            })
            ->rawColumns(['TF_TTCode', 'action'])
            ->make(true);
    }

    public function sediaSpecTeknikal($id, $flag){

        $response_type = $this->dropdownService->response_type();
        $unitMeasurement = $this->dropdownService->unitMeasurement();

        $tenderDetail = TenderDetail::where('TDDNo', $id)->first();

        $tenderSpecs = TenderSpec::where('TDS_TDNo', $tenderDetail->TDD_TDNo)
            ->where('TDS_TDDNo', $tenderDetail->TDDNo)
            ->orderBy('TDSSeq')
            ->get();

        return view('perolehan.tender.new.specTeknikal',
            compact('id', 'flag', 'tenderDetail', 'tenderSpecs', 'response_type', 'unitMeasurement')
        );
    }

    public function storeSpecTeknikal(Request $request){

        try {
            DB::beginTransaction();

            $idTSpecs = $request->TDSNo;
            $uMCodes = $request->uMCode;
            $qtys = $request->qty;
            $responses = $request->response;
            $titles = $request->title;
            $scores = $request->score;
            $index = $request->index;

            foreach ($idTSpecs as $key => $idTSpec){
                $tenderSpec = TenderSpec::where('TDSNo', $idTSpec)->first();
                $tenderSpec->TDSIndex = $index[$key];
                $tenderSpec->TDS_UMCode = $uMCodes[$key];
                $tenderSpec->TDSQty = $qtys[$key];
                $tenderSpec->TDSRespondType = $responses[$key];
                $tenderSpec->TDSDesc = $titles[$key];
                $tenderSpec->TDSScoreMax = $scores[$key];
                $tenderSpec->save();
            }

            if(count($idTSpecs)> 0){
                $tenderDetail = TenderDetail::where('TDDNo', $tenderSpec->TDS_TDDNo)->first();
                $tenderDetail->TDDCompleteT = 1;
                $tenderDetail->save();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>2]),
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

    public function deleteTenderSpec($id, $flag){
        try {
            DB::beginTransaction();

            $tenderSpec = TenderSpec::where('TDSNo', $id)->first();
            $tenderDetailID = $tenderSpec->TDS_TDDNo;
            $tenderSpec->delete();

            DB::commit();

            return redirect()->route('perolehan.tender.sedia.specTeknikal', [$tenderDetailID, 'flag' =>$flag] );

        }catch (\Throwable $e) {
            DB::rollback();

            return redirect()->route('perolehan.tender.sedia.specTeknikal', [$tenderDetailID, 'flag' =>$flag] );
        }
    }

    public function addIndexTenderSpec($id, $flag, $key){
        $response_type = $this->dropdownService->response_type();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();

        $tenderDetail = TenderDetail::where('TDDNo', $id)->first();

        return view('perolehan.tender.new.addSpecTeknikal',
            compact('id', 'flag', 'key', 'tenderDetail', 'response_type', 'unitMeasurement', 'header_detail')
        );
    }

    public function storeIndexSpecTeknikal(Request $request){
        try {
            DB::beginTransaction();

            $tenderSpec_last = TenderSpec::where('TDS_TDNo', $request->idTender)
                ->where('TDS_TDDNo', $request->idTDetail)
                ->orderBy('TDSNo', 'DESC')->first();

            if($request->key == 0){
                if($tenderSpec_last){
                    $tdsNo = $this->increment3digitSpec($tenderSpec_last->TDSNo);
                    $seq = $tenderSpec_last->TDSSeq+1;
                }
                else{
                    $tdsNo = $request->idTDetail.'-'.$formattedCounter = sprintf("%03d", 1);
                    $seq = 1;
                }
            }
            else{
                $seq = $request->key;
                $tdsNo = $this->increment3digitSpec($tenderSpec_last->TDSNo);

                $tenderSpecs_after = TenderSpec::where('TDSSeq', '>', $seq)
                    ->where('TDS_TDNo', $request->idTender)
                    ->where('TDS_TDDNo', $request->idTDetail)
                    ->get();

                foreach ($tenderSpecs_after as $tenderSpec_after){
                    $tenderSpec_after->TDSSeq = $tenderSpec_after->TDSSeq + 1;
                    $tenderSpec_after->save();
                }
                $seq = $request->key+1;
            }

            $tenderSpec = new TenderSpec();
            $tenderSpec->TDSNo = $tdsNo;
            $tenderSpec->TDS_TDNo = $request->idTender;
            $tenderSpec->TDS_TDDNo = $request->idTDetail;
            $tenderSpec->TDSDesc = $request->title;
            $tenderSpec->TDSSeq = $seq;
            $tenderSpec->TDStockInd = $request->header_detail;

            if($request->header_detail == 1){
                $tenderSpec->TDSQty = $request->qty;
                $tenderSpec->TDS_UMCode = $request->uMCode;
            }
            else{
                $tenderSpec->TDSRespondType = $request->response;
                $tenderSpec->TDSScoreMax = $request->score;
            }

            $tenderSpec->save();


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.sedia.specTeknikal', [$request->idTDetail, 'flag' =>$request->flag]),
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

    public function sediaSpecFinance($id, $flag){
        $unitMeasurement = $this->dropdownService->unitMeasurement();

        $tenderDetail = TenderDetail::where('TDDNo', $id)->first();

        $tenderSpecs = TenderSpec::where('TDS_TDNo', $tenderDetail->TDD_TDNo)
            ->where('TDS_TDDNo', $tenderDetail->TDDNo)
            ->where('TDStockInd', 1)
            ->orderBy('TDSSeq')
            ->get();

        return view('perolehan.tender.new.specFinance',
            compact('id', 'flag', 'tenderDetail', 'tenderSpecs', 'unitMeasurement')
        );
    }

    public function storeSpecFinance(Request $request){
        try {
            DB::beginTransaction();

            $tdsNos = $request->TDSNo;
            $estimatePrices = $request->estimatePrice;
            $totalEstimatePrices = $request->totalEstimatePrice;
            $scores = $request->score;

            foreach ($tdsNos as $key => $tdsNo){
                $tenderSpec = TenderSpec::where('TDSNo', $tdsNo)->first();
                $tenderSpec->TDSEstimatePrice = $estimatePrices[$key];
                $tenderSpec->TDSTotalEstimatePrice = $totalEstimatePrices[$key];
                $tenderSpec->TDSScoreMax = $scores[$key];
                $tenderSpec->save();
            }

            if(count($tdsNos)> 0){
                $tenderDetail = TenderDetail::where('TDDNo', $request->idTDetail)->first();
                $tenderDetail->TDDCompleteF = 1;
                $tenderDetail->save();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>3]),
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

    public function sediaAsetLiabiliti($id, $flag){
        $tenderDetail = TenderDetail::where('TDDNo', $id)->first();

        $jenis_score = $this->dropdownService->jenis_score();

        return view('perolehan.tender.new.asetLiabiliti',
            compact('id', 'flag', 'tenderDetail', 'jenis_score')
        );
    }

    public function storeAsetLiabiliti(Request $request){
        try {
            DB::beginTransaction();

            $tender = Tender::where('TDNo', $request->idTender)->first();
            $tender->TDLiquidityRatioScoreType = $request->jenisScoreLiquidity;
            $tender->TDLiquidityRatioScoreMax = $request->scoreLiquidity;
            $tender->TDCurrentRatioScoreType = $request->jenisScoreCurrent;
            $tender->TDCurrentRatioScoreMax = $request->scoreCurrent;
            $tender->save();

            $tenderDetail = TenderDetail::where('TDDNo', $request->idTDetail)->first();
            $tenderDetail->TDDCompleteF = 1;
            $tenderDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>$request->flag]),
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

    public function sediaBankLoan($id, $flag){
        $tenderDetail = TenderDetail::where('TDDNo', $id)->first();

        $jenis_score = $this->dropdownService->jenis_score();

        return view('perolehan.tender.new.bankLoan',
            compact('id', 'flag', 'tenderDetail', 'jenis_score')
        );
    }

    public function storeBankLoan(Request $request){
        try {
            DB::beginTransaction();

            $tender = Tender::where('TDNo', $request->idTender)->first();
            $tender->TDBankLoanScoreType = $request->jenisScoreBankLoan;
            $tender->TDBankLoanScoreMax = $request->scoreBankLoan;
            $tender->save();

            $tenderDetail = TenderDetail::where('TDDNo', $request->idTDetail)->first();
            $tenderDetail->TDDCompleteF = 1;
            $tenderDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>$request->flag]),
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

    public function sediaBankBalance($id, $flag){
        $tenderDetail = TenderDetail::where('TDDNo', $id)->first();

        $jenis_score = $this->dropdownService->jenis_score();

        return view('perolehan.tender.new.bankBalance',
            compact('id', 'flag', 'tenderDetail', 'jenis_score')
        );
    }

    public function storeBankBalance(Request $request){
        try {
            DB::beginTransaction();

            $tender = Tender::where('TDNo', $request->idTender)->first();
            $tender->TDBankStmtYear = $request->yearBankBalance;
            $tender->TDBankStmtScoreType = $request->jenisScoreBankBalance;
            $tender->TDBankStmtScoreMax = $request->scoreBankBalance;
            $tender->save();

            $tenderDetail = TenderDetail::where('TDDNo', $request->idTDetail)->first();
            $tenderDetail->TDDCompleteF = 1;
            $tenderDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>$request->flag]),
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

    public function sediaHisProj($id, $flag){
        $tenderDetail = TenderDetail::where('TDDNo', $id)->first();

        $jenis_score = $this->dropdownService->jenis_score();

        return view('perolehan.tender.new.hisProj',
            compact('id', 'flag', 'tenderDetail', 'jenis_score')
        );
    }

    public function storeHisProj(Request $request){
        try {
            DB::beginTransaction();

            $tender = Tender::where('TDNo', $request->idTender)->first();
            $tender->TDProjYear = $request->yearProj;
            $tender->TDProjScoreType = $request->jenisScore;
            $tender->TDProjScoreMax = $request->scoreProj;
            $tender->save();

            $tenderDetail = TenderDetail::where('TDDNo', $request->idTDetail)->first();
            $tenderDetail->TDDCompleteF = 1;
            $tenderDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>$request->flag]),
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

    public function updateStatusTender($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tender = Tender::where('TDNo',$id)->first();

            // if ($tender->TDLOI == 0) {
                //    $submitStatus = 'TR';
            // }else{
                $submitStatus = 'PA';
            // }

            $tender->TD_TPCode = $submitStatus;
            $tender->TDMB = $user->USCode;
            $tender->save();

            $this->sendNotification($tender,'S');

            DB::commit();

            return redirect()->route('perolehan.tender.edit', [$id,'flag' =>'2']);

            // return response()->json([
            //     'success' => '1',
            //     'redirect' => route('publicUser.proposal.index'),
            //     'message' => 'Maklumat cadangan berjaya dihantar.'
            // ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat cadangan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function addSpecHeaderTeknikal($id, $templateCode, $indexNo, $tddType = 'T'){
        // $jenis_doc = $this->dropdownService->jenis_docT();

        // $template_header = $this->dropdownService->template_header();

        $tender = Tender::where('TDNo', $id)->first();

        $templateSpecHeader = TemplateSpecHeader::where('TSHNo', $templateCode)->first();

        if ($tddType == 'T'){
            $tddType = 'T';
            $flag = 2;
        }
        else if ($tddType == 'F'){
            $tddType = 'F';
            $flag = 3;
        }
        else{
            $tddType = 'D';
            $flag = 4;
        }

        $key = $indexNo;

        return view('perolehan.tender.new.chooseTemplateSpec',
            compact('tender', 'key', 'indexNo', 'flag', 'tddType', 'templateSpecHeader')
        );
    }

    public function addSpecHeaderTeknikalOld($id, $templateCode, $indexNo, $tddType = 'T'){
        // $jenis_doc = $this->dropdownService->jenis_docT();

        // $template_header = $this->dropdownService->template_header();

        $tender = Tender::where('TDNo', $id)->first();

        //change here
        $templateDetail = TenderDetail::where('TDDNo', $templateCode)->first();

        if ($tddType == 'T'){
            $tddType = 'T';
            $flag = 2;
        }
        else if ($tddType == 'F'){
            $tddType = 'F';
            $flag = 3;
        }
        else{
            $tddType = 'D';
            $flag = 4;
        }

        $key = $indexNo;

        return view('perolehan.tender.new.chooseTemplateSpecOld',
            compact('tender', 'key', 'indexNo', 'flag', 'tddType', 'templateDetail')
        );
    }

    public function storeSpecHeaderTeknikal(Request $request){
        try {
            DB::beginTransaction();

            $latest = TenderDetail::where('TDD_TDNo', $request->idTender)->orderBy('TDDNo', 'desc')
                ->first();

            if($latest){
                $tddNo = $this->increment3digit($latest->TDDNo);
                $allTD = TenderDetail::where('TDD_TDNo', $request->idTender)
                    ->where('TDDType', 'LIKE', '%'.$request->tDDType.'%')
                    ->get();

                foreach ($allTD as $item => $tenderDetail) {
                    $tenderDetail->TDDSeq = $tenderDetail->TDDSeq + 1;
                    $tenderDetail->save();
                }

                $allTDF = TenderDetail::where('TDD_TDNo', $request->idTender)
                    ->where('TDDType', 'LIKE', '%F%')
                    ->get();

                foreach ($allTDF as $itemF => $tenderDetailF) {
                    $tenderDetailF->TDDSeq = $tenderDetailF->TDDSeq + 1;
                    $tenderDetailF->save();
                }

                $seq = count($allTD)+1;
                $seq = 1;
            }
            else{
                $tddNo = $request->idTender.'-'.$formattedCounter = sprintf("%03d", 1);
                $seq = 1;
            }

            $tenderDetail = new TenderDetail();
            $tenderDetail->TDDNo        = $tddNo;
            $tenderDetail->TDD_TDNo     = $request->idTender;
            $tenderDetail->TDDSeq       = $seq;
            $tenderDetail->TDD_MTCode   = 'SP';
            $tenderDetail->TDDCB        = Auth::user()->USCode;

            if(isset($request->templateSpecHeader)){
                $templateSpecHeader = TemplateSpecHeader::where('TSHNo', $request->templateSpecHeader)->first();


                $tdsNoSeq = 1;

                foreach($templateSpecHeader->templateSpecDetail as $templateSpecDetail){
                    $tdsNo = $tddNo.'-'.$formattedCounter = sprintf("%03d", $tdsNoSeq);

                    $tenderSpec = new TenderSpec();
                    $tenderSpec->TDSNo = $tdsNo;
                    $tenderSpec->TDS_TDNo = $request->idTender;
                    $tenderSpec->TDS_TDDNo = $tddNo;
                    $tenderSpec->TDSIndex = $templateSpecDetail->TSDIndex;
                    $tenderSpec->TDSSeq = $templateSpecDetail->TSDSeq;
                    $tenderSpec->TDStockInd = $templateSpecDetail->TSDStockInd;
                    $tenderSpec->TDSDesc = $templateSpecDetail->TSDDesc;
                    $tenderSpec->TDSQty = $templateSpecDetail->TSDQty;
                    $tenderSpec->TDS_UMCode = $templateSpecDetail->TSD_UMCode;
                    $tenderSpec->TDSRespondType = $templateSpecDetail->TSDRespondType;
                    $tenderSpec->TDSEstimatePrice = $templateSpecDetail->TSDEstimatePrice;
                    $tenderSpec->TDSTotalEstimatePrice = $templateSpecDetail->TSDTotalEstimatePrice;
                    $tenderSpec->TDSScoreMax = $templateSpecDetail->TSDScoreMax;
                    $tenderSpec->TDSCB = Auth::user()->USCode;
                    $tenderSpec->save();

                    $tdsNoSeq++;
                }
            }
            $tenderDetail->TDDType      = 'T,F';
            $tenderDetail->TDDTitle     = $request->title;
            $tenderDetail->TDDCompleteT  = 0;
            $tenderDetail->TDDCompleteF  = 0;
            $tenderDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>2]),
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

    public function storeSpecHeaderTeknikalOld(Request $request){
        try {
            DB::beginTransaction();

            $latest = TenderDetail::where('TDD_TDNo', $request->idTender)->orderBy('TDDNo', 'desc')
                ->first();

            if($latest){
                $tddNo = $this->increment3digit($latest->TDDNo);
                $allTD = TenderDetail::where('TDD_TDNo', $request->idTender)
                    ->where('TDDType', 'LIKE', '%'.$request->tDDType.'%')
                    ->get();

                foreach ($allTD as $item => $tenderDetail) {
                    $tenderDetail->TDDSeq = $tenderDetail->TDDSeq + 1;
                    $tenderDetail->save();
                }

                $allTDF = TenderDetail::where('TDD_TDNo', $request->idTender)
                    ->where('TDDType', 'LIKE', '%F%')
                    ->get();

                foreach ($allTDF as $itemF => $tenderDetailF) {
                    $tenderDetailF->TDDSeq = $tenderDetailF->TDDSeq + 1;
                    $tenderDetailF->save();
                }

                $seq = count($allTD)+1;
                $seq = 1;
            }
            else{
                $tddNo = $request->idTender.'-'.$formattedCounter = sprintf("%03d", 1);
                $seq = 1;
            }

            $tenderDetail = new TenderDetail();
            $tenderDetail->TDDNo        = $tddNo;
            $tenderDetail->TDD_TDNo     = $request->idTender;
            $tenderDetail->TDDSeq       = $seq;
            $tenderDetail->TDD_MTCode   = 'SP';
            $tenderDetail->TDDCB        = Auth::user()->USCode;

            if(isset($request->templateDetail)){
                $templateTenderDetail = TenderDetail::where('TDDNo', $request->templateDetail)->first();

                $tdsNoSeq = 1;

                foreach($templateTenderDetail->tenderSpec as $tenderSpecOld){
                    $tdsNo = $tddNo.'-'.$formattedCounter = sprintf("%03d", $tdsNoSeq);

                    $tenderSpec = new TenderSpec();
                    $tenderSpec->TDSNo = $tdsNo;
                    $tenderSpec->TDSIndex = $tenderSpecOld->TDSIndex;
                    $tenderSpec->TDS_TDNo = $request->idTender;
                    $tenderSpec->TDS_TDDNo = $tddNo;
                    $tenderSpec->TDSSeq = $tenderSpecOld->TDSSeq;
                    $tenderSpec->TDStockInd = $tenderSpecOld->TDStockInd;
                    $tenderSpec->TDSDesc = $tenderSpecOld->TDSDesc;
                    $tenderSpec->TDSQty = $tenderSpecOld->TDSQty;
                    $tenderSpec->TDS_UMCode = $tenderSpecOld->TDS_UMCode;
                    $tenderSpec->TDSRespondType = $tenderSpecOld->TDSRespondType;
                    $tenderSpec->TDSEstimatePrice = $tenderSpecOld->TDSEstimatePrice;
                    $tenderSpec->TDSTotalEstimatePrice = $tenderSpecOld->TDSTotalEstimatePrice;
                    $tenderSpec->TDSScoreMax = $tenderSpecOld->TDSScoreMax;
                    $tenderSpec->TDSCB = Auth::user()->USCode;
                    $tenderSpec->save();

                    $tdsNoSeq++;
                }
            }
            $tenderDetail->TDDType      = 'T,F';
            $tenderDetail->TDDTitle     = $request->title;
            $tenderDetail->TDDCompleteT  = 0;
            $tenderDetail->TDDCompleteF  = 0;
            $tenderDetail->save();

            $templateTenderDetailn = TenderDetail::where('TDDNo', $tddNo)->first();

            DB::commit();

//            return redirect()->route('perolehan.tender.edit', [$request->idTender, 'flag' =>$request->flag]);

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>$request->flag]),
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

    public function updatePIC(Request $request){
        try {
            DB::beginTransaction();

            $tender = Tender::where('TDNo', $request->noTender)->first();

            $old_tenderPIC = TenderPIC::where('TPIC_TDNo', $request->noTender)->get();

            $idTPICs = $request->idTPIC ?? array();
            $users = $request->user;
            $peranans = $request->peranan;

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_tenderPIC) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_tenderPIC as $otenderPIC){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($idTPICs as $idTPIC){
                            if($otenderPIC->TPICID == $idTPIC){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $otenderPIC->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($idTPICs as $key => $idTPIC){
                    $new_tenderPIC = TenderPIC::where('TPIC_TDNo', $request->noTender)->where('TPICID', $idTPIC)->first();
                    if(!$new_tenderPIC){
                        $new_tenderPIC = new TenderPIC();
                        $new_tenderPIC->TPIC_TDNo = $request->noTender;
                        $new_tenderPIC->TPICCB = Auth::user()->USCode;
                    }
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                    $new_tenderPIC->TPIC_USCode = $users[$key];
                    $new_tenderPIC->TPICType = $peranans[$key];
                    $new_tenderPIC->save();
                }
            }
            else{
                foreach($idTPICs as $key => $idTPIC){
                    $new_tenderPIC = new TenderPIC();
                    $new_tenderPIC->TPIC_TDNo = $request->noTender;
                    $new_tenderPIC->TPIC_USCode = $users[$key];
                    $new_tenderPIC->TPICType = $peranans[$key];
                    $new_tenderPIC->TPICCB = Auth::user()->USCode;
                    $new_tenderPIC->save();
                }
            }
            //END HERE

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$tender->TDNo, 'flag' => 1]),
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

    public function updateScore(Request $request){
        try {
            DB::beginTransaction();

            $tender = Tender::where('TDNo', $request->noTender)->first();
            $tender->TDOSCPassRate              = $request->passRateOSC;
            $tender->TDTechnicalPassRate        = $request->passRateTeknikal;
            $tender->TDFinancePassRate          = $request->passRateKewangan;
            $tender->TDOverallPassRate          = $request->passRateOverall;
            $tender->TDOSCScorePercent          = $request->osc;
            $tender->TDTechnicalScorePercent    = $request->teknikal;
            $tender->TDFinanceScorePercent      = $request->kewangan;
            $tender->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$tender->TDNo, 'flag' => 6]),
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

    public function editTitle($id, $flag){
        $tenderDetail = TenderDetail::where('TDDNo', $id)->first();

        return view('perolehan.tender.new.editTitle',
            compact('tenderDetail', 'flag')
        );
    }

    public function storeTitle(Request $request){
        try {

            DB::beginTransaction();

            $tenderDetail = TenderDetail::where('TDDNo', $request->idTDetail)->first();
            $tenderDetail->TDDTitle = $request->title;
            $tenderDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.edit', [$request->idTender, 'flag' =>$request->flag]),
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

    public function penilaian($id){

        $webSetting = WebSetting::first();
        $tender = Tender::where('TDNo', $id)
            ->first();

        $TDANo = $tender->TD_TDANo;

        $tenderProposal = TenderProposal::where('TP_TDNo', $id)
        ->where('TP_TDANo',$TDANo)
        ->whereHas('tenderProposalOSC')
        ->first();

        if($tenderProposal){
            $tenderProposalOSC = TenderProposalOSC::where('TPO_TPNo', $tenderProposal->TPNo)
                ->first();

            $oscScoreMax = $webSetting->OSCTotalScoreMax ?? 0;
        }
        else{
            $oscScoreMax = 0;
        }

        $typeSel = $tender->TDScoreTypeSel;
        $topSel  = $tender->TDScoreTopSel;

        if($typeSel == 'A'){
            $overallProposals = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TDANo',$TDANo)
            ->where('TP_TPPCode','SB')
            ->where('TPEvaluationStep', '>=', 1)
            ->with('tenderProposalSpec', 'tenderProposalSpec.tenderSpec')
            ->orderBy('TPOverallScore', 'DESC')
            ->get();
        }
        else if($typeSel == 'F'){
            $overallProposals = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TDANo',$TDANo)
            ->where('TP_TPPCode','SB')
                ->where('TPEvaluationStep', '>=', 1)
            ->with('tenderProposalSpec', 'tenderProposalSpec.tenderSpec')
            ->orderBy('TPFinanceScore', 'DESC')
            ->get();
        }
        else if($typeSel == 'T'){
            $overallProposals = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TDANo',$TDANo)
            ->where('TP_TPPCode','SB')
                ->where('TPEvaluationStep', '>=', 1)
            ->with('tenderProposalSpec', 'tenderProposalSpec.tenderSpec')
            ->orderBy('TPTechnicalScore', 'DESC')
            ->get();
        }
        else if($typeSel == 'O'){
            $overallProposals = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TDANo',$TDANo)
            ->where('TP_TPPCode','SB')
                ->where('TPEvaluationStep', '>=', 1)
            ->with('tenderProposalSpec', 'tenderProposalSpec.tenderSpec')
            ->orderBy('TPOSCScore', 'DESC')
            ->get();
        }else{
            $overallProposals = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TDANo',$TDANo)
            ->where('TP_TPPCode','SB')
                ->where('TPEvaluationStep', '>=', 1)
            ->with('tenderProposalSpec', 'tenderProposalSpec.tenderSpec')
            ->orderBy('TPOverallScore', 'DESC')
            ->get();
        }



        $technicalProposals = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TDANo',$TDANo)
            ->where('TP_TPPCode','SB')
            ->where('TPEvaluationStep', '>=', 1)
            ->with('tenderProposalSpec', 'tenderProposalSpec.tenderSpec')
            ->orderBy('TPTechnicalScore', 'DESC')
            ->get();

        $kewanganProposals = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TDANo',$TDANo)
            ->where('TP_TPPCode','SB')
            ->where('TPEvaluationStep', '>=', 1)
            ->with('tenderProposalSpec', 'tenderProposalSpec.tenderSpec')
            ->orderBy('TPFinanceScore', 'DESC')
            ->get();

         $oscProposals = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TDANo',$TDANo)
             ->where('TP_TPPCode','SB')
             ->where('TPEvaluationStep', '>=', 1)
             ->with('tenderProposalSpec', 'tenderProposalSpec.tenderSpec')
             ->orderBy('TPOSCScore', 'DESC')
             ->get();

        $tenderSpecs = TenderSpec::where('TDS_TDNo', $id)->get();

        $teknikalScore = 0;
        $kewanganScore = 0;

        foreach ($tenderSpecs as $tenderSpec){

            if($tenderSpec->TDStockInd == 0){
                $teknikalScore += $tenderSpec->TDSScoreMax;
            }
            if($tenderSpec->TDStockInd == 1){
                $kewanganScore += $tenderSpec->TDSScoreMax;
            }
        }

        $totalMarkTeknikal = $teknikalScore;
        $totalMarkKewanagan = $kewanganScore;

        foreach($oscProposals as $oscProposal){
            $oscScoreCheck = $oscProposal->tenderProposalOSC->TPOTotalScore ?? null;

            $proposalOSC = TenderProposalOSC::where('TPO_TPNo', $oscProposal->TPNo)->first();

            $scoreData = [
                'Bank' => [
                    'score' => $proposalOSC->TPOBankScore ?? '',
                ],
                'SSM' => [
                    'score' => $proposalOSC->TPOSSMScore ?? '',
                ],
                'CIDB' => [

                    'score' => $proposalOSC->TPOCIDBScore ?? '',
                ],
                'CTOS' => [

                    'score' => $proposalOSC->TPOCTOSScore ?? '',
                ],
                'Pengalaman Projek DBKL' => [

                    'score' => $proposalOSC->TPODBKLScore ?? '',
                ],
                'Pengalaman Projek BLP' => [

                    'score' => $proposalOSC->TPOBLPScore ?? '',
                ],
                'Insolvensi	' => [

                    'score' => $proposalOSC->TPOMDIScore ?? '',
                ],
                'Berita' => [

                    'score' => $proposalOSC->TPONewsScore ?? '',
                ],

            ];
        }


        return view('perolehan.tender.penilaian.index',
            compact('id', 'tender', 'technicalProposals', 'totalMarkTeknikal', 'totalMarkKewanagan', 'kewanganProposals',
                'oscProposals', 'overallProposals', 'oscScoreMax' , 'topSel' , 'webSetting' ,'oscScoreCheck' , 'scoreData'
            // , 'oscScore'  , 'tenderProposalOSC', 'tenderProposal' , 'tpSeqNo'
            )
        );
    }

    public function getTPScore($id){

        $tp = TenderProposal::where('TPNo' , $id)->first();

        $proposalOSC = TenderProposalOSC::where('TPO_TPNo', $tp->TPNo)->first();

        $scoreData = [
            'Bank' => [
                'score' => $proposalOSC->TPOBankScore ?? '',
            ],
            'SSM' => [
                'score' => $proposalOSC->TPOSSMScore ?? '',
            ],
            'CIDB' => [

                'score' => $proposalOSC->TPOCIDBScore ?? '',
            ],
            'CTOS' => [

                'score' => $proposalOSC->TPOCTOSScore ?? '',
            ],
            'Pengalaman Projek DBKL' => [

                'score' => $proposalOSC->TPODBKLScore ?? '',
            ],
            'Pengalaman Projek BLP' => [

                'score' => $proposalOSC->TPOBLPScore ?? '',
            ],
            'Insolvensi	' => [

                'score' => $proposalOSC->TPOMDIScore ?? '',
            ],
            'Berita' => [
                'score' => $proposalOSC->TPONewsScore ?? '',
            ],

        ];

        return $scoreData;
    }

    public function submitPenilaian($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tender = Tender::where('TDNo', $id)->first();
            $tender->TD_TPCode = 'ES';
            $tender->save();

            $proposals = TenderProposal::where('TP_TDNo', $id)
                ->where('TP_TDANo',$tender->TD_TDANo)
                ->where('TP_TPPCode','SB')
                ->where('TPEvaluationStep',1)
                ->get();

            foreach ($proposals as $proposal){
                // if($proposal->TPTechnicalPass == 1 && $proposal->TPFinancePass == 1 && $proposal->TPOverallPass == 1
                // && $proposal->TPProposeWinner != null && $proposal->TPProposeWinner != 0 ){
                if($proposal->TPProposeWinner != null && $proposal->TPProposeWinner != 0 ){
                    $proposal->TPEvaluationStep = 2;
                    $proposal->save();
                }
            }

            $this->sendPenilaianNotification($tender);

            DB::commit();
            // return redirect()->route('perolehan.tugasan.index');


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat cadangan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function indexPenilaian(){

        return view('perolehan.tender.indexPenilaian'
        );
    }

    public function penilaianDatatable(Request $request){
        $query = Tender::whereNotIn('TD_TPCode', ['DF', 'PA', 'CA'])
            ->orderBy('TDNo', 'DESC')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('TDNo', function($row) {
                $route = route('perolehan.tender.penilaian.index',[$row->TDNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->TDNo.' </a>';

                return $result;

            })
            ->editColumn('Status', function($row) {
                $tenderProcess = $this->dropdownService->tenderProcess();

                return $tenderProcess[$row->TD_TPCode];
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo', 'TDNo', 'TDTitle', 'Status'])
            ->make(true);
    }

    public function storeRumusan(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $webSetting = WebSetting::first();

            $TPNos = $request->TPNo;
            $proposeWinner = $request->kedudukan;
            $proposeRemark = $request->remark;

            $updateStatus = $request->updateStatus;
            $updateKedudukan = $request->updateKedudukan;  // TO determine either proceed with penilaian submit or not

            $tender = Tender::where('TDNo', $request->tdNo)->first();
            $tender->TDOSCPassRate              = $request->passRateOSC;
            $tender->TDTechnicalPassRate        = $request->passRateTeknikal;
            $tender->TDFinancePassRate          = $request->passRateKewangan;
            $tender->TDOverallPassRate          = $request->passRateOverall;
            $tender->TDOSCScorePercent          = $request->osc;
            $tender->TDTechnicalScorePercent    = $request->teknikal;
            $tender->TDFinanceScorePercent      = $request->kewangan;

            if($updateKedudukan == 1){
                $tender->TDScoreTypeSel         = $request->typeSel;
                $tender->TDScoreTopSel          = $request->topSelect;
            }
            $tender->save();

            $tenderSpecs = TenderSpec::where('TDS_TDNo', $request->tdNo)->get();

            $teknikalScore = 0;
            $kewanganScore = 0;

            foreach ($tenderSpecs as $tenderSpec){

                if($tenderSpec->TDStockInd == 0){
                    $teknikalScore += $tenderSpec->TDSScoreMax;
                }
                if($tenderSpec->TDStockInd == 1){
                    $kewanganScore += $tenderSpec->TDSScoreMax;
                }
            }

            $tenderProposal = TenderProposal::where('TP_TDNo', $request->tdNo)
            ->where('TP_TDANo',$tender->TD_TDANo)
            ->whereHas('tenderProposalOSC')
            ->first();

            if($tenderProposal){
                $tenderProposalOSC = TenderProposalOSC::where('TPO_TPNo', $tenderProposal->TPNo)
                    ->first();

                $OSCScore = (float)($webSetting->OSCTotalScoreMax) ?? 0;
            }
            else{
                $OSCScore = 0;
            }

            $totalMarkTeknikal = $teknikalScore;
            $totalMarkKewanagan = $kewanganScore;
            $totalMarkOSC = $OSCScore;

            // $proposals = TenderProposal::where('TP_TDNo', $request->tdNo)
            //     ->where('TP_TPPCode','SB')
            //     ->where('TPEvaluationStep',1)
            //     ->get();
            foreach ($TPNos as $key => $TPNo){
                $proposal = TenderProposal::where('TPNo', $TPNo)
                ->where('TP_TDANo',$tender->TD_TDANo)
                ->first();

                $percent = ($totalMarkTeknikal != 0) ? ($proposal->TPTechnicalScore / $totalMarkTeknikal) * 100 : 0;

                //$percent = ($proposal->TPTechnicalScore/$totalMarkTeknikal) * 100;
                $percentFormat = number_format($percent ?? 0, 2, '.', ',');

                if($percentFormat >= $tender->TDTechnicalPassRate){
                    $TPTechnicalPass = 1;
                }
                else{
                    $TPTechnicalPass = 0;
                }
                $percentF = ($totalMarkKewanagan != 0) ? ($proposal->TPFinanceScore / $totalMarkKewanagan) * 100 : 0;
                //$percentF = ($proposal->TPFinanceScore/$totalMarkKewanagan) * 100;
                $percentFormatF = number_format($percentF ?? 0, 2, '.', ',');
                if($percentFormatF >= $tender->TDFinancePassRate){
                    $TPFinancePass = 1;
                }
                else{
                    $TPFinancePass = 0;
                }

                $percentO = ($totalMarkOSC != 0) ? ($proposal->TPOSCScore / $totalMarkOSC) * 100 : 0;
                //$percentO = ($proposal->TPOSCScore/$totalMarkOSC) * 100;
                $percentFormatO = number_format($percentO ?? 0, 2, '.', ',');
                if($percentFormatO >= $tender->TDOSCPassRate){
                    $TPOSCPass = 1;
                }
                else{
                    $TPOSCPass = 0;
                }

                // $markOSC = ($proposal->TPOSCScore/$totalMarkOSC) * $tender->TDOSCScorePercent;
                // $markTeknikal = ($proposal->TPTechnicalScore/$totalMarkTeknikal) * $tender->TDTechnicalScorePercent;
                // $markFinance = ($proposal->TPFinanceScore/$totalMarkKewanagan) * $tender->TDFinanceScorePercent;

                $markOSC = ($totalMarkOSC != 0) ? ($proposal->TPOSCScore / $totalMarkOSC) * $tender->TDOSCScorePercent : 0;
                $markTeknikal = ($totalMarkTeknikal != 0) ? ($proposal->TPTechnicalScore / $totalMarkTeknikal) * $tender->TDTechnicalScorePercent : 0;
                $markFinance = ($totalMarkKewanagan != 0) ? ($proposal->TPFinanceScore / $totalMarkKewanagan) * $tender->TDFinanceScorePercent : 0;


                $totalmark = $markOSC + $markTeknikal +$markFinance;

                if($totalmark >= $tender->TDOverallPassRate){
                    $TPOverallPass = 1;
                }
                else{
                    $TPOverallPass = 0;
                }

                $proposal->TPOSCPass = $TPOSCPass;
                $proposal->TPTechnicalPass = $TPTechnicalPass;
                $proposal->TPFinancePass = $TPFinancePass;
                $proposal->TPOverallScore = $totalmark;
                $proposal->TPOverallPass = $TPOverallPass;
                $proposal->TPProposeWinner = $proposeWinner[$key];
                $proposal->TPProposeRemark = $proposeRemark[$key];
                $proposal->save();
            }

            if($updateStatus == 1){

                $this->submitPenilaian($request->tdNo);
                DB::commit();
                return response()->json([
                    'success' => '1',
                    'redirect' => route('perolehan.tugasan.index'),
                    'message' => 'Maklumat berjaya dikemaskini.'
                ]);

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tender.penilaian.index', [$request->tdNo, 'flag'=>0]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    function sendNotification($tender,$code){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $code = "TD-SM";
            $tenderPIC = $tender->tenderPIC;

            //#NOTIF-016
            $title = "Tender Baru Dikemukakan";
            $desc = "Perhatian, maklumat tender $tender->TDNo telah dikemukakan.";

            if(!empty($tenderPIC)){

                foreach($tenderPIC as $pic){

                    if($pic->TPICType == 'T'){
                        $pelaksanaType = "SO";

                    }else if($pic->TPICType == 'K'){
                        $pelaksanaType = "FO";

                    }else if($pic->TPICType == 'P'){
                        $pelaksanaType = "PO";

                    }

                    $refNo = $pic->TPIC_USCode;
                    $notiType = $pelaksanaType;

                    $data = array(
                        'TDNo' => $tender->TDNo
                    );

                    $notification = new GeneralNotificationController();
                    $result = $notification->sendNotification($refNo,$notiType,$code,$data);

                    // $notification = new Notification();
                    // $notification->NO_RefCode = $pic->TPIC_USCode;
                    // $notification->NOType = $pelaksanaType;
                    // $notification->NO_NTCode = $notiType;
                    // $notification->NOTitle = $title;
                    // $notification->NODescription = $desc;
                    // $notification->NORead = 0;
                    // $notification->NOSent = 1;
                    // $notification->NOActive = 1;
                    // $notification->NOCB = $user->USCode;
                    // $notification->NOMB = $user->USCode;
                    // $notification->save();
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

    function sendPenilaianNotification($tender){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            //#NOTIF-023
            $notiType = "ESP";
            $title = "Penilain Tender Perolehan Selesai - $tender->TDNo";
            $desc = "Perhatian, penilaian bagi tender $tender->TDNo telah selesai dinilai oleh perolehan.";

            //SEND NOTIFICATION TO PEROLEHAN
            $tenderPIC = $tender->tenderPIC_P;
            $pelaksanaType = "PO";

            if(!empty($tenderPIC)){

                foreach($tenderPIC as $pic){

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
