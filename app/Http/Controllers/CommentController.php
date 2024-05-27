<?php

namespace App\Http\Controllers;

use App\Models\LetterAcceptance;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\TenderProposal;
use App\Models\TenderProposalDetail;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\FileAttach;
use App\Helper\Custom;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Auth;
use Image;
use Imagick;
use App\Models\AutoNumber;
use App\Models\CertApp;
use App\Models\CommentLog;
use App\Models\Contractor;
use App\Models\IntegrateSSMLog;
use App\Models\EmailLog;
use App\Models\ExtensionOfTime;
use App\Models\Notification;
use App\Models\SuratArahanKerja;
use App\Models\VariantOrder;
use App\Services\DropdownService;
use Illuminate\Support\Facades\Auth as IlluminateAuth;
use Mail;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Session\Session;
use Yajra\DataTables\Facades\DataTables;

class CommentController extends Controller{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function store(Request $request){
        //#COMMENT-CONTROLLER

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $route  = "";
            $refNo  = $request->komenRefNo;
            $type   = $request->komenType;
            $urlId  = $request->komenUrlId;
            $desc   = $request->review_description;

            $CLNo   = $this->autoNumber->generateCommentLog();

            if($type == 'VO'){

                $variantOrder = VariantOrder::where('VONo',$refNo)->first();
                $variantOrder->VOStatus = "REVIEW";
                $variantOrder->VO_VPCode = "PV";
                $variantOrder->VOMB = $user->USCode;
                $variantOrder->save();

                $route = route('perolehan.vo.view',[$refNo]);
            }
            else if($type == 'PM'){

                $milestone = ProjectMilestone::where('PMNo',$refNo)->first();
                $milestone->PM_PMSCode = "R";
                $milestone->save();

                $route = route('pelaksana.project.view',[$urlId]);
            }
            else if($type == 'EOT'){

                $eot = ExtensionOfTime::where('EOTNo',$refNo)->first();
                $eot->EOTStatus = "REVIEW";
                $eot->EOT_EPCode = "RQV";
                $eot->EOTMB = $user->USCode;
                $eot->save();

                $route = route('pelaksana.eot.view',[$refNo]);
            }
            else if($type == 'EOTP'){

                $eot = ExtensionOfTime::where('EOTNo',$refNo)->first();
                $eot->EOTStatus = "REVIEW";
                $eot->EOT_EPCode = "PV";
                $eot->EOTMB = $user->USCode;
                $eot->save();

                $route = route('perolehan.eot.view',[$refNo , 'flag' =>'3'] );
            }
            else if($type == 'LA'){

                $letterAcccept = LetterAcceptance::where('LANo',$refNo)->first();
//                $letterAcccept->LAStatus = 'SUBMIT';
//                $letterAcccept->save();

                $tenderProposal = TenderProposal::where('TPNo', $letterAcccept->LA_TPNo)->first();
                $tenderProposal->TP_TRPCode = 'LA';
                $tenderProposal->save();

                $project = Project::where('PT_TPNo', $letterAcccept->LA_TPNo)->first();
                $project->PT_PPCode = 'LAV';
                $project->save();

                $route = route('perolehan.letter.accept.view',[$refNo , 'flag' =>'5'] );
            }
            else if($type == 'SAK'){
                $sak = SuratArahanKerja::where('SAKNo' , $refNo)->first();
                $sak->SAKStatus = 'REVIEW';
                $sak->save();

                $route = route('pelaksana.sak.edit' , $refNo);
            }

            $commentLog = new CommentLog();
            $commentLog->CLNo = $CLNo;
            $commentLog->CLRefNo = $refNo;
            $commentLog->CLType = $type;
            $commentLog->CL_USCode = $user->USCode;
            $commentLog->CLDesc = $desc;
            $commentLog->save();

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => $route,
				'message' => 'Keterangan berjaya dihantar.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Keterangan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }


}
