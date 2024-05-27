<?php

namespace App\Http\Controllers\Perolehan\Project;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Services\DropdownService;
use App\Models\Department;
use App\Models\Project;


use App\Http\Requests;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class ProjectController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){
        $ssm_no = [
            0 => 'Pembangunan',
            1 => 'Penyelenggaraan',
            2 => 'One Off',
        ];

        $tender_no = [
            0 => 'ABC SDN BHD',
            1 => 'KENCANA SDN BHD',
            2 => 'VISTA SDN BHD',
        ];

        return view('perolehan.project.index', compact('ssm_no', 'tender_no'));
    }

    public function view($id){

        $project = Project::where('PTNo',$id)->with('contractor','tenderProposal')->first();

        // dd($project->tenderProposal->letterIntent,$project->tenderProposal,$project->tenderProposal->letterIntent->letterAcceptance);

        $tender = $project->tenderProposal->tender;
        $tenderProposal = $project->tenderProposal;
        $contractor = $project->contractor;

        //JENIS PROJECT
        $jenis_projek = $this->dropdownService->jenis_projek();
        $project_type = "";

        if(!empty($project->tenderProposal->tender->TD_PTCode))
            $project_type = $jenis_projek[$project->tenderProposal->tender->TD_PTCode];

        // TARIKH SST
        $SSTDate = "";
        if($project->tenderProposal->letterIntent->letterAcceptance){

            $letterAccept = $project->tenderProposal->letterIntent->letterAcceptance;


            $carbonDatetime = Carbon::parse($letterAccept->LAConfirmDate);
            $SSTDate = $carbonDatetime->format('d/m/Y');
        }

        // TARIKH SAK
        $carbonDatetime = Carbon::parse($project->PTSAKDate);
        $SAKDate = $carbonDatetime->format('d/m/Y');

        return view('perolehan.project.view',
                compact('project','project_type','SSTDate','SAKDate','tender','tenderProposal','contractor')
            );
    }

//    public function create(){
//        $project_type = [
//            0 => 'Pembangunan',
//            1 => 'Penyelenggaraan',
//            2 => 'One Off',
//        ];
//
//        $contractor = [
//            0 => 'ABC SDN BHD',
//            1 => 'KENCANA SDN BHD',
//            2 => 'VISTA SDN BHD',
//        ];
//
//        return view('perolehan.project.create', compact('project_type', 'contractor'));
//    }


    //{{--Working Code Datatable with indexNo--}}
    public function projectDatatable(Request $request){

        $query = Project::whereHas('tenderProposal')->get();
        $count = 0;

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('department', function($row) {

                $department = $this->dropdownService->department();

                $result = "";

                if(!empty($row->tenderProposal))
                    $result = $department[$row->tenderProposal->tender->TD_DPTCode ] ?? "" . "(" . $row->tenderProposal->tender->TD_DPTCode ?? "" .")";

                return $result;

            })
            ->addColumn('projectTitle', function($row) {

                //$route = route('perolehan.project.view',[$row->PTNo] );

                //$result = '<a href="'.$route.'">'.$row->tenderProposal->tender->TDTitle ?? "None".'</a>';

                return $row->tenderProposal->tender->TDTitle ;
            })
            ->editColumn('PTCode', function($row) {

                $code = $row->tenderProposal->tender->TD_PTCode ?? "";

                $jenis_projek = $this->dropdownService->jenis_projek();
                $result = $jenis_projek[$code];

                return $result;
            })
            ->addColumn('COName', function($row) {

                $result = "";

                if(!empty($row->contractor))
                    $result = $row->contractor->COName;

                return $result;

            })
            ->addColumn('TDNo', function($row) {

                $result = "";

                if(!empty($row->tenderProposal))
                    $result = $row->tenderProposal->TP_TDNo;

                return $result;

            })
            ->editColumn('PTSAKDate', function($row) {
                $carbonDatetime = Carbon::parse($row->PTSAKDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','department', 'projectTitle', 'PTCode', 'COName', 'TDNo', 'PTSAKDate'])
            ->make(true);
    }

}
