<?php

namespace App\Http\Controllers\Pelaksana\VariantOrder;

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
use App\Models\ExtensionOfTimeSpec;
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

        return view('pelaksana.variantOrder.index');
    }

    public function projectList(){

        return view('pelaksana.variantOrder.projectList');
    }

    public function create($id){

        $user = Auth::user();

        $project = Project::where('PTNo',$id)->first();

        $autoNumber = new AutoNumber();
        $VONo = $autoNumber->generateVariantOrderNo();

        $voStatus = 'NEW';

        $variantOrder = new VariantOrder();
        $variantOrder->VONo = $VONo;
        $variantOrder->VO_PTNo = $project->PTNo;
        $variantOrder->VOStatus = $voStatus;
        $variantOrder->VOCB = $user->USCode;
        $variantOrder->VOMB = $user->USCode;
        $variantOrder->VO_VPCode = 'VON';
        $variantOrder->save();

        // return $this->edit($variantOrder->VONo);
        return redirect()->route('pelaksana.vo.edit', [$variantOrder->VONo]);

    }

    public function edit($id){

        $user = Auth::user();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();
        $statusProcess = $this->dropdownService->statusProcess();

        $variantOrder = VariantOrder::where('VONo',$id)->first();

        //dd($variantOrder->variantOrderSpec);

        $VOdate = $variantOrder->VOStartDate ? Carbon::parse($variantOrder->VOStartDate)->format('Y-m-d') : null;

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
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $header_detail = $this->dropdownService->header_detail();
        $response_type = $this->dropdownService->response_type();

        return view('pelaksana.variantOrder.edit',
        compact('variantOrder','project','unitMeasurement','header_detail','response_type' , 'VOdate')
        );
    }

    public function update(Request $request){

        $messages = [
            'vo_lampiran.required'          => 'Nama lampiran diperlukan.',
            // 'vo_file.required'              => 'Fail lampiran diperlukan.',
            'spec_name.required'            => 'Spesifikasi diperlukan.',
            'spec_unit.required'            => 'Spesifikasi unit ukuran diperlukan.',
            'spec_kuantiti.required'        => 'Spesifikasi kuantiti diperlukan.',
            'spec_priceunit.required'       => 'Spesifikasi harga seunit diperlukan.',
        ];

        $validation = [
            'vo_lampiran'      => 'required',
            // 'vo_file'      => 'required',
            'spec_name'      => 'required',
            'spec_unit'      => 'required',
            'spec_kuantiti'      => 'required',
            'spec_priceunit'      => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $autoNumber = new AutoNumber();

            $VONo            = $request->variantOrderNo;

            $vo_detail       = $request->detailNo;
            $vo_lampiran     = $request->vo_lampiran;
            // $vo_file         = $request->vo_file;

            $specNo          = $request->specNo;
            $spec_header     = $request->spec_header;
            $spec_name       = $request->spec_name;
            $spec_unit       = $request->spec_unit;
            $spec_kuantiti   = $request->spec_kuantiti;
            $spec_priceunit  = $request->spec_priceunit;
            $spec_total      = $request->spec_total;

            //INSERT VO DETAILS
            if(count($vo_detail) > 0 || !empty($vo_detail)){

                $oldDatas = VariantOrderDet::where('VOD_VONo',$VONo)->get();
                $count = 0;
                foreach($vo_detail as $index => $detail){


                    $exists = $oldDatas->contains('VODNo', $detail);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $VODNo = $autoNumber->generateVariantOrderDetNo();
                        $variantOrderDet = new VariantOrderDet();
                        $variantOrderDet->VODNo = $VODNo;
                        $variantOrderDet->VOD_VONo = $VONo;
                        $variantOrderDet->VODSeq = $count;
                        $variantOrderDet->VODDesc = $vo_lampiran[$index];
                        $variantOrderDet->VODComplete = 0;
                        $variantOrderDet->VODCB = $user->USCode;
                        $variantOrderDet->VODMB = $user->USCode;
                        $variantOrderDet->save();

                        $file = $request->file('vo_file')[$index];
                        $fileType = 'VOD';
                        $refNo = $VODNo;
                        $this->saveFile($file,$fileType,$refNo);

                    }else{
                        //UPDATE CURRENT DATA
                        $variantOrderDet = VariantOrderDet::where('VODNo',$detail)->first();
                        $variantOrderDet->VODDesc = $vo_lampiran[$index];
                        $variantOrderDet->VODSeq = $count;
                        $variantOrderDet->VODComplete = 0;
                        $variantOrderDet->VODCB = $user->USCode;
                        $variantOrderDet->VODMB = $user->USCode;
                        $variantOrderDet->save();

                        if ($request->hasFile('vo_file.' . $index)) {

                            $file = $request->file('vo_file')[$index];
                            $fileType = 'VOD';
                            $refNo = $variantOrderDet->VODNo;
                            $this->saveFile($file,$fileType,$refNo);
                        }

                    }

                    $count++;

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->VODNo, $vo_detail)) {

                        // DELETE
                        $oldData->delete();

                    }

                }


            }
            //INSERT VO SPEC
            if(count($specNo) > 0 || !empty($specNo)){
                $count = 0;
                foreach($specNo as $index => $spec){

                    $variantOrderSpec = VariantOrderSpec::where('VOSNo',$spec)->first();
                    $variantOrderSpec->VOSDesc = $spec_name[$index];
                    $variantOrderSpec->VOSQty = $spec_kuantiti[$index];
                    $variantOrderSpec->VOS_UMCode = $spec_unit[$index];
                    $variantOrderSpec->VOSProposeAmt = $spec_priceunit[$index];
                    $variantOrderSpec->VOSTotalProposeAmt = $spec_total[$index];

                    if($spec_header[$index] == 1){
                        $variantOrderSpec->VOSQty = $spec_kuantiti[$index];
                        $variantOrderSpec->VOS_UMCode = $spec_unit[$index];
                    }

                    $variantOrderSpec->VOSCB = $user->USCode;
                    $variantOrderSpec->VOSMB = $user->USCode;
                    $variantOrderSpec->save();

                }
            }

            $route = route('pelaksana.vo.edit', [$request->variantOrderNo, 'flag' =>'2']);
            $total = array_sum($spec_total);

            if($request->updateStatus == 1){
                $variantOrder = VariantOrder::where('VONo',$VONo)->first();
                $variantOrder->VOStatus = 'SUBMIT';
                $variantOrder->VOAmount = $total;
                $variantOrder->VOMB = $user->USCode;
                $variantOrder->save();

                $route = route('pelaksana.vo.index');
            }

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

    public function updateMaklumatVO(Request $request){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $autoNumber = new AutoNumber();

            $VONo            = $request->variantOrderNo;

            $vo_detail       = $request->detailNo;
            $vo_lampiran     = $request->vo_lampiran;
            // $vo_file         = $request->vo_file;


            //INSERT VO DETAILS
            if( isset($vo_detail) && count($vo_detail) > 0 || !empty($vo_detail)){

                $oldDatas = VariantOrderDet::where('VOD_VONo',$VONo)->get();
                $count = 0;
                foreach($vo_detail as $index => $detail){


                    $exists = $oldDatas->contains('VODNo', $detail);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $VODNo = $autoNumber->generateVariantOrderDetNo();
                        $variantOrderDet = new VariantOrderDet();
                        $variantOrderDet->VODNo = $VODNo;
                        $variantOrderDet->VOD_VONo = $VONo;
                        $variantOrderDet->VODSeq = $count;
                        $variantOrderDet->VODDesc = $vo_lampiran[$index];
                        $variantOrderDet->VODComplete = 0;
                        $variantOrderDet->VODCB = $user->USCode;
                        $variantOrderDet->VODMB = $user->USCode;
                        $variantOrderDet->save();

                        $file = $request->file('vo_file')[$index];
                        $fileType = 'VOD';
                        $refNo = $VODNo;
                        $this->saveFile($file,$fileType,$refNo);

                    }else{
                        //UPDATE CURRENT DATA
                        $variantOrderDet = VariantOrderDet::where('VODNo',$detail)->first();
                        $variantOrderDet->VODDesc = $vo_lampiran[$index];
                        $variantOrderDet->VODSeq = $count;
                        $variantOrderDet->VODComplete = 0;
                        $variantOrderDet->VODCB = $user->USCode;
                        $variantOrderDet->VODMB = $user->USCode;
                        $variantOrderDet->save();

                        if ($request->hasFile('vo_file.' . $index)) {

                            $file = $request->file('vo_file')[$index];
                            $fileType = 'VOD';
                            $refNo = $variantOrderDet->VODNo;
                            $this->saveFile($file,$fileType,$refNo);
                        }

                    }

                    $count++;

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->VODNo, $vo_detail)) {

                        // DELETE
                        $oldData->delete();

                    }

                }

            }


            $route = route('pelaksana.vo.edit', [$request->variantOrderNo, 'flag' =>'1']);

            $totalVOSpec = VariantOrderSpec::where('VOS_VONo', $request->variantOrderNo)->sum('VOSTotalProposeAmt');

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

    public function updateSpecVO(Request $request){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $specNo          = $request->specNo;
            $spec_header     = $request->spec_header;
            $spec_name       = $request->spec_name;
            $spec_unit       = $request->spec_unit;
            $spec_kuantiti   = $request->spec_kuantiti;
            $spec_priceunit  = $request->spec_priceunit;
            $spec_total      = $request->spec_total;

            //INSERT VO SPEC
            if(count($specNo) > 0 || !empty($specNo)){
                $count = 0;
                foreach($specNo as $index => $spec){

                    $variantOrderSpec = VariantOrderSpec::where('VOSNo',$spec)->first();
                    $variantOrderSpec->VOSDesc = $spec_name[$index];
                    $variantOrderSpec->VOSQty = $spec_kuantiti[$index];
                    $variantOrderSpec->VOS_UMCode = $spec_unit[$index];
                    $variantOrderSpec->VOSProposeAmt = $spec_priceunit[$index];
                    $variantOrderSpec->VOSTotalProposeAmt = $spec_total[$index];

                    if($spec_header[$index] == 1){
                        $variantOrderSpec->VOSQty = $spec_kuantiti[$index];
                        $variantOrderSpec->VOS_UMCode = $spec_unit[$index];
                    }

                    $variantOrderSpec->VOSCB = $user->USCode;
                    $variantOrderSpec->VOSMB = $user->USCode;
                    $variantOrderSpec->save();
                }
            }

            $route = route('pelaksana.vo.edit', [$request->variantOrderNo, 'flag' =>'2']);

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

    public function updateMaklumatMilestone(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $variantOrder = VariantOrder::where('VONo', $request->variantOrderNo)->first();

            $request->voStartDate = \Carbon\Carbon::parse($request->voStartDate)->format('Y-m-d');

            $variantOrder->VODesc = $request->voDesc;
            $variantOrder->VOWorkDay = $request->voWorkDay;
            $variantOrder->VOStartDate = $request->voStartDate;
            $variantOrder->save();

            $route = route('pelaksana.vo.edit', [$request->variantOrderNo, 'flag' =>'3']);

            $totalVOSpec = VariantOrderSpec::where('VOS_VONo', $request->variantOrderNo)->sum('VOSTotalProposeAmt');

            if($request->updateStatusVOMS == 1){
                $variantOrder = VariantOrder::where('VONo',$request->variantOrderNo)->first();
                $variantOrder->VOStatus = 'SUBMIT';
                $variantOrder->VO_VPCode = 'VOA';
                $variantOrder->VOAmount = $totalVOSpec;
                $variantOrder->VOMB = $user->USCode;
                $variantOrder->save();

                $route = route('pelaksana.vo.index');
            }

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


    public function addSpec(Request $request){

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $autoNumber = new AutoNumber();

            // $variantOrderSpec_last = VariantOrderSpec::where('VOS_VONo', $request->VONo)->first();

            $VOSNo = $autoNumber->generateVariantOrderSpecNo();

            if(isset($request->VOSID)){
                $VOSIDs = $request->VOSID;
                $VOSDescs = $request->VOSDesc;
                $VOSStockInds = $request->VOSStockInd;
                $VOS_UMCodes = $request->VOS_UMCode;
                $VOSQtys = $request->VOSQty;
                $VOSProposedAmts = $request->VOSProposedAmt;

            }
            else{
                $VOSIDs = [];
            }

            //dd($VOSIDs);

            $old_VOSpec = VariantOrderSpec::where('VOS_VONo', $request->VONo)->get();

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_VOSpec) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_VOSpec as $oVOSpec){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($VOSIDs as $VOSID){
                        if($oVOSpec->VOSID == $VOSID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $oVOSpec->delete();
                    }
                }


                //ADD NEW 4,5,6
                foreach($VOSIDs as $key => $VOSID){

                    $new_VOSpec = VariantOrderSpec::where('VOS_VONo', $request->VONo)->where('VOSID', $VOSID)->first();

                    if(!$new_VOSpec){
                        $autoNumber = new AutoNumber();
                        $VOSNo = $autoNumber->generateVariantOrderSpecNo();

                        $new_VOSpec = new VariantOrderSpec();
                        $new_VOSpec->VOSNo            = $VOSNo;
                        $new_VOSpec->VOS_VONo        = $request->VONo;
                        $new_VOSpec->VOSCB             = $user->USCode;
                    }
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                    $new_VOSpec->VOSSeq             = $key + 1;
                    $new_VOSpec->VOSStockInd        = $VOSStockInds[$key];
                    $new_VOSpec->VOSDesc            = $VOSDescs[$key];

                    if($VOSStockInds[$key] == 1){

                        $totalprice = $VOSProposedAmts[$key] * $VOSQtys[$key];

                        $new_VOSpec->VOSProposeAmt = $VOSProposedAmts[$key];
                        $new_VOSpec->VOSTotalProposeAmt = $totalprice;
                        $new_VOSpec->VOSQty = $VOSQtys[$key];
                        $new_VOSpec->VOS_UMCode = $VOS_UMCodes[$key];
                    }

                    $new_VOSpec->VOSMB              = $user->USCode;
                    $new_VOSpec->save();

                    }
                }
                else{
                    if(count($VOSIDs) > 0){
                        foreach ($VOSIDs as $key => $VOSID){
                            $autoNumber = new AutoNumber();
                            $VOSNo = $autoNumber->generateVariantOrderSpecNo();

                            $VOSpec = new VariantOrderSpec();
                            $VOSpec->VOSNo            = $VOSNo;
                            $VOSpec->VOS_VONo        = $request->VONo;
                            $VOSpec->VOSStockInd      = $VOSStockInds[$key];
                            $VOSpec->VOSSeq           = $key + 1;
                            $VOSpec->VOSDesc          = $VOSDescs[$key];

                            if($VOSStockInds[$key] == 1){

                                $totalprice = $VOSProposedAmts[$key] * $VOSQtys[$key];

                                $VOSpec->VOSProposeAmt = $VOSProposedAmts[$key];
                                $VOSpec->VOSTotalProposeAmt = $totalprice;
                                $VOSpec->VOSQty = $VOSQtys[$key];
                                $VOSpec->VOS_UMCode = $VOS_UMCodes[$key];
                            }

                            $VOSpec->VOSCB = $user->USCode;
                            $VOSpec->VOSMB = $user->USCode;
                            $VOSpec->save();

                        }
                    }
                }


            // if($request->key == 0){
            //     if($variantOrderSpec_last){
            //         $seq = $variantOrderSpec_last->VOSSeq+1;
            //     }
            //     else{
            //         $seq = 1;
            //     }
            // }
            // else{
            //     $seq = $request->key;

            //     $variantOrderSpec_after = VariantOrderSpec::where('VOSSeq', '>', $seq)
            //         ->where('VOS_VONo', $request->variantOrderNo)
            //         ->orderBy('VOSSeq', 'ASC')
            //         ->get();

            //     foreach ($variantOrderSpec_after as $spec){
            //         $spec->VOSSeq = $spec->VOSSeq + 1;
            //         $spec->save();
            //     }
            //     $seq = $request->key+1;
            // }

            // $variantOrderSpec = new VariantOrderSpec();
            // $variantOrderSpec->VOSNo = $VOSNo;
            // $variantOrderSpec->VOS_VONo = $request->variantOrderNo;
            // $variantOrderSpec->VOSStockInd = $request->header_detail;
            // $variantOrderSpec->VOSSeq = $seq;
            // $variantOrderSpec->VOSDesc = $request->title;

            // if($request->header_detail == 1){

            //     $totalprice = $request->priceunit * $request->qty;

            //     $variantOrderSpec->VOSProposeAmt = $request->priceunit;
            //     $variantOrderSpec->VOSTotalProposeAmt = $totalprice;
            //     $variantOrderSpec->VOSQty = $request->qty;
            //     $variantOrderSpec->VOS_UMCode = $request->uMCode;
            // }

            // $variantOrderSpec->VOSCB = $user->USCode;
            // $variantOrderSpec->VOSMB = $user->USCode;
            // $variantOrderSpec->save();
            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.vo.edit', [$request->VONo, 'flag' =>'2']),
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

    public function variantOrderDatatable(Request $request){

        $user = Auth::user();

        $query = VariantOrder::orderBy('VONo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('VONo', function($row){

                $route = route('pelaksana.vo.edit',[$row->VONo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->VONo.' </a>';

                return $result;
            })
            ->addColumn('TDTitle', function($row) {

                return $row->project->tender->TDTitle;
            })
            ->editColumn('VO_VPCode', function($row) {

                $voProcessDesc = $this->dropdownService->voProcessDesc();
                if($row->VO_VPCode ==  null){
                    return '-';
                }
                else{
                    return $voProcessDesc[$row->VO_VPCode];
                }
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['VONo','VO_VPCode', 'TDTitle'])
            ->make(true);


    }

    public function projectDatatable(Request $request){

        $user = Auth::user();

        $query = Project::whereNotNull('PTPwd')->whereHas('tenderProposal.tender')->orderBy('PTNo' , 'desc')->get();

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

                $route = route('pelaksana.vo.create',[$row->PTNo]);
                $result = '<a class="btn-sm fw-bold btn btn-primary" href="'.$route.'">Pilih</a>';

                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['PTNo', 'projectName','projectType','action'])
            ->make(true);
    }

    public function requestInfo(Request $request){
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $VONo          = $request->VONo;

            $vo = VariantOrder::where('VONo', $VONo)->first();

            if(count($vo->variantOrderSpecStock1) > 0 ){
                $vo->VOStatus   = 'INFO';
                $vo->VO_VPCode  = 'RQ-RQ';
                $vo->VOMB       = $user->USCode;
                $vo->save();

                $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());

                $approvalController->storeApproval($VONo, 'VO-RQ');
            }
            else{
                return 0;
            }

            DB::commit();

            return 1;

        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function cancelVO(Request $request){
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $VONo = $request->VONo;

            $vo = VariantOrder::where('VONo', $VONo)->first();
            $vo->VO_VPCode = 'VOC';
            $vo->VOMB = $user->USCode;
            $vo->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.vo.edit', [$VONo]),
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
