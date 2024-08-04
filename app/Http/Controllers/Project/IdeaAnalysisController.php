<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileController;
use App\Models\AutoNumber;
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

class IdeaAnalysisController extends Controller
{

    public function index(){

        return view('ideaAnalysis.index');

    }

    public function ideaAnalysisDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $allowRole = ['RL007', 'RL003'];

        $query = Project::where('PJStatus', 'IDEA-ALS')
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

                $routeView = route('project.idea.analysis.edit',[$row->PJCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-eye text-dark"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo','PJCode','PJStatus','action'])
            ->make(true);

    }

    public function edit(Request $request, $id){

        $user = Auth::user();

        $dropdownService = new DropdownService();
        $editPage = 0;

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();
        $projectStatus = $dropdownService->projectStatus();

        $project = Project::where('PJCode', $id)->first();

        if($request->status){

            $editPage = $request->status;

        }

        $roleCodeToFind = 'RL003';

        $myProjectRole = $project->myProjectRole($user->USCode)
            ->whereRaw('FIND_IN_SET(?, PT_RLCode)', [$roleCodeToFind])
            ->first();

        if($myProjectRole){
            $leader = 1;
        }

        return view('ideaAnalysis.edit',
        compact(
            'editPage','projectStatus',
            'projectCategory','roleUser','project',
            'leader'
        ));

    }

    public function formRequirement(Request $request, $id){

        $dropdownService = new DropdownService();
        $editPage = 0;

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();
        $projectStatus = $dropdownService->projectStatus();

        $projectIdea = ProjectIdea::where('PICode', $id)->first();

        $project = $projectIdea->project;

        return view('ideaAnalysis.formRequirement',
        compact(
            'projectIdea','projectStatus',
            'projectCategory','roleUser','project'
        ));

    }

    public function submitRequirement(Request $request){

        // dd($request);
        // projectIdeaCode
        // projectIdea
        // persona
        // doAction
        // impact
        // description

        $messages = [
            // 'projectIdeaCode.required'  => 'Project code required.',
            // 'projectIdea.required' 		=> 'Idea required.',
            'persona.required' 		    => 'persona required.',
            'doAction.required' 		=> 'do something required.',
            'impact.required' 		    => 'impact/priority/value required.',
            'description.required' 		=> 'Description required.',

        ];

        $validation = [
            // 'projectIdeaCode'   => 'required',
            // 'projectIdea'       => 'required',
            'persona'           => 'required',
            'doAction'          => 'required',
            'impact'            => 'required',
            'description'       => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $projectIdeaCode = $request->projectIdeaCode;
            $persona = $request->persona;
            $doAction = $request->doAction;
            $impact = $request->impact;
            $description = $request->description;

            $projectIdea = ProjectIdea::where('PICode', $projectIdeaCode)->first();
            $projectIdea->PIPersona = $persona;
            $projectIdea->PIAction = $doAction;
            $projectIdea->PIImpact = $impact;
            $projectIdea->PI_ReqDesc = $description;
            $projectIdea->PI_ReqDate = Carbon::now();
            $projectIdea->PI_ReqComplete = 1;
            $projectIdea->save();

            if($request->file('diagram')){

                $fileType = "PT-ID";
                $documentFile = $request->file('diagram');

                $result = $this->saveFile($documentFile, $fileType, $projectIdeaCode);

            }

            return response()->json([
                'success' => '1',
                'message' => 'Requirement analysis of idea has been submitted.',
                'redirect' => route('project.idea.analysis.edit', $projectIdea->PI_PJCode)
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

    public function submitAllRequirement(Request $request){

        $messages = [
            'projectCode.required'  => 'Project code required.',

        ];

        $validation = [
            'projectCode'   => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $projectCode = $request->projectCode;

            $status = "IDEA-SCR";

            $project = Project::where('PJCode', $projectCode)->first();
            $project->PJStatus = $status;
            $project->save();

            $routeScoring = route('project.idea.scoring.edit', $project->PJCode);

            return response()->json([
                'success' => '1',
                'message' => 'Requirement analysis of idea has been submitted.',
                'redirect' => $routeScoring
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
