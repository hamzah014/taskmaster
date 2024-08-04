<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileController;
use App\Models\AutoNumber;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectTeam;
use App\Models\TaskProject;
use App\Models\TaskProjectIssue;
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

class TaskController extends Controller
{

    public function index(){

        return view('task.index');

    }

    public function projectTaskDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $query = Project::whereHas('taskProject')
                ->where('PJStatus', 'PROGRESS')
                ->orderBy('PJCD', 'DESC')
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

                $statusProject = $status[$row->PJStatus] ?? "";

                $result = '<span class="badge badge-outline badge-primary">'.$statusProject.'</span>';

                return $result;
            })
            ->addColumn('action', function($row) {

                $routeView = route('task.listTask',[$row->PJCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-eye text-dark"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo','PJCode','PJStatus','action'])
            ->make(true);

    }

    public function listTask($id){

        $dropdownService = new DropdownService();
        $user = Auth::user();

        $projectCode = $id;

        $taskprojects = TaskProject::where('TP_PJCode', $projectCode)
                ->orderBy('TPCD', 'DESC')
                ->get();

        $project = Project::orderBy('PJCD', 'DESC')
                ->first();

        $roleCodeToFind = 'RL003';
        $leader = 0;

        $myProjectRole = $project->myProjectRole($user->USCode)
            ->whereRaw('FIND_IN_SET(?, PT_RLCode)', [$roleCodeToFind])
            ->first();

        if($myProjectRole){
            $leader = 1;
        }

        $taskpending = array();
        $taskprogress = array();
        $tasksubmit = array();
        $taskcomplete = array();

        foreach($taskprojects as $taskproject){

            if($taskproject->TPStatus == 'PENDING'){
                array_push($taskpending, $taskproject);

            }
            elseif($taskproject->TPStatus == 'PROGRESS'){
                array_push($taskprogress, $taskproject);

            }
            elseif($taskproject->TPStatus == 'SUBMIT'){
                array_push($tasksubmit, $taskproject);
            }
            elseif($taskproject->TPStatus == 'COMPLETE'){
                array_push($taskcomplete, $taskproject);
            }

        }

        $tasks = array(
            'pending' => $taskpending,
            'progress' => $taskprogress,
            'submit' => $tasksubmit,
            'complete' => $taskcomplete
        );

        $project = $dropdownService->project();
        $roleUser = $dropdownService->roleUser();
        $priorityLevel = $dropdownService->priorityLevel();
        $users = $dropdownService->users();
        $taskStatus = $dropdownService->taskStatus();

        return view('task.listTask',
        compact(
            'leader',
            'project','roleUser','priorityLevel','users','taskStatus',
            'projectCode','taskprojects','tasks'
        ));

    }

    public function detail(Request $request){

        $id = $request->id;

        $dropdownService = new DropdownService();
        $user = Auth::user();

        $project = $dropdownService->project();
        $roleUser = $dropdownService->roleUser();
        $priorityLevel = $dropdownService->priorityLevel();
        $users = $dropdownService->users();
        $taskStatus = $dropdownService->taskStatus();

        $taskProject = TaskProject::where('TPCode', $id)->first();
        $taskProject->TPDueDate = $taskProject->TPDueDate != "" ? Carbon::parse($taskProject->TPDueDate)->format('Y-m-d') : null;

        $taskIssue = $taskProject->taskIssue ?? null;

        $roleCodeToFind = 'RL003';
        $leader = 0;

        $myProjectRole = $taskProject->project->myProjectRole($user->USCode)
            ->whereRaw('FIND_IN_SET(?, PT_RLCode)', [$roleCodeToFind])
            ->first();

        if($myProjectRole){
            $leader = 1;
        }

        return view('task.detail',
        compact(
            'leader','user',
            'project','roleUser','priorityLevel','users','taskStatus',
            'taskProject','taskIssue'
        ));

    }

    public function taskDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $projectCode = $request->projectCode;

        $query = TaskProject::where('TPCB',$user->USCode)
                ->where('TP_PJCode', $projectCode)
                ->orderBy('TPCD', 'DESC')
                ->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('TPCode', function($row) {

                $result = $row->TPCode;

                return $result;
            })
            ->editColumn('PJName', function($row) {

                $result = $row->project->PJName;

                return $result;
            })
            ->editColumn('TPPriority', function($row) {

                $result = $row->TPPriority ?? "Not set";

                return $result;
            })
            ->editColumn('TPDueDate', function($row) {

                $result = Carbon::parse($row->TPDueDate)->format('d/m/Y');

                return $result;
            })
            ->editColumn('TPAssignee', function($row) {

                $result = $row->assignee ? $row->assignee ->USName : "Not set";

                return $result;
            })
            ->editColumn('TPStatus', function($row) use(&$dropdownService) {

                $status = $dropdownService->taskStatus();

                $statusTask = $status[$row->TPStatus];

                $result = '<span class="badge badge-outline badge-primary">'.$statusTask.'</span>';

                return $result;
            })
            ->addColumn('action', function($row) {

                $routeView = route('task.edit',[$row->TPCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-eye text-dark"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo','TPCode','PJName','TPPriority','TPDueDate','TPAssignee','TPStatus','action'])
            ->make(true);

    }

    public function create(){

        $user = Auth::user();

        $dropdownService = new DropdownService();

        $project = $dropdownService->userProject($user->USCode);
        $roleUser = $dropdownService->roleUser();
        $priorityLevel = $dropdownService->priorityLevel();
        $users = $dropdownService->users();

        return view('task.create', compact('project','roleUser','priorityLevel','users'));

    }

    public function add(Request $request){

        // dd($request);
        // "project"
        // "name"
        // "assignee"
        // "description"
        // "priority"
        // "dueDate"

        $messages = [
            'project.required' 		=> 'Project required.',
            'name.required'         => 'Task name required.',
            'assignee.required'     => 'Task Assignee required.',
            'description.required'  => 'Description required.',
            'priority.required'     => 'Level of priority required.',
            'dueDate.required'      => 'Due date required.',

        ];

        $validation = [
            'project' => 'required',
            'name' => 'required',
            'assignee' => 'required',
            'description' => 'required',
            'priority' => 'required',
            'dueDate' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $taskCode = $autoNumber->generateTaskCode();

            $user = Auth::user();

            $status = "PENDING";

            $taskProject = new TaskProject();
            $taskProject->TPCode = $taskCode;
            $taskProject->TP_PJCode = $request->project;
            $taskProject->TPName = $request->name;
            $taskProject->TPDesc = $request->description;
            $taskProject->TPPriority = $request->priority;
            $taskProject->TPAssignee = $request->assignee;
            $taskProject->TPDueDate = $request->dueDate;
            $taskProject->TPStatus = $status;
            $taskProject->TPCB = $user->USCode;

            if($request->parentTask){

                $taskProject->TP_ParentCode = $request->parentTask;

            }

            $taskProject->save();

            if($request->file('taskFile')){

                $fileType = "TP";
                $documentFile = $request->file('taskFile');

                $result = $this->saveFile($documentFile, $fileType, $taskCode);

            }

            DB::commit();
            $route = route('task.listTask',$taskProject->project->PJCode);

            return response()->json([
                'success' => '1',
                'message' => 'Task has been successfully saved',
                'redirect' =>  $route
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

    public function edit($id){

        $dropdownService = new DropdownService();

        $project = $dropdownService->project();
        $roleUser = $dropdownService->roleUser();
        $priorityLevel = $dropdownService->priorityLevel();
        $users = $dropdownService->users();
        $taskStatus = $dropdownService->taskStatus();

        $taskProject = TaskProject::where('TPCode', $id)->first();
        $taskProject->TPDueDate = Carbon::parse($taskProject->TPDueDate)->format('Y-m-d');

        $taskIssue = $taskProject->taskIssue ?? null;

        return view('task.edit',
        compact(
            'project','roleUser','priorityLevel','users','taskStatus',
            'taskProject','taskIssue'
        ));

    }

    public function update(Request $request){

        $messages = [
            'project.required' 		=> 'Project required.',
            'name.required'         => 'Task name required.',
            'assignee.required'     => 'Task Assignee required.',
            'description.required'  => 'Description required.',
            'priority.required'     => 'Level of priority required.',
            'dueDate.required'      => 'Due date required.',
            'taskStatus.required'   => 'Status required.',

        ];

        $validation = [
            'project' => 'required',
            'name' => 'required',
            'assignee' => 'required',
            'description' => 'required',
            'priority' => 'required',
            'dueDate' => 'required',
            'taskStatus' => 'required',
        ];

        $status = $request->status;

        if($status == 1){

            $request->validate($validation, $messages);

        }


        try {

            DB::beginTransaction();

            $taskCode = $request->taskCode;

            $user = Auth::user();

            $taskProject = TaskProject::where('TPCode', $taskCode)->first();

            if(!$taskProject){

                return response()->json([
                    'error' => '1',
                    'message' => 'Task not found. Try again.'
                ], 400);

            }

            $route = route('task.listTask',$taskProject->project->PJCode);

            if($status == 1){
                $taskProject->TPStatus = 'PROGRESS';
            }

            $taskProject->TP_PJCode = $request->project;
            $taskProject->TPName = $request->name;
            $taskProject->TPDesc = $request->description;
            $taskProject->TPPriority = $request->priority;
            $taskProject->TPAssignee = $request->assignee;
            $taskProject->TPDueDate = $request->dueDate;
            $taskProject->save();

            if($request->file('taskFile')){

                $fileType = "TP";
                $documentFile = $request->file('taskFile');

                $result = $this->saveFile($documentFile, $fileType, $taskCode);

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Task has been successfully updated',
                'redirect' => $route
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

    public function indexUser(){

        return view('task.user.index');

    }

    public function taskUserDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $query = Project::whereNotNull('PJStatus')
        ->whereHas('taskProject', function($query) use(&$user){
            $query->where('TPAssignee',$user->USCode)
            ->whereIn('TPStatus',['PROGRESS']);
        })
        ->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('totalTask', function($row) use(&$user) {

                $result = $row->myTaskProject($user->USCode)->count();

                return $result;
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

                $statusProject = $status[$row->PJStatus] ?? "-";

                $result = '<span class="badge badge-outline badge-primary">'.$statusProject.'</span>';

                return $result;
            })
            ->addColumn('action', function($row) {

                $routeView = route('task.user.viewUser',[$row->PJCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" data-bs-toggle="modal" data-bs-target="#modal-task"
                 onclick="viewTask(\'' . $row->PJCode . '\')"><i class="fa fa-eye text-dark"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo','totalTask','PJName','PJStatus','action'])
            ->make(true);

    }

    public function myTaskDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $query = TaskProject::where('TPAssignee',$user->USCode)
                ->where('TP_PJCode', $request->projectCode)
                ->whereIn('TPStatus',['PROGRESS'])
                ->orderBy('TPCD', 'DESC')
                ->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('TPCode', function($row) {

                $result = $row->TPCode;

                return $result;
            })
            ->editColumn('PJName', function($row) {

                $result = $row->project->PJName;

                return $result;
            })
            ->editColumn('TPPriority', function($row) {

                $result = $row->TPPriority;

                return $result;
            })
            ->editColumn('TPDueDate', function($row) {

                $result = Carbon::parse($row->TPDueDate)->format('d/m/Y');

                return $result;
            })
            ->editColumn('TPAssignee', function($row) {

                $result = $row->assignee->USName;

                return $result;
            })
            ->editColumn('TPStatus', function($row) use(&$dropdownService) {

                $status = $dropdownService->taskStatus();

                $statusTask = $status[$row->TPStatus];

                $result = '<span class="badge badge-outline badge-primary">'.$statusTask.'</span>';

                return $result;
            })
            ->addColumn('action', function($row) {

                $routeView = route('task.user.viewUser',[$row->TPCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-pen text-dark"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo','TPCode','PJName','TPPriority','TPDueDate','TPAssignee','TPStatus','action'])
            ->make(true);

    }

    public function taskUserDatatable2(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $query = TaskProject::where('TPAssignee',$user->USCode)
                ->orderBy('TPCD', 'DESC')
                ->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('TPCode', function($row) {

                $result = $row->TPCode;

                return $result;
            })
            ->editColumn('PJName', function($row) {

                $result = $row->project->PJName;

                return $result;
            })
            ->editColumn('TPPriority', function($row) {

                $result = $row->TPPriority;

                return $result;
            })
            ->editColumn('TPDueDate', function($row) {

                $result = Carbon::parse($row->TPDueDate)->format('d/m/Y');

                return $result;
            })
            ->editColumn('TPAssignee', function($row) {

                $result = $row->assignee->USName;

                return $result;
            })
            ->editColumn('TPStatus', function($row) use(&$dropdownService) {

                $status = $dropdownService->taskStatus();

                $statusTask = $status[$row->TPStatus];

                $result = '<span class="badge badge-outline badge-primary">'.$statusTask.'</span>';

                return $result;
            })
            ->addColumn('action', function($row) {

                $routeView = route('task.user.viewUser',[$row->TPCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-pen text-dark"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo','TPCode','PJName','TPPriority','TPDueDate','TPAssignee','TPStatus','action'])
            ->make(true);

    }

    public function viewUser($id){

        $dropdownService = new DropdownService();

        $project = $dropdownService->project();
        $roleUser = $dropdownService->roleUser();
        $priorityLevel = $dropdownService->priorityLevel();
        $users = $dropdownService->users();
        $taskStatus = $dropdownService->taskStatus();

        $taskProject = TaskProject::where('TPCode', $id)->first();
        $taskProject->TPDueDate = Carbon::parse($taskProject->TPDueDate)->format('Y-m-d');

        $taskIssue = $taskProject->taskIssue ?? null;

        return view('task.user.view',
        compact(
            'project','roleUser','priorityLevel','users','taskStatus',
            'taskProject','taskIssue'
        ));

    }

    public function submitTask(Request $request){

        $messages = [
            'taskDesc.required' 		=> 'Task description required.',

        ];

        $validation = [
            'taskDesc' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $autoNumber = new AutoNumber();
            $taskDesc = $request->taskDesc;
            $taskCode = $request->taskCode;

            $user = Auth::user();

            $status = "SUBMIT";

            $taskProject = TaskProject::where('TPCode', $taskCode)->first();
            $taskProject->TPStatus = $status;
            $taskProject->save();

            $issueCode = $taskCode . Carbon::now()->format('ymdhisu') . rand(100,999);

            $taskProjectIssue = new TaskProjectIssue();
            $taskProjectIssue->TPICode = $issueCode;
            $taskProjectIssue->TPI_TPCode = $taskCode;
            $taskProjectIssue->TPIDesc = $taskDesc;
            $taskProjectIssue->TPICB = $user->USCode;
            $taskProjectIssue->save();

            if($request->file('taskFile')){

                $fileType = "TIF";
                $documentFile = $request->file('taskFile');

                $result = $this->saveFile($documentFile, $fileType, $issueCode);

            }

            return response()->json([
                'success' => '1',
                'message' => 'Task progress has been successfully submitted',
                // 'redirect' =>  route('task.user.index')
                'redirect' =>  route('task.listTask',[$taskProject->project->PJCode])
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

    public function submitTaskLead(Request $request){

        $messages = [
            'taskDesc.required' 		=> 'Task description required.',

        ];

        $validation = [
            'taskDesc' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $autoNumber = new AutoNumber();
            $taskDesc = $request->taskDesc;
            $taskCode = $request->taskCode;

            $user = Auth::user();

            $status = "PROGRESS";

            $taskProject = TaskProject::where('TPCode', $taskCode)->first();
            $taskProject->TPStatus = $status;
            $taskProject->save();

            $issueCode = $taskCode . Carbon::now()->format('ymdhisu') . rand(100,999);

            $taskProjectIssue = new TaskProjectIssue();
            $taskProjectIssue->TPICode = $issueCode;
            $taskProjectIssue->TPI_TPCode = $taskCode;
            $taskProjectIssue->TPIDesc = $taskDesc;
            $taskProjectIssue->TPI_isLead = 1;
            $taskProjectIssue->TPICB = $user->USCode;
            $taskProjectIssue->save();

            if($request->file('taskFile')){

                $fileType = "TIF";
                $documentFile = $request->file('taskFile');

                $result = $this->saveFile($documentFile, $fileType, $issueCode);

            }

            return response()->json([
                'success' => '1',
                'message' => 'Task progress has been update.',
                'redirect' =>  route('task.listTask',$taskProject->project->PJCode)
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

    public function completeTask(Request $request){

        try {

            $autoNumber = new AutoNumber();
            $taskCode = $request->taskCode;

            $user = Auth::user();

            $status = "COMPLETE";

            $taskProject = TaskProject::where('TPCode', $taskCode)->first();
            $taskProject->TPStatus = $status;
            $taskProject->save();

            return response()->json([
                'success' => '1',
                'message' => 'Task progress has been updated to complete',
                // 'redirect' =>  route('task.index')
                'redirect' =>  route('task.listTask',[$taskProject->project->PJCode])
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
