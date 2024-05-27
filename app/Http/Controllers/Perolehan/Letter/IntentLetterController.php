<?php

namespace App\Http\Controllers\Perolehan\Letter;

use App\Helper\Custom;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Models\Contractor;
use App\Models\EmailLog;
use App\Models\Project;
use App\Models\SSMCompany;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Mail;
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
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use App\Services\DropdownService;
use App\Models\TenderApplication;
use App\Models\TenderFormDetail;
use App\Models\TenderProcess;
use App\Models\TenderApplicationAuthSign;
use App\Models\TenderDetail;
use App\Models\TenderProposal;
use App\Models\TenderProposalDetail;
use App\Models\TenderProposalSpec;
use App\Models\FileAttach;
use App\Models\AutoNumber;
use App\Models\BoardMeeting;
use App\Models\BoardMeetingTender;
use App\Models\BoardMeetingProposal;
use App\Models\LetterIntent;
use App\Models\LetterIntentDet;
use App\Models\Notification;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\DataTables;

class IntentLetterController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){

        return view('perolehan.letter.intentLetter.index');
    }


    public function listProposal(){

        return view('perolehan.letter.intentLetter.listProposal');
    }

    public function create($id){

        $user = Auth::user();
        $meetingLocation = $this->dropdownService->meetingLocation();

        $tenderProposal = TenderProposal::where('TPNo', $id)
                        ->first();

        $TDANo = $tenderProposal->TP_TDANo;

        $tender = Tender::whereHas('tenderProposal', function($query){
            $query->where('TP_TPPCode','SB');
        })
        ->get()->pluck('TDTitle','TDNo')->map(function ($item, $key) {
            $code = Tender::where('TDNo', $key)->value('TDNo');
            return  $code . " - " . $item;
        });

        return view('perolehan.letter.intentLetter.create',
        compact('tender','tenderProposal','meetingLocation')
        );
    }

    public function store(Request $request){

        $messages = [
            'li_location.required'   => 'Lokasi diperlukan.',
            'li_date.required'  => 'Masa Mesyuarat diperlukan.',
            'li_time.required'   => 'Tarikh Mesyuarat diperlukan.',
            'terma.required'       => 'Sila isi sekurang-kurangnya satu terma.',
        ];

        $validation = [
            'li_location' => 'required|string',
            'li_date' => 'required',
            'li_time' => 'required',
            'terma'        => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $autoNumber = new AutoNumber();
            $LINo = $autoNumber->generateLetterIntent();

            $TPNo           = $request->TPNo;
            $li_location    = $request->li_location;
            $li_date        = $request->li_date;
            $li_time        = $request->li_time;
            $terma_id       = $request->terma_id;
            $terma          = $request->terma;

            $letterIntent = new LetterIntent();

            $letterIntent->LINo         = $LINo;
            $letterIntent->LI_TPNo      = $TPNo;
            $letterIntent->LILocation   = $li_location;
            $letterIntent->LIDate       = $li_date;
            $letterIntent->LITime       = $li_time;
            $letterIntent->LICB         = $user->USCode;
            $letterIntent->LIMB         = $user->USCode;
            $letterIntent->save();

            if(!empty($terma)){

                $sequence = 0;

                foreach($terma as $termaName){

                    if(!$termaName == ""){

                        $sequence++;

                        $letterIntentDet = new LetterIntentDet();
                        $letterIntentDet->LID_LINo       = $LINo;
                        $letterIntentDet->LIDSeq         = $sequence;
                        $letterIntentDet->LIDTermDesc    = $termaName;

                        $letterIntentDet->LIDCB         = $user->USCode;
                        $letterIntentDet->LIDMB         = $user->USCode;
                        $letterIntentDet->save();

                    }

                }

            }


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.letter.intent.edit',[$LINo]),
                'message' => 'Maklumat surat niat berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat surat niat tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }
    }

    public function edit($id){

        $user = Auth::user();
        $meetingLocation = $this->dropdownService->meetingLocation();
        $letterIntent = LetterIntent::where('LINo', $id)
                        ->first();

        $li_date = Carbon::parse($letterIntent->LIDate)->format('Y-m-d');
        $li_time = Carbon::parse($letterIntent->LITime)->format('H:i');

        $acceptStatus = $this->dropdownService->acceptStatus();

        return view('perolehan.letter.intentLetter.edit',
                compact('letterIntent','li_date','li_time','acceptStatus','meetingLocation')
        );
    }

    public function update(Request $request){

        $messages = [
            'li_location.required'   => 'Lokasi diperlukan.',
            'li_date.required'  => 'Masa Mesyuarat diperlukan.',
            'li_time.required'   => 'Tarikh Mesyuarat diperlukan.',
            'terma.required'       => 'Sila isi sekurang-kurangnya satu terma.',
        ];

        $validation = [
            'li_location' => 'required|string',
            'li_date' => 'required',
            'li_time' => 'required',
            'terma'        => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $LINo           = $request->LINo;
            $li_location    = $request->li_location;
            $li_date        = $request->li_date;
            $li_time        = $request->li_time;
            $terma_id       = $request->terma_id;
            $terma          = $request->terma;
            $updateStatus          = $request->updateStatus;

            $letterIntent = LetterIntent::where('LINo',$LINo)->first();

            $letterIntent->LILocation   = $li_location;
            $letterIntent->LIDate       = $li_date;
            $letterIntent->LITime       = $li_time;
            $letterIntent->LIMB         = $user->USCode;
            $letterIntent->save();

            if(!empty($terma)){


                $oldLetterIntentDet = LetterIntentDet::where('LID_LINo',$LINo)->get();

                //DELETE MEETING LetterIntentDet
                foreach ($oldLetterIntentDet as $oLetterIntentDet) {
                    if (!in_array($oLetterIntentDet->LIDID, $terma_id)) {
                        // DELETE
                        $oLetterIntentDet->delete();

                    }
                }

                //CHECK LetterIntentDet DATA
                $count = 0;
                $sequence = 0;

                foreach($terma_id as $tid){

                    $termadesc = $terma[$count];

                    if($tid == "new")
                        $tid = 0;

                    if(!$termadesc == "" || !empty($termadesc)){

                        $sequence++;

                        $existIntentDetail = LetterIntentDet::where('LID_LINo',$LINo)
                                        ->where('LIDID',$tid)
                                        ->first();

                        if(empty($existIntentDetail)){
                            //INSERT
                            $newIntentDetail = new LetterIntentDet();
                            $newIntentDetail->LID_LINo       = $LINo;
                            $newIntentDetail->LIDSeq         = $sequence;
                            $newIntentDetail->LIDTermDesc    = $termadesc;

                            $newIntentDetail->LIDCB         = $user->USCode;
                            $newIntentDetail->LIDMB         = $user->USCode;
                            $newIntentDetail->save();

                        }else{
                            //UPDATE
                            $existIntentDetail->LIDSeq         = $sequence;
                            $existIntentDetail->LIDTermDesc    = $termadesc;
                            $existIntentDetail->LIDMB         = $user->USCode;
                            $existIntentDetail->save();
                        }

                    }


                    $count++;


                }

            }

            if($updateStatus == 1){
                $this->updateStatus($LINo);
                $tenderProposal = $letterIntent->tenderProposal;
                $contractor = $tenderProposal->contractor;
                $phoneNo = $contractor->COPhone;
                $title = $tenderProposal->tender->TDTitle;
                $refNo = $tenderProposal->tender->TDNo;

//                $custom = new Custom();
//                $sendWS = $custom->sendWhatsappLetter('niat_notice',$phoneNo,$title,$refNo); //CONTOHWSLETTER
                DB::commit();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.letter.intent.index'),
                'message' => 'Maklumat surat niat berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat surat niat tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }
    }

    public function printLetter($id){


        $user = Auth::user();
        $letterIntent = LetterIntent::where('LINo', $id)
                        ->first();
        $contractor = $letterIntent->tenderProposal->contractor;
        $tenderProposal = $letterIntent->tenderProposal;

        $currentYear = Carbon::now()->year;

        $li_date = Carbon::parse($letterIntent->LIDate)->format('d/m/Y');
        $li_time = Carbon::parse($letterIntent->LITime)->format('h:i A');

        $submitDate = Carbon::parse($letterIntent->LISubmitDate)->format('d/m/Y');

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$letterIntent->tenderProposal->contractor->COReg_StateCode];

        $meetingLocation = $this->dropdownService->meetingLocation();
        $location = $meetingLocation[$letterIntent->LILocation] ?? $letterIntent->LILocation;

        // $qrCode = QRcode::size(80)->generate($letterIntent->LINo);

        $contractAddress = $contractor->CORegAddr . ", " . $contractor->CORegPostcode . ", " . $contractor->CORegCity . ", " . $COState ;

        $qrvalue = $letterIntent->LINo . " | " . $contractor->COName . " | " . $contractAddress . " | " . $tenderProposal->tender->TDTitle;

        $qrCode = QRcode::size(80)->generate($qrvalue);

        //dd($qrCode , $letterIntent->LINo);

        $template = "LETTER";
        $download = false; //true for download or false for view
        $templateName = "INTENT"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF'
        ,compact('template','templateName','letterIntent','li_date','li_time',
        'submitDate','COState' , 'qrCode' , 'currentYear' , 'location')
        );
        $response = $this->generatePDF($view,$download);

        return $response;

    }

    public function updateStatus($id){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $trpcode = "LI";

            $dateNow = Carbon::now()->format('Y-m-d');

            $letterIntent = LetterIntent::where('LINo',$id)->first();
            $letterIntent->LISubmitBy = $user->USCode;
            $letterIntent->LISubmitDate = $dateNow;
            $letterIntent->LIMB = $user->USCode;
            $letterIntent->save();

            $project = Project::where('PT_TPNo', $letterIntent->tenderProposal->TPNo)
                ->where('PT_TDNo', $letterIntent->tenderProposal->TP_TDNo)
                ->first();

//            $project->PT_PPCode = 'LIS-RQ';
            $project->PT_PPCode = 'LIS';
            $project->save();

            $this->processSentActivation($id);

//            $approvalController = new ApprovalController(new DropdownService(), new AutoNumber());
//
//            $approvalController->storeApproval($id, 'LI-SM');

            DB::commit();

            return redirect()->route('perolehan.letter.intent.index');

            // return response()->json([
            //     'success' => '1',
            //     'redirect' => route('perolehan.letter.intent.index'),
            //     'message' => 'Maklumat surat niat berjaya dikemaskini.'
            // ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat surat niat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function processSentActivation($id){
        try {

            DB::beginTransaction();

            $user = Auth::user();

            $trpcode = "LI";

            $dateNow = Carbon::now()->format('Y-m-d');

            $letterIntent = LetterIntent::where('LINo',$id)->first();
            $letterIntent->LIStatus = "SUBMIT";
            $letterIntent->save();

            $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LI_TPNo)->first();
            $tenderProposal->TP_TRPCode = $trpcode;
            $tenderProposal->save();

            $tender = Tender::where('TDNo',$letterIntent->tenderProposal->TP_TDNo)->first();

            $project = Project::where('PT_TPNo', $letterIntent->tenderProposal->TPNo)
                ->where('PT_TDNo', $letterIntent->tenderProposal->TP_TDNo)
                ->first();

            $contractor = Contractor::where('CONo',$letterIntent->tenderProposal->TP_CONo)->first();

            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCode;
            $emailLog->ELType 	= 'Activate Code';
            // Send Email
            $emailData = array(
                'id' => $contractor->CONo,
                'name'  => $contractor->COName ?? '',
                'email' => $contractor->COEmail,
                'activationCode' => $project->PTActivationCode ?? '',
                'domain' => config('app.url'),
                'projectCode' => $project->PTNo,
            );

            try {
                Mail::send(['html' => 'email.newUserProject'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pengaktifan Akaun Projek');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
                $emailLog->save();
            } catch (\Exception $e) {
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
                $emailLog->save();
            }

            // $updateStatus = "TR";
            // $tender->TD_TPCode = $updateStatus;
            // $tender->save();

            $resultNoti = $this->sendNotification($letterIntent);

            $phoneNo = $contractor->COPhone;
            $title = $tenderProposal->tender->TDTitle;
            $refNo = $tenderProposal->tender->TDNo;
            $refNo2 = $project->PTNo;
            $refNo3 = $project->PTActivationCode;

//            $custom = new Custom();
//            $sendWS = $custom->sendWhatsappLetter('niat_notice',$phoneNo,$title,$refNo); //CONTOHWSLETTER
//            $sendWSPTActive = $custom->sendWhatsappLetter('active_project_notice',$phoneNo,$title,$refNo,$refNo2,$refNo3); //CONTOHWSLETTER

            DB::commit();

            return true;
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return false;
        }
    }


    public function letterIntentDatatable(Request $request){

        $user = Auth::user();
        $meetingLocation = $this->dropdownService->meetingLocation();

        $query = LetterIntent::orderBy('LINo' , 'desc')->get();

        $letterStatus = $this->dropdownService->letterStatus();
        $count = 0;

        return DataTables::of($query)
            ->editColumn('LINo', function($row) {

                $route = route('perolehan.letter.intent.edit',[$row->LINo]);
                $result = '<a href="'.$route.'">'.$row->LINo.'</a>';

                return $result;
            })
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('LILocation', function($row) use(&$meetingLocation) {

                return $meetingLocation[$row->LILocation] ?? $row->LILocation;

            })
            ->editColumn('LIDate', function($row) {
                $formattedDate = null;
                if($row->LIDate != null){
                    $carbonDatetime = Carbon::parse($row->LIDate);
                    $formattedDate = $carbonDatetime->format('d/m/Y');
                }

                return $formattedDate;
            })
            ->editColumn('LITime', function($row) {
                $formattedDate = null;
                if($row->LITime != null){
                    $carbonDatetime = Carbon::parse($row->LITime);
                    $formattedDate = $carbonDatetime->format('h:i A');
                }

                return $formattedDate;
            })
            ->addColumn('status', function($row) use ($letterStatus) {

                return $letterStatus[$row->LIStatus];
            })
            ->editColumn('LIMD', function($row) {
                return [
                    'display' => e(carbon::parse($row->LIMD)->format('d/m/Y h:ia')),
                    'timestamp' => carbon::parse($row->LIMD)->timestamp
                ];

            })
            ->addColumn('action', function($row) {

                $route = route('perolehan.letter.intent.edit',[$row->LINo]);
                $routePrint = route('perolehan.letter.intent.printLetter',[$row->LINo]);

                // $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light btn btn-primary">Papar</a>';
                $result = '&nbsp<a target="_blank" href="'.$routePrint.'" class="new modal-trigger waves-effect waves-light btn btn-light-primary"><i class="material-icons left">print</i>Cetak</a>';


                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['status', 'action','LINo'])
            ->make(true);
    }

    public function proposalDatatable(Request $request){

        $user = Auth::user();

        $query = TenderProposal::where('TP_CONo', $user->USCode)
                ->where('TP_TPPCode', 'SB')
                ->get();

        $proposalProcess = $this->dropdownService->proposalProcess();
        $count = 0;

        return DataTables::of($query)
            ->addColumn('TDTitle', function($row) {

                return $row->tender->TDTitle;
            })
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('TPSubmitDate', function($row) {
                $formattedDate = null;
                if($row->TPSubmitDate != null){
                    $carbonDatetime = Carbon::parse($row->TPSubmitDate);
                    $formattedDate = $carbonDatetime->format('d/m/Y');
                }

                return $formattedDate;
            })
            ->addColumn('status', function($row) use ($proposalProcess) {

                return $proposalProcess[$row->TP_TPPCode];
            })
            ->addColumn('action', function($row) {

                if(isset($row->letterIntent)){

                    $route = route('perolehan.letter.intent.edit',[$row->letterIntent->LINo]);
                    $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light btn btn-light-primary">Lihat Surat Niat</a>';

                }else{
                    $route = route('perolehan.letter.intent.create',[$row->TPNo]);
                    $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light btn btn-primary">Sedia Surat Niat</a>';

                }


                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['TPSubmitDate', 'status', 'action','TDTitle'])
            ->make(true);
    }


    function sendNotification($letterIntent){

        //#SEND-NOTIFICATION-EXAMPLE
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $tenderProposal = TenderProposal::where('TPNo',$letterIntent->LI_TPNo)->first();
            $tender = Tender::where('TDNo',$tenderProposal->TP_TDNo)->first();

            $notiType = "LI";

            //##NOTIF-027
            //SEND NOTIFICATION TO PIC - PELAKSANA
            $tenderPIC = $tender->tenderPIC_T;
            $pelaksanaType = "SO";

            $title = "Penganjuran Surat Niat - $letterIntent->LINo";
            $desc = "Perhatian, surat niat $letterIntent->LINo bagi cadangan $tenderProposal->TPNo telah berjaya dihantar.";

            if(!empty($tenderPIC)){

                foreach($tenderPIC as $pic){

                    $notification = new Notification();
                    $notification->NO_RefCode = $pic->TPIC_USCode;
                    $notification->NOType = $pelaksanaType;
                    $notification->NO_NTCode = $notiType;
                    $notification->NOTitle = $title;
                    $notification->NODescription = $desc;
                    $notification->NORead = 0;
                    $notification->NOSent = 1;
                    $notification->NOActive = 1;
                    $notification->NOCB = $user->USCode;
                    $notification->NOMB = $user->USCode;
                    $notification->save();
                }

            }

            //##NOTIF-028
            //SEND NOTIFICATION TO PUBLIC USER
            $pelaksanaType = "PU";

            $title2 = "Penganjuran Surat Niat - $letterIntent->LINo";
            $desc2 = "Tahniah, surat niat $letterIntent->LINo bagi cadangan $tenderProposal->TPNo telah dianjurkan. Sila lengkapkan surat niat dengan maklumat yang berkenaan.";

            $contractorNo = $tenderProposal->TP_CONo;

            $notification = new Notification();
            $notification->NO_RefCode = $contractorNo;
            $notification->NOType = $pelaksanaType;
            $notification->NO_NTCode = $notiType;
            $notification->NOTitle = $title2;
            $notification->NODescription = $desc2;
            $notification->NORead = 0;
            $notification->NOSent = 1;
            $notification->NOActive = 1;
            $notification->NOCB = $user->USCode;
            $notification->NOMB = $user->USCode;
            $notification->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'message' => 'Maklumat notifikasi berjaya dihantar.',
            ], 400);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat notifikasi tidak berjaya dihantar!'.$e->getMessage()
            ], 400);
        }

    }


}
