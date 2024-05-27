<?php

namespace App\Http\Controllers\Perolehan\Meeting;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SSMCompany;
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
use App\Models\EmailLog;
use App\Models\Meeting;
use App\Models\MeetingAttendanceList;
use App\Models\MeetingNP;
use App\Models\Notification;
use Yajra\DataTables\DataTables;
use Mail;

class MeetingNPController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index($id){

        // $meetingProposalNo = $id;
        $tenderProposalNo = $id;

        $tenderProposal = TenderProposal::where('TPNo',$tenderProposalNo)->first();
        $meetingProposal = $tenderProposal->meetingProposal;
        $meetingProposalNo = $meetingProposal->BMPNo;

        return view('perolehan.meeting.meetingNego.index',compact('meetingProposalNo','tenderProposal','meetingProposal'));
    }


    public function viewMeeting(Request $request){
        $meetingLocation = $this->dropdownService->meetingLocation();

        $tenderProposalNo = $request->tenderProposalNo;
        $mno = $request->meetingNo;

        $tenderProposal = TenderProposal::where('TPNo',$tenderProposalNo)->first();
        $meetingProposal = $tenderProposal->meetingProposal;
        $meetingProposalNo = $meetingProposal->BMPNo;

        $meeting = Meeting::where('MNo',$mno)->first();
        $meetingType = $this->dropdownService->meetingTypeAll();
        $tmscode = $this->dropdownService->meetingStatus();
        $crscode = $this->dropdownService->claimResultStatus();

        $meetingNP = null;

        $meetingAttendanceLists = MeetingAttendanceList::where('MAL_MNo',$mno)->get();
        $departmentAll = $this->dropdownService->departmentAll();

        if ($meeting == null || !isset($meeting)) {

            $countPTMeeting = MeetingNP::get()->count();

            $meetingDate = null;
            $meetingTime = null;
            $meetingNP = null;
            $meetingTitle = $this->createMeetingTitle('MNP');

        } else {
            $meetingDate = Carbon::parse($meeting->MDate)->format('Y-m-d');
            $meetingTime = Carbon::parse($meeting->MTime)->format('H:i');
            $meetingTitle = $meeting->MTitle;

            $meetingNP = MeetingNP::where('MNP_MNo',$mno)->where('MNP_TPNo',$tenderProposalNo)->first();

        }

        $meeting['meetingDate'] = $meetingDate;
        $meeting['meetingTime'] = $meetingTime;
        $meeting['meetingTitle'] = $meetingTitle;

        $canEdit = 0;

        if( isset($meeting) && isset($meeting->meetingNP) && count($meeting->meetingNP) == 1 ){
            $canEdit = 1;
        }

        $fileAttachDownloadMN = FileAttach::where('FAFileType','MT-MN')->first();

        return view('perolehan.meeting.meetingNego.include.viewMeeting',
        compact('meetingProposalNo','meetingType','meeting','tmscode','crscode','meetingLocation','fileAttachDownloadMN','tenderProposalNo',
        'tenderProposal','meetingNP','meetingAttendanceLists','departmentAll','canEdit')
        );

    }

    public function updateFinal(Request $request, $meetingProposalNo){
        try{
            DB::beginTransaction();

            $user = Auth::user();

            $haveError = false;
            $errorMsg = "";

            $meetingNP = MeetingNP::where('MNP_BMPNo',$meetingProposalNo)->orderBy('MNPID','DESC')->first();

            if($meetingNP){

                if($meetingNP->meeting->MStatus == "SUBMIT"){

                    $allDoneMeetingNP = MeetingNP::where('MNP_BMPNo',$meetingProposalNo)
                    ->where('MNP_MSCode','D')
                    ->orderBy('MNPID','DESC')
                    ->first();

                    if($allDoneMeetingNP){

                        $boardMeetingProposal = BoardMeetingProposal::where('BMPNo',$meetingProposalNo)->first();
                        $boardMeetingProposal->BMPPriceNegoMeeting = 1;
                        $boardMeetingProposal->save();

                        $tenderProposal = $boardMeetingProposal->tenderProposal;
                        $tender = $tenderProposal->tender;

                        $project = Project::where('PT_TPNo', $tenderProposal->TPNo)
                            ->where('PT_TDNo', $tender->TDNo)
                            ->first();

                        // if($tender->TDLOI == 1){
                        if($tender->meetingTender->BMTLOI != 1){
                            $tenderProposal->TP_TRPCode = 'MLI';
                            $project->PT_PPCode = 'NPS';

                        }else{
                            $tenderProposal->TP_TRPCode = 'MLA';
                            $project->PT_PPCode = 'NPS';
                        }
                        $tenderProposal->save();
                        $project->save();


                    }else{
                        $haveError = true;
                        $errorMsg = "Tiada mesyuarat yang berstatus 'Selesai' didalam rekod. Sila adakan mesyuarat dengan status 'Selesai' terlebih dahulu.";

                    }



                }else{

                    $haveError = true;
                    $errorMsg = "Mesyuarat terakhir masih belum selesai. Sila kemaskini status mesyuarat.";

                }

            }else{
                $haveError = true;
                $errorMsg = "Tiada mesyuarat didalam rekod. Sila adakan mesyuarat terlebih dahulu.";

            }

            if($haveError == false){

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('perolehan.negoMeeting.list',[$meetingNP->MNP_TPNo]),
                    'message' => 'Maklumat mesyuarat berjaya dikemaskini.'
                ]);

            }else{

                return response()->json([
                    'error' => '1',
                    'message' => $errorMsg
                ], 400);

            }

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini! '.$e->getMessage()
            ], 400);
        }


    }

    //{{--Working Code Datatable--}}
    public function meetingNegoDatatable(Request $request){

        $user = Auth::user();

        $tenderProposalNo = $request->tenderProposalNo;


        $query = Meeting::
        whereHas('meetingNP', function ($query) use ($tenderProposalNo) {
            $query->where('MNP_TPNo', $tenderProposalNo);
        })
        ->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('MNo', function($row){

                $result = '<a href="#" id="MT'.$row->MNo.'" data-bs-toggle="modal" data-bs-stacked-modal="#meetingModal" onclick="openModalMesyuarat(\'' . $row->MNo . '\',\'' . $row->MStatus . '\',\'' . $row->MSSentInd . '\')">' . $row->MNo . '</a>';

                return $result;
            })
            ->editColumn('MDate', function($row){

                if(empty($row->MDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->MDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;

            })
            ->editColumn('MTime', function($row){

                if(empty($row->MTime)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->MTime);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('h:i A');

                }

                return $formattedDate;
            })
            ->editColumn('MStatus', function($row) {

                $boardMeetingStatus = $this->dropdownService->boardMeetingStatus();

                return $boardMeetingStatus[$row->MStatus];

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['MNo','MDate','MTime','MStatus'])
            ->make(true);
    }

    public function cetakResult($id){
        $yt = $this->dropdownService->yt();

        $boardMeetingProposal = BoardMeetingProposal::where('BMPNo',$id)->first();
        $contractor = $boardMeetingProposal->tenderProposal->contractor;

        $meetingNP = MeetingNP::where('MNP_BMPNo',$id)->orderBy('MNPID','DESC')->first();

        $totalDiscount = MeetingNP::where('MNP_MSCode','D')->where('MNP_BMPNo',$id)->get()->sum('MNPDiscAmt');

        $tenderProposal = $meetingNP->tenderProposal;

        $originalAmount = $tenderProposal->TPTotalAmt;

        $finalAmount = $meetingNP->MNPFinalAmt;
        $proposeAmount = $meetingNP->MNPProposalAmt;
        $discAmount = $originalAmount - $finalAmount;

        $totalPercent = (($originalAmount - $finalAmount) / $originalAmount) * 100;

        $meetingNP->originalAmount = number_format($originalAmount,2,'.',',');
        $meetingNP->proposeAmount = number_format($proposeAmount,2,'.',',');
        $meetingNP->finalAmount = number_format($finalAmount,2,'.',',');
        $meetingNP->discAmount = number_format($discAmount,2,'.',',');
        $meetingNP->discPercent = number_format($totalPercent,2,'.',',');

        $meeting = $meetingNP->meeting;
        $meetingDate = Carbon::parse($meeting->MDate)->isoFormat('D MMMM YYYY');
        $meeting->meetingDate = $meetingDate;

        $download = false;

        $filename = "KEPUTUSAN MESYUARAT JAWATANKUASA RUNDINGAN HARGA - " . $id;

        $view = View::make('perolehan.meeting.meetingNego.resultPDF'
        // return view('perolehan.meeting.meetingNego.resultPDF'
        ,compact('meeting','meetingNP','contractor')
        );
        $response = $this->generatePDF($view,$download,$filename);

        return $response;
    }

}
