<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\Department;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{

    public function listApprovalAuthUser(Request $request) {

        $messages = [
           'contractorCode.required'	=> 'Contractor is required.',
           'appNo.required'		        => 'Approval No is required.',
       ];

       $validation = [
            'contractorCode'  => 'required|string',
            'appNo'          => 'nullable|string',
       ];

       $validator = Validator::make($request->all(), $validation, $messages);

       if ($validator->fails()) {
           return response()->json([
               'status'  => 'failed',
               'message' => $validator->messages()->First()
           ]);
       }

       $user = Auth::user();

        $approvalData = Approval::join('vwApprovalAuthUser','ApprovalNo','APNo')
                                ->join('MSApprovalType','ATCode','AP_ATCode')
                                ->where('ContractorCode',$request->contractorCode)
                                ->where('UserCode',$user->USCode)
                                ->whereNull('APApprove')
                                ->OrderBy('APCD','ASC')
                                ->get();
       $data = [];

       if(isset($approvalData) && count($approvalData)>0) {
           foreach ($approvalData as $x => $approval) {
               array_push($data, [
                   'approvalNo'     => $approval->APNo ,
                   'typeCode'       => $approval->ATCode ,
                   'typeDesc'       => $approval->ATDesc ,
                   'typeCategory'   => $approval->ATType ,
                   'typeEntity'     => $approval->ATEntity ,
                   'createDate'     => carbon::parse($approval->APCD)->format('Y-m-d H:i:s'),
                   'contractorCode' => $approval->ContractorCode ,
              ]);
           }
       }

       return response()->json([
           'status'  => 'success',
           'data' => $data
       ]);
   }

   public function listApproval(Request $request) {

    $messages = [
       'appNo.required'		=> 'Approval No is required.',
   ];

   $validation = [
       'appNo'  => 'nullable|string',
   ];

   $validator = Validator::make($request->all(), $validation, $messages);

   if ($validator->fails()) {
       return response()->json([
           'status'  => 'failed',
           'message' => $validator->messages()->First()
       ]);
   }

   $user = Auth::user();
   $dept = Department::where('DPTEmail', $user->USEmail)->first();

   if ($user->USType == 'AU'){
        $approvalData = Approval::leftjoin('vwApprovalAuthUser','ApprovalNo','APNo')
                                ->leftjoin('MSApprovalType','ATCode','AP_ATCode')
                                ->where('UserCode',$user->USCode)
                                ->whereNull('APApprove')
                                ->OrderBy('APCD','ASC')
                                ->get();
   }else{
        $approvalData = Approval::leftjoin('vwApproval','ApprovalNo','APNo')
                                ->leftjoin('MSApprovalType','ATCode','AP_ATCode')
                                ->where('UserCode',$user->USCode)
                                ->whereNull('APApprove')
                                ->OrderBy('APCD','ASC')
                                ->get();
    }
   $data = [];

   if(isset($approvalData) && count($approvalData)>0) {
       foreach ($approvalData as $x => $approval) {
           array_push($data, [
               'approvalNo'     => $approval->APNo ,
               'typeCode'       => $approval->ATCode ,
               'typeDesc'       => $approval->ATDesc ,
               'typeCategory'   => $approval->ATType ,
               'typeEntity'     => $approval->ATEntity ,
               'createDate'     => carbon::parse($approval->APCD)->format('Y-m-d H:i:s'),
          ]);
       }
   }

   return response()->json([
       'status'  => 'success',
       'data' => $data
   ]);
}

   public function viewApproval(Request $request) {
         $messages = [
           'approvalNo.required'	=> 'Approval No is required.',
       ];

       $validation = [
           'approvalNo'	=> 'required|string',
       ];

       $validator = Validator::make($request->all(), $validation, $messages);

       if ($validator->fails()) {
           return response()->json([
               'status'  => 'failed',
               'message' => $validator->messages()->First()
           ]);
       }

       $user = Auth::user();

       $approval = Approval::leftjoin('vwApproval','ApprovalNo','APNo')
                            ->leftjoin('MSApprovalType','ATCode','AP_ATCode')
                            ->where('APNo',$request->approvalNo)
                            ->whereNull('APApprove')
                            ->first();

       if ($approval == null){
           return response()->json([
               'status'  => 'failed',
               'message' => 'Transaction does not exists!',
           ]);
       }

       $data = array(
            'approvalNo'     => $approval->APNo ,
            'typeCode'       => $approval->ATCode ,
            'typeDesc'       => $approval->ATDesc ,
            'typeCategory'   => $approval->ATType ,
            'typeEntity'     => $approval->ATEntity ,
            'createDate'     => carbon::parse($approval->APCD)->format('Y-m-d H:i:s'),
       );

       return response()->json([
           'status'  => 'success',
           'data' => $data
       ]);
   }

   public function updateApproval(Request $request){

    $messages = [
        'approvalNo.required'	=> 'Approval No is required.',
        'approve.required'	    => 'Approve is required.',
    ];

    $validation = [
        'approvalNo'	=> 'required|string',
        'approve'	    => 'required|boolean',
    ];

    $validator = Validator::make($request->all(), $validation, $messages);

    if ($validator->fails()) {
        return response()->json([
            'status'  => 'failed',
            'message' => $validator->messages()->First()
        ]);
    }

    $user = Auth::user();

    $approval = Approval::where('APNo',$request->approvalNo) ->first();

    if ($approval == null){
        return response()->json([
            'status'  => 'failed',
            'message' => 'Transaction does not exists!',
        ]);
    }

    if ($approval->APApprove != null){
        return response()->json([
            'status'  => 'failed',
            'message' => 'Transaction has been approved!',
        ]);
    }

    try {
        DB::beginTransaction();

        $approval->APApprove 	    = $request->approve;
        $approval->APResponseDate 	= Carbon::now();
        $approval->APMB 			= $user->USCode;
        $approval->APMD		        = Carbon::now();
        $approval->save();

        DB::commit();

    } catch (\Throwable $e) {
        DB::rollback();
        throw $e;
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Transaction has been updated successfully!',
        'data' => null
    ]);
}

}
