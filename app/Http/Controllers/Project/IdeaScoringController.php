<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileController;
use App\Models\AutoNumber;
use App\Models\IdeaScoring;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectIdea;
use App\Models\ProjectTeam;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\WebSetting;
use App\Services\DropdownService;
use Defuse\Crypto\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Session;
use Yajra\DataTables\DataTables;

class IdeaScoringController extends Controller
{

    public function index(){

        return view('ideaScoring.index');

    }

    public function ideaScoringDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $allowRole = ['RL007', 'RL003'];

        $query = Project::where('PJStatus', 'IDEA-SCR')
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
            ->editColumn('PJCode', function($row) {

                $result = $row->PJCode;

                return $result;
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
            ->addColumn('action', function($row) {

                $routeView = route('project.idea.scoring.edit',[$row->PJCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-eye text-dark"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo','PJCode','PJStatus','action'])
            ->make(true);

    }

    public function edit(Request $request, $id){

        $user = Auth::user();

        $dropdownService = new DropdownService();

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();
        $projectStatus = $dropdownService->projectStatus();

        $project = Project::where('PJCode', $id)->first();

        $roleCodeToFind = 'RL003';

        $myProjectRole = $project->myProjectRole($user->USCode)
            ->whereRaw('FIND_IN_SET(?, PT_RLCode)', [$roleCodeToFind])
            ->first();

        if($myProjectRole){
            $leader = 1;
        }

        return view('ideaScoring.edit',
        compact(
            'projectStatus',
            'projectCategory','roleUser','project','leader'
        ));

    }

    public function formScoring(Request $request, $id){

        $dropdownService = new DropdownService();
        $editPage = 0;

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();
        $projectStatus = $dropdownService->projectStatus();
        $moscowType = $dropdownService->moscowType();
        $requirementType = $dropdownService->requirementType();

        $projectIdea = ProjectIdea::where('PICode', $id)->first();

        $project = $projectIdea->project;
        $ideaScoring = $projectIdea->ideaScoring ?? null;

        return view('ideaScoring.formScoring',
        compact(
            'projectIdea','projectStatus','requirementType','moscowType',
            'projectCategory','roleUser','project','ideaScoring'
        ));

    }

    public function submitScoring(Request $request){

        // dd($request);
        // projectIdea
        // persona
        // doAction
        // impact
        // description
        // projectIdeaCode
        // ideaScoreCode
        // requirementName
        // requirementType
        // requirementDesc
        // requirementRate
        // moscowType

        $messages = [
            // 'projectIdeaCode.required'  => 'Project code required.',
            // 'projectIdea.required' 		=> 'Idea required.',
            'requirementName.required'  => 'Requirement Name required.',
            'requirementType.required'  => 'Requirement Type required.',
            // 'requirementDesc.required'  => 'Details required.',
            'requirementRate.required'  => 'Requirement required.',
            'requirementRate.integer'   => 'Requirement rate must be an integer.',
            'requirementRate.min'       => 'Requirement rate must be at least 1.',
            'requirementRate.max'       => 'Requirement rate may not be greater than 100.',
            'moscowType.required'       => 'Importance Type required.',

        ];

        $validation = [
            // 'projectIdeaCode'   => 'required',
            // 'projectIdea'       => 'required',
            'requirementName'   => 'required',
            'requirementType'   => 'required',
            // 'requirementDesc'   => 'required',
            'requirementRate'   => 'required|integer|min:1|max:100',
            'moscowType'        => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $user = Auth::user();
            $autoNumber = new AutoNumber();

            $projectIdeaCode    = $request->projectIdeaCode;
            $ideaScoreCode      = $request->ideaScoreCode;
            $requirementName    = $request->requirementName;
            $requirementType    = $request->requirementType;
            $requirementDesc    = $request->requirementDesc;
            $requirementRate    = $request->requirementRate;
            $moscowType         = $request->moscowType;

            $projectIdea = ProjectIdea::where('PICode', $projectIdeaCode)->first();

            $ideaScoring = IdeaScoring::where('PISCode', $ideaScoreCode)->where('PIS_PICode', $projectIdeaCode)->first();

            if($ideaScoring){

                $ideaScoring->PISMB = $user->USCode;

            }
            else{

                $PISCode = $projectIdeaCode . rand(1000,9999);

                $ideaScoring = new IdeaScoring();
                $ideaScoring->PISCode = $PISCode;
                $ideaScoring->PISCB = $user->USCode;

            }

            $ideaScoring->PIS_PJCode = $projectIdea->PI_PJCode;
            $ideaScoring->PIS_PICode = $projectIdeaCode;
            $ideaScoring->PIS_ReqName = $requirementName;
            $ideaScoring->PIS_ReqType = $requirementType;
            $ideaScoring->PIS_ReqDesc = $requirementDesc;
            $ideaScoring->PISRate = $requirementRate;
            $ideaScoring->PIS_MoscowType = $moscowType;
            $ideaScoring->save();

            $projectIdea->PI_PISComplete = 1;
            $projectIdea->save();

            return response()->json([
                'success' => '1',
                'message' => 'Scoring analysis of idea has been submitted.',
                'redirect' => route('project.idea.scoring.edit', $projectIdea->PI_PJCode)
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

    public function submitAllScoring(Request $request){

        $messages = [
            'projectCode.required'  => 'Project code required.',

        ];

        $validation = [
            'projectCode'   => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $projectCode = $request->projectCode;

            $status = "PROJ-ALS";

            $project = Project::where('PJCode', $projectCode)->first();


            $projectScorePending = ProjectIdea::where('PI_PJCode', $projectCode)->where('PI_PISComplete', 0)->get();

            if(!$projectScorePending->isEmpty())
            {

                return response()->json([
                    'error' => '1',
                    'message' => 'Please complete all scoring for requirement analysis before submit.'
                ], 400);

            }

            $project->PJStatus = $status;
            $project->save();

            $routeAnalysis = route('project.analysis.view', $project->PJCode);

            return response()->json([
                'success' => '1',
                'message' => 'All scoring analysis of idea has been submitted.',
                'redirect' => $routeAnalysis
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
