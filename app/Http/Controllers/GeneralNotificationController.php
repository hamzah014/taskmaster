<?php

namespace App\Http\Controllers;

use App\Models\TenderProposalDetail;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\FileAttach;
use App\Helper\Custom;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Auth;
use Image;
use Imagick;
use App\Models\AutoNumber;
use App\Models\CertApp;
use App\Models\Contractor;
use App\Models\IntegrateSSMLog;
use App\Models\EmailLog;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Services\DropdownService;
use Illuminate\Support\Facades\Auth as IlluminateAuth;
use Mail;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Session\Session;
use Yajra\DataTables\Facades\DataTables;

class GeneralNotificationController extends Controller{

    public function index(){

        $user = Auth::user();

        $usr = $user->role->RLName;

        return view('notification.all_notification',
        compact('usr')
        );

    }

    function sendNotification($refNo,$notiType,$code,$data){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $title = "";
            $desc = "";

            $notificationType = NotificationType::where('NTCode',$code)->first();
            $title = $notificationType->NTTitle;
            $desc = $notificationType->NTDesc;

            if($code == 'TD-SM'){
                $desc = str_replace("{TDNo}", $data['TDNo'], $desc);

            }
            else if($code == 'PM-SM'){
                $title = str_replace("{PROJECTNO}", $data['PTNo'], $title);
                $desc = str_replace("{PROJECTNO}", $data['PTNo'], $desc);

            }
            else if($code == 'PM-RV'){
                //nothing to change

            }
            else if($code == 'PM-AP'){
                //nothing to change

            }
            else if($code == 'KOM-C'){
                $komno = $data['kickoff']->KOMNo;
                $desc = str_replace("{MEETINGNO}", $komno, $desc);

            }
            else if($code == 'KOM-E'){

                $komno = $data['kickoff']->KOMNo;
                $kickdate =  Carbon::parse($data['kickoff']->KOMDate)->format('d/m/Y');
                $kicktime =  Carbon::parse($data['kickoff']->KOMTime)->format('h:i A');

                $title = str_replace("{MEETINGNO}", $komno, $title);
                $desc = str_replace("{MEETINGNO}", $komno, $desc);
                $desc = str_replace("{DATE}", $kickdate, $desc);
                $desc = str_replace("{TIME}", $kicktime, $desc);

            }
            else if($code == 'PC-SM'){

                $PTNo = $data['PTNo'];
                $PCNo = $data['PCNo'];

                $title = str_replace("{CLAIMNO}", $PCNo, $title);
                $desc = str_replace("{PROJECTNO}", $PTNo, $desc);

            }
            else if($code == 'PC-OB'){

                $PTNo = $data['PTNo'];
                $PCNo = $data['PCNo'];

                $title = str_replace("{CLAIMNO}", $PCNo, $title);
                $desc = str_replace("{CLAIMNO}", $PCNo, $desc);
                $desc = str_replace("{PROJECTNO}", $PTNo, $desc);

            }
            else if($code == 'PC-RV'){

                $PTNo = $data['PTNo'];
                $PCNo = $data['PCNo'];

                $title = str_replace("{CLAIMNO}", $PCNo, $title);

            }
            else if($code == 'PC-AP-CO'){

                $PTNo = $data['PTNo'];
                $PCNo = $data['PCNo'];

                $title = str_replace("{CLAIMNO}", $PCNo, $title);

            }
            else if($code == 'PC-APT-CO'){

                $PTNo = $data['PTNo'];
                $PCNo = $data['PCNo'];

                $title = str_replace("{CLAIMNO}", $PCNo, $title);

            }
            else if($code == 'PC-AP'){

                $PTNo = $data['PTNo'];
                $PCNo = $data['PCNo'];

                $title = str_replace("{CLAIMNO}", $PCNo, $title);
                $desc = str_replace("{CLAIMNO}", $PCNo, $desc);
//                $desc = str_replace("{CLAIMNO}", $kicktime, $desc);

            }
            else if($code == 'PC-APT'){

                $PTNo = $data['PTNo'];
                $PCNo = $data['PCNo'];

                $title = str_replace("{CLAIMNO}", $PCNo, $title);
                $desc = str_replace("{CLAIMNO}", $PCNo, $desc);
//                $desc = str_replace("{CLAIMNO}", $kicktime, $desc);

            }
            else if($code == 'PC-MT'){

                $cmno = $data['CM']->CMNo;
                $cmdate =  Carbon::parse($data['CM']->CMate)->format('d/m/Y');
                $cmtime =  Carbon::parse($data['CM']->CMime)->format('h:i A');

                $PTNo = $data['PTNo'];

                $title = str_replace("{CLAIMNO}", $cmno, $title);

                $desc = str_replace("{CLAIMNO}", $cmno, $desc);
                $desc = str_replace("{DATE}", $cmdate, $desc);
                $desc = str_replace("{TIME}", $cmtime, $desc);
                $desc = str_replace("{PROJECTNO}", $PTNo, $desc);


            }
            else if($code == 'PC-CR'){

                $cmno = $data['CM']->CMNo;
                $PTNo = $data['PTNo'];

                $title = str_replace("{CLAIMNO}", $cmno, $title);

                $desc = str_replace("{CLAIMNO}", $cmno, $desc);
                $desc = str_replace("{PROJECTNO}", $PTNo, $desc);

            }
            else if($code == 'PC-CRP'){

                $cmno = $data['CM']->CMNo;
                $PTNo = $data['PTNo'];

                $title = str_replace("{PROJECTNO}", $cmno, $title);
                $desc = str_replace("{PROJECTNO}", $PTNo, $desc);

            }
            else if($code == 'PC-CRR'){

                $cmno = $data['CM']->CMNo;
                $PTNo = $data['PTNo'];

                $title = str_replace("{PROJECTNO}", $cmno, $title);
                $desc = str_replace("{PROJECTNO}", $PTNo, $desc);

            }
            else if($code == 'PC-CRT'){

                $cmno = $data['CM']->CMNo;
                $PTNo = $data['PTNo'];


                $title = str_replace("{PROJECTNO}", $cmno, $title);
                $desc = str_replace("{PROJECTNO}", $PTNo, $desc);

            }
            else if($code == 'PT-C'){

                $PTNo = $data['PTNo'];

                $title = str_replace("{PROJECTNO}", $PTNo, $title);
                $desc = str_replace("{PROJECTNO}", $PTNo, $desc);

            }
            else if($code == 'TD-BM'){

                $no = $data['BM']->BMNo;
                $date =  Carbon::parse($data['BM']->BMDate)->format('d/m/Y');
                $time =  Carbon::parse($data['BM']->BMTime)->format('h:i A');

                $title = str_replace("{BMNO}", $no, $title);

                $desc = str_replace("{BMNO}", $no, $desc);
                $desc = str_replace("{DATE}", $date, $desc);
                $desc = str_replace("{TIME}", $time, $desc);


            }
            else if($code == 'TD-TR'){

                $no = $data['BM']->BMNo;
                $td = $data['TD']->TDNo;

                $title = str_replace("{BMNO}", $no, $title);

                $desc = str_replace("{BMNO}", $no, $desc);
                $desc = str_replace("{TDNO}", $td, $desc);

            }
            else if($code == 'TD-TRM'){

                $no = $data['BM']->BMNo;
                $td = $data['TD']->TDNo;

                $title = str_replace("{BMNO}", $no, $title);

                $desc = str_replace("{BMNO}", $no, $desc);
                $desc = str_replace("{TDNO}", $td, $desc);

            }
            else if($code == 'TD-TRC'){

                $no = $data['BM']->BMNo;
                $td = $data['TD']->TDNo;

                $title = str_replace("{BMNO}", $no, $title);
                $desc = str_replace("{TDNO}", $td, $desc);

            }
            else if($code == 'TD-TRD'){

                $no = $data['BM']->BMNo;
                $td = $data['TD']->TDNo;

                $title = str_replace("{BMNO}", $no, $title);
                $desc = str_replace("{TDNO}", $td, $desc);

            }
            else if($code == 'TD-TRR'){

                $no = $data['BM']->BMNo;
                $td = $data['TD']->TDNo;

                $title = str_replace("{BMNO}", $no, $title);
                $desc = str_replace("{TDNO}", $td, $desc);

            }
            else if($code == 'TD-TRPM'){

                $no = $data['BM']->BMNo;
                $td = $data['TD']->TDNo;

                $title = str_replace("{BMNO}", $no, $title);
                $desc = str_replace("{TDNO}", $td, $desc);

            }
            else if($code == 'TD-TRP'){

                $no = $data['BM']->BMNo;
                $td = $data['TD']->TDNo;

                $title = str_replace("{BMNO}", $no, $title);
                $desc = str_replace("{TDNO}", $td, $desc);

            }
            else if($code == 'PTD-MT'){

                $no = $data['MT']->MNo;
                $date =  Carbon::parse($data['MT']->BMDate)->format('d/m/Y');
                $time =  Carbon::parse($data['MT']->BMTime)->format('h:i A');

                $ptdno = $data['PTDNo'];

                $title = str_replace("{MNO}", $no, $title);

                $desc = str_replace("{MNO}", $no, $desc);
                $desc = str_replace("{DATE}", $date, $desc);
                $desc = str_replace("{TIME}", $time, $desc);
                $desc = str_replace("{PTDNO}", $ptdno, $desc);

            }
            else if($code == 'PTD-D'){

                $no = $data['MT']->MNo;
                $ptdno = $data['PTDNo'];

                $title = str_replace("{MNO}", $no, $title);
                $desc = str_replace("{PTDNO}", $ptdno, $desc);

            }
            else if($code == 'PTD-C'){

                $no = $data['MT']->MNo;
                $ptdno = $data['PTDNo'];

                $title = str_replace("{MNO}", $no, $title);
                $desc = str_replace("{PTDNO}", $ptdno, $desc);

            }
            else if($code == 'PTD-P'){

                $no = $data['MT']->MNo;
                $ptdno = $data['PTDNo'];

                $title = str_replace("{MNO}", $no, $title);
                $desc = str_replace("{PTDNO}", $ptdno, $desc);

            }
            else if($code == 'PTD-R'){

                $no = $data['MT']->MNo;
                $ptdno = $data['PTDNo'];

                $title = str_replace("{MNO}", $no, $title);
                $desc = str_replace("{PTDNO}", $ptdno, $desc);

            }

            $notification = new Notification();
            $notification->NO_RefCode = $refNo;
            $notification->NOType = $notiType; //type for user
            $notification->NO_NTCode = $code;
            $notification->NOTitle = $title;
            $notification->NODescription = $desc;
            $notification->NORead = 0;
            $notification->NOSent = 1;
            $notification->NOActive = 1;
            $notification->NOCB = $user->USCode;
            $notification->NOMB = $user->USCode;
            $notification->save();

            DB::commit();

            return $notification;


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat notifikasi tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }
    public function notificationDatatable(Request $request){

        $user = Auth::user();
        $typeCode = $request->typeCode;

        $refNo = '';

        if($request->refNo){
            $refNo = $request->refNo;

        }else{
            $refNo = $user->USCode;
        }

        $query = Notification::where('NOActive',1)
                ->where('NO_RefCode',$refNo)
                ->where('NOType',$typeCode)
                ->orderBy('NOID', 'desc') // Order by the 'created_at' column in descending order
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
            ->editColumn('NOCD', function($row) {

                if(empty($row->NOCD)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->NOCD);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y h:i A');

                }

                return $formattedDate;
            })
            ->addColumn('action', function($row) {

                $result = '<label class="chkarea" onclick="checkThis(this)">'.
                          '     <input type="checkbox" class="filled-in my-checkbox" value="'.$row->NOID.'">'.
                          '     <span></span>'.
                          ' </label>';

                return $result;
            })
            ->rawColumns(['action','NOTitle','NOCD'])
            ->make(true);

    }

    public function getNotification(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $notiID = $request->notiID;

            $notification = Notification::where('NOID',$notiID)->first();
            $notification->NORead = 1;
            $notification->save();

            if($notification !== null){

                DB::commit();

                return response()->json([
                    'status' => '1',
                    'data' => $notification,
                ]);

            }else{

            return response()->json([
                'status' => '0',
                'message' => 'Notifikasi tidak berjaya dijumpai.'
            ], 400);

            }

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Notifikasi tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }



    }

    public function readNotification(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $notiID_array = explode(',',$request->notiID);

            $notification = Notification::whereIn('NOID', $notiID_array)->where('NORead',0)->update(['NORead' => 1]);

            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Notifikasi berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Notifikasi tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }



    }

    public function deleteNotification(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $notiID_array = explode(',',$request->notiID);

            $notification = Notification::whereIn('NOID', $notiID_array)->update(['NOActive' => 0]);

            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Notifikasi berjaya dipadam.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Notifikasi tidak berjaya dipadam!'.$e->getMessage()
            ], 400);
        }



    }




}
