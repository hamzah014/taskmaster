<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileController;
use App\Models\AutoNumber;
use App\Models\Project;
use App\Models\ProjectDocument;
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
use App\Services\GeneralService;
use Defuse\Crypto\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Session;
use Yajra\DataTables\DataTables;

class ProjectController extends Controller
{

    public function index(){

        return view('project.index');

    }

    public function create(){

        $dropdownService = new DropdownService();

        $projectCategory = $dropdownService->projectCategory();
        $roleUser = $dropdownService->roleUser();

        return view('project.create', compact('projectCategory','roleUser'));

    }

    public function submitInfo(Request $request){

        $messages = [
            'name.required' 		=> 'Application name required.',
            'description.required'  => 'Description required.',
            'startDate.required'    => 'Start date required.',
            'endDate.required'      => 'End date required.',
            'budget.required'       => 'Budget required.',

        ];

        $validation = [
            'name' => 'required',
            'description' => 'required',
            'startDate' => 'required',
            'endDate' => 'required',
            // 'budget' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $autoNumber = new AutoNumber();
            $projectCode = $autoNumber->generateProjectCode();

            $newProject = 0;

            $user = Auth::user();

            $project = Project::where('PJCode', $request->projectCode)->first();

            if(!$project){

                $project = new Project();
                $project->PJCode = $projectCode;

                $newProject = 1;

            }

            $status = "PENDING";

            $project->PJName = $request->name;
            $project->PJDesc = $request->description;
            $project->PJStartDate = $request->startDate;
            $project->PJEndDate = $request->endDate;
            $project->PJBudget = $request->budget;
            $project->PJStatus = $status;
            $project->PJCB = $user->USCode;
            $project->save();

            $teamLeadCode = 'RL003';

            $projectTeam = new ProjectTeam();
            $projectTeam->PT_PJCode = $project->PJCode;
            $projectTeam->PT_USCode = $user->USCode;
            $projectTeam->PT_RLCode = $teamLeadCode;
            $projectTeam->save();

            return response()->json([
                'success' => '1',
                'message' => 'Success',
                'code' => $projectCode,
                'redirect' => route('project.edit',['id' => $project->PJCode, 'status' => 1]),
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

    public function searchUser(Request $request){

        $messages = [
            'email.required' 		=> 'Email required.',

        ];

        $validation = [
            'email' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $user = User::where('USEmail', $request->email)->first();

            if(!$user){

                return response()->json([
                    'error' => '1',
                    'message' => 'Sorry, user does not exist.'
                ], 400);

            }

            $id = $user->USCode;
            $name = $user->USName;
            $email = $user->USEmail;

            return response()->json([
                'success' => '1',
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'message' => 'Success',
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

    public function storeMember(Request $request){

        $messages = [
            'memberID.required' 		=> 'Member ID required.',
            'memberName.required' 		=> 'Member Name required.',
            'memberEmail.required' 		=> 'Member Email required.',
            'memberRole.required' 		=> 'Member Role required.',

        ];

        $validation = [
            'memberID' => 'required',
            'memberName' => 'required',
            'memberEmail' => 'required',
            'memberRole' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $dropdownService = new DropdownService();

            $roleMandatory = $dropdownService->roleMandatory()->toArray();

            DB::beginTransaction();

            $memberIDs = $request->memberID;
            $memberNames = $request->memberName;
            $memberEmails = $request->memberEmail;
            $memberRoles = $request->memberRole;
            $projectTeamIDs = $request->projectTeamID;

            $project = Project::where('PJCode', $request->projectCode)->first();

            if(!$project){

                return response()->json([
                    'error' => '1',
                    'message' => 'Project not found!'
                ], 400);

            }

            $resultproject = array();

            if(count($memberIDs) == count($memberNames) &&
                count($memberNames) == count($memberEmails))
            {

                $oldProjectTeams = $project->projectTeam;

                if(count($oldProjectTeams) > 0){

                    $count = 0;
                    foreach($memberIDs as $memberID){

                        $memberName = $memberNames[$count];
                        $memberEmail = $memberEmails[$count];
                        $projectTeamID = $projectTeamIDs[$count];
                        $memberRole = implode(',', $memberRoles[$projectTeamID]);

                        if(!$memberRole){

                            return response()->json([
                                'error' => '1',
                                'message' => 'Please select role for ' . $memberName
                            ], 400);

                        }

                        $exists = $oldProjectTeams->contains('PTID', $projectTeamID);

                        if (!$exists) {
                            //INSERT NEW DATA
                            $projectTeam = new ProjectTeam();
                            $projectTeam->PT_PJCode = $request->projectCode;
                            $projectTeam->PT_USCode = $memberID;

                        }else{
                            //UPDATE CURRENT DATA
                            $projectTeam = ProjectTeam::where('PTID',$projectTeamID)->first();
                            $projectTeam->PT_USCode = $memberID;

                        }

                        $projectTeam->PT_RLCode = $memberRole;
                        $projectTeam->save();

                        array_push($resultproject, $projectTeam);

                        $count++;

                    }

                    //DELETE DATA and UPDATE STATUS
                    foreach ($oldProjectTeams as $oldProjectTeam) {

                        if (!in_array($oldProjectTeam->PTID, $projectTeamIDs)) {

                            // DELETE
                            $userCode = $oldProjectTeam->PT_USCode;
                            $oldProjectTeam->delete();

                        }

                    }


                }
                else{

                    foreach($memberIDs as $index => $memberID){

                        $memberName = $memberNames[$index];
                        $memberEmail = $memberEmails[$index];
                        $projectTeamID = $projectTeamIDs[$index];
                        $memberRole = implode(',', $memberRoles[$projectTeamID]);

                        if(!$memberRole){

                            return response()->json([
                                'error' => '1',
                                'message' => 'Please select role for ' . $memberName
                            ], 400);

                        }

                        $projectTeam = new ProjectTeam();
                        $projectTeam->PT_PJCode = $request->projectCode;
                        $projectTeam->PT_USCode = $memberID;
                        $projectTeam->PT_RLCode = $memberRole;
                        $projectTeam->save();

                    }

                }

            }
            else{

                return response()->json([
                    'error' => '1',
                    'message' => 'There is missing value. Please check again.'
                ], 400);

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Success',
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

    public function storeDocument(Request $request){

        $messages = [
            'documentID.required' 		=> 'Document ID required.',
            'documentDesc.required'     => 'Document Name required.',

        ];

        $validation = [
            'documentID' => 'required',
            'documentDesc' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $fileController = new FileController();

            $documentIDs = $request->documentID;
            $documentDescs = $request->documentDesc;
            $documentCodes = $request->fileID;
            $documentFiles = $request->file('documentFile');

            $project = Project::where('PJCode', $request->projectCode)->first();

            if(!$project){

                return response()->json([
                    'error' => '1',
                    'message' => 'Project not found!'
                ], 400);

            }

            if(count($documentIDs) == count($documentDescs))
            {

                $oldProjectDocuments = $project->projectDocument;

                if(count($oldProjectDocuments) > 0){

                    $count = 0;
                    foreach($documentIDs as $documentID){

                        $fileType = "PT-DP";

                        $documentDesc = $documentDescs[$count];
                        $documentCode = $documentCodes[$count];

                        if(!$documentDesc){

                            return response()->json([
                                'error' => '1',
                                'message' => 'Document name required : File no ' . $count+1
                            ], 400);

                        }

                        $exists = $oldProjectDocuments->contains('PDCode', $documentID);

                        if (!$exists) {
                            //INSERT NEW DATA

                            if(!$request->file($documentCode)){

                                return response()->json([
                                    'error' => '1',
                                    'message' => 'Document file required : File no ' . $count+1
                                ], 400);

                            }

                            $projectDocumentCode = $request->projectCode . "_" . Carbon::now()->format('ymdsu');

                            $projectDocument = new ProjectDocument();
                            $projectDocument->PDCode = $projectDocumentCode;
                            $projectDocument->PD_PJCode = $request->projectCode;

                        }else{
                            //UPDATE CURRENT DATA
                            $projectDocument = ProjectDocument::where('PD_PJCode',$request->projectCode)->where('PDCode', $documentID)->first();
                            $projectDocumentCode = $projectDocument->PDCode;

                        }

                        $result = null;

                        if($request->file($documentCode)){

                            $fileType = "PT-DP";
                            $documentFile = $request->file($documentCode)[0];

                            $result = $this->saveFile($documentFile, $fileType, $projectDocumentCode);

                        }

                        $projectDocument->PDDesc = $documentDesc;
                        $projectDocument->save();

                        $count++;

                    }

                    //DELETE DATA and UPDATE STATUS
                    foreach ($oldProjectDocuments as $oldProjectDocument) {

                        if (!in_array($oldProjectDocument->PDCode, $documentIDs)) {

                            // DELETE
                            $fileAttach = $oldProjectDocument->fileAttach;

                            if($fileAttach){
                                $deleteFile = $fileController->delete($fileAttach->FAGuidID );
                            }

                            $oldProjectDocument->delete();

                        }

                    }


                }
                else{

                    foreach($documentIDs as $index => $documentID){

                        $fileType = "PT-DP";

                        $documentDesc = $documentDescs[$index];
                        $documentFile = $documentFiles[$index];
                        $documentCode = $documentCodes[$index];

                        if(!$request->file($documentCode)){

                            return response()->json([
                                'error' => '1',
                                'message' => 'Document file required : File no ' . $index+1
                            ], 400);

                        }

                        $projectDocumentCode = $request->projectCode . "_" . Carbon::now()->format('ymds');

                        $projectDocument = new ProjectDocument();
                        $projectDocument->PDCode = $projectDocumentCode;
                        $projectDocument->PD_PJCode = $request->projectCode;
                        $projectDocument->PDDesc = $documentDesc;
                        $projectDocument->save();

                        if($request->file($documentCode)){

                            $fileType = "PT-DP";
                            $documentFile = $request->file($documentCode)[0];

                            $result = $this->saveFile($documentFile, $fileType, $projectDocumentCode);

                        }
                        else{

                            return response()->json([
                                'error' => '1',
                                'message' => 'Document file required : File no ' . $index+1
                            ], 400);

                        }

                        $result = $this->saveFile($documentFile, $fileType, $projectDocumentCode);

                    }

                }

            }
            else{

                return response()->json([
                    'error' => '1',
                    'message' => 'There is missing value. Please check again.'
                ], 400);

            }

            if($request->status == 1){
                $status = 'IDEA';
            }
            else{
                $status = 'PENDING';
            }

            $route = route('project.edit',$request->projectCode);

            $project->PJStatus = $status;
            $project->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => $route,
                'message' => 'Success'
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

    public function projectDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $query = Project::whereNotIn('PJStatus', ['','DELETED'])
                ->where('PJCB', $user->USCode)
                ->where(function ($query2) use ($user) {
                    $query2->whereHas('projectTeam', function ($query3) use ($user) {
                        $query3->orWhere('PT_USCode', $user->USCode);
                    });
                })
                ->orderBy('PJID', 'DESC')
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

                $routeView = route('project.edit',[$row->PJCode]);

                if(in_array($row->PJStatus, ['PROGRESS-PD','PROGRESS-FD','PROGRESS-PC'])){

                    $type = str_replace('PROGRESS-','',$row->PJStatus);

                    $routeView = route('task.listTask',[$type,$row->PJCode]);

                }

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-eye text-dark"></i> View</a>';
                $result .= '<button class="btn btn-sm btn-danger cursor-pointer mx-2" onclick="projectDelete(\''.$row->PJCode.'\')"><i class="fa fa-trash"></i> Delete</button>';

                return $result;
            })
            ->addColumn('actionAnalysis', function($row) {

                $routeView = route('project.analysis.view',[$row->PJCode]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-eye text-dark"></i> View</a>';

                return $result;
            })
            ->rawColumns(['indexNo','PJCode','PJStatus','action','actionAnalysis'])
            ->make(true);

    }

    public function edit(Request $request, $id){

        $user = Auth::user();
        $generalService = new GeneralService();

        $dropdownService = new DropdownService();
        $editPage = 0;
        $leader = 0;

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

        $projectRisk = $project->projectRisk ?? null;
        $riskStatus = 'P';

        if($projectRisk){

            $totalRisk = $generalService->totalRisk($project->projectRisk);

            if($totalRisk <= 33){
                $riskStatus = 'L';
            }
            elseif($totalRisk <= 66){
                $riskStatus = 'M';
            }
            else{
                $riskStatus = 'H';
            }

        }

        $inputDisable = in_array($project->PJStatus, ['PENDING']) ? '' : 'disabled';

        $project->progressCode = (in_array($project->PJStatus, ['PROGRESS-PD','PROGRESS-FD','PROGRESS-PC'])) ? str_replace('PROGRESS-','',$project->PJStatus) : "#";

        return view('project.edit',
        compact(
            'editPage','projectStatus','leader','inputDisable',
            'projectCategory','roleUser','project','projectRisk','riskStatus'
        ));

    }

    public function updateInfo(Request $request, $id){

        $messages = [
            'name.required' 		=> 'Application name required.',
            'description.required'  => 'Description required.',
            'startDate.required'    => 'Start date required.',
            'endDate.required'      => 'End date required.',
            'budget.required'       => 'Budget required.',

        ];

        $validation = [
            'name' => 'required',
            'description' => 'required',
            'startDate' => 'required',
            'endDate' => 'required',
            // 'budget' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $autoNumber = new AutoNumber();
            $projectCode = $autoNumber->generateProjectCode();

            $user = Auth::user();

            $project = Project::where('PJCode', $id)->first();
            if(!$project){

                return response()->json([
                    'error' => '1',
                    'message' => 'Project not found!'
                ], 400);

            }

            $project->PJName = $request->name;
            $project->PJDesc = $request->description;
            $project->PJStartDate = $request->startDate;
            $project->PJEndDate = $request->endDate;
            $project->PJBudget = $request->budget;
            $project->save();

            return response()->json([
                'success' => '1',
                'message' => 'Success',
                'code' => $projectCode
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

    public function deleteProject(Request $request){

        $messages = [
            // 'name.required' 		=> 'Application name required.',

        ];

        $validation = [
            // 'name' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $autoNumber = new AutoNumber();

            $id = $request->projectCode;

            $user = Auth::user();

            $project = Project::where('PJCode', $id)->first();
            if(!$project){

                return response()->json([
                    'error' => '1',
                    'message' => 'Project not found!'
                ], 400);

            }

            $status = 'DELETED';

            $project->PJStatus = $status;
            $project->save();

            return response()->json([
                'success' => '1',
                'message' => 'Success',
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

    public function updateStatus(Request $request){

        $messages = [
            // 'name.required' 		=> 'Application name required.',

        ];

        $validation = [
            // 'name' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $autoNumber = new AutoNumber();

            $id = $request->projectCode;
            $statusCode = $request->statusCode;

            $user = Auth::user();

            $project = Project::where('PJCode', $id)->first();
            if(!$project){

                return response()->json([
                    'error' => '1',
                    'message' => 'Project not found!'
                ], 400);

            }

            $status = $statusCode == 'CM' ? 'COMPLETE' : 'CANCEL';

            $project->PJStatus = $status;
            $project->save();

            return response()->json([
                'success' => '1',
                'message' => 'Success',
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

    public function cancelProject(Request $request){

        $messages = [
            'reasonCancel.required' 		=> 'Reason required.',

        ];

        $validation = [
            'reasonCancel' => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            $autoNumber = new AutoNumber();
            $user = Auth::user();

            $id = $request->projectCode;
            $reason = $request->reasonCancel;

            $project = Project::where('PJCode', $id)->first();
            if(!$project){

                return response()->json([
                    'error' => '1',
                    'message' => 'Project not found!'
                ], 400);

            }

            $status = 'CANCEL';

            $project->PJStatus = $status;
            $project->PJ_RejectReason = $reason;
            $project->save();

            return response()->json([
                'success' => '1',
                'message' => 'Success',
                'redirect' => route('project.edit',[$project->PJCode])
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

    function arrayContainsKey($valueArray, $searchArray) {

        $missingKeys = [];

        foreach ($searchArray as $key => $code) {

            if(!in_array($key, $valueArray)){

                $missingKeys[$code] = $code;

            }

        }

        return array_values($missingKeys);
    }

}
