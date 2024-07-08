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

        $taskCount = TaskProject::where('TPAssignee', $user->USCode)->get()->count();

        $projects = Project::where('PJCB', $user->USCode)->orderBy('PJID', 'DESC')->limit(10)->get();

        // dd($projectCount);

        return view('dashboard.index',
        compact(
            'projectCount','ideaCount','taskCount',
            'projects','projectStatus'
        ));

    }



}
