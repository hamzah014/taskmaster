<?php

namespace App\Http\Controllers\Perolehan;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Tender;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use Spatie\PdfToText\Pdf;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;
use Dompdf\Dompdf;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PaymentLog;
use App\Models\Project;
use App\Models\Contractor;
use App\Models\Notification;
use App\Services\DropdownService;

class NotificationController extends Controller
{
    public function __construct(DropdownService $dropdownService){

        $this->dropdownService = $dropdownService;
    }


    public function index(){

        $user = Auth::user();

        return view('perolehan.notification.all_notification',
        );

    }

    public function notificationDatatable(Request $request){

        $user = Auth::user();

        $query = Notification::where('NOActive',1)
                ->where('NO_RefCode',$user->USCode)
                ->get();

        return DataTables::of($query)
            ->editColumn('NOTitle', function($row) {

                $textcolor = "";

                if($row->NORead == 1){
                    $textcolor = 'black-text';
                }
                $result = '<a href="#notifModal" class="new modal-trigger '.$textcolor.'" onclick="openNotifModal('.$row->NOID.')">'.$row->NOTitle.'</a>';

                return $result;
            })
            ->addColumn('action', function($row) {
                
                $result = '<label class="chkarea" onclick="checkThis(this)">'.
                          '     <input type="checkbox" class="filled-in my-checkbox" value="'.$row->NOID.'">'.
                          '     <span></span>'.
                          ' </label>';

                return $result;
            })
            ->rawColumns(['action','NOTitle'])
            ->make(true);

    }

}
