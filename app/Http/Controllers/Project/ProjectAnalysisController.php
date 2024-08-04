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

class ProjectAnalysisController extends Controller
{

    public function index(){

        return view('projectAnalysis.index');

    }

    public function view(Request $request, $id){

        $dropdownService = new DropdownService();

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();
        $projectStatus = $dropdownService->projectStatus();
        $moscowType = $dropdownService->moscowType();
        $requirementType = $dropdownService->requirementType();

        $infoMoscow = "M-Must Have, S-Should Have, C-Could Have, W-Wont Have";

        $project = Project::where('PJCode', $id)->first();

        return view('projectAnalysis.view',
        compact(
            'projectStatus','requirementType','moscowType',
            'projectCategory','roleUser','project','infoMoscow'
        ));

    }

    public function projectDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $query = Project::where('PJStatus', 'PROJ-ALS')
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
            ->addColumn('actionAnalysis', function($row) {

                $routeView = route('project.analysis.view',[$row->PJCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-eye text-dark"></i></a>';

                return $result;
            })
        ->rawColumns(['indexNo','PJCode','PJStatus','action','actionAnalysis'])
        ->make(true);

    }

    public function projectAnalysisDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $query = IdeaScoring::where('PIS_PJCode',$request->projectCode)
                ->where('PIS_ReqType', $request->type)
                ->orderBy('PISRate', 'DESC')
                ->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PIDesc', function($row) {

                $desc = $row->projectIdea->PIDesc;

                $result = '<a class="text-underline text-primary cursor-pointer" onclick="viewIdea(\'' . $row->PISCode . '\')">'.$desc.'</a>';

                return $result;
            })
        ->rawColumns(['indexNo','PIDesc'])
        ->make(true);





    }

    public function viewIdea(Request $request){

        $dropdownService = new DropdownService();

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();
        $projectStatus = $dropdownService->projectStatus();
        $moscowType = $dropdownService->moscowType();
        $requirementType = $dropdownService->requirementType();

        $ideaScoring = IdeaScoring::where('PISCode', $request->ideaScoreCode)->first();

        $projectIdea = $ideaScoring->projectIdea ?? null;
        $project = $ideaScoring->project ?? null;

        return view('projectAnalysis.viewIdea',
        compact(
            'projectIdea','projectStatus','requirementType','moscowType',
            'projectCategory','roleUser','project','ideaScoring'
        ));

    }

    public function submitAnalysis(Request $request){

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

            $status = "RISK";

            $project->PJStatus = $status;
            $project->save();

            $routeRisk = route('risk.view',$project->PJCode);

            return response()->json([
                'success' => '1',
                'message' => 'Project idea has been successfully submitted.',
                'redirect' => $routeRisk
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
