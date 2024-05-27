<?php

namespace App\Http\Controllers\Pelaksana\SAK;

use App\Helper\Custom;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Models\AutoNumber;
use App\Models\Project;
use App\Models\SSMCompany;
use App\Models\SuratArahanKerja;
use App\Models\SuratArahanKerjaDet;
use App\Models\TugasanPelaksana;
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
use App\Models\Contractor;
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
use App\Models\LetterAcceptance;
use App\Models\WebSetting;

class SAKController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        return view('pelaksana.sak.index');
    }

    public function create($id){

        $project = Project::where('PT_TPNo', $id)->first();

        return view('pelaksana.sak.create',
            compact('project')
        );
    }

    public function store(Request $request){

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $SAKDIDs = $request->SAKDID;
            $SAKDDescs = $request->SAKDDesc;

            $project = Project::where('PTNo', $request->PTNo)->first();

            $tender = Tender::where('TDNo' , $project->PT_TDNo)->first();

            $period = $tender->TDContractPeriod;
            $sakStartDate = Carbon::parse($request->sak_start_date);
            $sakEndDate = $sakStartDate->copy()->addMonths($period)->subDay();

            $autoNumber                 = new AutoNumber();
            $sakNo                      = $autoNumber->generateSAKNo();

            $new_sak = new SuratArahanKerja();
            $new_sak->SAKNo     = $sakNo;
            $new_sak->SAK_PTNo  = $project->PTNo;
            $new_sak->SAKDate   = $request->sak_date;
            $new_sak->SAKStartDate   = $request->sak_start_date;
            $new_sak->SAKEndDate = $sakEndDate;
            $new_sak->SAKStatus = 'NEW';
            $new_sak->save();

            $project->PT_SAKNo = $sakNo;
            $project->PT_PPCode = 'SAK';
            $project->save();

           if(count($SAKDIDs) > 0) {
               foreach ($SAKDIDs as $key => $SAKDID){
                   $new_sak_det = new SuratArahanKerjaDet();
                   $new_sak_det->SAKD_SAKNo     = $sakNo;
                   $new_sak_det->SAKDSeq        = $key+1;
                   $new_sak_det->SAKDDesc       = $SAKDDescs[$key];
                   $new_sak_det->SAKDCB         = $user->USCode;
                   $new_sak_det->save();
               }
           }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.sak.edit', [$sakNo]),
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

    public function edit($id){

        $webSetting = WebSetting::first();

        $sak =SuratArahanKerja::where('SAKNo', $id)->first();

        $project = Project::where('PTNo', $sak->SAK_PTNo)->first();

        $letterAccept = LetterAcceptance::where('LANo' , $project->PT_LANo)->first();

        $sak->SAKDate = Carbon::parse($sak->SAKDate)->format('Y-m-d');

        $sak->SAKStartDate = Carbon::parse($sak->SAKStartDate)->format('Y-m-d');

        return view('pelaksana.sak.edit',
            compact('project', 'sak' ,'letterAccept' , 'webSetting')
        );
    }

    public function update(Request $request){
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $updateStatus = $request->updateStatus;
            $updateAccept = $request->updateAccept;
            $SAKDIDs = $request->SAKDID;
            $SAKDDescs = $request->SAKDDesc;

            $project = Project::where('PTNo', $request->PTNo)->first();

            $sak = SuratArahanKerja::where('SAKNo', $request->SAKNo)->first();
            $sak->SAKDate   = $request->sak_date;
            $sak->SAKStartDate   = $request->sak_start_date;

            $old_sakDets = SuratArahanKerjaDet::where('SAKD_SAKNo', $request->SAKNo)->get();

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_sakDets) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_sakDets as $osakDets){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($SAKDIDs as $SAKDID){
                        if($osakDets->SAKDID == $SAKDID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $osakDets->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($SAKDIDs as $key => $SAKDID){

                    $new_sakDet = SuratArahanKerjaDet::where('SAKD_SAKNo', $request->SAKNo)
                        ->where('SAKDID', $SAKDID)->first();

                    if(!$new_sakDet){
                        $new_sakDet = new SuratArahanKerjaDet();
                        $new_sakDet->SAKD_SAKNo = $request->SAKNo;
                        $new_sakDet->SAKDCB     = $user->USCode;
                    }
                    $new_sakDet->SAKDSeq = $key+1;
                    $new_sakDet->SAKDDesc = $SAKDDescs[$key];
                    $new_sakDet->save();
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                }
            }
            else{
                if(isset($SAKDIDs)){
                    foreach($SAKDIDs as $key2 => $SAKDID){
                        $new_sakDet = new SuratArahanKerjaDet();
                        $new_sakDet->SAKD_SAKNo = $request->SAKNo;
                        $new_sakDet->SAKDSeq = $key2+1;
                        $new_sakDet->SAKDDesc = $SAKDDescs[$key2];
                        $new_sakDet->SAKDCB = $user->USCode;
                        $new_sakDet->save();
                    }
                }
            }
//END HERE

            if($updateStatus == 1){
                $sak->SAKStatus = 'SUBMIT';
//                $project->PT_PPCode = 'KO-RQ';
                $project->PT_PPCode = 'KO';
                $project->save();

//                $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//                $approvalController->storeApproval($request->SAKNo, 'SAK-SM');

                //send whatsapp notification
                $tenderProposal = $project->tenderProposal;
                $contractor = Contractor::where('CONo',$tenderProposal->TP_CONo)->first();
                $phoneNo = $contractor->COPhone;
                $title = $tenderProposal->tender->TDTitle;
                $refNo = $tenderProposal->tender->TDNo;

//                $custom = new Custom();
//                $sendWS = $custom->sendWhatsappLetter('sak_notice',$phoneNo,$title,$refNo); //CONTOHWSLETTER

            }
            if($updateAccept == 1){
                $sak->SAKStatus = 'ACCEPT';
            }

            $sak->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.sak.edit', [$request->SAKNo]),
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

    public function sakDatatable(Request $request){

        $user = Auth::user();

        $query = SuratArahanKerja::orderBy('SAKNo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('SAKNo', function($row){

                $route = route('pelaksana.sak.edit',[$row->SAKNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->SAKNo.' </a>';

                return $result;
            })
            ->editColumn('SAKDate', function ($row) {
                $carbonDatetime = Carbon::parse($row->SAKDate);
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->editColumn('SAKStatus', function($row) {

                $status = $this->dropdownService->statusProcess();

                return $status[$row->SAKStatus];

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo', 'SAKNo', 'SAKDate','SAKStatus'])
            ->make(true);


    }

    public function projectDatatable(Request $request){

        $user = Auth::user();

        $query = Project::whereHas('tenderProposal.tender')->orderBy('PTNo' , 'desc')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PTNo', function($row) {

                $result = $row->PTNo;

                return $result;
            })
            ->addColumn('projectName', function($row) {

                $result = $row->tenderProposal->tender->TDTitle;

                return $result;
            })
            ->addColumn('projectType', function($row) {

                $jenis_projek = $this->dropdownService->jenis_projek();

                $result = $jenis_projek[$row->tenderProposal->tender->TD_PTCode];

                return $result;
            })
            ->addColumn('action', function($row) {

                $route = route('pelaksana.sak.create',[$row->PT_TPNo]);
                $result = '<a class="btn-sm btn btn-primary" href="'.$route.'">Pilih</a>';

                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['PTNo', 'projectName','projectType','action'])
            ->make(true);
    }

    public function updateStatusSAK(Request $request){
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $updateAccept = $request->updateAcceptSAK;

            $sak = SuratArahanKerja::where('SAKNo', $request->SAKNo)->first();

            if($updateAccept == 1){
                $sak->SAKStatus = 'ACCEPT';
                $sak->save();

                $sakGuid = $sak->fileAttachSAKDC->FAGuidID;

                $sakPDF = $this->getFile64($sakGuid);

                $imagePath = public_path('assets/images/chop/dbkl_chop.png');
                $imageContent = file_get_contents($imagePath);
                $stamp = base64_encode($imageContent);

                try{
//                    $custom = new Custom();
//                    $jsonArray = $custom->generateDigitalCert($sakPDF, $stamp , $request->SAKNo , 'SAK-DC');

                }catch (\Exception $e) {

                    return response()->json([
                        'error' => '1',
                        'message' => $e->getMessage()
                    ], 400);
                }
            }

            $sak->save();
            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.sak.edit', [$request->SAKNo]),
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

}
