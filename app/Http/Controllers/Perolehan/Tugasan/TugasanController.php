<?php

namespace App\Http\Controllers\Perolehan\Tugasan;

use App\Http\Controllers\Controller;
use App\Models\SSMCompany;
use App\Models\TugasanPerolehan;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Yajra\DataTables\DataTables;


use App\Http\Requests;
use App\Models\BoardMeeting;
use App\Models\BoardMeetingProposal;
use App\Models\BoardMeetingTender;
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
use App\Models\TenderAdv;
use App\Models\TenderSpec;

class TugasanController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){

        return view('perolehan.tugasan.index');
    }

    public function indexBukaPeti(){

        return view('perolehan.tugasan.indexBukaPeti');
    }

    public function proposalDetail($id){

        $department = $this->dropdownService->department();

        $tender = Tender::where('TDNo',$id)->first();
        $TDANo = $tender->TD_TDANo;

        $proposal = TenderProposal::where('TP_TDNo', $id)->first();

        $numberProposal = TenderProposal::where('TP_TDNo' , $id)
            ->where('TP_TDANo',$TDANo)
            ->where('TP_TPPCOde' , '!=' , 'CL')
            ->count();

        $tenderSpecYN = TenderSpec::where('TDS_TDNo' , $id)->where('TDSRespondType' , '2')->get();

        // $allproposal = TenderProposal::where('TP_TDNo', $id)->get();

        // $checkProposal = $allproposal;

        $allproposal = TenderProposal::where('TP_TDNo', $id)->get();
//        $checkProposal = [];
        $partialTPs = [];

        $minRange = 1;
        $maxRange = 15;

        foreach ($allproposal as $proposal) {
            $specLength = count($proposal->tenderProposalSpec);
            $maxRange = max($maxRange, $specLength);
        }

        for ($min = $minRange; $min <= $maxRange; $min += 5) {
            $max = $min + 4; // Each range is a multiple of 5

            $listArray[] = array(
                'min' => $min,
                'max' => $max
            );
        }

        $resultList = array();
        foreach ($listArray as $indexl => $listArr) {
            $resultList[$indexl]['seq'] = $indexl;
            $resultList[$indexl]['tpno'] = array();
        }

        foreach ($allproposal as $index => $proposal) {

            $flag_partial = false;
            $result0 = 0;
            $result1 = 0;

            foreach($proposal->tenderProposalSpec as $tenderProposalSpec){
                $tenderSpec = $tenderProposalSpec->tenderSpec;

                if($tenderSpec->TDSRespondType == '2' && $tenderProposalSpec->TPSRespond == 0){
                    $flag_partial = true;
                }

                if($tenderProposalSpec->TPSRespond == 0 && isset($tenderProposalSpec->TPSRespond)){
                    $result0++;
                }

                if($tenderProposalSpec->TPSRespond == 1){
                    $result1++;
                }

            }

            foreach ($listArray as $indexl => $listArr) {
                $max = $listArr['max'];
                $min = $listArr['min'];

                if ($result0 <= $max && $result0 >= $min) {
                    array_push($resultList[$indexl]['tpno'], $proposal);
                }
            }

            if($flag_partial == true){
                array_push($partialTPs, $proposal->TPNo);
                $proposal->TPFullComply = 0;
                $proposal->save();
            }
            else{
                $proposal->TPFullComply = 1;
                $proposal->save();
            }
        }

        // dd($resultList);

        $tender = Tender::where('TDNo', $id)->first();
        $jabatan = $department[$tender->TD_DPTCode];

        $teknikals = TenderDetail::where('TDD_TDNo',$id)
            ->where('TDDType', 'LIKE', '%T%')
            ->where(function ($query) {
                $query->where('TDD_MTCode', 'BF')
                    ->orWhere('TDD_MTCode', 'UF');
            })
            ->get();

        foreach ($teknikals as $teknikal){

            $teknikal['fileAttachUploadDD'] = null;
            $teknikal['fileAttachDownloadDD'] = null;

            $fileAttachDownloadDD = FileAttach::where('FARefNO', $teknikal->TDDNo)->where('FAFileType', 'TD-DD')->first();
            if($fileAttachDownloadDD){
                $teknikal['fileAttachDownloadDD'] = $fileAttachDownloadDD;
            }
        }

        $kewangans = TenderDetail::where('TDD_TDNo',$id)
            ->where('TDDType', 'LIKE', '%F%')
            ->where(function ($query) {
                $query->where('TDD_MTCode', 'BF')
                    ->orWhere('TDD_MTCode', 'UF');
            })
            ->get();

        foreach ($kewangans as $kewangan){

            $kewangan['fileAttachUploadDD'] = null;
            $kewangan['fileAttachDownloadDD'] = null;

            $fileAttachDownloadDD = FileAttach::where('FARefNO', $kewangan->TDDNo)->where('FAFileType', 'TD-DD')->first();
            if($fileAttachDownloadDD){
                $kewangan['fileAttachDownloadDD'] = $fileAttachDownloadDD;
            }
        }

        return view('perolehan.tugasan.proposalDetail',
            compact('id', 'proposal', 'teknikals', 'kewangans','tender','jabatan' , 'numberProposal','resultList','listArray'
                , 'partialTPs')
        );

    }

    public function viewCA($id){

        if(isset($_GET['flag'])){

            $flag = $_GET['flag'];
        }else{
            $flag = "";
        }

        $TDNO = $id;

        $proposalDocStatus = $this->dropdownService->proposalDocStatus();

        $TPPCode = 'SB';

        $tenderDetail = TenderDetail::where('TDDNo',$id)->first();
        $tender = $tenderDetail->tender;

        $proposalDetails = TenderProposalDetail::where('TPD_TDDNo', $id)
            ->whereHas('tenderProposal', function ($query) use ($TPPCode,$tender) {
                $query->where('TP_TPPCode', $TPPCode)->where('TPFullComply', 1)
                    ->where('TP_TDANo',$tender->TD_TDANo);
            })
            ->join('TRTenderProposal', 'TRTenderProposalDetail.TPD_TPNo', '=', 'TRTenderProposal.TPNo')
            ->orderBy('TRTenderProposal.TPSeqNo', 'asc')
            ->with('tenderProposal')
            ->get();

        foreach($proposalDetails as $proposalDetail){

            $fileType = 'TPD-DD';
            $proposalDetail['fileAttachDD'] = null;

            $refNo = $proposalDetail->TPDNo;

            $fileAttachDownloadDD = FileAttach::where('FARefNO', $refNo)->where('FAFileType', $fileType)->first();
            if($fileAttachDownloadDD){
                $proposalDetail['fileAttachDD'] = $fileAttachDownloadDD;
            }
        }

        return view('perolehan.tugasan.closingAds',
            compact('TDNO','proposalDetails','proposalDocStatus','flag','tenderDetail', 'tender')
        );
    }

    public function updateTenderPropDetail(Request $request){
        $messages = [
            //
        ];

        $validation = [
            //
        ];

        $request->validate($validation, $messages);


        try {
            DB::beginTransaction();

            $tdno = 0;

            $allupdate = 1;

            foreach($request->TPDNo as $index => $TPDNo){

                $status     = $request->proposalDocStatus[$index];
                $remarks    = $request->remark[$index];
                if($status == null){
                    $completeO = 0;
                    $allupdate = 0;
                }
                else{
                    if($request->updateStatus == 1){
                        $completeO  = 0;
                    }else{
                        $completeO  = 1;
                    }
                }

                $tenderPropDet = TenderProposalDetail::where('TPDNo',$TPDNo)
                    ->first();
                $tenderPropDet->TPD_PDSCode = $status;
                $tenderPropDet->TPDRemarkO = $remarks;
                $tenderPropDet->TPDCompleteO = $completeO;
                $tenderPropDet->save();

                $tdno = $tenderPropDet->tenderProposal->TP_TDNo;
            }

            $tenderDetail =  TenderDetail::where('TDDNo', $request->TDDNo)->first();
            $tenderDetail->TDDCompleteO = $allupdate;
            $tenderDetail->save();

            if($request->TDDType == 'F'){
                $flag = 2;
            }else{
                $flag = 1;
            }

            DB::commit();

            // return redirect()->route('perolehan.tugasan.viewCA', [$request->TDDNo]);

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tugasan.proposalDetail', [$tdno,'flag2'=>$flag]),
                'message' => 'Maklumat Pembekal telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat Pembekal tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function viewResult($id){

        $department = $this->dropdownService->department();

        $tenderRef = Tender::where('TDNo',$id)->first();
        $refTDANo = $tenderRef->TD_TDANo;
        // $tender['publishDate'] = Carbon::parse($tender->TDPublishDate)->format('d/m/Y h:ia');
        // $tender['closingDate'] = Carbon::parse($tender->TDClosingDate)->format('d/m/Y h:ia');
        // $tender['department'] = $department[$tender->TD_DPTCode] ?? null;

        $tenderAdvs = TenderAdv::where('TDANo','!=',$refTDANo)
            ->where('TDA_TDNo',$tenderRef->TDNo)
            ->get()
            ->pluck('TDANo');

        $tender = Tender::with(['meetingTender' => function ($query) use ($tenderRef) {
            $query->where(function ($query) use ($tenderRef) {
                $query->where('BMT_TDANo', $tenderRef->TD_TDANo)
                    ->where('BMT_TMSCode', 'D')
                    ->orWhere('BMT_TMSCode', 'M');
            });
        }])->where('TDNo', $id)->first();

        $tender['publishDate'] = $tender->tenderAdv ? Carbon::parse($tender->tenderAdv->TDAPublishDate)->format('d/m/Y h:iA') : null;
        $tender['closingDate'] = $tender->tenderAdv ? Carbon::parse($tender->tenderAdv->TDAClosingDate)->format('d/m/Y h:iA') : null;
        $tender['department'] = $department[$tender->TD_DPTCode] ?? null;


        $tenderPrev = Tender::with(['meetingTender' => function ($query) use ($tenderRef) {
            $query->where(function ($query) use ($tenderRef) {
                $query->where('BMT_TDANo', '!=', $tenderRef->TD_TDANo)
                    ->where(function ($query) {
                        $query->where('BMT_TMSCode', 'D')
                            ->orWhere('BMT_TMSCode', 'M');
                    });
            });
        }])->where('TDNo', $id)->first();

        $existTenderAdv = BoardMeetingTender::
        whereIn('BMT_TDANo',$tenderAdvs)
            ->orderBy('BMT_TDANo','DESC')
            ->get()
            ->pluck('BMT_TDANo');

        $listTenderPrev = array();

        foreach($existTenderAdv as $index => $etenderAdv){

            $tenderAdv = TenderAdv::where('TDANo',$etenderAdv)->first();
            $meetingProposal = BoardMeetingTender::where('BMT_TDANo',$etenderAdv)->with(['meetingProposal', 'meetingProposalR'])->first();

            $listTenderPrev[$index]['tenderAdv'] = $tenderAdv;
            $listTenderPrev[$index]['meetingProposal'] = $meetingProposal->meetingProposalR;

        }

        return view('perolehan.tugasan.viewResult',
            compact('tender','tenderPrev','existTenderAdv','listTenderPrev')
        );

    }

    public function tugasanDatatable(Request $request){

        $data = TugasanPerolehan::
        select(['RefNo', 'RefDate', 'Type', 'Status', 'Status2', 'Status3'])
            ->where(function ($query) {
                $query->where(function ($subquery) {
                    $subquery->where('Type', 'PTD')->where('Status', 'COMPLETE');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'EM1A');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'EM1S');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'EM2A');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'EM2S');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'EM3A');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'EM3S');
                })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'TD')->whereIn('Status', ['DF', 'CA', 'OT', 'BM','ES']);
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'BM')->where('Status', 'NEW');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'LI')->where('Status', 'NEW');
                    })
