<?php

namespace App\Http\Controllers\Contractor\Claim;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralNotificationController;
use App\Models\AutoNumber;
use App\Models\CommentLog;
use App\Models\FileAttach;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectBudgetDet;
use App\Models\ProjectClaim;
use App\Models\ProjectClaimDet;
use App\Models\ProjectInvoice;
use App\Models\ProjectMilestone;
use App\Models\TemplateClaimFile;
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
use App\Models\LetterAcceptance;
use App\Models\Notification;
use App\Models\ProjectBudgetYear;
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


    public function index($id){
        $projectNo = Session::get('project');

        $milestone = ProjectMilestone::where('PM_PTNo', $projectNo)
            ->whereDoesntHave('projectClaim', function ($query) {
                $query->where('PC_PCPCode', '!=','DF');
            })
            ->where('PMClaimInd', 1)
            ->get(['PMDesc', 'PMNo'])
            ->pluck('PMDesc', 'PMNo');

//        dd($milestone);

        return view('contractor.claim.index',
            compact('milestone')
        );
    }

    public function create($id){
        try {
            DB::beginTransaction();
            $projectNo = Session::get('project');



            $claim = ProjectClaim::where('PCNo', $id)->first();

            // $PCNo = Session::get('PCNo');

            // dd($id , $claim , $PCNo);

            if(!$claim){

                $autoNumber = new AutoNumber();
                $PCNo = $autoNumber->generateClaimNo();

                $claim = new ProjectClaim();
                $claim->PCNo = $PCNo;
                $claim->PC_PTNo = $projectNo;
                $claim->PC_PMNo = $id;
                $claim->PC_PCPCode = 'DF';
                $claim->PCTotalAmt = 0;
                $claim->save();

                $project = ProjectMilestone::where('PMNo', $id)->first();

//                $templateClaimFile = TemplateClaimFile::where('TCF_DPTCode', $project->project->tenderProposal->tender->TD_DPTCode)
//                    ->where('TCF_PTCode', $project->project->tenderProposal->tender->TD_PTCode)
//                    ->first();

                $seq = 1;

                if(isset($project->projectMilestoneDetC) && count($project->projectMilestoneDetC)>0) {
                    foreach ($project->projectMilestoneDetC as $projectMilestoneDet){
                        $PCDNo = $PCNo.'-'.$formattedCounter = sprintf("%03d", $seq);
                        $projectClaimDet = new ProjectClaimDet();
                        $projectClaimDet->PCDNo = $PCDNo;
                        $projectClaimDet->PCD_PCNo = $PCNo;
                        $projectClaimDet->PCDSeq = $projectMilestoneDet->PMDSeq;
                        $projectClaimDet->PCDDesc = $projectMilestoneDet->PMDDesc;
                        $projectClaimDet->PCDComplete = $projectMilestoneDet->PMDComplete;
                        $projectClaimDet->PCD_PMDNo = $projectMilestoneDet->PMDNo;
                        $projectClaimDet->save();

                        $seq++;
                    }
                }
            }

            $commentLog = $claim->commentLog;
            $commentLog->each(function ($comment) {
                $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
            });

            $webSetting = WebSetting::first();

            DB::commit();

            return redirect()->route('contractor.claim.edit', [$claim->PCNo]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function edit($id){
        try {
            DB::beginTransaction();
            $projectNo = Session::get('project');

            $claim = ProjectClaim::where('PCNo', $id)->first();

            $project = Project::where('PTNo' , $projectNo)->first();

            $letterAccept = LetterAcceptance::where('LANo', $project->PT_LANo)->first();

            // $PCNo = Session::get('PCNo');

            // dd($id , $claim , $PCNo);



            $commentLog = $claim->commentLog;
            $commentLog->each(function ($comment) {
                $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
            });

            $webSetting = WebSetting::first();

            DB::commit();

            return view('contractor.claim.create' ,
                compact('id', 'claim' , 'webSetting' , 'letterAccept')
            );

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function createWTerm($id){
        try {
            DB::beginTransaction();
            $webSetting = WebSetting::first();
            $projectNo = Session::get('project');

            $claim = ProjectClaim::where('PCNo', $id)->first();

            //dd($claim);

            if(!$claim){

                $autoNumber = new AutoNumber();
                $PCNo = $autoNumber->generateClaimNo();

                $claim = new ProjectClaim();
                $claim->PCNo = $PCNo;
                $claim->PC_PTNo = $projectNo;
                $claim->PC_PMNo = $id;
                $claim->PC_PCPCode = 'DF';
                $claim->PCTotalAmt = 0;
                $claim->save();

                $project = ProjectMilestone::where('PMNo', $id)->first();

//                $templateClaimFile = TemplateClaimFile::where('TCF_DPTCode', $project->project->tenderProposal->tender->TD_DPTCode)
//                    ->where('TCF_PTCode', $project->project->tenderProposal->tender->TD_PTCode)
//                    ->first();

                $seq = 1;

                if(isset($project->projectMilestoneDetC) && count($project->projectMilestoneDetC)>0) {
                    foreach ($project->projectMilestoneDetC as $projectMilestoneDet){
                        $PCDNo = $PCNo.'-'.$formattedCounter = sprintf("%03d", $seq);
                        $projectClaimDet = new ProjectClaimDet();
                        $projectClaimDet->PCDNo = $PCDNo;
                        $projectClaimDet->PCD_PCNo = $PCNo;
                        $projectClaimDet->PCDSeq = $projectMilestoneDet->PMDSeq;
                        $projectClaimDet->PCDDesc = $projectMilestoneDet->PMDDesc;
                        $projectClaimDet->PCDComplete = $projectMilestoneDet->PMDComplete;
                        $projectClaimDet->PCD_PMDNo = $projectMilestoneDet->PMDNo;
                        $projectClaimDet->save();

                        $seq++;
                    }
                }
            }

            $commentLog = $claim->commentLog;
            $commentLog->each(function ($comment) {
                $comment['submitDate'] = Carbon::parse($comment->CLCD)->format('d/m/Y');
            });

            DB::commit();

            return view('contractor.claim.createWTerm',
                compact('id', 'claim' , 'webSetting')
            );

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function addAttachment($id){
        $invoice_no = [
            0 => 'C00001',
            1 => 'C00002',
        ];

        return view('contractor.claim.add_attachment', compact('invoice_no'));
    }

    public function claimDatatable(Request $request){
        $projectNo = Session::get('project');

        $query = ProjectClaim::where('PC_PTNo', $projectNo)->orderBy('PCID','desc')->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PCTotalAmt', function($row) {
                $amount = number_format($row->PCTotalAmt ?? 0,2, '.', ',');

                return $amount;
            })
            ->editColumn('PC_PCPCode', function($row) {
                $claimProcess = $this->dropdownService->claimProcess();

                return $claimProcess[$row->PC_PCPCode];
            })
            ->addColumn('action', function($row) {
                $result = '';


                $route = route('contractor.claim.create', [$row->PCNo]);

                if($row->PC_PCPCode == 'DF' || $row->PC_PCPCode == 'RV'){
                    $result = '<a class="btn btn-light-primary" href="'.$route.'"><i class="material-icons">edit</i></a>';

                }

                return $result;
            })
            ->rawColumns(['indexNo', 'PCTotalAmt', 'PC_PCPCode', 'action'])
            ->make(true);
    }

    public function addInvoice($id){
        $projectNo = Session::get('project');

        $claim = ProjectClaim::where('PCNo', $id)->first();

        return view('contractor.claim.createInvoice',
            compact('id', 'claim')
        );
    }

    public function storeInvoice(Request $request){

		$messages = [
            'invoice_no.required' 	=> "No. Invois diperlukan.",
            'amount.required' 	    => "Amaun diperlukan.",
		];

		$validation = [
			'invoice_no' 	=> 'required',
			'amount' 	    => 'required',
		];


        $request->validate($validation, $messages);


        $setting = WebSetting::first();
        try {


            DB::beginTransaction();
            //$projectNo = Session::get('project');
            $projectNo = Session::get('project');
            $updateStatus = $request->updateStatus;

            $claimInvoice = ProjectInvoice::where('PCI_PCNo', $request->idClaim)->first();

            if(!$claimInvoice){
                $autoNumber = new AutoNumber();
                $PINo = $autoNumber->generateInvoiceNo();

                $claimInvoice = new ProjectInvoice();
                $claimInvoice->PCINo = $PINo;
                $claimInvoice->PCI_PCNo = $request->idClaim;
            }

            $updateProjectClaim = $claimInvoice->claim;

            $deductAmt = $request->amount * $setting->ClaimDeductionPercent;

            if($deductAmt >= $setting->ClaimMaxCap){
                $deductAmt = $setting->ClaimMaxCap;
            }

            $claimAmt = $request->amount - $deductAmt;

            $updateProjectClaim->PCTotalInvAmt  = $request->amount;
            $updateProjectClaim->PCTotalDeductionAmt  = $deductAmt;
            $updateProjectClaim->PCTotalAmt  = $claimAmt;
            $updateProjectClaim->save();

            $claimInvoice->PCIInvNo = $request->invoice_no;
            $claimInvoice->PCIInvDate = $request->invoice_date;
            $claimInvoice->PCIInvAmt = $request->amount;
            $claimInvoice->save();

            //dd($updateProjectClaim , $claimInvoice);

            if($updateStatus == 0){
                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('contractor.claim.create', [$request->idClaim]),
                    'message' => 'Maklumat berjaya dikemaskini.'
                ]);

            }else if($updateStatus == 1){

                $result = $this->storeClaim($request->idClaim);
                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('contractor.claim.createWTerm', [$request->idClaim]),
                    'message' => 'Maklumat claim berjaya dikemaskini.'
                ]);

            }


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function invoiceDatatable(Request $request){
        $projectNo = Session::get('project');

        $query = ProjectInvoice::where('PCI_PCNo', $request->idClaim)->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PCIInvAmt', function($row) {
                $amount = number_format($row->PCIInvAmt ?? 0,2, '.', '');

                return $amount;
            })
            ->editColumn('PCIInvDate', function($row) {
                $PCIInvDate = \Carbon\Carbon::parse($row->PCIInvDate)->format('d/m/Y');

                return $PCIInvDate;
            })
            ->addColumn('action', function($row) use($projectNo){
                $result = '';


                $route = route('contractor.claim.delete.invoice', [$row->PCIID]);
                $route2 = "openUploadModal('$row->PCINo','".Auth::user()->USCode."','PCI')";

                if(in_array($row->claim->PC_PCPCode, ["DF", "RV"])) {
                    $result = '<a class="btn btn-light-primary" href="' . $route . '"><i class="material-icons">delete</i></a>';
                }

                //  $result .= '<a class="new modal-trigger btn btn-light-primary" href="#" onclick="'.$route2.'"><i class="material-icons left">file_upload</i> Lampiran</a>';

                return $result;
            })
            ->rawColumns(['indexNo', 'PCIInvAmt', 'action'])
            ->make(true);
    }

    public function deleteInvoice($id){
        $projectNo = Session::get('project');

        $claimInvoice = ProjectInvoice::where('PCIID', $id)->first();
        $claim = $claimInvoice->PCI_PCNo;
        $claimInvoice->delete();

        return redirect()->back()->with('success', 'Data telah dipadam');
    }

    public function attachmentDatatable(Request $request){
        $projectNo = Session::get('project');

        $invoices = ProjectInvoice::where('PCI_PCNo', $request->idClaim)->get();
        $invoiceNo = [];

        foreach ($invoices as $invoice){
            array_push($invoiceNo, $invoice->PCINo);
        }

        $query = FileAttach::whereIn('FARefNo', $invoiceNo)->get();



        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('attachment', function($row) use($projectNo){
                $result = '';

                $route = route('file.view', [$row->FAGuidID]);

                $result .= '<a class="new modal-trigger btn btn-light-primary" href="'.$route.'" target="_blank"><i class="material-icons left">visibility</i> Papar</a>';

                return $result;
            })

            ->addColumn('action', function($row) use($projectNo){
                $result = '';

                $route2 = route('file.delete', [$row->FAGuidID]);

                $result .= '<a class="btn btn-light-primary" href="'.$route2.'"><i class="material-icons">delete</i></a>';

                return $result;
            })
            ->rawColumns(['indexNo', 'attachment', 'action'])
            ->make(true);
    }

    public function storeClaim($id){

        try {
            DB::beginTransaction();

            $projectNo = Session::get('project');

            $claimInvoices = ProjectInvoice::where('PCI_PCNo', $id)->get();
            $claimInvoiceAmount = 0;

            foreach ($claimInvoices as $claimInvoice){
                $claimInvoiceAmount += $claimInvoice->PCIInvAmt;
            }

            $claim = ProjectClaim::where('PCNo', $id)->first();
            $claim->PC_PCPCode = 'SM'; //Submit
            $claim->PCSubmittedDate = Carbon::now();
            $claim->save();

            $milestone = ProjectMilestone::where('PMNo', $claim->PC_PMNo)->first();
            // $milestone->PM_PMSCode = 'I'; // In progress
            $milestone->PMClaimInd = 2;
            $milestone->save();

            $result = $this->checkLimitBudget($projectNo);
            $noti = null;

            if($result == 0){
                $noti = $this->sendNotification($claim,'OB');
            }

            $noti = $this->sendNotification($claim,'S');

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.index'),
                'message' => 'Maklumat tuntutan berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::error('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tuntutan tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }


    public function checkLimitBudget($projectNo){

        $currentYear = Carbon::now()->year;

        $project = Project::where('PTNo',$projectNo)->first();

        // $budgetYearly = ProjectBudgetDet::where('PBD_PTNo', $projectNo)->where('PBDYear' , $currentYear)->first();

        $currentYear = Carbon::now()->year;

        $projectBudget = ProjectBudgetYear::where('PBY_PTNo', $projectNo)
                                            ->where('PBYContractNo',$project->PTContractNo)
                                            ->where('PBYYear', $currentYear)
                                            ->sum('PBYBudgetAmt');

        // $totalClaim = 0;

        // $projectClaims = ProjectClaim::where('PC_PTNo', $projectNo)->get();

        // foreach ($projectClaims as $claim) {
        //     $claimYear = Carbon::parse($claim->PCCD)->year;

        //     if ($claimYear == $currentYear) {
        //         $totalClaim += $claim->PCTotalAmt;
        //     }
        // }

        // $checkLimit = $totalClaim;


        $totalClaim = ProjectClaim::where('PC_PTNo' , $projectNo)
                ->sum('PCTotalAmt');

        if($totalClaim > $projectBudget){
            return 0;

        }else{
            return 1;
        }

    }

    public function checklistDatatable(Request $request){
        $projectNo = Session::get('project');

        $query = ProjectClaimDet::where('PCD_PCNo', $request->idClaim)
            ->orderBy('PCDSeq')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PCDComplete', function($row) {

                $result = '';

                if($row->PCDComplete == null){

                }
                else{
                    if($row->PCDComplete == 1){
                        $result = '<i class="ki-solid ki-check-square text-success fs-2"></i>';
                    }
                    else{
                        $result = '<i class="ki-solid ki-cross-square text-danger fs-2"></i>';
                    }
                }

                return $result;
            })
            ->addColumn('action', function($row) {
                $result = '';

                if(in_array($row->claim->PC_PCPCode, ["DF", "RV"])){
                    $route = "openUploadModal('$row->PCDNo','" . Auth::user()->USCode . "','PCD','PCD','$row->PCDNo')";

                    $result .= '<a class="new modal-trigger btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#uploadModal" onclick="' . $route . '"><i class="ki-solid ki-folder-up fs-2"></i>Lampiran</a>';
                }


                if($row->fileAttachPCB){
                    $route2 = route('file.view', [$row->fileAttachPCB->FAGuidID]);
                    $result .= ' <a target="_blank" class="new modal-trigger btn btn-sm btn-light-primary" href="' . $route2 . '"><i class="ki-solid ki-eye fs-2"></i>Papar</a>';
                }

                return $result;
            })
            ->rawColumns(['indexNo', 'PCDComplete', 'action'])
            ->make(true);
    }

    function sendNotification($claim,$sendCode){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $claimNo = $claim->PCNo;
            $projMileNo = $claim->PC_PMNo;

            $projectMilestone = ProjectMilestone::where('PMNo',$projMileNo)->first();
            $projectNo = $projectMilestone->PM_PTNo;
            $tender = $projectMilestone->project->tenderProposal->tender;

            //SEND NOTIFICATION TO PELAKSANA
            $tenderPICT = $tender->tenderPIC_T;
            $pelaksanaType = "SO";
            $result = null;


            $notiType = $pelaksanaType;

            if($sendCode == 'S'){
                $code = 'PC-SM'; //#NOTIF-006

            }else if($sendCode == 'OB'){
                $code = 'PC-OB'; //#NOTIF-006a

            }

            $data = array(
                'PTNo' => $projectNo,
                'PCNo' => $claimNo,
            );

            if(!empty($tenderPICT)){

                foreach($tenderPICT as $pict){

                    $refNo = $pict->TPIC_USCode;

                    $notification = new GeneralNotificationController();
                    $result = $notification->sendNotification($refNo,$notiType,$code,$data);
                }

            }

            DB::commit();

            return $result;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::error('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat notifikasi tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }

    public function uploadSupportDoc(Request $request){

		$messages = [
            'documentName.required' 	=> "Nama dokumen diperlukan.",
            'suppDoc.required' 	        => "Sila pilih fail untuk muat naik.",
		];

		$validation = [
			'documentName' 	=> 'required',
			'suppDoc' 	    => 'required',
		];


        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $user = Auth::user();

			$dok_FAUSCode		    = $user->USCode;
			$dok_FARefNo		    = $request->projectClaimNo;
			$dok_FAFileType		    = "PC-SD";

            //FILE UPLOAD
            if ($request->hasFile('suppDoc')) {

                $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

                $file = $request->file('suppDoc');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = new FileAttach();
                $fileAttach->FACB 		        = Auth::user()->USCode;
                $fileAttach->FAFileType 	    = $fileCode;
                $fileAttach->FARefNo     	    = $dok_FARefNo;
                $fileAttach->FA_USCode     	    = $dok_FAUSCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = Auth::user()->USCode;
                $fileAttach->save();

            }


            DB::commit();

			return response()->json([
				'success' => '1',
				'redirect' => route('contractor.claim.createWTerm',[$dok_FARefNo]),
				'message' => 'Dokumen sokongan berjaya dimuat naik.'
			]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Dokumen sokongan tidak berjaya dimuat naik!'.$e->getMessage()
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

            $projectClaim->PC_PCAPCode = 'SM';
            $projectClaim->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('contractor.claim.createWTerm',[$claimNo]),
                'message' => 'Senarai semak berjaya dihantar.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Senarai semak tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }


    }

}
