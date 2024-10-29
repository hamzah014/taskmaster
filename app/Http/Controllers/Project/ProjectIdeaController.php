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

class ProjectIdeaController extends Controller
{

    public function index(){

        return view('projectIdea.index');

    }

    public function projectIdeaDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $allowRole = [ 'RL005', 'RL003' ]; //Team Lead and Product Owner

        $query = Project::where('PJStatus', 'IDEA')
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

                $routeView = route('project.idea.edit',[$row->PJCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-eye text-dark"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo','PJCode','PJStatus','action'])
            ->make(true);

    }

    public function edit(Request $request, $id){

        $dropdownService = new DropdownService();
        $editPage = 0;

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();
        $projectStatus = $dropdownService->projectStatus();

        $project = Project::where('PJCode', $id)->first();

        if($request->status){

            $editPage = $request->status;

        }

        return view('projectIdea.edit',
        compact(
            'editPage','projectStatus',
            'projectCategory','roleUser','project'
        ));

    }

    public function add(Request $request){

        $messages = [
            'idea.required' 		=> 'Idea required.',
            'projectCode.required'  => 'Project code required.',

        ];

        $validation = [
            'idea' => 'required',
            'projectCode' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $projectCode = $request->projectCode;
            $idea = $request->idea;

            $autoNumber = new AutoNumber();
            $projectIdeaCode = $projectCode . rand(1000,9999);

            $user = Auth::user();

            $project = Project::where('PJCode', $projectCode)->first();
            if(!$project){

                return response()->json([
                    'error' => '1',
                    'message' => 'Project not found!'
                ], 400);

            }

            $projectIdea = new ProjectIdea();
            $projectIdea->PICode = $projectIdeaCode;
            $projectIdea->PI_PJCode = $projectCode;
            $projectIdea->PIDesc = $idea;
            $projectIdea->PICB = $user->USCode;
            $projectIdea->save();

            return response()->json([
                'success' => '1',
                'message' => 'Idea has been submitted.',
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

    public function ideaProjectDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $query = ProjectIdea::where('PI_PJCode', $request->projectCode)
                ->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('viewIdea', function($row) {

                $result = $row->PIDesc;

                if( !in_array($row->project->PJStatus, ['PENDING']) ){

                    $route = route('project.idea.view',[$row->PICode]);

                    $result = '<a href="'.$route.'" class="text-primary" target="_blank"><u>'.$row->PIDesc.'</u></a>';

                }

                return $result;

            })
            ->editColumn('PICB', function($row) {

                $createDate = Carbon::parse($row->PICD)->format('d/m/Y');
                $createUser = $row->user->USName;

                $result = $createUser . ", " . $createDate;

                return $result;
            })
            ->editColumn('PI_ReqComplete', function($row) {

                $complete = '<span class="badge badge-outline badge-success">Complete</span>';
                $pending = '<span class="badge badge-outline badge-dark">Pending</span>';

                $result = $row->PI_ReqComplete ? $complete : $pending;

                return $result;
            })
            ->addColumn('action', function($row) {

                $routeView = route('project.idea.analysis.formRequirement',[$row->PICode]);
                $result = "";

                $btnText = $row->PI_ReqComplete ? 'View Analysis' : 'Create Analysis';

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa-regular fa-file-lines text-dark"></i> ' .$btnText. '</a>';

                // if($row->project->PJStatus == 'IDEA-ALS'){
                //     $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa-regular fa-file-lines text-dark"></i></a>';
                // }
                // elseif($row->project->PJStatus == 'IDEA-SCR'){
                //     $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa-solid fa-file-pen text-dark"></i></a>';
                // }

                return $result;
            })
            ->addColumn('scoringStatus', function($row) {

                $complete = '<span class="badge badge-outline badge-success">Complete</span>';
                $pending = '<span class="badge badge-outline badge-dark">Pending</span>';

                $result = $row->ideaScoring ? $complete : $pending;

                return $result;
            })
            ->addColumn('actionScoring', function($row) {

                $routeView = route('project.idea.scoring.formScoring',[$row->PICode]);

                $btnText = $row->ideaScoring ? 'View Scoring' : 'Create Scoring';

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa-solid fa-file-pen text-dark"></i> '.$btnText.'</a>';

                return $result;
            })
            ->rawColumns(['indexNo','viewIdea','PICB','PI_ReqComplete','action',
                            'actionScoring','scoringStatus'
                        ])
            ->make(true);

    }

    public function updateStatus(Request $request){

        $messages = [
            'projectCode.required' 		=> 'Project required.',

        ];

        $validation = [
            'projectCode' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $projectCode = $request->projectCode;

            $autoNumber = new AutoNumber();

            $user = Auth::user();

            $project = Project::where('PJCode', $projectCode)->first();
            if(!$project){

                return response()->json([
                    'error' => '1',
                    'message' => 'Project not found!'
                ], 400);

            }

            $status = "IDEA-ALS";

            $project->PJStatus = $status;
            $project->save();

            $routeScore = route('project.idea.analysis.edit',[$project->PJCode]);

            return response()->json([
                'success' => '1',
                'message' => 'Project idea has been successfully submitted.',
                'redirect' => $routeScore
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

    public function view($id){

        $dropdownService = new DropdownService();

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();
        $projectStatus = $dropdownService->projectStatus();
        $moscowType = $dropdownService->moscowType();
        $requirementType = $dropdownService->requirementType();

        $projectIdea = ProjectIdea::where('PICode', $id)->first();

        $project = $projectIdea->project;
        $ideaScoring = $projectIdea->ideaScoring ?? null;

        return view('projectIdea.view',
        compact(
            'projectIdea','projectStatus','requirementType','moscowType',
            'projectCategory','roleUser','project','ideaScoring'
        ));


    }

}
