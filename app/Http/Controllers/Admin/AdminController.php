<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Project;
use App\Models\ProjectMiletstone;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Auth;
use Yajra\DataTables\DataTables;

class AdminController extends Controller{

    public function index(){

		$user = Auth::user();

        $now = Carbon::now();

        $nowDate = Carbon::today();

        return view('admin.dashboard',compact('user'));

    }



}
