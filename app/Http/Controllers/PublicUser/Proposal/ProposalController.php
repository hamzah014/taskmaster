<?php

namespace App\Http\Controllers\PublicUser\Proposal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Osc\Approval\ApprovalController;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController as AppApprovalController;
use App\Models\FileAttach;
use App\Models\SSMCompany;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\TenderApplicationAuthSign;
use App\Models\TenderDetail;
use App\Models\TenderProposal;
use App\Models\TenderProposalDetail;
use App\Models\TenderProposalHisProj;
use App\Models\TenderProposalSpec;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Services\DropdownService;

use Yajra\DataTables\DataTables;
use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\SUbmission;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use App\Models\AutoNumber;
use ZipStream\File;
use App\Models\LetterIntent;
use App\Models\LetterAcceptance;
use App\Models\LetterAcceptanceDeduction;
use App\Models\Notification;
use App\Models\TemplateFile;
use App\Models\TenderFormHeader;
use App\Models\TenderSpec;

class ProposalController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        return view('publicUser.proposal.index');
    }

    public function proposalDatatable(Request $request){

        $user = Auth::user();

        $query = TenderProposal::join('TRTender','TDNo','TP_TDNo')
                ->where('TP_CONo', $user->USCode)
                ->orderBy('TPNo', 'DESC')
                ->get();

        $proposalProcess = $this->dropdownService->proposalProcess();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('TDTitle', function($row) {
                // $result = '<a href="#" onclick="openIklanModal(\''.$row->TDNo.'\')" class="new modal-trigger waves-effect waves-light">'.$row->TDTitle.'</a>';

                $routeView = route('publicUser.proposal.view',[$row->TP_TDNo]);

                $result = '<a href="'.$routeView.'" class="new modal-trigger waves-effect waves-light">'.$row->TDTitle.'</a>';
                return $result;
            })
            ->editColumn('TPSubmitDate', function($row) {
                $formattedDate = null;
                if($row->TPSubmitDate != null){
                    $carbonDatetime = Carbon::parse($row->TPSubmitDate);
                    $formattedDate = $carbonDatetime->format('d/m/Y');
                }

                return $formattedDate;
            })
            ->addColumn('status', function($row) use ($proposalProcess) {

                return $proposalProcess[$row->TP_TPPCode];
            })
            ->addColumn('action', function($row) {
                $result = '';


                if($row->TP_TPPCode == 'DF'){
                    $route = route('publicUser.proposal.edit',[$row->TPNo]);
                    $result .= '<a href="'.$route.'" class="btn btn-sm btn-primary"><i class="ki-solid ki-notepad-edit"></i>Sedia</a>';

                }

                elseif($row->TP_TPPCode == 'SB-RQ'){
                    $result .= 'MENUNGGU PENGESAHAN';

                }
                else if($row->TP_TPPCode == 'SB'){

                    $routeZip = route('zipit.generate', ['id'=> $row->TPNo, 'PU']);
                    $result .= '&nbsp;<a target="_blank" href="'.$routeZip.'" class=" btn btn-sm btn-light-primary mr-1"><i class="ki-solid ki-folder"></i>.Zip Fail</a>';

                    // $routePrint = route('publicUser.proposal.printTender',[$row->TPNo]);
                    // $result .= '&nbsp;<a target="_blank" href="'.$routePrint.'" class="new modal-trigger waves-effect waves-light btn btn-primary"><i class="material-icons left">print</i>Print</a>';

                    // if(isset($row->letterIntent) && $row->letterIntent->LIStatus !="NEW"){
                    //     // $routePrintLI = route('perolehan.letter.intent.printLetter',[$row->letterIntent->LINo]);
                    //     $routeViewLI = route('publicUser.proposal.viewLetterIntent',[$row->letterIntent->LINo]);

                    //     $result .= '&nbsp;<a href="'.$routeViewLI.'" class="new modal-trigger waves-effect waves-light btn btn-light-primary"><i class="material-icons left">visibility</i>Surat Niat</a>';

                    // }

                    // if(isset($row->letterAcceptance) && $row->letterAcceptance->LAStatus !="NEW"){
                    //     // $routePrintLI = route('perolehan.letter.intent.printLetter',[$row->letterIntent->LINo]);
                    //     $routeViewLA = route('publicUser.proposal.viewLetterAccept',[$row->letterAcceptance->LANo]);

                    //     $result .= '&nbsp;<a href="'.$routeViewLA.'" class="new modal-trigger waves-effect waves-light btn btn-light-primary"><i class="material-icons left">visibility</i>Surat Setuju</a>';

                    // }
                }

                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['TDTitle','TPSubmitDate', 'status', 'action'])
            ->make(true);
    }

    public function edit($id){

        $proposal = TenderProposal::where('TPNo', $id)->first();

        $dokumens = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%D%');
            })
            ->join('TRTenderDetail', 'TRTenderProposalDetail.TPD_TDDNo', '=', 'TRTenderDetail.TDDNo')
            ->select('TRTenderProposalDetail.*')
            ->orderBy('TRTenderDetail.TDDSeq')
            ->get();

        foreach ($dokumens as $dokumen){

            $dokumen['fileAttachUploadPD'] = null;
            $dokumen->tenderDetail['fileAttachDownloadPD'] = null;

            $fileAttachUploadPD = FileAttach::where('FARefNO', $dokumen->TPDNo)->where('FAFileType', 'TPD-PD')->first();
            if($fileAttachUploadPD){
                $dokumen['fileAttachUploadPD'] = $fileAttachUploadPD;
            }

            $fileAttachDownloadPD = FileAttach::where('FARefNO', $dokumen->tenderDetail->TDDNo)->where('FAFileType', 'TD-PD')->first();
            if($fileAttachDownloadPD){
                $dokumen->tenderDetail['fileAttachDownloadPD'] = $fileAttachDownloadPD;
            }
        }

        $teknikals = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%T%');
            })
            ->join('TRTenderDetail', 'TRTenderProposalDetail.TPD_TDDNo', '=', 'TRTenderDetail.TDDNo')
            ->select('TRTenderProposalDetail.*')
            ->orderBy('TRTenderDetail.TDDSeq')
            ->get();

        foreach ($teknikals as $teknikal){

            $teknikal['fileAttachUploadDD'] = null;
            $teknikal->tenderDetail['fileAttachDownloadDD'] = null;

            $fileAttachUploadDD = FileAttach::where('FARefNO', $teknikal->TPDNo)->where('FAFileType', 'TPD-DD')->first();
            if($fileAttachUploadDD){
                $teknikal['fileAttachUploadDD'] = $fileAttachUploadDD;
            }

            $fileAttachDownloadDD = FileAttach::where('FARefNO', $teknikal->tenderDetail->TDDNo)->where('FAFileType', 'TD-DD')->first();
            if($fileAttachDownloadDD){
                $teknikal->tenderDetail['fileAttachDownloadDD'] = $fileAttachDownloadDD;
            }
        }

        $kewangans = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%F%');
            })
            ->join('TRTenderDetail', 'TRTenderProposalDetail.TPD_TDDNo', '=', 'TRTenderDetail.TDDNo')
            ->select('TRTenderProposalDetail.*')
            ->orderBy('TRTenderDetail.TDDSeq')
            ->get();

        foreach ($kewangans as $kewangan){

            $kewangan['fileAttachUploadDD'] = null;
            $kewangan->tenderDetail['fileAttachDownloadDD'] = null;

            $fileAttachUploadDD = FileAttach::where('FARefNO', $kewangan->TPDNo)->where('FAFileType', 'TPD-DD')->first();
            if($fileAttachUploadDD){
                $kewangan['fileAttachUploadDD'] = $fileAttachUploadDD;
            }

            $fileAttachDownloadDD = FileAttach::where('FARefNO', $kewangan->tenderDetail->TDDNo)->where('FAFileType', 'TD-DD')->first();
            if($fileAttachDownloadDD){
                $kewangan->tenderDetail['fileAttachDownloadDD'] = $fileAttachDownloadDD;
            }
        }

        // $kewangans = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
        //     ->whereHas('tenderDetail', function ($query){
        //         $query->where('TDDType', 'LIKE' ,'%T%');
        //     })
        //     ->with(['tenderDetail' => function ($query) {
        //         $query->with(['fileAttachDownload' => function ($query2) {
        //             $query2->where('FAFileType', 'TD-DD');
        //         }]);
        //     },'fileAttachUpload' => function ($query) {
        //         $query->where('FAFileType', 'TPD-DD');
        //     }])
        //     ->get();

        $tender = Tender::where('TDNo', $proposal->TP_TDNo)->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $yn = $this->dropdownService->yn();
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod = $tender->TDPublishPeriod;

        $tenderDocuments = [];
        $tenderDetails = $tender->tenderDetailD;

        return view('publicUser.proposal.edit',
            compact('id', 'proposal', 'dokumens', 'teknikals', 'kewangans', 'tender', 'TD_TCCode', 'TDPublishDate',
                'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms', 'tenderDocuments', 'tenderDetails')
        );
    }

    public function sediaSpec($idProposal, $idProposalDetail){

        $tenderProposalSpecs = TenderProposalSpec::where('TPS_TPNo', $idProposal)
            ->where('TPS_TPDNo', $idProposalDetail)
            ->with('tenderSpec')
            ->with('tenderSpec.unitMeasurement')
            ->get();

        //#SORTING-TABLE-CONTROLLER
        $tenderProposalSpecs = $tenderProposalSpecs->sortBy(function ($item) {
            return $item->tenderSpec->TDSSeq;
        });

        $yt = $this->dropdownService->yt();

        return view('publicUser.proposal.sediaSpesifikasiTeknikal',
            compact('idProposal', 'idProposalDetail', 'tenderProposalSpecs', 'yt')
        );
    }

    public function storeSpecTeknikal(Request $request){
        try {
            DB::beginTransaction();

            $data_TPSNo = $request->TPSNo;
            $data_response = $request->response;
            $data_remark = $request->remark;

            foreach($data_TPSNo as $key => $TPSNo){
                $proposalSpec = TenderProposalSpec::where('TPSNo', $TPSNo)->first();
                $proposalSpec->TPSRespond = $data_response[$key];
                $proposalSpec->TPDRemarkT = $data_remark[$key];
                $proposalSpec->save();
            }

            if($request->updateStatus == 1){
                $this->updateSelesai($request->idProposal, $request->idProposalDetail, 1, 'T');
            }

            DB::commit();

            if($request->updateStatus == 1) {
                return response()->json([
                    'success' => '1',
                    'redirect' => route('publicUser.proposal.edit', [$request->idProposal, 'flag' => 1]),
                    'message' => 'Maklumat berjaya disimpan.'
                ]);
            }
            else{
                return response()->json([
                    'success' => '1',
                    'redirect' => route('publicUser.proposal.sediaSpec', [$request->idProposal, $request->idProposalDetail]),
                    'message' => 'Maklumat berjaya disimpan.'
                ]);
            }

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function sediaSpecKewangan($idProposal, $idProposalDetail){

        $tenderProposalSpecs = TenderProposalSpec::where('TPS_TPNo', $idProposal)
            ->where('TPS_TPDNo', $idProposalDetail)
            ->with('tenderSpec')
            ->with('tenderSpec.unitMeasurement')
            ->get();

        $tenderProposalSpecs = $tenderProposalSpecs->sortBy(function ($item) {
            return $item->tenderSpec->TDSSeq;
        });

        return view('publicUser.proposal.sediaSpesifikasiKewangan',
            compact('idProposal', 'idProposalDetail', 'tenderProposalSpecs')
        );
    }

    public function storeSpecKewangan(Request $request){
        try {
            DB::beginTransaction();

            $idTender = $request->idProposal;

            $data_TPSNo = $request->TPSNo;
            $data_unitPrice = $request->unitPrice;
            $data_totalUnitPrice = $request->totalUnitPrice;
            $data_remark = $request->remark;

            $idProposal = $request->idProposal;
            $idProposalDetail = $request->idProposalDetail;
            $flag = 2;


            $totalUnitPrice = 0;

            foreach($data_TPSNo as $key => $TPSNo){
                $proposalSpec = TenderProposalSpec::where('TPSNo', $TPSNo)->first();
                $proposalSpec->TPSProposeAmt = $data_unitPrice[$key];
                $proposalSpec->TPSTotalProposeAmt = $data_totalUnitPrice[$key];
                $proposalSpec->TPDRemarkF = $data_remark[$key];
                $proposalSpec->save();

                $totalUnitPrice += $data_totalUnitPrice[$key];
            }

            $tenderProposal =  TenderProposal::where('TPNo', $idTender)->first();
            $tenderProposal->TPTotalAmt = $totalUnitPrice;
            $tenderProposal->save();

            DB::commit();

            if($request->updateStatus == 1){
                $this->updateSelesai($idProposal, $idProposalDetail, 2 , 'F');

                $route = route('publicUser.proposal.edit', [$idProposal, 'flag' => $flag]);
            }else{
                $route = route('publicUser.proposal.sediaSpecKewangan', [$request->idProposal, $request->idProposalDetail]);
            }

            return response()->json([
                'success' => '1',
                'redirect' => $route,
                'message' => 'Maklumat berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function sediaBankStatement($idProposal, $idProposalDetail){

        $proposal = TenderProposal::where('TPNo', $idProposal)->first();

        Carbon::setLocale('ms');

        $initialDate = Carbon::parse($proposal->tender->TDBankStmtYear);
        $firstMonthAfter = $initialDate->copy()->addMonth();
        $secondMonthAfter = $initialDate->copy()->addMonths(2);


        $firstMonthFormatted = $initialDate->format('F Y');
        $secondMonthFormatted = $firstMonthAfter->format('F Y');
        $thirdMonthFormatted = $secondMonthAfter->format('F Y');

        return view('publicUser.proposal.sediaBankStatement',
            compact('idProposal', 'idProposalDetail', 'proposal', 'firstMonthFormatted', 'secondMonthFormatted', 'thirdMonthFormatted')
        );
    }

    public function storeBankStatement(Request $request){
        try {
            DB::beginTransaction();

            $proposal = TenderProposal::where('TPNo', $request->idProposal)->first();
            $proposal->TPBankStmtBalAmt1 = $request->bulanPertama;
            $proposal->TPBankStmtBalAmt2 = $request->bulanKedua;
            $proposal->TPBankStmtBalAmt3 = $request->bulanKetiga;
            $proposal->TPTotalBankStmt = $request->totalBankStatement;
            $proposal->TPBankStmtAverage = $request->purata;
            $proposal->save();

            $proposalDetail = TenderProposalDetail::where('TPDNo', $request->idProposalDetail)->first();
            $proposalDetail->TPDCompleteFE = 1;
            $proposalDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.proposal.edit', [$request->idProposal, 'flag'=>2]),
                // 'redirect' => route('publicUser.proposal.sediaBankStatement', [$request->idProposal, $request->idProposalDetail]),
                'message' => 'Maklumat berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function sediaAssetLiabiliti($idProposal, $idProposalDetail){

       $proposal = TenderProposal::where('TPNo', $idProposal)->first();

        return view('publicUser.proposal.sediaAssetLiabiliti',
            compact('idProposal', 'idProposalDetail', 'proposal')
        );
    }

    public function storeAssetLiabiliti(Request $request){
        try {
            DB::beginTransaction();

            $proposal = TenderProposal::where('TPNo', $request->idProposal)->first();
            $proposal->TPCurrentAssetAmt = $request->asetSemasa;
            $proposal->TPCurrentLiabilityAmt = $request->liabilitiSemasa;
            $proposal->TPCashAmt = $request->tunaiTangan;
            $proposal->TPAccrualAmt = $request->belumTerima;
            $proposal->TPLiquidityRatio = $request->nisbahKecairan;
            $proposal->TPCurrentRatio = $request->nisbahSemasa;
            $proposal->save();

            $proposalDetail = TenderProposalDetail::where('TPDNo', $request->idProposalDetail)->first();
            $proposalDetail->TPDCompleteFE = 1;
            $proposalDetail->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.proposal.edit', [$request->idProposal, 'flag'=>2]),
                // 'redirect' => route('publicUser.proposal.sediaAssetLiabiliti', [$request->idProposal, $request->idProposalDetail]),
                'message' => 'Maklumat berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function sediaBankLoan($idProposal, $idProposalDetail){

        $proposal = TenderProposal::where('TPNo', $idProposal)->first();

        return view('publicUser.proposal.sediaBankLoan',
            compact('idProposal', 'idProposalDetail', 'proposal')
        );
    }

    public function storeBankLoan(Request $request){
        try {
            DB::beginTransaction();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.proposal.edit', [$request->idProposal, 'flag'=>2]),
                'message' => 'Maklumat berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function sediaHisProj($idProposal, $idProposalDetail){

        $proposal = TenderProposal::where('TPNo', $idProposal)->first();

        return view('publicUser.proposal.sediaHisProj',
            compact('idProposal', 'idProposalDetail', 'proposal')
        );
    }

    public function storeHisProj(Request $request){
        try {
            DB::beginTransaction();

            $idProposalHisProjs = $request->idProposalHisProj;

            $old_proposalHisProjs = TenderProposalHisProj::where('TPHP_TPNo', $request->idProposal)->get();

            foreach($old_proposalHisProjs as $proposalHisProj){
                $proposalHisProj->delete();
            }

            foreach ($idProposalHisProjs as $key => $idProposalHisProj){
                $proposalHisProjs = new TenderProposalHisProj();
                $proposalHisProjs->TPHP_TPNo = $request->idProposal;
                $proposalHisProjs->TPHPAgencyName = $request->agencyName[$key];
                $proposalHisProjs->TPHPTitle = $request->title[$key];
                $proposalHisProjs->TPHPContractNo = $request->contractNo[$key];
                $proposalHisProjs->TPHPStartDate = $request->startDate[$key];
                $proposalHisProjs->TPHPEndDate = $request->endDate[$key];
                $proposalHisProjs->TPHPRemark = $request->remark[$key];
                $proposalHisProjs->save();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.proposal.sediaHisProj', [$request->idProposal, $request->idProposalDetail]),
                'message' => 'Maklumat berjaya disimpan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function updateSelesai($idProposal, $idProposalDetail, $flag, $type = 'T'){

        try {
            DB::beginTransaction();

            if($type == 'T'){
                $proposalDetail = TenderProposalDetail::where('TPDNo', $idProposalDetail)->first();
                $proposalDetail->TPDCompleteTE = 1;
                $proposalDetail->save();
            }
            else {
                $proposalDetail = TenderProposalDetail::where('TPDNo', $idProposalDetail)->first();
                $proposalDetail->TPDCompleteFE = 1;
                $proposalDetail->save();
            }

            DB::commit();
            return redirect()->route('publicUser.proposal.edit', [$idProposal, 'flag' => $flag]);


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya disimpan!'.$e->getMessage()
            ], 400);
        }
    }

    public function printTender($id){

        $department = $this->dropdownService->department();
        $proposal = TenderProposal::where('TPNo', $id)->first();

        $tenderProposalDetail = TenderProposalDetail::where('TPD_TPNo' , $proposal->TPNo)->first();

        $tenderDetail = TenderDetail::where('TDDNo' , $tenderProposalDetail->TPD_TDDNo)->first();

        $bakibank = $tenderDetail->TDD_MTCode;

        $dokumens = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%D%');
            })
            ->with(['tenderDetail'])
            ->get();

        $teknikals = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%T%');
            })
            ->with(['tenderDetail'])
            ->first();

        $kewangans = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%F%');
            })
            ->with(['tenderDetail'])
            ->get();

        $idProposal = $proposal->TPNo;
        $idProposalDetail = $teknikals->TPDNo;

        // $tenderProposalSpecs = TenderProposalSpec::where('TPS_TPNo', $idProposal)
        // ->where('TPS_TPDNo', $idProposalDetail)
        // ->with('tenderSpec')
        // ->with('tenderSpec.unitMeasurement')
        // ->get();
        $tenderProposalSpecs = $proposal->tenderProposalSpec()->with('tenderSpec')->get();


        $initialDate = Carbon::parse($proposal->tender->TDBankStmtYear);
        $bankInitialDate = Carbon::parse($proposal->tender->TDBankStmtYear);
        $bankfirstMonthAfter = $initialDate->copy()->addMonth();
        $banksecondMonthAfter = $initialDate->copy()->addMonths(2);

        $jabatan = $department[$proposal->tender->TD_DPTCode];

        $template = "PROPOSAL";
        $download = false; //true for download or false for view
        $templateName = "PROPOSAL"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF',
        compact('id','templateName', 'proposal', 'dokumens', 'teknikals', 'kewangans','template','jabatan',
        'tenderProposalSpecs','bankInitialDate', 'bankfirstMonthAfter', 'banksecondMonthAfter' , 'bakibank'
        )
        );
        $response = $this->generatePDF($view,$download);

        return $response;
    }

    public function viewLetterIntent($id){

        $user = Auth::user();
        $letterIntent = LetterIntent::where('LINo', $id)
                        ->first();

        $li_date = Carbon::parse($letterIntent->LIDate)->format('d/m/Y');
        $li_time = Carbon::parse($letterIntent->LITime)->format('H:i');

        $acceptStatus = $this->dropdownService->acceptStatus();

        return view('publicUser.proposal.intentLetter.view',
                compact('letterIntent','li_date','li_time','acceptStatus')
        );


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

            if($submitStatus == 'APPROVE'){

                $trpcode = "LIA";

            }else if($submitStatus == 'REJECT'){

                $trpcode = "LIR";

            }
            $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LI_TPNo)->first();
            $tenderProposal->TP_TRPCode = $trpcode;
            $tenderProposal->save();

            $this->sendLetterNotification($letterIntent,'LI');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.proposal.index'),
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

    public function viewLetterAccept($id){

        $user = Auth::user();
        $letterAccept = LetterAcceptance::where('LANo', $id)->first();

        // $letterIntent = LetterIntent::where('LINo',$letterAccept->LA_LINo)->first();

        // $letterAccept = $letterIntent->letterAcceptance;

        // $li_date = Carbon::parse($letterIntent->LIDate)->format('d/m/Y');
        // $li_time = Carbon::parse($letterIntent->LITime)->format('H:i');

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


        $routerLetterALA = '#';
        $routerLetterCU = '#';
        $routerLetterSBDL = '#';

        $fileAttachDownloadALA = FileAttach::where('FAFileType','LA-ALA')->first();
        if(!empty($fileAttachDownloadALA)){
            $fileguid   = $fileAttachDownloadALA->FAGuidID;
            $routerLetterALA = route('file.download', ['fileGuid' => $fileguid]);
        }

        $fileAttachDownloadCU = FileAttach::where('FAFileType','LA-CU')->first();
        if(!empty($fileAttachDownloadCU)){
            $fileguid   = $fileAttachDownloadCU->FAGuidID;
            $routerLetterCU = route('file.download', ['fileGuid' => $fileguid]);
        }

        $fileAttachDownloadSBDL = FileAttach::where('FAFileType','LA-SBDL')->first();
        if(!empty($fileAttachDownloadSBDL)){
            $fileguid   = $fileAttachDownloadSBDL->FAGuidID;
            $routerLetterSBDL = route('file.download', ['fileGuid' => $fileguid]);
        }

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
                                                            ->where('LAP_LANo', $id)->get();

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

        return view('publicUser.proposal.acceptLetter.view',
        compact('tenderProposal','acceptStatus','yn','pentadbir','letterAccept','letterAcceptDeduction',
            'tender','tenderProject_type','tempohSah','tenderJabatan','startDate','endDate','templates','tenderProposalSpec','paymentDeductType',
            'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms', 'tenderDocuments', 'tenderDetails',
            'routerLetterALA','routerLetterCU','routerLetterSBDL')
        );


    }

    public function updateStatusLetterAccept(Request $request){

        $messages = [
            'submitStatus.required'   => 'Status penerimaan diperlukan.',
            //     TBR - #LAMPIRAN-NOTWORKING
            // 'lampiran.required'       => 'Bahagian lampiran perlukan diselesaikan.',
            // 'lampiran.*.accepted'       => 'Bahagian lampiran perlukan diselesaikan.',
        ];

        $validation = [
            'submitStatus' => 'required',
            // 'lampiran' => 'required',
            // 'lampiran.*' => 'accepted',

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

            $trpcode = "";

            if($submitStatus == 'APPROVE'){

                $trpcode = "LAA";

            }else if($submitStatus == 'REJECT'){

                $trpcode = "LAR";

            }
            $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LA_TPNo)->first();
            $tenderProposal->TP_TRPCode = $trpcode;
            $tenderProposal->save();

            $this->sendLetterNotification($letterIntent,'LA');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.proposal.index'),
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

    public function updateStatusProposal($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            //check teknikal
            $teknikals = TenderProposalDetail::where('TPD_TPNo', $id)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%T%');
            })
            ->whereNotNull('TPDCompleteTE')
            ->where('TPDCompleteTE', 0)
            ->get();

            if(count($teknikals) > 0){

                return response()->json([
                    'error' => 1,
                    'message' => 'Sila lengkapkan maklumat cadangan teknikal.'
                ],400);

            }

            //check kewangan
            $kewangans = TenderProposalDetail::where('TPD_TPNo', $id)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%F%');
            })
            ->whereNotNull('TPDCompleteFE')
            ->where('TPDCompleteFE', 0)
            ->get();

            if(count($kewangans) > 0){

                return response()->json([
                    'error' => 1,
                    'message' => 'Sila lengkapkan maklumat cadangan kewangan.'
                ],400);

            }

            // $submitStatus = 'SB-RQ';
            $submitStatus = 'SB';

            $proposal = TenderProposal::where('TPNo',$id)->first();


            $seqProposal = TenderProposal::where('TP_TDNo', $proposal->TP_TDNo)->where('TP_TPPCode', 'SB')->get();

            if(count($seqProposal) > 0){
                $proposal->TPSeqNo = count($seqProposal) +1;
            }
            else{
                $proposal->TPSeqNo = 1;

            }
            $proposal->TPSubmitDate = Carbon::now();
            $proposal->TP_TPPCode = $submitStatus;
            $proposal->TPMB = $user->USCode;
            $proposal->save();

