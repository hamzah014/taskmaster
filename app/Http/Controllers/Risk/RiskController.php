<?php

namespace App\Http\Controllers\Risk;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileController;
use App\Models\AutoNumber;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectRisk;
use App\Models\ProjectTeam;
use App\Models\TaskProject;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\WebSetting;
use App\Services\DropdownService;
use App\Services\GeneralService;
use Defuse\Crypto\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Session;
use Yajra\DataTables\DataTables;

class RiskController extends Controller
{

    public function index(){

        return view('risk.index');

    }

    public function riskDatatable(Request $request){

        $user = Auth::user();
        $dropdownService = new DropdownService();
        $generalService = new GeneralService();

        $allowRole = ['RL007', 'RL003'];

        $query = Project::where('PJStatus', 'RISK')
        ->whereHas('projectTeam', function($query) use($user, $allowRole){
            $query->where('PT_USCode', $user->USCode)
                  ->where(function($query) use ($allowRole) {
                      foreach ($allowRole as $role) {
                          $query->orWhereRaw('FIND_IN_SET(?, PT_RLCode)', [$role]);
                      }
                  });
        })
        ->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PJStartDate', function($row) {

                $result = Carbon::parse($row->PJStartDate)->format('d/m/Y');

                return $result;
            })
            ->editColumn('PJEndDate', function($row) {

                $result = Carbon::parse($row->PJEndDate)->format('d/m/Y');

                return $result;
            })
            ->editColumn('PJStatus', function($row) use(&$dropdownService) {

                $status = $dropdownService->projectStatus();

                $statusProject = $status[$row->PJStatus];

                $result = '<span class="badge badge-outline badge-primary">'.$statusProject.'</span>';

                return $result;
            })
            ->addColumn('riskStatus', function($row) use(&$generalService){

                $result = '<span class="badge badge-outline badge-dark">Pending</span>';

                if($row->projectRisk){

                    $totalRisk = $generalService->totalRisk($row->projectRisk);

                    if($totalRisk <= 33){
                        $result = '<span class="badge badge-outline badge-success">Low Risk</span>';
                    }
                    elseif($totalRisk <= 66){
                        $result = '<span class="badge badge-outline badge-warning">Medium Risk</span>';
                    }
                    else{
                        $result = '<span class="badge badge-outline badge-danger">High Risk</span>';
                    }

                }

                return $result;
            })
            ->addColumn('action', function($row) {

                $routeView = route('risk.view',[$row->PJCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-pen text-dark"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo','PJCode','PJStatus','riskStatus','action'])
            ->make(true);

    }

    public function view($id){

        $dropdownService = new DropdownService();

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();
        $projectStatus = $dropdownService->projectStatus();
        $moscowType = $dropdownService->moscowType();
        $requirementType = $dropdownService->requirementType();

        $project = Project::where('PJCode', $id)->first();
        $projectRisk = $project->projectRisk ?? null;

        return view('risk.view',
        compact(
            'projectStatus','requirementType','moscowType',
            'projectCategory','roleUser','project','projectRisk'
        ));

    }

    public function submitRisk(Request $request){

        $messages = [
            'security1.required'  => 'Question 1 required.',
            'security2.required'  => 'Question 2 required.',
            'operational1.required'  => 'Question 3 required.',
            'operational2.required'  => 'Question 4 required.',
            'architect.required'  => 'Question 5 required.',
            'regulatory.required'  => 'Question 6 required.',
            'reputation1.required'  => 'Question 7 required.',
            'reputation2.required'  => 'Question 8 required.',
            'financial.required'  => 'Question 9 required.',
            'buildApp.required'  => 'Question 10 required.',
            'integrate.required'  => 'Question 11 required.',
            'uicreate.required'  => 'Question 12 required.',

        ];

        $validation = [
            'security1' => 'required',
            'security2' => 'required',
            'operational1' => 'required',
            'operational2' => 'required',
            'architect' => 'required',
            'regulatory' => 'required',
            'reputation1' => 'required',
            'reputation2' => 'required',
            'financial' => 'required',
            'buildApp' => 'required',
            'integrate' => 'required',
            'uicreate' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $projectCode = $request->projectCode;
            $riskCode = $request->riskCode;

            $autoNumber = new AutoNumber();

            $user = Auth::user();

            $projectRisk = ProjectRisk::where('PRCode', $riskCode)
                        ->where('PR_PJCode', $projectCode)->first();

            if(!$projectRisk){

                $riskCode = $projectCode . "_" . Carbon::now()->format('ymdhisu') . rand(100,999);
                $projectRisk = new ProjectRisk();
                $projectRisk->PR_PJCode = $projectCode;
                $projectRisk->PRCode = $riskCode;
                $projectRisk->PRCB = $user->USCode;

            }

            $projectRisk->PR_Security1      = $request->security1;
            $projectRisk->PR_Security2      = $request->security2;
            $projectRisk->PR_Operational1   = $request->operational1;
            $projectRisk->PR_Operational2   = $request->operational2;
            $projectRisk->PR_Architect      = $request->architect;
            $projectRisk->PR_Regulatory     = $request->regulatory;
            $projectRisk->PR_Reputation1    = $request->reputation1;
            $projectRisk->PR_Reputation2    = $request->reputation2;
            $projectRisk->PR_Financial      = $request->financial;
            $projectRisk->PR_BuildApp       = $request->buildApp;
            $projectRisk->PR_Integrate      = $request->integrate;
            $projectRisk->PR_UICreate       = $request->uicreate;
            $projectRisk->save();

            return response()->json([
                'success' => '1',
                'message' => 'Project analysis has been successfully submitted.',
                'redirect' => route('risk.index')
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error!'.$e->getMessage()
            ], 400);
        }

    }

    public function updateStatusRisk(Request $request){

        $messages = [
            'projectCode.required'  => 'Project not valid.',
            'riskCode.required'  => 'Risk not valid.',
        ];

        $validation = [
            'projectCode' => 'required',
            'riskCode' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $projectCode = $request->projectCode;
            $riskCode = $request->riskCode;

            $autoNumber = new AutoNumber();

            $user = Auth::user();

            $projectRisk = ProjectRisk::where('PRCode', $riskCode)
                        ->where('PR_PJCode', $projectCode)->first();

            if(!$projectRisk){

                return response()->json([
                    'error' => '1',
                    'message' => 'Risk data not found!'
                ], 400);

            }

            $status = "PROGRESS";

            $project = $projectRisk->project;
            $project->PJStatus = $status;
            $project->save();

            return response()->json([
                'success' => '1',
                'message' => 'Risk analysis result has been successfully submitted.',
                'redirect' => route('project.index')
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error!'.$e->getMessage()
            ], 400);
        }



    }

}
