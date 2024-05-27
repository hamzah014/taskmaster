<?php

namespace App\Http\Controllers\Perolehan\VariantOrder;

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
use App\Models\EmailLog;
use App\Models\Notification;
use App\Models\Project;
use App\Models\VariantOrder;
use App\Models\VariantOrderDet;
use App\Models\VariantOrderSpec;
use Yajra\DataTables\DataTables;
use Mail;

class VariantOrderController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){

        return view('perolehan.variantOrder.index');
    }

    public function view($id){

        $user = Auth::user();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();
        $statusProcess = $this->dropdownService->statusProcess();

        $variantOrder = VariantOrder::where('VONo',$id)->first();
        $variantOrder->statusVO = $statusProcess[$variantOrder->VOStatus] ?? "-";

        $project = $variantOrder->project;

        //JENIS PROJECT
        $jenis_projek = $this->dropdownService->jenis_projek();
        $project_type = "";

        if(!empty($project->tenderProposal->tender->TD_PTCode)){
            $project_type = $jenis_projek[$project->tenderProposal->tender->TD_PTCode];
        }

        $project->projectType = $project_type;

        // TARIKH SST
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

        $project->projectSAKDate = $SAKDate;

        $commentLog = $variantOrder->commentLog;
        $commentLog->each(function ($comment) {
            $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
        });

        return view('perolehan.variantOrder.view',
        compact('variantOrder','project','unitMeasurement','header_detail', 'commentLog')
        );

    }

    public function updateStatus(Request $request){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $VONo         = $request->variantOrderNo;
            $updateStatus = $request->updateStatus;
            $status = "";  //status refer - dropdownService->statusProcess
            $variantOrder = VariantOrder::where('VONo',$VONo)->first();

            if($updateStatus == 0){ //ACCEPT
                $status = 'ACCEPT';
                $variantOrder->VO_VPCode = 'PA';
            }

            $variantOrder->VOStatus = $status;
            $variantOrder->VOMB = $user->USCode;
            $variantOrder->save();

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => route('perolehan.vo.index'),
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Fail tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }

    }

    public function variantOrderDatatable(Request $request){

        $user = Auth::user();

        $query = VariantOrder::select('TRVariationOrder.*','TDTitle')
                                ->leftjoin('TRProject','VO_PTNo', 'PTNo')
                                ->leftjoin('TRTenderProposal','PT_TPNo', 'TPNo')
                                ->leftjoin('TRTender','TP_TDNo', 'TDNo')
                                ->whereNotIn('VOStatus', ['NEW', 'SUBMIT', 'SUBMIT'])
                                ->orderBy('VONo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('VONo', function($row){

                $route = route('perolehan.vo.view',[$row->VONo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->VONo.' </a>';

                return $result;
            })
            ->editColumn('VOStatus', function($row) {

                $status = $this->dropdownService->statusProcess();

                return $status[$row->VOStatus];

            })
            ->editColumn('VOMD', function($row) {
                return [
                    'display' => e(carbon::parse($row->VOMD)->format('d/m/Y h:ia')),
                    'timestamp' => carbon::parse($row->VOMD)->timestamp
                ];

            })
            ->setRowId('indexNo')
            ->rawColumns(['VONo','VOStatus'])
            ->make(true);


    }

}
