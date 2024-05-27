<?php

namespace App\Http\Controllers\Pelaksana;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\ProjectClaim;
use App\Models\ClaimMeetingDet;
use App\Models\WebSetting;
use App\Models\Tender;
use App\Models\TenderProposal;
use App\Models\Project;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use App\Services\DropdownService;
use App\Models\AutoNumber;
use App\Models\ProjectBudgetYear;

class PelaksanaController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        $user = Auth::user();
        if($user->USLastLogin){
            $timestamp = strtotime($user->USLastLogin);

            // Convert the timestamp to a date object
            $user->USLastLogin  = date('d/m/Y H:i', $timestamp);
        }

        $milestonePengesahan = Project::whereIn('PT_PSCode', ['N', 'S'])->count();
        $tuntutanSemakan = ProjectClaim::where('PC_PCPCode', 'SM')->count();
        $mesyuaratTuntutan = ProjectClaim::where('PC_PCPCode', 'BM')->count();
        $kickOff = Project::where('PTStatus' , 'KOM')->count();

        $statusmenunggu = Tender::where('TD_TPCode','OT')->get();
        $menungguPenilaian = 0;

        foreach ($statusmenunggu as $tender) {
            $tenderProposals = $tender->tenderProposal;

            //dd($tenderProposals);
            $menunggupenilaians = $tenderProposals->where('TPEvaluationStep', 1)->whereNull('TPTechnicalPass');

            $menungguPenilaian += $menunggupenilaians->count();
        }

        $paidClaims = ProjectClaim::where('PC_PCPCode', 'PD')->get();
        $belumbayar = ClaimMeetingDet::where('CMD_TMSCode', 'D')
            ->whereIn('CMD_CRSCode', ['P', 'T'])
            ->get();

        $cmdPcNos = $belumbayar->pluck('CMD_PCNo')->toArray();
        $lulusClaimAmounts = ProjectClaim::whereIn('PCNo', $cmdPcNos)->pluck('PCTotalAmt')->toArray();
        $lulusClaim = array_sum($lulusClaimAmounts);

        $paidTotalAmount = $paidClaims->sum('PCTotalAmt');

        $projects = Project::whereNotNull('PTPwd')->orderBy('PTCD', 'desc')->get();

        $projectDetails = [];

        $currentYear = Carbon::now()->year;

        $budgetYearly = ProjectBudgetYear::where('PBYYear', $currentYear)
                                            ->sum('PBYBudgetAmt');

        foreach ($projects as $project) {
            $tenderProposal = TenderProposal::where('TPNo', $project->PT_TPNo)->first();
            $tender = $tenderProposal ? Tender::where('TDNo', $tenderProposal->TP_TDNo)->first() : null;

            if ($tender) {
                $completePercentage = $project->projectMilestone->where('PMRefType' ,'PJ')->where('PM_PMSCode', 'C')->sum('PMWorkPercent') / 100;
                $remainingPercentage = $project->projectMilestone->where('PMRefType' ,'PJ')->where('PM_PMSCode', 'D')->sum('PMWorkPercent') / 100;
                $progressPercentage = $project->projectMilestone->where('PMRefType' ,'PJ')->where('PM_PMSCode', 'N')->sum('PMWorkPercent') / 100;
                $priority = $project->PTPriority;
                $progress = $project->PTProgress;

                if($priority == 1){ //Late
                    $statusColor = 'yellow';
                }else if($priority == 2){ //Critical
                    $statusColor = 'red';
                }else{
                    $statusColor = 'green';
                }

                $progressPercent = (1-$progress) * 100;

                //  dd($project->PT_TPNo , $tenderProposal , $tender , $tenderProposal->TP_TDNo, $tender->TDTitle);

                $projectDetails[] = [
                    'projectNo' => $project->PTNo,
                    'projectTitle' => $tender->TDTitle,
                    'statusColor' => $statusColor,
                    'progressPercent' => $progressPercent,
                    'completePercentage' => $completePercentage,
                    'remainingPercentage' => $remainingPercentage,
                    'progressPercentage' => $progressPercentage,
                ];
            }
        }


        // $pcdate = ProjectClaim::select('PCResultedDate')->get();

        // $resultDate = \Carbon\Carbon::parse($pcdate);
        // $now = \Carbon\Carbon::now();

        // $x = $resultDate->diffInDays($now);

        // dd($pcdate , $now , $x);

        // $websetting = Websetting::first();
        // $firstLevel = (float) $websetting->PaymentClaimReminder1;
        // $secondLevel = (float) $websetting->PaymentClaimReminder2;

        // if($x >= $firstLevel) {
        //     if($x >= $secondLevel) {
        //         $priority = 2; //critical
        //     }
        //     else{
        //         $priority = 1; //late
        //     }
        // }
        // else {
        //     $priority = 0; //normal
        // }

        $pcdates = ProjectClaim::select('PCResultedDate')->get();
        $now = Carbon::now();

        $priorityCounts = [
            'Normal' => 0,
            'Late' => 0,
            'Critical' => 0,
        ];

        foreach ($pcdates as $pcdate) {
            if ($pcdate->PCResultedDate !== null) {
                $resultDate = Carbon::parse($pcdate->PCResultedDate);
                $differenceInDays = $resultDate->diffInDays($now);

                $priority = 0;

                $websetting = Websetting::first();
                $firstLevel = (float) $websetting->PaymentClaimReminder1;
                $secondLevel = (float) $websetting->PaymentClaimReminder2;

                if ($differenceInDays >= $firstLevel) {
                    if ($differenceInDays >= $secondLevel) {
                        $priority = 2;
                    } else {
                        $priority = 1;
                    }
                }

                if ($priority === 0) {
                    $priorityCounts['Normal']++;
                } elseif ($priority === 1) {
                    $priorityCounts['Late']++;
                } elseif ($priority === 2) {
                    $priorityCounts['Critical']++;
                }
            }
        }


        return view('pelaksana.index', compact('user','paidClaims' , 'paidTotalAmount', 'lulusClaim' , 'projectDetails',
        'milestonePengesahan' , 'tuntutanSemakan' , 'mesyuaratTuntutan' , 'menungguPenilaian' , 'priorityCounts' , 'kickOff' , 'budgetYearly'));
    }

    public function index_auto(){
        $user = Auth::user();

        if($user->USType == 'CO'){
            Auth::attempt(['USCode' => 'SO1000', 'password' => '123456', 'USActive' => 1]);
        }

//        if (Auth::attempt(['USCode' => 'SO1000', 'password' => '123456', 'USActive' => 1])) {

            $user = Auth::user();
            if($user->USLastLogin){
                $timestamp = strtotime($user->USLastLogin);

                // Convert the timestamp to a date object
                $user->USLastLogin  = date('d/m/Y H:i', $timestamp);
            }

            // The user is active, not suspended, and exists.
            //return view('home');

            $milestonePengesahan = Project::whereIn('PT_PSCode', ['N', 'S'])->count();
            $tuntutanSemakan = ProjectClaim::where('PC_PCPCode', 'SM')->count();
            $mesyuaratTuntutan = ProjectClaim::where('PC_PCPCode', 'BM')->count();
            $kickOff = Project::where('PTStatus' , 'KOM')->count();

            $statusmenunggu = Tender::where('TD_TPCode','OT')->get();
            $menungguPenilaian = 0;

            foreach ($statusmenunggu as $tender) {
                $tenderProposals = $tender->tenderProposal;

                //dd($tenderProposals);
                $menunggupenilaians = $tenderProposals->where('TPEvaluationStep', 1)->whereNull('TPTechnicalPass');

                $menungguPenilaian += $menunggupenilaians->count();
            }

            $paidClaims = ProjectClaim::where('PC_PCPCode', 'PD')->get();
            $belumbayar = ClaimMeetingDet::where('CMD_TMSCode', 'D')
                ->whereIn('CMD_CRSCode', ['P', 'T'])
                ->get();

            $cmdPcNos = $belumbayar->pluck('CMD_PCNo')->toArray();
            $lulusClaimAmounts = ProjectClaim::whereIn('PCNo', $cmdPcNos)->pluck('PCTotalAmt')->toArray();
            $lulusClaim = array_sum($lulusClaimAmounts);

            $paidTotalAmount = $paidClaims->sum('PCTotalAmt');

            $projects = Project::whereNotNull('PTPwd')->orderBy('PTCD', 'desc')->get();

            $projectDetails = [];

            $currentYear = Carbon::now()->year;

            $budgetYearly = ProjectBudgetYear::where('PBYYear', $currentYear)
                                            ->sum('PBYBudgetAmt');

            foreach ($projects as $project) {
                $tenderProposal = TenderProposal::where('TPNo', $project->PT_TPNo)->first();
                $tender = $tenderProposal ? Tender::where('TDNo', $tenderProposal->TP_TDNo)->first() : null;

                if ($tender) {
                    $completePercentage = $project->projectMilestone->where('PMRefType' ,'PJ')->where('PM_PMSCode', 'C')->sum('PMWorkPercent') / 100;
                    $remainingPercentage = $project->projectMilestone->where('PMRefType' ,'PJ')->where('PM_PMSCode', 'D')->sum('PMWorkPercent') / 100;
                    $progressPercentage = $project->projectMilestone->where('PMRefType' ,'PJ')->where('PM_PMSCode', 'N')->sum('PMWorkPercent') / 100;
                    $priority = $project->PTPriority;
                    $progress = $project->PTProgress;

                    if($priority == 1){ //Late
                        $statusColor = 'yellow';
                    }else if($priority == 2){ //Critical
                        $statusColor = 'red';
                    }else{
                        $statusColor = 'green';
                    }

                    $progressPercent = (1-$progress) * 100;

                    //  dd($project->PT_TPNo , $tenderProposal , $tender , $tenderProposal->TP_TDNo, $tender->TDTitle);

                    $projectDetails[] = [
                        'projectNo' => $project->PTNo,
                        'projectTitle' => $tender->TDTitle,
                        'statusColor' => $statusColor,
                        'progressPercent' => $progressPercent,
                        'completePercentage' => $completePercentage,
                        'remainingPercentage' => $remainingPercentage,
                        'progressPercentage' => $progressPercentage,
                    ];
                }
            }


            // $pcdate = ProjectClaim::select('PCResultedDate')->get();

            // $resultDate = \Carbon\Carbon::parse($pcdate);
            // $now = \Carbon\Carbon::now();

            // $x = $resultDate->diffInDays($now);

            // dd($pcdate , $now , $x);

            // $websetting = Websetting::first();
            // $firstLevel = (float) $websetting->PaymentClaimReminder1;
            // $secondLevel = (float) $websetting->PaymentClaimReminder2;

            // if($x >= $firstLevel) {
            //     if($x >= $secondLevel) {
            //         $priority = 2; //critical
            //     }
            //     else{
            //         $priority = 1; //late
            //     }
            // }
            // else {
            //     $priority = 0; //normal
            // }

            $pcdates = ProjectClaim::select('PCResultedDate')->get();
            $now = Carbon::now();

            $priorityCounts = [
                'Normal' => 0,
                'Late' => 0,
                'Critical' => 0,
            ];

            foreach ($pcdates as $pcdate) {
                if ($pcdate->PCResultedDate !== null) {
                    $resultDate = Carbon::parse($pcdate->PCResultedDate);
                    $differenceInDays = $resultDate->diffInDays($now);

                    $priority = 0;

                    $websetting = Websetting::first();
                    $firstLevel = (float) $websetting->PaymentClaimReminder1;
                    $secondLevel = (float) $websetting->PaymentClaimReminder2;

                    if ($differenceInDays >= $firstLevel) {
                        if ($differenceInDays >= $secondLevel) {
                            $priority = 2;
                        } else {
                            $priority = 1;
                        }
                    }

                    if ($priority === 0) {
                        $priorityCounts['Normal']++;
                    } elseif ($priority === 1) {
                        $priorityCounts['Late']++;
                    } elseif ($priority === 2) {
                        $priorityCounts['Critical']++;
                    }
                }
            }

            // dd($priorityCounts);

            Session::put('page', 'pelaksana');
            return view('pelaksana.index', compact('user','paidClaims' , 'paidTotalAmount', 'lulusClaim' , 'projectDetails',
            'milestonePengesahan' , 'tuntutanSemakan' , 'mesyuaratTuntutan' , 'menungguPenilaian' , 'priorityCounts' , 'kickOff' , 'budgetYearly'));

//        }
    }

    public function notification($id){
        return view('pelaksana.notification');
    }

    public function allNotification($id){
        return view('pelaksana.all_notification');
    }

    public function iklanModal($id){

        $tender = Tender::where('TDNo', $id)->first();

        $tender_sebutharga = $this->dropdownService->tender_sebutharga();
        $yn = $this->dropdownService->yn();

        $TD_TCCode = $tender_sebutharga[$tender->TD_TCCode];
        $TDSpecialTerms = $yn[$tender->TDSpecialTerms];

        $TDTerms = str_replace('/"""/', '', $tender->TDTerms);

        $TDPublishDate = Carbon::parse($tender->TDPublishDate)->format('d/m/Y');
        $TDPublishPeriod = $tender->TDPublishPeriod;

        $tenderDocuments = [];
        $tenderDetails = $tender->tenderDetail;

        // if($tender->tenderDetail){
        //     foreach ($tender->tenderDetail as $tenderFormHeaderPublic){
        //         $tenderFormDetails = TenderFormDetail::where('TDFD_TDNo', $tenderFormHeaderPublic->TDFH_TDNo)
        //             ->where('TDFD_TDFHCode', $tenderFormHeaderPublic->TDFHCode)
        //             ->where('TDFDType', 'FILE')
        //             ->where('TDFDDownload_FileID', 1)
        //             ->get();

        //         foreach($tenderFormDetails as $tenderFormDetail){
        //             $tenderDocuments = [];

        //             $tenderDocuments = [
        //                 'TDFDDesc' => $tenderFormDetail->TDFDDesc,
        //                 'TDFDCode' => $tenderFormDetail->TDFDCode,
        //             ];

        //             array_push($data, $tenderDocuments);
        //         }
        //     }
        // }

        return view('pelaksana.general.iklanModal',
            compact('tender', 'TD_TCCode', 'TDPublishDate', 'TDPublishPeriod', 'TDSpecialTerms', 'TDTerms', 'tenderDocuments', 'tenderDetails')
        );


    }
}
