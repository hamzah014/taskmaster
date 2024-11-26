<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileController;
use App\Models\AutoNumber;
use App\Models\FileAttach;
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

class ClosureController extends Controller
{

    public function closureDatatable(Request $request){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $projectCode = $request->projectCode;
        $taskType = $request->taskType;

        $query = FileAttach::where('FAFileType', $taskType)
                ->where('FARefNo', $projectCode)
                ->get();


        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('FACD', function($row) {

                $result = Carbon::parse($row->FACD)->format('d/m/Y');

                return $result;
            })
            ->editColumn('FACB', function($row) {

                // $result = Carbon::parse($row->FACB)->format('d/m/Y');
                $result = $row->submitBy->USName ?? "-";

                return $result;
            })
            ->addColumn('action', function($row) use($taskType) {

                $routeView = route('file.download',[$row->FAGuidID]);

                $result = '<a class="btn btn-sm btn-secondary cursor-pointer" href="'.$routeView.'"><i class="fa fa-upload text-dark"></i> Download</a>';

                return $result;
            })
            ->rawColumns(['indexNo','FACD','FACB','action'])
            ->make(true);

    }

    public function upload(Request $request){

        try {

            DB::beginTransaction();

            $autoNumber = new AutoNumber();

            $user = Auth::user();

            $project = Project::where('PJCode', $request->projectCode)->first();

            if($request->file('reportFile')){

                $fileType = "CLR";
                $documentFile = $request->file('reportFile');

                $result = $this->saveFile($documentFile, $fileType, $project->PJCode);

            }
            else{

                return response()->json([
                    'error' => '1',
                    'message' => 'Please upload report file'
                ], 400);
            }

            DB::commit();
            $route = route('project.edit',[$project->PJCode]);

            return response()->json([
                'success' => '1',
                'message' => 'Report has been successfully uploaded',
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

    public function updateComplete(Request $request)
    {

        try {

            DB::beginTransaction();

            $projectCode = $request->projectCode;

            $user = Auth::user();

            $project = Project::where('PJCode', $projectCode)
                        ->first();

            if(!$project->fileAttachCLR->isNotEmpty())
            {

                return response()->json([
                    'error' => '1',
                    'message' => 'Please submit at least one report Project Closure.'
                ], 400);

            }

            $status = "CLOSED";

            $project->PJStatus = $status;
            $project->save();

            DB::commit();

            $route = route('project.edit',[$project->PJCode]);

            return response()->json([
                'success' => '1',
                'message' => 'Project status has been updated.',
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

}
