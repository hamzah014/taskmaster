<?php

namespace App\Http\Controllers\Pelaksana\Tender;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\SSMCompany;
use App\Models\Tender;
use App\Models\TenderProcess;
use App\Models\TenderProposal;
use App\Models\TenderProposalSpec;
use App\Models\TenderSpec;
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
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;

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

        return view('pelaksana.tender.index', compact('template'));
    }

    public function view($id){
        $tender = Tender::where('TDNo', $id)->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $yn = $this->dropdownService->yn();

        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod = $tender->TDPublishPeriod;

        $tenderDocuments = [];
        $tenderDetails = $tender->tenderDetailD;

        return view('pelaksana.tender.view',
            compact('tender', 'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms',
                'tenderDocuments', 'tenderDetails')
        );
    }

    public function create(){
        $jenis = [
            0 => 'Tender',
            1 => 'Sebutharga',
            2 => 'Sebutharga B',
        ];

        $jenis_projek = [
            0 => 'Pembangunan',
            1 => 'Penyelenggaraan',
            2 => 'One Off',
        ];

        $status = [
            0 => 'Aktif',
            1 => 'Tidak Aktif',
        ];

        $dokumen_lampiran = [
            0 => 'Lampiran A1',
            1 => 'Lampiran A2',
            2 => 'Lampiran B',
        ];

        $wajib = [
            0 => 'Wajib',
            1 => 'Tidak',
        ];

        return view('pelaksana.tender.create',
            compact('jenis','jenis_projek', 'status', 'dokumen_lampiran', 'wajib')
        );
    }

    public function check($id){
        $jenis = [
            0 => 'Tender',
            1 => 'Sebutharga',
            2 => 'Sebutharga B',
        ];

        $jenis_projek = [
            0 => 'Pembangunan',
            1 => 'Penyelenggaraan',
            2 => 'One Off',
        ];

        $status = [
            0 => 'Aktif',
            1 => 'Tidak Aktif',
        ];

        $dokumen_lampiran = [
            0 => 'Lampiran A1',
            1 => 'Lampiran A2',
            2 => 'Lampiran B',
        ];

        $wajib = [
            0 => 'Wajib',
            1 => 'Tidak',
        ];

        $kod_bidang = [
            0 => 'B01 - Kategori B - IBS:Sistem Konkrit Pasang Siap',
            1 => 'B02 - Kategori B - IBS:Sistem Kerangka Keluli',
            2 => 'CE01 - Kategori CE - Pembinaan Jalan dan Pavmen',
            3 => 'CE02 - Kategori CE - Pembinaan Jambatan',
        ];

        return view('pelaksana.tender.check',
            compact('jenis','jenis_projek', 'status', 'dokumen_lampiran', 'wajib', 'kod_bidang')
        );
    }

    public function indexV2(){
        return view('pelaksana.tender.indexV2');
    }

    public function penilaian($id){

        $tender = Tender::where('TDNo',$id)->first();

        $proposals = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TDANo',$tender->TD_TDANo)
            ->where('TP_TPPCode','SB')
            ->where('TPEvaluationStep', '>=',1)
            ->with('tenderProposalSpec', 'tenderProposalSpec.tenderSpec')
            ->orderBy('TPSeqNo')
            ->get();

        $tenderSpecs = TenderSpec::where('TDS_TDNo', $id)->get();

        $teknikalScore = 0;

        foreach ($tenderSpecs as $tenderSpec){

            if($tenderSpec->TDStockInd == 0){
                $teknikalScore += $tenderSpec->TDSScoreMax;
            }
        }

        $totalMark = $teknikalScore;

        return view('pelaksana.tender.penilaian',
            compact('id', 'proposals', 'totalMark', 'tender')
        );
    }

    public function storePenilaian(Request $request){
        try {
            DB::beginTransaction();

            $TPSNos = $request->TPSNo;
            $scores = $request->score;
            $remarks = $request->remark;

            foreach ($TPSNos as $key => $TPSNo){
                $proposalSpec = TenderProposalSpec::where('TPSNo', $TPSNo)
                    ->first();

                $proposalSpec->TPSScore = $scores[$key];
                $proposalSpec->TPSRemarkSpec = $remarks[$key];
                $proposalSpec->save();
            }

            DB::commit();

            return redirect()->route('pelaksana.tender.indexV2');

        }catch (\Throwable $e) {
            DB::rollback();
        }
    }

    public function tenderDatatable(Request $request){
        $query = Tender::orderby('TDID','desc')->get();

        return datatables()->of($query)
            ->editColumn('TD_TCCode', function($row) {
                $tender_sebutharga = $this->dropdownService->tender_sebutharga();
                return $tender_sebutharga[$row->TD_TCCode];
            })
            ->editColumn('TDNo', function($row) {

                $route = route('pelaksana.tender.view', [$row->TDNo]);

                $result = '<a class="" href="'.$route.'">'.$row->TDNo.' </a>';

                return $result;
            })
            ->editColumn('TDClosingDate', function($row) {
                $carbonDatetime = Carbon::parse($row->TDClosingDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->editColumn('TD_TPCode', function($row) {
                $tenderProcess = TenderProcess::where('TPCode',$row->TD_TPCode)->first();

                if($tenderProcess){
                    $result = $tenderProcess->TPInstruction;
                }
                else{
                    $result = '-';
                }

                return $result;
            })
            ->addColumn('action', function($row) {
                $result = '';

                if($row->TD_TPCode == 'OT'){
                    $route = route('pelaksana.tender.penilaian', [$row->TDNo]);

                    $result = '<a class="btn btn-secondary" href="'.$route.'">Penilaian</a>';
                }

                return $result;
            })
            ->rawColumns(['TD_TCCode', 'TDClosingDate', 'action', 'TDNo' , 'TD_TPCode'])
            ->make(true);
    }

    public function submitPenilaian($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tender = Tender::where('TDNo', $id)->first();
            // $tender->TD_TPCode = 'ES';
            $tender->save();

            DB::commit();

            // return redirect()->route('pelaksana.tender.indexV2');
            return redirect()->route('pelaksana.tugasan.index');


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat cadangan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function indexPenilaian(){

        return view('pelaksana.tender.indexPenilaian'
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
                $route = route('pelaksana.tender.penilaian',[$row->TDNo]);

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

    public function penilaianProposal($id){
        $yt = $this->dropdownService->yt();
        $proposal = TenderProposal::where('TPNo', $id)
            ->first();

        $idTender= $proposal->TP_TDNo;

        return view('pelaksana.tender.penilaianProposal',
            compact('id', 'proposal', 'idTender', 'yt')
        );
    }

    public function storePenilaianProposal(Request $request){
        try {
            DB::beginTransaction();

            $TPSNos = $request->TPSNo;
            $scores = $request->score;
            $remarks = $request->remark;

            //submitproposal

            $id     = $request->TPNo;
            $proposal = TenderProposal::where('TPNo', $id)->first();

            foreach ($TPSNos as $key => $TPSNo){
                $proposalSpec = TenderProposalSpec::where('TPSNo', $TPSNo)
                    ->first();

                $proposalSpec->TPSScore = $scores[$key];
                $proposalSpec->TPSRemarkSpecT = $remarks[$key];
                $proposalSpec->save();
            }

            DB::commit();

            if($request->updateStatus == 1){
                $this->submitPenilaianProposal($id);

                $route = route('pelaksana.tender.penilaian', [$proposal->TP_TDNo]);
            }else{
                $route = route('pelaksana.tender.penilaian.proposal', [$request->TPNo]);
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
                'message' => 'Maklumat cadangan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }
    }

    public function submitPenilaianProposal($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $proposal = TenderProposal::where('TPNo', $id)
                ->first();
            $proposal->TPCompleteT = 1;

            $proposalSpecs = TenderProposalSpec::where('TPS_TPNo', $id)
                ->get();
            $teknikalScore = 0;

            foreach ($proposalSpecs as $proposalSpec){

                if($proposalSpec->tenderSpec->TDStockInd == 0){
                    $teknikalScore += $proposalSpec->TPSScore;
                }
            }

            $proposal->TPTechnicalScore = $teknikalScore;
            $proposal->save();

            DB::commit();


            // return redirect()->route('pelaksana.tender.penilaian', [$proposal->TP_TDNo]);


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat cadangan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    function sendNotification($tender){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            //#NOTIF-021
            $notiType = "ESS";
            $title = "Penilain Tender Pelaksana Selesai - $tender->TDNo";
            $desc = "Perhatian, penilaian bagi tender $tender->TDNo telah selesai dinilai oleh pelaksana.";

            //SEND NOTIFICATION TO PEROLEHAN
            $tenderPIC = $tender->tenderPIC_P;
            $pelaksanaType = "SO";

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
