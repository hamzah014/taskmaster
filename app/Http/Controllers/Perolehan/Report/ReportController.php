<?php

namespace App\Http\Controllers\Perolehan\Report;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Services\DropdownService;
use App\Models\Department;
use App\Models\Project;
use App\Models\AutoNumber;


use App\Http\Requests;
use App\Models\Tender;
use App\Models\TenderProposal;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class ReportController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        $tenderProposal = TenderProposal::whereHas('tenderProposalDetail')->get()->pluck('TPNo','TP_TDNo' )
        ->map(function ($item, $key) {
            $totalamt = Tender::where('TDNo', $key)->value('TDTitle');
            return  $item . " (" . $totalamt . ")" ;
        });

        return view('perolehan.report.index',compact('tenderProposal'));
    }

    public function zipFile(){

        $TPNo = 'TP00000060';

    }


}
