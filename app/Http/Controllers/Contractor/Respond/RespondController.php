<?php

namespace App\Http\Controllers\Contractor\Respond;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Models\AutoNumber;
use App\Models\CommentLog;
use App\Models\ExtensionOfTime;
use App\Models\FileAttach;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectClaimDet;
use App\Models\ProjectInvoice;
use App\Models\ProjectMilestone;
use App\Models\TemplateClaimFile;
use App\Models\TugasanKontraktor;
use App\Models\VariantOrder;
use App\Models\VariantOrderSpec;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Cassandra\Exception\ExecutionException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class RespondController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        $PTNo = Session::get('project');

        return view('contractor.respond.index',
            compact('PTNo')
        );
    }

    public function respondDatatable(Request $request){

        $projectNo = Session::get('project');

        $data = TugasanKontraktor::
        select(['RefNo', 'RefDate', 'Type', 'Status', 'Status2', 'Status3'])
            ->where(function ($query) use ($request, $projectNo) {
                $query->where(function ($subquery) use ($request){
                    $subquery->where('Type', 'VO2')->whereIn('Status', ['RQ', 'VOS'])
                        ->where('Status2', $request->PTNo);
                })
//                $query->where(function ($subquery) use ($request){
//                    $subquery->where('Type', 'VO')->whereIn('Status', ['INFO', 'NEWINFO','SUBMIT','AGREE','DISAGREE'])
//                        ->where('Status2', $request->PTNo);
//                })
//                ->orWhere(function ($subquery) use ($request) {
//                    $subquery->where('Type', 'EOT')->whereIn('Status', ['SUBMIT','AGREE','DISAGREE'])
//                        ->where('Status2', $request->PTNo);
//                })
                ->orWhere(function ($subquery) use ($request) {
                    $subquery->where('Type', 'EOT2')->whereIn('Status', ['RQV', 'EOTS'])
                        ->where('Status2', $request->PTNo);
                })
                ->orWhere(function ($subquery) use ($request, $projectNo) {
                    $subquery->where('Type', 'SAK')->whereIn('Status', ['SUBMIT','UPLOAD','REVIEW','ACCEPT'])
                        ->where('Status2', $projectNo);
                });
            })
            ->orderBy('RefDate', 'DESC')
            ->get();


        return datatables()->of($data)
            ->editColumn('RefNo', function($row) {
                if($row->Type == 'VO2' && in_array($row->Status, ['RQ'])){
                    $route = route('contractor.respond.vo.createInfo',[$row->RefNo]);
                }
                else if($row->Type == 'VO2' && in_array($row->Status, ['VOS'])){
                    $route = route('contractor.respond.vo.decision',[$row->RefNo]);
                }
//                if($row->Type == 'VO' && in_array($row->Status, ['INFO','NEWINFO'])){
//                    $route = route('contractor.respond.vo.createInfo',[$row->RefNo]);
//                }
//                else if($row->Type == 'VO' && in_array($row->Status, ['SUBMIT','AGREE','DISAGREE'])){
//                    $route = route('contractor.respond.vo.decision',[$row->RefNo]);
//                }
//                else if($row->Type == 'EOT' && in_array($row->Status, ['SUBMIT','AGREE','DISAGREE'])){
//                    $route = route('contractor.eot.viewEOT',[$row->RefNo]);
//                }
                else if($row->Type == 'SAK' && in_array($row->Status, ['SUBMIT','UPLOAD','REVIEW','ACCEPT'])){
                    $route = route('contractor.sak.viewSAK',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && in_array($row->Status, ['RQV'])){
                    $route = route('contractor.eot.edit',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && in_array($row->Status, ['EOTS'])){
                    $route = route('contractor.eot.viewEOT',[$row->RefNo]);
                }
//                else if($row->Type == 'EOT' && $row->Status  == 'SUBMIT' && $row->Status2  == 'LD' && $row->Status3  == 0){
//                    $route = route('pelaksana.eot.view',[$row->RefNo]);
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'REVIEW' && $row->Status2  == 'ES' && $row->Status3  == 1){
//                    $route = route('pelaksana.eot.editPaidService',[$row->RefNo]);
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'REVIEW' && $row->Status2  == 'LD' && $row->Status3  == 1){
//                    $route = route('pelaksana.eot.create.eotBerbayar',[$row->RefNo]);
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'NEW' && $row->Status2  == 'ES' && $row->Status3  == 1){
//                    $route = route('pelaksana.eot.editPaidService',[$row->RefNo]);
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'NEW' && $row->Status2  == 'LD' && $row->Status3  == 1){
//                    $route = route('pelaksana.eot.create.eotBerbayar',[$row->RefNo]);
//                }
//                else if($row->Type == 'VO' && $row->Status  == 'NEW'){
//                    $route = route('pelaksana.vo.edit',[$row->RefNo]);
//                }
//                else if($row->Type == 'VO' && $row->Status  == 'REVIEW'){
//                    $route = route('pelaksana.VO.edit',[$row->RefNo]);
//                }
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
                if($row->Type == 'VO2' && $row->Status == 'RQ'){
                    $result = 'Menunggu Maklumat Item.';
                }
                else if($row->Type == 'VO2' && in_array($row->Status, ['VOS'])){
                    $result = 'Menunggu Maklum Balas.';
                }
//                if($row->Type == 'VO' && $row->Status == 'INFO'){
//                    $result = 'Menunggu Maklumat Item.';
//                }
//                else if($row->Type == 'VO' && in_array($row->Status, ['NEWINFO'])){
//                    $result = '-.';
//                }
//                else if($row->Type == 'VO' && in_array($row->Status, ['SUBMIT'])){
//                    $result = 'Menunggu Maklum Balas.';
//                }
//                else if($row->Type == 'EOT' && in_array($row->Status, ['SUBMIT'])){
//                    $result = 'Menunggu Maklum Balas.';
//                }
                else if($row->Type == 'SAK' && in_array($row->Status, ['SUBMIT'])){
                    $result = 'Muat Naik Surat Arahan Kerja..';
                }
                else if($row->Type == 'SAK' && in_array($row->Status, ['REVIEW'])){
                    $result = 'Semak Semula Surat Arahan Kerja.';
                }
                else if($row->Type == 'EOT2' && in_array($row->Status, ['RQV'])){
                    $result = 'Semak Semula Lanjutan Masa.';
                }
                else if($row->Type == 'EOT2' && in_array($row->Status, ['EOTS'])){
                    $result = 'Menunggu Maklum Balas Lanjutan Masa.';
                }
//                else if($row->Type == 'EOT' && $row->Status  == 'SUBMIT' && $row->Status2  == 'LD'){
//                    $result = 'Semakan Lanjutan Masa.';
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'REVIEW' && $row->Status2  == 'ES' && $row->Status3  == 1){
//                    $result = 'Semak Semula Lanjutan Perkhidmatan Berbayar.';
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'REVIEW' && $row->Status2  == 'LD' && $row->Status3  == 1){
//                    $result = 'Semak Semula Lanjutan Masa Berbayar.';
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'NEW' && $row->Status2  == 'ES' && $row->Status3  == 1){
//                    $result = 'Menunggu Lanjutan Perkhidmatan Berbayar Dihantar.';
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'NEW' && $row->Status2  == 'LD' && $row->Status3  == 1){
//                    $result = 'Menunggu Lanjutan Masa Berbayar Dihantar.';
//                }
//                else if($row->Type == 'VO' && $row->Status  == 'NEW'){
//                    $result = 'Menunggu Perubahan Kerja Dihantar.';
//                }
//                else if($row->Type == 'VO' && $row->Status  == 'REVIEW'){
//                    $result = 'Semak Semula Perubahan Kerja.';
//                }

                return $result;
            })
            ->rawColumns(['RefNo','RefDate', 'Arahan', 'Type', 'Status', 'Status2'])
            ->make(true);
    }

    public function createInfo($id){
        $PTNo = Session::get('project');
        $unitMeasurement = $this->dropdownService->unitMeasurement();

        $variantOrder = VariantOrder::where('VONo', $id)->first();

        return view('contractor.respond.vo.createInfo',
            compact('variantOrder', 'PTNo', 'unitMeasurement')
        );
    }

    public function decision($id){
        $PTNo = Session::get('project');
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $statusProcess = $this->dropdownService->statusProcess();

        $project = Project::where('PTNo', $PTNo)->first();

        $variantOrder = VariantOrder::where('VONo', $id)->first();
        $variantOrder->statusVO = $statusProcess[$variantOrder->VOStatus] ?? "-";

        $jenis_projek = $this->dropdownService->jenis_projek();
        $project_type = "";

        if(!empty($project->tender->TD_PTCode)){
            $project_type = $jenis_projek[$project->tender->TD_PTCode];
        }

        $project->projectType = $project_type;

        return view('contractor.respond.vo.view',
            compact('variantOrder', 'PTNo', 'unitMeasurement', 'project')
        );
    }

    public function storeInfo(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $specNo          = $request->specNo;
            $spec_priceunit  = $request->VOSProposedAmt;
            $spec_total      = $request->VOSTotalProposeAmt;

            $vo = VariantOrder::where('VONo', $request->VONo)->first();
            $vo->VOInfo     = 1;
            $vo->VOStatus   = 'NEWINFO';
            $vo->VO_VPCode  = 'RQS';
            $vo->VOMB       = $user->USCode;
            $vo->save();

            if(count($specNo) > 0 || !empty($specNo)){
                $count = 0;
                foreach($specNo as $index => $spec){
                    $variantOrderSpec = VariantOrderSpec::where('VOSNo',$spec)->first();
                    $variantOrderSpec->VOSCOProposeAmt = $spec_priceunit[$index];
                    $variantOrderSpec->VOSCOTotalProposeAmt = $spec_total[$index];
                    $variantOrderSpec->VOSCB = $user->USCode;
                    $variantOrderSpec->VOSMB = $user->USCode;
                    $variantOrderSpec->save();
                }
            }

            $route = route('contractor.respond.vo.createInfo', [$request->VONo]);

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => $route,
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

    public function updateStatus(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $VONo         = $request->variantOrderNo;
            $updateStatus = $request->tidakSetuju;
            $status = "";  //status refer - dropdownService->statusProcess

            $variantOrder = VariantOrder::where('VONo',$VONo)->first();

            if($updateStatus == 1){ //DISAGREE
                $status = 'DISAGREE';
                $variantOrder->VO_VPCode = 'VOR';
                $variantOrder->VOAccept = 0;
                $variantOrder->VOResponseDate = Carbon::now();
            }else if($updateStatus == 0){ //AGREE
                $status = 'AGREE';
                $variantOrder->VO_VPCode = 'VOA-RQ';
                $variantOrder->VOAccept = 1;
                $variantOrder->VOResponseDate = Carbon::now();

                #VORP
                $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
                $result = $approvalController->storeApproval($variantOrder->VONo, 'VO-RP');
            }

            $variantOrder->VOStatus = $status;
            $variantOrder->VOMB = $user->USCode;
            $variantOrder->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.index'),
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

    public function rundingSemula(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $VONo = $request->VONo;

            $vo = VariantOrder::where('VONo', $VONo)->first();
            $vo->VO_VPCode = 'VOV';
            $vo->VOMB = $user->USCode;
            $vo->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.respond.index', [$VONo]),
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
