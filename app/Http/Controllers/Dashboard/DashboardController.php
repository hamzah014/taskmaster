<?php

namespace App\Http\Controllers\Dashboard;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Project;
use App\Models\ProjectIdea;
use App\Models\ProjectMiletstone;
use App\Models\TaskProject;
use App\Services\DropdownService;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Auth;
use Yajra\DataTables\DataTables;

class DashboardController extends Controller{

    public function index(){

        $dropdownService = new DropdownService();
		$user = Auth::user();

        $projectStatus = $dropdownService->projectStatus();

        $projectCount = Project::where('PJCB', $user->USCode)
        ->orWhere(function ($query) use ($user) {
            $query->whereHas('projectTeam', function ($query) use ($user) {
                $query->where('PT_USCode', $user->USCode);
            });
        })
        ->get()->count();

        $ideaCount = ProjectIdea::where('PICB', $user->USCode)->get()->count();

        $taskCount = TaskProject::where('TPAssignee', $user->USCode)
        ->whereIn('TPStatus', ['PROGRESS','ACP'])
        ->get()->count();

        $projects = Project::where('PJCB', $user->USCode)
        ->orWhere(function ($query) use ($user) {
            $query->whereHas('projectTeam', function ($query) use ($user) {
                $query->where('PT_USCode', $user->USCode);
            });
        })
        ->orderByRaw("CASE
            WHEN PJStatus = 'PENDING' THEN 1
            WHEN PJStatus = 'IDEA' THEN 2
            WHEN PJStatus = 'IDEA-ALS' THEN 3
            WHEN PJStatus = 'IDEA-SCR' THEN 4
            WHEN PJStatus = 'PROJ-ALS' THEN 5
            WHEN PJStatus = 'RISK' THEN 6
            WHEN PJStatus = 'PROGRESS-PD' THEN 7
            WHEN PJStatus = 'PROGRESS-FD' THEN 8
            WHEN PJStatus = 'PROGRESS-PC' THEN 9
            WHEN PJStatus = 'COMPLETE' THEN 10
            WHEN PJStatus = 'CANCEL' THEN 11
            ELSE 12 END")
        // ->orderBy('PJID', 'DESC')
        ->limit(10)
        ->get();

        // dd($projectCount);

        return view('dashboard.index',
        compact(
            'projectCount','ideaCount','taskCount',
            'projects','projectStatus'
        ));

    }



}