//                    ->orWhere(function ($subquery) {
//                        $subquery->where('Type', 'TP')->where('Status', 'LIA');
//                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PT')->whereIn('Status', ['NPS']);
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'LA')->whereIn('Status', ['NEW', 'APPROVE'])->whereNull('Status2');
                    })
                    // ->orWhere(function ($subquery) {
                    //     $subquery->where('Type', 'EOT')->where('Status', 'AGREE');
                    // })
                    // ->orWhere(function ($subquery) {
                    //     $subquery->where('Type', 'EOT')->where('Status', 'ACCEPT')->where('Status2', 'LD');
                    // })
                    // ->orWhere(function ($subquery) {
                    //     $subquery->where('Type', 'EOT')->where('Status', 'ACCEPT')->where('Status2', 'ES');
                    // })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'EOT2')->whereIn('Status', ['AJKA', 'PA']);
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PTE1')->where('Status', 'NEW');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PTE2')->where('Status', 'NEW');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PTA')->where('Status', 'NEW');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'MLI')->where('Status', 'NEW');
                    })
                    // ->orWhere(function ($subquery) {
                    //     $subquery->where('Type', 'VO')->whereIn('Status', ['AGREE', 'ACCEPT']);
                    // })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'VO2')->whereIn('Status', ['AJKA', 'PA']);
                    });
            })
            ->orderBy('RefDate', 'DESC')
            ->get();


        return datatables()->of($data)
            ->editColumn('RefNo', function($row) {

                if($row->Type == 'PTD' && in_array($row->Status,['COMPLETE','EM1S'])){
                    // $route = route('perolehan.mesyuarat.create',['type'=>'MPTA', 'refNo' => $row->RefNo]);
                    $route = route('perolehan.mesyuarat.create',['type'=>'MPTE1', 'refNo' => $row->RefNo]);
                }
                // elseif($row->Type == 'PTD' && $row->Status  == 'EM1A' && $row->Status2  == NULL){
                elseif($row->Type == 'PTD' && in_array($row->Status,['EM1A','EM2S'])){
                    // $route = route('perolehan.mesyuarat.create',['type'=>'MPTA', 'refNo' => $row->RefNo]);
                    $route = route('perolehan.mesyuarat.create',['type'=>'MPTA', 'refNo' => $row->RefNo]);
                }
                elseif($row->Type == 'PTD' && in_array($row->Status,['EM2A','EM3S'])){
                    // $route = route('perolehan.mesyuarat.create',['type'=>'MPTA', 'refNo' => $row->RefNo]);
                    $route = route('perolehan.mesyuarat.create',['type'=>'MPTA', 'refNo' => $row->RefNo]);
                }
                elseif($row->Type == 'PTD' && in_array($row->Status,['EM3A'])){
                    $route = route('perolehan.tender.create',[$row->RefNo]);
                }
                // else if($row->Type == 'PTD' && $row->Status  == 'APPROVE' && $row->Status2  == NULL){
                //     $route = route('perolehan.tender.create',[$row->RefNo]);
                // }
                else if($row->Type == 'TD' && $row->Status  == 'DF'){
                    $route = route('perolehan.tender.edit',[$row->RefNo]);
                }
                else if($row->Type == 'TD' && $row->Status  == 'CA'){
                    $route = route('perolehan.tugasan.proposalDetail',[$row->RefNo]);
                }
                else if($row->Type == 'TD' && $row->Status  == 'OT'){
                    $route = route('perolehan.tender.penilaian.index',[$row->RefNo]);
                }
                else if($row->Type == 'TD' && $row->Status  == 'ES'){
                    // $route = route('perolehan.meeting.create',['tenderNo' => $row->RefNo]);
                    $route = route('perolehan.meeting.index');
                }
                else if($row->Type == 'PT' && $row->Status  == 'NPS'){
                    // $route = route('perolehan.meeting.create',['tenderNo' => $row->RefNo]);
                    $route = route('perolehan.meeting.index');
                }
                else if($row->Type == 'BM' && $row->Status  == 'NEW'){
                    $route = route('perolehan.meeting.edit',[$row->RefNo]);
                }
                else if($row->Type == 'TD' && $row->Status  == 'BM'){
                    $route = route('perolehan.tugasan.viewResult',[$row->RefNo]);
                }
                else if($row->Type == 'LI' && $row->Status  == 'NEW'){
                    $route = route('perolehan.letter.intent.edit',[$row->RefNo]);
                }
//                else if($row->Type == 'TP' && $row->Status  == 'LIA'){
//                    $route = route('perolehan.mesyuarat.create',['type'=>'MLI', 'refNo' => $row->RefNo]);
//                }
                else if($row->Type == 'LA' && $row->Status2 == null){
                    $route = route('perolehan.letter.accept.view',[$row->RefNo]);
                }
                // else if($row->Type == 'LA' && $row->Status  == 'NEW'){
                //     $route = route('perolehan.letter.accept.view',[$row->RefNo]);
                // }
                // else if($row->Type == 'LA' && $row->Status  == 'APPROVE'){
                //     $route = route('perolehan.letter.accept.view',[$row->RefNo]);
                // }
                else if($row->Type == 'EOT2' && $row->Status  == 'AJKA'&& $row->Status3  == 1){
                    $route = route('perolehan.eot.view',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && $row->Status  == 'PA'&& $row->Status3  == 1){
                    $route = route('perolehan.mesyuarat.create',['type'=>'EOT', 'refNo' => $row->RefNo]);
                }
                else if($row->Type == 'EOT2' && $row->Status == 'AJKA' && $row->Status3 == 0){
                    $route = route('perolehan.mesyuarat.create',['type'=>'EOT', 'refNo' => $row->RefNo]);
                }
                // else if($row->Type == 'EOT' && $row->Status  == 'ACCEPT'){
                //     $route = route('perolehan.mesyuarat.create',['type'=>'EOT', 'refNo' => $row->RefNo]);
                // }
                // else if($row->Type == 'VO' && $row->Status  == 'AGREE'){
                //     $route = route('perolehan.vo.view',[$row->RefNo]);
                // }
                else if($row->Type == 'VO2' && $row->Status  == 'AJKA'){
                    $route = route('perolehan.vo.view',[$row->RefNo]);
                }
                else if($row->Type == 'VO2' && $row->Status  == 'PA'){
                    $route = route('perolehan.mesyuarat.create',['type'=>'VO', 'refNo' => $row->RefNo]);
                }
                // else if($row->Type == 'VO' && $row->Status  == 'ACCEPT'){
                //     $route = route('perolehan.mesyuarat.create',['type'=>'VO', 'refNo' => $row->RefNo]);
                // }
                else if($row->Type == 'PTE1' && $row->Status  == 'NEW'){
                    $route = route('perolehan.mesyuarat.edit',[$row->RefNo]);
                }
                else if($row->Type == 'PTE2' && $row->Status  == 'NEW'){
                    $route = route('perolehan.mesyuarat.edit',[$row->RefNo]);
                }
                else if($row->Type == 'PTA' && $row->Status  == 'NEW'){
                    $route = route('perolehan.mesyuarat.edit',[$row->RefNo]);
                }
                else if($row->Type == 'MLI' && $row->Status  == 'NEW'){
                    $route = route('perolehan.mesyuarat.edit',[$row->RefNo]);
                }
                else{
                    $route = '#';
                }

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->RefNo.'</a>';

                return $result;
            })
            ->editColumn('RefDate', function($row) {

                $result = '-';

                if(isset($row->RefDate)){
                    $result = \Carbon\Carbon::parse($row->RefDate)->format('d/m/Y H:i');
                }

                return $result;
            })
            ->addColumn('Arahan', function($row) {

                $result = '-';

                if($row->Type == 'PTD' && $row->Status  == 'COMPLETE'){
                    $result = 'Cadangan projek dihantar. Sila anjurkan mesyuarat pengesahan 1 cadangan projek';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'EM1A'){
                    $result = 'Cadangan projek dihantar. Sila anjurkan mesyuarat kelulusan cadangan projek';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'EM1S'){
                    $result = 'Cadangan projek selesai disemak semula. Sila anjurkan mesyuarat pengesahan 1 cadangan projek';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'EM2A'){
                    $result = 'Cadangan projek dihantar. Sila anjurkan mesyuarat kelulusan cadangan projek';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'EM2S'){
                    $result = 'Cadangan projek selesai disemak semula. Sila anjurkan mesyuarat pengesahan 2 cadangan projek';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'EM3A'){
                    $result = 'Mesyuarat kelulusan cadangan projek telah diluluskan. Sila merangka tender.';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'EM3S'){
                    $result = 'Cadangan projek selesai disemak semula. Sila anjurkan mesyuarat kelulusan cadangan projek';
                }
                // else if($row->Type == 'PTD' && $row->Status  == 'APPROVE'){
                //     $result = 'Mesyuarat kelulusan cadangan projek telah diluluskan. Sila merangka tender.';
                // }
                else if($row->Type == 'TD' && $row->Status  == 'DF'){
                    $result = 'Tender Masih Draf. Sila hantar tender.';
                }
                else if($row->Type == 'TD' && $row->Status  == 'CA'){
                    $result = 'Tender Buka Peti.';
                }
                else if($row->Type == 'TD' && $row->Status  == 'OT'){
                    $result = 'Penilaian Keseluruhan.';
                }
                else if($row->Type == 'TD' && $row->Status  == 'ES'){
                    $result = 'Mesyuarat Tender.';
                }
                else if($row->Type == 'PT' && $row->Status  == 'NPS'){
                    $result = 'Mesyuarat Tender.';
                }
                else if($row->Type == 'BM' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Mesyuarat Tender Dihantar.';
                }
                else if($row->Type == 'TD' && $row->Status  == 'BM'){
                    $result = 'Pemilihan Pembekal.';
                }
//                else if($row->Type == 'TP' && $row->Status  == 'LIA'){
//                    $result = 'Mesyuarat Surat Niat.';
//                }
                else if($row->Type == 'LA' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Surat Setuju Terima dihantar.';
                }
                else if($row->Type == 'LA' && $row->Status  == 'APPROVE'){
                    $result = 'Menunggu Surat Setuju Terima diterima.';
                }
                // else if($row->Type == 'LA' && $row->Status  == 'CONFIRM' && $row->Status2  == null){
                //     $result = 'Menunggu projek dicipta.';
                // }
                else if($row->Type == 'LI' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Surat Niat Dihantar.';
                }
                // else if($row->Type == 'LA' && $row->Status  == 'NEW'){
                //     $result = 'Isu Surat Setuju Terima.';
                // }
                // else if($row->Type == 'LA' && $row->Status  == 'APPROVE'){
                //     $result = 'Surat Setuju Terima Selesai.';
                // }
                else if($row->Type == 'EOT2' && $row->Status == 'AJKA' && $row->Status2 == 'LD' && $row->Status3 == 1){
                    $result = 'Menunggu Pengesahan Lanjutan Masa Berbayar.';
                }
                else if($row->Type == 'EOT2' && $row->Status == 'AJKA' && $row->Status2 == 'ES' && $row->Status3 == 1){
                    $result = 'Menunggu Pengesahan Lanjutan Perkhidmatan Berbayar.';
                }
                else if($row->Type == 'EOT2' && $row->Status == 'PA'){
                    $result = 'Menunggu Mesyuarat Lanjutan Masa';
                }
                else if($row->Type == 'EOT2' && $row->Status == 'AJKA' && $row->Status3 == 0){
                    $result = 'Menunggu Mesyuarat Lanjutan Masa';
                }
                // else if($row->Type == 'EOT' && $row->Status  == 'AGREE' && $row->Status2  == 'ES'){
                //     $result = 'Menunggu Pengesahan Lanjutan Perkhidmatan Berbayar.';
                // }
                // else if($row->Type == 'EOT' && $row->Status  == 'ACCEPT' && $row->Status2  == 'LD'){
                //     $result = 'Mesyuarat Lanjutan Masa.';
                // }
                // else if($row->Type == 'EOT' && $row->Status  == 'ACCEPT' && $row->Status2  == 'ES'){
                //     $result = 'Mesyuarat Lanjutan Perkhidmatan.';
                // }
                else if($row->Type == 'PTE1' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Mesyuarat Pengesahan 1 Cadangan Projek Dihantar.';
                }
                else if($row->Type == 'PTE2' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Mesyuarat Pengesahan 2 Cadangan Projek Dihantar.';
                }
                else if($row->Type == 'PTA' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Mesyuarat Kelulusan Cadangan Projek Dihantar.';
                }
                else if($row->Type == 'MLI' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Mesyuarat Surat Niat Dihantar.';
                }
                else if($row->Type == 'VO2' && $row->Status  == 'AJKA'){
                    $result = 'Semakan Perubahan Kerja.';
                }
                else if($row->Type == 'VO2' && $row->Status  == 'PA'){
                    $result = 'Menunggu Mesyuarat Perubahan Kerja.';
                }
                // else if($row->Type == 'VO' && $row->Status  == 'ACCEPT'){
                //     $result = 'Mesyuarat Perubahan Kerja.';
                // }

                return $result;
            })
            ->rawColumns(['RefNo','RefDate', 'Arahan', 'Type', 'Status', 'Status2'])
            ->make(true);
    }

    public function bukaPetiDatatable(Request $request){
        $query = Tender::whereNotIn('TD_TPCode', ['DF', 'PA'])
            ->orderBy('TDNo', 'DESC')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('TDNo', function($row) {
                $route = route('perolehan.tugasan.proposalDetail',[$row->TDNo]);

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

    public function tenderDatatable(Request $request){
        $query = Tender::where('TD_TPCode', 'PA')->orderby('TDID','desc')->get();

        return datatables()->of($query)
            ->editColumn('TD_TCCode', function($row) {
                $tender_sebutharga = $this->dropdownService->tender_sebutharga();
                return $tender_sebutharga[$row->TD_TCCode];
            })
            ->editColumn('TDTitle', function($row) {

                $processCode = $row->TD_TPCode;
                $route = "#";
                $onclick = "";

                switch ($processCode) {
                    case 'CA': //PENUTUP IKLAN
                        $route = route('perolehan.tugasan.proposalDetail', [$row->TDNo]);
                        break;
                    case 'DF': // DRAF
                        $route = "#";
                        break;
                    case 'PA': // TERBITKAN IKLAN
                        $route = "#";
                        break;
                    case 'OT': //PEMBUKAAN PETI
                        $route = route('perolehan.tender.penilaian.index', [$row->TDNo]);
                        break;
                    case 'ES': // PERNILAIAN CADANGAN
                        $route = "#meetingTenderModal";
                        $onclick = 'onclick="setTenderRefNo(\''.$row->TDNo.'\')"';
                        break;
                    case 'IM': // PERNILAIAN CADANGAN
                        $route = "#";
                        break;
                    case 'BM': // MESYUARAT PENYEDIAAN PEMILIH PEMBEKAL
                        $route = route('perolehan.tugasan.viewResult', [$row->TDNo]);
                        break;
                    case 'TR': // KEPUTUSAN MESYUARAT
                        $route = route('perolehan.tugasan.viewResult', [$row->TDNo]);
                        break;
                    default:
                        $route = "#";
                }

                $result = '<a '.$onclick.' class="text-decoration-underline new modal-trigger" href="'.$route.'">'.$row->TDTitle.' </a>';

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
            ->addColumn('TPDesc', function($row) {
                $tenderProcess = $this->dropdownService->tenderProcess();

                if(!$row->TD_TPCode){
                    return "";
                }else{
                    if($row->TD_TPCode == 'PA'){
                        $route = route('perolehan.tugasan.close.advertise', [$row->TDNo]);

                        $result = '<a class="text-decoration-underline" href="'.$route.'">'.$tenderProcess[$row->TD_TPCode].' </a>';

                        return $result;
                    }
                    else{
                        return $tenderProcess[$row->TD_TPCode];
                    }

                }
            })
            ->addColumn('TPInstruction', function($row) {
                $tenderProcess = TenderProcess::where('TPCode',$row->TD_TPCode)->first();

                if($tenderProcess != null){
                    return $tenderProcess->TPInstruction;
                }else{
                    return "";
                }
            })
            ->rawColumns(['TD_TCCode','TDPublishDate', 'TDClosingDate', 'TPDesc','TDTitle'])
            ->make(true);
    }

    public function tenderProposalDatatable(Request $request,$id){

        $TPPCode = 'SB';
        $query = TenderProposal::where('TP_TDNo', $id)
            ->where('TP_TPPCode',$TPPCode)
            ->orderBy('TPSeqNo', 'asc')
            ->get();

        return datatables()->of($query)
            ->editColumn('TPNo', function($row) {

                $route = route('perolehan.tugasan.proposalDetail', [$row->TPNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->TPNo.' </a>';

                return $result;
            })
            ->addColumn('company', function($row) {

                $result = $row->contractor->COName;

                return $result;
            })
            ->editColumn('TPTotalAmt', function($row) {

                $result = number_format($row->TPTotalAmt ?? 0,2, '.', ',');

                return $result;
            })
            ->rawColumns(['TPNo','company','TPTotalAmt'])
            ->make(true);
    }

    public function closeAds($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tender = Tender::where('TDNo', $id)->first();
            $tender->TD_TPCode = 'CA';
            $tender->save();

            $tenderProposalDrafs = TenderProposal::where('TP_TDNo', $id)->where('TP_TPPCode', 'DF')->get();

            foreach ($tenderProposalDrafs as $tenderProposalDraf){
                $tenderProposalDraf->TP_TPPCode = 'CL';
                $tenderProposalDraf->save();
            }

            $this->sendNotification($tender,'CA');

            DB::commit();

            return redirect()->route('perolehan.tugasan.index');


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat cadangan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function updateStatusTender($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            //check teknikal
            $teknikals = TenderDetail::where('TDD_TDNo',$id)
                ->where('TDDType', 'LIKE', '%T%')
                ->where(function($query){
                    $query->whereNotNull('TDDCompleteO')
                        ->where('TDDCompleteO', 0);

                })
                ->get();

            if(count($teknikals) > 0){

                return response()->json([
                    'error' => 1,
                    'message' => 'Sila lengkapkan maklumat cadangan teknikal.'
                ],400);

            }

            //check kewangan
            $kewangans = TenderDetail::where('TDD_TDNo',$id)
                ->where('TDDType', 'LIKE', '%F%')
                ->where(function($query){
                    $query->whereNotNull('TDDCompleteO')
                        ->where('TDDCompleteO', 0);

                })
                ->get();

            if(count($kewangans) > 0){

                return response()->json([
                    'error' => 1,
                    'message' => 'Sila lengkapkan maklumat cadangan kewangan.'
                ],400);

            }

            $submitStatus = 'OT';

            $tender = Tender::where('TDNo',$id)->first();
            $tender->TD_TPCode = $submitStatus;
            $tender->TDMB = $user->USCode;
            $tender->save();

            $proposals = TenderProposal::where('TP_TDNo', $id)
                ->where('TPFullComply', 1)
                ->where('TP_TPPCode', 'SB')
                ->get();

            foreach($proposals as $proposal){
                $proposalDetails = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
                    ->get();

                $evaluationStep = 1;

                foreach($proposalDetails as $proposalDetail){
                    if($proposalDetail->TPD_PDSCode == 'N'){
                        $evaluationStep = 0;
                    }
                }

                $proposal->TPEvaluationStep = $evaluationStep;
                $proposal->save();
            }

            $this->sendNotification($tender,'OT');

            DB::commit();

            // return redirect()->route('perolehan.tugasan.index');

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tugasan.index'),
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


    function sendNotification($tender,$code){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            if($code == 'CA'){
                //#NOTIF-019
                $notiType = "CA"; // TUTUP IKLAN
                $title = "Penutupan Tender - $tender->TDNo";
                $desc = "Perhatian, iklan bagi tender $tender->TDNo telah ditutup.";

                //SEND NOTIFICATION TO PEROLEHAN
                $tenderPICT = $tender->tenderPIC_P;
                $pelaksanaType = "PO";

                if(!empty($tenderPICT)){

                    foreach($tenderPICT as $pict){

                        $notification = new Notification();
                        $notification->NO_RefCode = $pict->TPIC_USCode;
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

            }else if($code == 'OT'){
                //#NOTIF-020
                $notiType = "OT"; // BUKA PETI
                $title = "Pembukaan Peti Tender - $tender->TDNo";
                $desc = "Perhatian, pembukaan peti bagi tender $tender->TDNo telah dibuka.";

                //SEND NOTIFICATION TO ALL PIC
                $tenderPIC = $tender->tenderPIC;

                if(!empty($tenderPIC)){

                    foreach($tenderPIC as $pic){

                        if($pic->TPICType == 'T'){
                            $pelaksanaType = "SO";

                        }else if($pic->TPICType == 'K'){
                            $pelaksanaType = "FO";

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

    //{{--Working Code Datatable--}}
    public function meetingTenderDatatable(Request $request){

        $user = Auth::user();

        $query = BoardMeeting::where('BMStatus','NEW')->orderBy('BMNo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('BMDate', function($row){

                if(empty($row->BMDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->BMDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;

            })
            ->editColumn('BMTime', function($row){

                if(empty($row->BMTime)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->BMTime);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('h:i A');

                }

                return $formattedDate;
            })
            ->editColumn('BMStatus', function($row) {

                $boardMeetingStatus = $this->dropdownService->boardMeetingStatus();

                return $boardMeetingStatus[$row->BMStatus];

            })
            ->addColumn('action', function($row) {

                $result = '<a class="btn btn-primary" onclick="chooseMeetingTender(\''.$row->BMNo.'\')">Pilih</a>';

                return $result;

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['BMNo','BMDate','BMTime','BMStatus','action'])
            ->make(true);
    }


    public function addTenderMeeting(Request $request){
        try {

            DB::beginTransaction();

            $user = Auth::user();

            $tenderNo = $request->tenderNo;
            $meetingNo = $request->meetingNo;

            $BMNo = $meetingNo;

            $boardMeeting = BoardMeeting::where('BMNo',$meetingNo)->first();

            if(!empty($tenderNo)){
                $tenderStatus = "IM";

                $tender = Tender::where('TDNo',$tenderNo)->first();
                $tender->TD_TPCode = $tenderStatus;
                $tender->save();

                $tenderProposal = TenderProposal::select('TPNo')
                    ->where('TP_TDNo',$tenderNo)
                    ->where('TP_TPPCode','SB')
                    ->where('TPEvaluationStep',2)
                    ->get();

                $TMSCode = "I"; //default value; get from MSTenderMeetingStatus

                $boardMeetingTender = new BoardMeetingTender();
                $boardMeetingTender->BMT_BMNo       = $BMNo;
                $boardMeetingTender->BMT_TDNo       = $tenderNo;
                $boardMeetingTender->BMT_TMSCode    = $TMSCode;
                $boardMeetingTender->BMTCB         = $user->USCode;
                $boardMeetingTender->BMTMB         = $user->USCode;
                $boardMeetingTender->save();

                foreach($tenderProposal as $proposal){

                    $TPNo = $proposal->TPNo;

                    $boardMeetingProposal = new BoardMeetingProposal();
                    $boardMeetingProposal->BMP_BMNo       = $BMNo;
                    $boardMeetingProposal->BMP_TPNo       = $TPNo;
                    $boardMeetingProposal->BMPCB          = $user->USCode;
                    $boardMeetingProposal->BMPMB          = $user->USCode;
                    $boardMeetingProposal->save();

                }

            }

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }

    public function updateDocStatus(Request $request){

        try {
            DB::beginTransaction();

            $TPDNo = $request->TPDNo;
            $statusDoc = $request->statusDoc;

            $tenderProposalDetail =  TenderProposalDetail::where('TPDNo', $TPDNo)->first();
            $tenderProposalDetail->TPD_PDSCode = $statusDoc;
            $tenderProposalDetail->save();

            $TDDNo = $tenderProposalDetail->TPD_TDDNo;

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tugasan.viewCA', [$TDDNo]),
                'message' => 'Maklumat Pembekal telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat Pembekal tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }

    }

    public function updateTenderStatus($id){

        try {
            DB::beginTransaction();

            $tender =  Tender::where('TDNo', $id)->first();
            $tender->TD_TPCode = 'CP';
            $tender->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.tugasan.viewResult', [$id]),
                'message' => 'Maklumat telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }


    }

}
