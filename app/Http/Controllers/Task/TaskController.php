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

    public function taskDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $query = TaskProject::where('TPCB',$user->USCode)
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
            $taskProject->save();

            if($request->file('taskFile')){

                $fileType = "TP";
                $documentFile = $request->file('taskFile');

                $result = $this->saveFile($documentFile, $fileType, $taskCode);

            }

            return response()->json([
                'success' => '1',
                'message' => 'Task has been successfully saved',
                'redirect' =>  route('task.edit', $taskCode)
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

        $request->validate($validation, $messages);

        try {

            $taskCode = $request->taskCode;
            $status = $request->status;

            $user = Auth::user();

            $taskProject = TaskProject::where('TPCode', $taskCode)->first();

            if(!$taskProject){

                return response()->json([
                    'error' => '1',
                    'message' => 'Task not found. Try again.'
                ], 400);

            }

            if($status == 1){
                $taskProject->TPStatus = 'PROGRESS';
                $route = route('task.index');
            }
            else{
                $route = route('task.edit', $taskCode);
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
            $query->where('TPAssignee',$user->USCode);
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
                'redirect' =>  route('task.user.index')
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
                'redirect' =>  route('task.index')
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
                'redirect' =>  route('task.index')
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