//            $approvalController = new AppApprovalController($this->dropdownService, $this->autoNumber);
//            $atType = 'TP-SM';
//            $result = $approvalController->storeApproval($proposal->TPNo, $atType);

            $this->sendNotification($proposal);

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.proposal.index'),
                'message' => 'Maklumat cadangan berjaya dihantar.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat cadangan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

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
        $tenderDetails = $tender->tenderDetailD;

        return view('publicUser.proposal.view', compact('tender', 'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod',
            'TDSpecialTerms', 'TDTerms', 'tenderDocuments', 'tenderDetails'));
    }

    function sendNotification($proposal){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $tender = $proposal->tender;

            //#NOTIF-018
            $notiType = "TP";
            $title = "Penyerahan Cadangan - $proposal->TPNo";
            $desc = "Perhatian, penyerahan cadangan ($proposal->TPNo) bagi tender $proposal->TP_TDNo telah dikemukakan.";

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

    function sendLetterNotification($letterData, $letterType){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            if($letterType == "LI"){

                $letterIntent = $letterData;

                $status = $letterIntent->LIStatus;

                $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LA_TPNo)->first();
                $tender = Tender::where('TDNo',$tenderProposal->TP_TDNo)->first();


                if($status == 'APPROVE'){
                    //##NOTIF-029
                    $notiType = "LIA";

                    $title = "Surat Niat Diterima - $letterIntent->LINo";
                    $desc = "Perhatian, surat niat $letterIntent->LINo bagi cadangan $tenderProposal->TPNo telah diterima.";

                }else if($status == 'REJECT'){
                    //##NOTIF-030
                    $notiType = "LIR";

                    $title = "Surat Niat Ditolak - $letterIntent->LINo";
                    $desc = "Perhatian, surat niat $letterIntent->LINo bagi cadangan $tenderProposal->TPNo telah ditolak.";

                }
                //SEND NOTIFICATION TO PIC - PELAKSANA, PEROLEHAN
                $tenderPIC = $tender->tenderPIC_PT;

                if(!empty($tenderPIC)){

                    foreach($tenderPIC as $pic){

                        if($pic->TPICType == 'T'){
                            $pelaksanaType = "SO";

                        }else if($pic->TPICType == 'P' || $status == 'APPROVE'){
                            $pelaksanaType = "PO";
                            $desc .= " Sila anjurkan Surat Setuju Terima untuk proses seterusnya.";

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

            }else if($letterType == "LA"){

                $letterIntent = $letterData;

                $status = $letterIntent->LAStatus;

                $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LA_TPNo)->first();
                $tender = Tender::where('TDNo',$tenderProposal->TP_TDNo)->first();


                if($status == 'APPROVE'){
                    //##NOTIF-032
                    $notiType = "LAA";

                    $title = "Surat Setuju Terima Diterima - $letterIntent->LANo";
                    $desc = "Perhatian, surat setuju terima $letterIntent->LANo bagi cadangan $tenderProposal->TPNo telah diterima.";

                }else if($status == 'REJECT'){
                    //##NOTIF-033
                    $notiType = "LAR";

                    $title = "Surat Setuju Terima Ditolak - $letterIntent->LANo";
                    $desc = "Perhatian, surat setuju terima $letterIntent->LANo bagi cadangan $tenderProposal->TPNo telah ditolak.";

                }
                //SEND NOTIFICATION TO PIC - PELAKSANA, PEROLEHAN
                $tenderPIC = $tender->tenderPIC_PT;

                if(!empty($tenderPIC)){

                    foreach($tenderPIC as $pic){

                        if($pic->TPICType == 'T'){
                            $pelaksanaType = "SO";

                        }else if($pic->TPICType == 'P' || $status == 'APPROVE'){
                            $pelaksanaType = "PO";
                            $desc .= " Sila lakukan verifikasi untuk proses seterusnya.";

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
