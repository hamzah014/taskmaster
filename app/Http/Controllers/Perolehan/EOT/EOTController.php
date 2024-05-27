<?php

namespace App\Http\Controllers\Perolehan\EOT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Models\SSMCompany;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Illuminate\Support\Facades\Storage;


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
use App\Models\CommentLog;
use App\Models\EmailLog;
use App\Models\ExtensionOfTime;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\VariantOrder;
use App\Models\VariantOrderDet;
use App\Models\VariantOrderSpec;
use Yajra\DataTables\DataTables;
use Mail;

class EOTController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){

        return view('perolehan.eot.index');
    }

    public function view($id){

        $eotType = $this->dropdownService->eotType();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();
        $statusProcess = $this->dropdownService->statusProcess();
        $response_type = $this->dropdownService->response_type();

        $eot = ExtensionOfTime::where('EOTNo',$id)->first();

        $EOTdate = isset($eot->EOTStartDate) ? Carbon::parse($eot->EOTStartDate)->format('Y-m-d') : '-';

        $eot->statusEOT = $statusProcess[$eot->EOTStatus] ?? '-';

        $milestone = ProjectMilestone::where('PMNo', $eot->EOT_PMNo)
            // ->whereHas('projectClaim', function ($query) {
            //     $query->whereIn('PC_PCPCode', ['AP', 'BM', 'CR', 'PD']);
            // })
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

        $projectNo = $eot->EOT_PTNo;
        $project = Project::where('PTNo', $projectNo)->first();

        //JENIS PROJEK
        $jenis_projek = $this->dropdownService->jenis_projek();
        $project_type = "";

        if(!empty($project->tenderProposal->tender->TD_PTCode)){
            $project_type = $jenis_projek[$project->tenderProposal->tender->TD_PTCode];
        }

        $project->projectType = $project_type;

        //TARIKH SST
        $SSTDate = "";
        if($project->tenderProposal->letterAcceptance){

            $letterAccept = $project->tenderProposal->letterAcceptance;


            $carbonDatetime = Carbon::parse($letterAccept->LAConfirmDate);
            $SSTDate = $carbonDatetime->format('d/m/Y');
        }

        $project->projectSSTDate = $SSTDate;

        // TARIKH SAK
        $carbonDatetime = Carbon::parse($project->PTSAKDate);
        $SAKDate = $carbonDatetime->format('d/m/Y');


        $commentLog = $eot->commentLog;
        $commentLog->each(function ($comment) {
            $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
        });

        return view('perolehan.eot.view',
                compact('id', 'eot' , 'milestone', 'eotType' , 'project' , 'SAKDate' ,
                'SSTDate','project','unitMeasurement','header_detail','response_type' , 'commentLog'));
    }

    public function EOTDatatable(Request $request){

        $user = Auth::user();

        $query = ExtensionOfTime::select('TRExtensionOfTime.*','EOTDesc')
                                ->leftjoin('TRProject','EOT_PTNo', 'PTNo')
                                ->leftjoin('TRProjectMilestone','EOT_PMNo', 'PMNo')
                                ->whereIn('EOT_EPCode', ['AJKA', 'PV', 'PA' , 'MT', 'MTA', 'MTR'])
                                ->where('EOTPaid' , '1')
                                ->orderBy('EOTNo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('EOTNo', function($row){

                if($row->EOType == 'LD'){
                    $route = route('perolehan.eot.view' , $row->EOTNo);

                }elseif($row->EOType == 'ES'){
                    $route = route('perolehan.eot.view' , $row->EOTNo);
                }

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->EOTNo.' </a>';

                return $result;
            })
            ->editColumn('EOType', function($row) {
                $result = '';

                if($row->EOType == 'LD'){
                    $result = 'Lanjutan Masa';
                }
                else if($row->EOType == 'ES'){
                    $result = 'Lanjutan Perkhidmatan';
                }

                return $result;
            })
            ->editColumn('EOTStatus', function($row) {

                $status = $this->dropdownService->statusProcess();

                return $status[$row->EOTStatus];

            })
            ->addColumn('projectName', function($row) {
                $result= $row->project->tenderProposal->tender->TDTitle;

                return $result;
            })
            ->editColumn('EOTMD', function($row) {
                return [
                    'display' => e(carbon::parse($row->VOMD)->format('d/m/Y h:ia')),
                    'timestamp' => carbon::parse($row->VOMD)->timestamp
                ];

            })
            ->setRowId('indexNo')
            ->rawColumns(['EOTNo','EOTStatus','EOTType'])
            ->make(true);


    }

    public function acceptEOT(Request $request){
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $EOTNo          = $request->EOTNo;

            $eot= ExtensionOfTime::where('EOTNo',$EOTNo)->first();
            $eot->EOTStatus = 'ACCEPT';
            $eot->EOT_EPCode = "PA";
            $eot->EOTMB = $user->USCode;
            $eot->save();

//            $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//            $approvalController->storeApproval($EOTNo, 'EOT-SMP');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.eot.view',[$EOTNo, 'flag' =>'3']),
                'message' => 'Lanjutan masa berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Lanjutan masa tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }



}
