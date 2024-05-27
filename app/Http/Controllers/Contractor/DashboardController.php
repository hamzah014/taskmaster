<?php

namespace App\Http\Controllers\Contractor;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\ProjectMiletstone;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\Tender;
use App\Models\TenderProposal;
use App\Models\ClaimMeetingDet;
use App\Models\ProjectMilestone;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Auth;
use Session;

class DashboardController extends Controller{

    public function index(Request $request){

        $projectNo = Session::get('project');
        $project = Project::where('PTNo', $projectNo)->first();

        $tenderProposal = TenderProposal::where('TPNo', $project->PT_TPNo)->first();
        $tender = $tenderProposal ? Tender::where('TDNo', $tenderProposal->TP_TDNo)->first() : null;
        // $tender = Tender::where('TDNo', $tenderProposal->TP_TDNo)->first();
        $projectClaim = ProjectClaim::where('PC_PTNo', $projectNo)->get();

        // $milestone = ProjectMilestone::where('PM_PTNo', $projectNo)->get();
        // $complete = ProjectMilestone::select('PMWorkPercent')->where('PM_PTNo', $projectNo)->where('PM_PMSCode', 'C')->get();
        // $remaining = ProjectMilestone::select('PMWorkPercent')->where('PM_PTNo', $projectNo)->where('PM_PMSCode', 'D')->get();
        // $progress = ProjectMilestone::select('PMWorkPercent')->where('PM_PTNo', $projectNo)->where('PM_PMSCode', 'N')->get();

        // // Calculate the total work percentage
        // $totalComplete = $complete->sum('PMWorkPercent');
        // $totalRemaining = $remaining->sum('PMWorkPercent');
        // $totalProgress = $progress->sum('PMWorkPercent');
        // $totalWorkPercent = $totalComplete + $totalRemaining + $totalProgress;

        // $totalSum = $totalComplete + $totalRemaining + $totalProgress;

        // if ($totalSum !== 0) {
        //     $scalingFactor = 100 / $totalSum;

        //     $totalComplete = $totalComplete * $scalingFactor;
        //     $totalRemaining = $totalRemaining * $scalingFactor;
        //     $totalProgress = $totalProgress * $scalingFactor;
        // } else {
        //     $scalingFactor = 0;
        // }

        //total
        $totalClaim = $projectClaim->sum('PCTotalAmt');

        //hantar
        $hantarClaims = ProjectClaim::where('PC_PTNo', $projectNo)->where('PC_PCPCode', 'AP')->get();
        $hantarClaim = $hantarClaims->sum('PCTotalAmt');
        
        //paid
        $paidClaims = ProjectClaim::where('PC_PTNo', $projectNo)->where('PC_PCPCode', 'PD')->get();
        $paidClaim = $paidClaims->sum('PCTotalAmt');

        //review
        $reviewClaims = ProjectClaim::where('PC_PTNo', $projectNo)->whereIn('PC_PCPCode', ['RV'])->get();
        $reviewClaim = $reviewClaims->sum('PCTotalAmt');

        $lulusClaims = ProjectClaim::where('PC_PTNo', $projectNo)->whereIn('PC_PCPCode', ['CRP' , 'CRT'])->get();
        $lulusClaim = $lulusClaims->sum('PCTotalAmt');

        // $pcno = ProjectClaim::where('PC_PTNo', $projectNo)->pluck('PCNo');
        // $lulusClaims = ClaimMeetingDet::whereIn('CMD_PCNo', $pcno)
        //     ->where('CMD_TMSCode', 'D')
        //     ->whereIn('CMD_CRSCode', ['P', 'T'])
        //     ->get();
        // $cmdPcNos = $lulusClaims->pluck('CMD_PCNo')->toArray();
        // $lulusClaimAmounts = ProjectClaim::whereIn('PCNo', $cmdPcNos)->pluck('PCTotalAmt')->toArray();
        // $lulusClaim = array_sum($lulusClaimAmounts);

        //dd($projectNo, $projectClaim,$totalClaim , $paidClaim , $reviewClaim , $lulusClaim);
        //percentage
        if ($totalClaim > 0) {
            $paidPercentage = ($paidClaim / $totalClaim) * 100;
            $reviewPercentage = ($reviewClaim / $totalClaim) * 100;
            $lulusPercentage = ($lulusClaim / $totalClaim) * 100;
        } else {
            $paidPercentage = 0;
            $reviewPercentage = 0;
            $lulusPercentage = 0;
        }

        $projectDetails = [];

        if ($tender) {
            $completePercentage = $project->projectMilestone->where('PM_PMSCode', 'C')->sum('PMWorkPercent') / 100;
            $remainingPercentage = $project->projectMilestone->where('PM_PMSCode', 'D')->sum('PMWorkPercent') / 100;
            $progressPercentage = $project->projectMilestone->where('PM_PMSCode', 'N')->sum('PMWorkPercent') / 100;

            $projectDetails[] = [
                'project' => $tender->TDTitle,
                'completePercentage' => $completePercentage,
                'remainingPercentage' => $remainingPercentage,
                'progressPercentage' => $progressPercentage,
            ];
        }

        return view('contractor.dashboard.index',
        compact('project', 'tenderProposal', 'tender', 'totalClaim', 'paidClaim',
            'reviewClaim', 'lulusClaim', 'paidPercentage', 'reviewPercentage', 'lulusPercentage',
           'projectDetails' , 'hantarClaim'));
    }


}
