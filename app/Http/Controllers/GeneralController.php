<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\TenderProposalDetail;
use App\Models\WebSetting;
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
use App\Models\Contractor;
use App\Models\IntegrateSSMLog;
use App\Models\EmailLog;
use App\Models\ProjectClaim;
use App\Models\ProjectClaimDet;
use App\Models\ProjectInvoice;
use App\Services\DropdownService;
use Mail;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use League\CommonMark\Reference\Reference;

class GeneralController extends Controller{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function welcome(){

        return view('welcome'
        );

    }

    public function tuntutan(Request $request){

        $proj = $request->query('proj');
        $inv = $request->query('inv');

        // $projectInvoice = ProjectInvoice::where('PCIInvNo',$inv)->first();
        // dd($projectInvoice);

        // $projectClaim = $projectInvoice->claim()->first();
        // $projectMilestone = $projectClaim->projectMilestone()->first();
        // $project = $projectMilestone->project()->first();

        $project = Project::where('PTNo',$proj)->first();

        foreach($project->projectMilestone as $projectMilestone){

            foreach($projectMilestone->projectClaimM as $projectClaim){

                foreach($projectClaim->projectInvoice as $projectInvoice){

                    if($projectInvoice->PCIInvNo == $inv){

                        $claim = $projectClaim;

                    }

                }

            }

        }



        // $claim = ProjectClaim::where('PCNo', $projectClaim->PCNo)->first();

        // dd($claim);

        return view('general.tuntutan',
            compact('claim')
        );
    }


    public function invoiceDatatable(Request $request){

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
            ->rawColumns(['indexNo', 'PCIInvAmt', 'action'])
            ->make(true);
    }


    public function checklistDatatable(Request $request){

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
                        $result = '<i class="material-icons">check_box</i>';
                    }
                    else{
                        $result = '<i class="material-icons">check_box_outline_blank</i>';
                    }
                }

                return $result;
            })
            ->addColumn('action', function($row) {
                $result = '';

                if($row->fileAttachPCB){
                    $route2 = route('file.view', [$row->fileAttachPCB->FAGuidID]);
                    $result .= ' <a target="_blank" class="new modal-trigger btn btn-light-primary" href="' . $route2 . '"><i class="material-icons left">visibility</i>Papar</a>';
                }

                return $result;
            })
            ->rawColumns(['indexNo', 'PCDComplete', 'action'])
            ->make(true);
    }

    public function semakan(Request $request){

        $cono = $request->query('refno');

        $contractor = Contractor::where('CORegisterRefNo' , $cono)->first();

        $webSetting = WebSetting::first();

        return view('general.reference',
             compact('webSetting' , 'contractor' , 'cono')
         );
    }

    public function testemail(){


        $emailData = array(
            'id' => 1,
            'name'  => 'hehehhe',
            'email' => 'mejohgaming97@gmail.com',
            'domain' => env('APP_URL'),
            'token' => '123',
        );

        try {
            Mail::send(['html' => 'email.resetPassword'], $emailData, function($message) use ($emailData) {
                //$message->from('parking@example.com', 'noreply');
                $message->to($emailData['email'] ,$emailData['name'])->subject('Emofa Reset Password');
            });

            dd('okay siyep');
        } catch (\Exception $e) {
            dd($e);
        }

    }

}
