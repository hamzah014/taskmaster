<?php

namespace App\Http\Controllers\Pelaksana\Claim;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralNotificationController;
use App\Models\AutoNumber;
use App\Models\CommentLog;
use App\Models\ProjectClaim;
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
use App\Models\Notification;
use App\Models\ProjectClaimDet;
use App\Models\ProjectMilestone;
use App\Models\WebSetting;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class ClaimController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){
        return view('pelaksana.claim.index');
    }

    public function view($id){
        $webSetting = WebSetting::first();
        $claim = ProjectClaim::where('PCNo', $id)->first();
        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $yt = $this->dropdownService->yt();

        $projectInvoice = $claim->projectInvoice;

        return view('pelaksana.claim.view', compact('claim', 'tender_sebutharga','yt' , 'projectInvoice' , 'webSetting'));
    }

    public function claimDatatable(Request $request){
        $projectNo = Session::get('project');

        $query = ProjectClaim::where('PC_PCPCode', '!=', 'DF')->orderby('PCID','desc')->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PCNo', function($row) {

                $route = route('pelaksana.claim.view', [$row->PCNo]);
                $result = '<a class="" href="'.$route.'">'.$row->PCNo.'</a>';
                return $result;
            })
            ->editColumn('PCTotalAmt', function($row) {
                $amount = number_format($row->PCTotalAmt ?? 0 ,2, '.', ',');

                return $amount;
            })
            ->editColumn('PC_PCPCode', function($row) {
                $claimProcess = $this->dropdownService->claimProcess();

                return $claimProcess[$row->PC_PCPCode];
            })
            ->addColumn('action', function($row) {
                $result = '';


                $route = route('pelaksana.claim.review', [$row->PCNo]);

                if($row->PC_PCPCode == 'SM'){
                    $result = '<a class="btn btn-primary btn-sm" href="'.$route.'"><i class="ki-solid ki-pencil"></i></a>';

                }

                return $result;
            })
            ->rawColumns(['indexNo', 'PCNo', 'PCTotalAmt', 'PC_PCPCode', 'action'])
            ->make(true);
    }

    public function review($id){
        $webSetting = WebSetting::first();
        $claim = ProjectClaim::where('PCNo', $id)->first();
        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        // dd($claim->projectMilestone->project->tenderProposal->tender->TDTitle);
        $yt = $this->dropdownService->yt();

        $commentLog = $claim->commentLog;
        $commentLog->each(function ($comment) {
            $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
        });

        $projectInvoice = $claim->projectInvoice;

        return view('pelaksana.claim.review', compact('claim', 'tender_sebutharga','yt' , 'projectInvoice' , 'webSetting'));
    }

    public function acceptClaim($id){
        $projectNo = Session::get('project');

        $claim = ProjectClaim::where('PCNo', $id)->first();
        $claim->PC_PCPCode = 'AP';
        $claim->save();

        $this->sendNotification($claim,'A');

        return redirect()->route('pelaksana.project.view', [$claim->PC_PTNo]);
    }

    public function acceptClaimTerm($id){
        $projectNo = Session::get('project');

        $claim = ProjectClaim::where('PCNo', $id)->first();
        $claim->PC_PCAPCode = 'AP';
        $claim->save();

        $this->sendNotification($claim,'AW');// Notify accept claim lulus bersyarat

        return redirect()->route('pelaksana.project.view', [$claim->PC_PTNo]);
    }

    public function commentStore(Request $request){

        $user = Auth::user();

        // $messages = [
        //     'review_description.required' 	=> "Keterangan diperlukan.",
        // ];

        // $validation = [
        //     'review_description' 	=> 'required',
        // ];


        // $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $autoNumber     = new AutoNumber();
            $CLNo           = $autoNumber->generateCommentLog();

            $claimNo        = $request->claimNo;
            $review		    = $request->review_description;
            $checkLampiran  = $request->checkLampiran;
            $type           = "C";
            $pcpCode        = "RV";


            $claim= ProjectClaim::where('PCNo',$claimNo)->first();

            if($checkLampiran == 0){
                $claim->PC_PCPCode = $pcpCode;
            }else{
                $claim->PC_PCAPCode = 'RV';

            }
            $claim->save();

            $commentLog = new CommentLog();
            $commentLog->CLNo = $CLNo;
            $commentLog->CLRefNo = $claimNo;
            $commentLog->CLType = $type;
            $commentLog->CL_USCode = $user->USCode;
            $commentLog->CLDesc = $review;
            $commentLog->save();

            $this->sendNotification($claim,'R');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.claim.review',[$claimNo]),
                'message' => 'Komen bagi tuntutan berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Komen bagi milestone tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }

    public function commentTermStore(Request $request){

        $user = Auth::user();

        // $messages = [
        //     'review_description.required' 	=> "Keterangan diperlukan.",
        // ];

        // $validation = [
        //     'review_description' 	=> 'required',
        // ];


        // $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $autoNumber     = new AutoNumber();
            $CLNo           = $autoNumber->generateCommentLog();

            $claimNo        = $request->claimNo;
            $review		    = $request->review_description;
            $checkLampiran  = $request->checkLampiran;
            $type           = "C";
            $pcapCode        = "RV";


            $claim= ProjectClaim::where('PCNo',$claimNo)->first();

            if($checkLampiran == 0){
                $claim->PC_PACPCode = $pcapCode;
            }
            $claim->save();

            $commentLog = new CommentLog();
            $commentLog->CLNo = $CLNo;
            $commentLog->CLRefNo = $claimNo;
            $commentLog->CLType = $type;
            $commentLog->CL_USCode = $user->USCode;
            $commentLog->CLDesc = $review;
            $commentLog->save();
            $this->sendNotification($claim,'R');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.claim.review',[$claimNo]),
                'message' => 'Komen bagi tuntutan berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Komen bagi milestone tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }

    function sendNotification($claim,$status){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $claimNo = $claim->PCNo;
            $projMileNo = $claim->PC_PMNo;

            $projectMilestone = ProjectMilestone::where('PMNo',$projMileNo)->first();
            $project = $projectMilestone->project;
            $projectNo = $project->PTNo;
            $tender = $projectMilestone->project->tenderProposal->tender;

            $data = array(
                'PTNo' => $projectNo,
                'PCNo' => $claimNo,
            );

            if($status == 'R'){ //review
                //#NOTIF-007
                $code = 'PC-RV';

            }elseif ($status == 'A') { //accept
                //#NOTIF-008
                $code = 'PC-AP-CO';

                //#NOTIF-009
                $code2 = 'PC-AP';

            }elseif ($status == 'AW') { //accept with term
                //#NOTIF-008A
                $code = 'PC-APT-CO';

                //#NOTIF-009A
                $code2 = 'PC-APT';
            }

            $notification = new GeneralNotificationController();

            if($status == 'A' || $status=='AW'){ //accept or accept with terms

                //SEND NOTIFICATION TO KEWANGAN
                $tenderPIC = $tender->tenderPIC;

                if(!empty($tenderPIC)){

                    foreach($tenderPIC as $pic){

                        $refNo = $pic->TPIC_USCode;

                        if($pic->TPICType == 'T'){
                            $notiType = 'SO';
                        }
                        // else if($pic->TPICType == 'K'){
                        //     $notiType = 'FO';
                        // }
                        $result = $notification->sendNotification($refNo,$notiType,$code,$data);
                    }

                }


                //SEND NOTIFICATION TO CONTRACTOR
                $contractorNo = $project->PT_CONo;

                $refNo = $contractorNo;
                $notiType = 'CO';
                $result = $notification->sendNotification($refNo,$notiType,$code2,$data);

            }

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat notifikasi tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function addClaimDetail(Request $request){

        $user = Auth::user();

        $messages = [
            'keterangan.required' 	=> "Keterangan diperlukan.",
            'wajib.required' 	=> "Sila pilih wajib atau tidak.",
        ];

        $validation = [
            'keterangan' 	=> 'required',
            'wajib' 	=> 'required',
        ];


        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $claimNo        = $request->projectClaimNo;
            $review		    = $request->keterangan;
            $wajib          = $request->wajib;

            $last_claimDet = ProjectClaimDet::where('PCD_PCNo', $claimNo)
                ->orderBy('PCDSeq', 'DESC')
                ->first();

            if($last_claimDet){
                $seq = $last_claimDet->PCDSeq +1;
                $PCDNo = $this->increment3DigitNo($last_claimDet->PCDNo,'PCD');
            }
            else{
                $seq = 1;
                $PCDNo = $claimNo.'-'.$formattedCounter = sprintf("%03d", $seq);
            }

            $projectClaimDet = new ProjectClaimDet();
            $projectClaimDet->PCDNo = $PCDNo;
            $projectClaimDet->PCD_PCNo = $claimNo;
            $projectClaimDet->PCDSeq = $seq;
            $projectClaimDet->PCDDesc = $review;
            $projectClaimDet->PCDComplete = $wajib;
            $projectClaimDet->PCDAddDoc = 1;
            $projectClaimDet->save();
            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.claim.review',[$claimNo]),
                'message' => 'Senarai semak berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Senarai semak tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }


    }

    public function deleteClaimDetail($id){

        $user = Auth::user();

        try {
            DB::beginTransaction();

            $claimDet = ProjectClaimDet::where('PCDNo', $id)
                ->first();
            $claimNo = $claimDet->PCD_PCNo;

            $claimDet->delete();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.claim.review',[$claimNo]),
                'message' => 'Senarai semak berjaya dipadam.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Senarai semak tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }


    }

    public function updateClaimStatus($id){

        $user = Auth::user();

        try {
            DB::beginTransaction();

            $projectClaim = ProjectClaim::where('PCNo', $id)
                ->first();
            $claimNo = $projectClaim->PCNo;

            $projectClaim->PC_PCAPCode = 'RD';
            $projectClaim->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('pelaksana.claim.review',[$claimNo]),
                'message' => 'Senarai semak berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Senarai semak tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }


    }
}
