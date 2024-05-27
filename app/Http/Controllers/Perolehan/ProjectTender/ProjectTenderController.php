<?php

namespace App\Http\Controllers\Perolehan\ProjectTender;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\CommentLog;
use App\Models\ExtensionOfTime;
use App\Models\FileAttach;
use App\Models\Project;
use App\Models\ProjectClaim;
use App\Models\ProjectClaimDet;
use App\Models\ProjectInvoice;
use App\Models\ProjectMilestone;
use App\Models\TemplateClaimFile;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Cassandra\Exception\ExecutionException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Yajra\DataTables\DataTables;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Meeting;
use App\Models\MeetingAttendanceList;
use App\Models\MeetingPT;
use App\Models\MeetingType;
use App\Models\Notification;
use App\Models\ProjectTender;
use App\Models\ProjectTenderDept;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class ProjectTenderController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        return view('perolehan.projectTender.index'
        );
    }

    public function create(){

        $projectType = $this->dropdownService->jenis_projek();
        $department = $this->dropdownService->department();
        $projectTender = null;

        return view('perolehan.projectTender.create',
        compact('projectType','department','projectTender')
        );
    }

    public function store(Request $request){

        $messages = [
            'title.required'          => 'Tajuk project diperlukan.',
            'duration.required'       => 'Tempoh project diperlukan.',
            'amount.required'         => 'Harga project diperlukan.',
            'projectType.required'    => 'Jenis Projek diperlukan.',
            'department.required'    => 'Jenis jabatan diperlukan.',
        ];

        $validation = [
            'title'      => 'required',
            'duration'      => 'required',
            'amount'      => 'required',
            'department'      => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $projectTenderNo = $this->autoNumber->generateProjectTenderNo();

            $projectTender = new ProjectTender();
            $projectTender->PTDNo = $projectTenderNo;
            $projectTender->PTDTitle = $request->title;
            $projectTender->PTDPeriod = $request->duration;
            $projectTender->PTDAmt = $request->amount;
            $projectTender->PTD_PTCode = $request->projectType;
            $projectTender->PTD_DPTCode = $request->department;
            $projectTender->PTDCB = $user->USCode;
            $projectTender->PTDMB = $user->USCode;
            $projectTender->save();

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => route('perolehan.projectTender.edit',[$projectTenderNo]),
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }
    }

    public function edit($id){

        $projectType = $this->dropdownService->jenis_projek();
        $department = $this->dropdownService->department();
        $meetingType = $this->dropdownService->meetingTypeAll();
        $projectTenderStatus = $this->dropdownService->projectTenderStatus();
        $departmentAll = $this->dropdownService->departmentAll();

        $projectTender = ProjectTender::where('PTDNo',$id)->first();

        $arrayDepartment = [];

        foreach($projectTender->projectTenderDept as $tenderDepart){

            array_push($arrayDepartment, $tenderDepart->PTDD_DPTCode);

        }

        $ptscode = $projectTender->PTD_PTSCode ?? 'NEW';

        $projectTender->statusProjek =  $projectTenderStatus[$ptscode] ;


        return view('perolehan.projectTender.edit',
        compact('projectType','department','projectTender','arrayDepartment','meetingType','departmentAll')
        );
    }


    public function update(Request $request){

        $messages = [
            'title.required'          => 'Tajuk project diperlukan.',
            'duration.required'       => 'Tempoh project diperlukan.',
            'amount.required'         => 'Harga project diperlukan.',
            'projectType.required'    => 'Jenis Projek diperlukan.',
            'department.required'    => 'Jenis jabatan diperlukan.',
        ];

        $validation = [
            'title'      => 'required',
            'duration'      => 'required',
            'amount'      => 'required',
            'department'      => 'required',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();
            $projectTenderNo = $request->projectTenderNo;

            $projectTender = ProjectTender::where('PTDNo',$projectTenderNo)->first();
            $projectTender->PTDTitle = $request->title;
            $projectTender->PTDPeriod = $request->duration;
            $projectTender->PTDAmt = $request->amount;
            $projectTender->PTD_PTCode = $request->projectType;
            $projectTender->PTD_DPTCode = $request->department;
            $projectTender->PTDResult = $request->result;
            $projectTender->PTDMB = $user->USCode;

            if($request->departNo){

                $datas = $request->departNo;

                $oldDatas = ProjectTenderDept::where('PTDD_PTDNo',$projectTenderNo)->get();

                foreach($request->departNo as $data){


                    $exists = $oldDatas->contains('PTDD_DPTCode', $data);

                    if (!$exists) {
                        //INSERT NEW DATA
                        $tenderDept = new ProjectTenderDept();
                        $tenderDept->PTDD_PTDNo      = $projectTenderNo;
                        $tenderDept->PTDD_DPTCode    = $data;
                        $tenderDept->PTDDCB          = $user->USCode;
                        $tenderDept->PTDDMB          = $user->USCode;
                        $tenderDept->save();

                    }

                }

                //DELETE DATA and UPDATE STATUS
                foreach ($oldDatas as $oldData) {

                    if (!in_array($oldData->PTDD_DPTCode, $datas)) {

                        // DELETE
                        $oldData->delete();

                    }

                }

            }

            if($request->updateStatus == 1){

                $projectTender->PTDComplete = 1;
                $projectTender->PTD_PTSCode = 'COMPLETE';

            }
            else if($request->updateStatus == 2){

                $projectTender->PTDComplete = 1;
                $projectTender->PTD_PTSCode = 'CANCEL';

            }

            $projectTender->save();

            DB::commit();

			return response()->json([
				'success' => '1',
                'redirect' => route('perolehan.projectTender.edit',[$projectTenderNo]),
				'message' => 'Maklumat berjaya dikemaskini.'
			]);


        } catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }
    }

    public function viewMeeting(Request $request){

        $projectTenderNo = $request->projectTenderNo;
        $mno = $request->meetingNo;
        $meetingLocation = $this->dropdownService->meetingLocation();

        $meeting = Meeting::where('MNo',$mno)->first();
        $meetingType = $this->dropdownService->meetingTypeAll();
        $tmscode = $this->dropdownService->tenderMeetingStatus();
        $crscode = $this->dropdownService->claimResultStatus();

        $meetingAttendanceLists = MeetingAttendanceList::where('MAL_MNo',$mno)->get();
        $departmentAll = $this->dropdownService->departmentAll();

        if ($meeting == null || !isset($meeting)) {
            $meetingDate = null;
            $meetingTime = null;
            $meetingTitle = $this->createMeetingTitle('MPT');
        } else {
            $meetingDate = Carbon::parse($meeting->MDate)->format('Y-m-d');
            $meetingTime = Carbon::parse($meeting->MTime)->format('H:i');
            $meetingTitle = $meeting->MTitle;
        }

        $meeting['meetingDate'] = $meetingDate;
        $meeting['meetingTime'] = $meetingTime;
        $meeting['meetingTitle'] = $meetingTitle;

        $fileAttachDownloadMN = FileAttach::where('FAFileType','MT-MN')
            ->first();

        return view('perolehan.projectTender.include.viewMeeting',
        compact('projectTenderNo','meetingType','meeting','tmscode','crscode','meetingAttendanceLists',
            'departmentAll', 'meetingLocation', 'fileAttachDownloadMN')
        );

    }

    public function projectTenderDatatable(Request $request){

        $user = Auth::user();

        $query = ProjectTender::where('PTD_PTSCode','COMPLETE')->orderBy('PTDNo', 'DESC')->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('PTDNo', function($row){

                $route = route('perolehan.projectTender.edit',[$row->PTDNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->PTDNo.' </a>';

                return $result;
            })
            ->editColumn('PTD_PTCode', function($row) {

                $type = $this->dropdownService->jenis_projek();

                return $type[$row->PTD_PTCode];

            })
            ->editColumn('PTD_DPTCode', function($row) {

                $type = $this->dropdownService->department();

                return $type[$row->PTD_DPTCode];

            })
            ->editColumn('PTD_PTSCode', function($row) {

                $type = $this->dropdownService->projectTenderStatus();

                return $type[$row->PTD_PTSCode ?? 'NEW'];

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','PTDNo','PTD_PTCode','PTD_DPTCode','PTD_PTSCode'])
            ->make(true);


    }


    public function mesyuaratDatatable(Request $request){

        $user = Auth::user();

        $ptdNo = $request->ptdNo;

        $query = Meeting::whereHas('meetingPT', function ($query2) use ($ptdNo) {
            $query2->where('MPT_PTDNo', $ptdNo);
        })
        ->get();

        return DataTables::of($query)
            ->addColumn('mType', function($row) {
                $result = '';

                foreach($row->meetingDet as $key => $meetingDet){
                    $meetingType = MeetingType::where('MTCode', $meetingDet->MD_MTCode)->first();

                    if($key == 0){
                        $result .= $meetingType->MTDesc;
                    }
                    else{
                        $result .= ', ' . $meetingType->MDesc;
                    }

                }

                return $result;
            })
            ->editColumn('MNo', function($row){

                $result = '<a href="#" id="MT'.$row->MNo.'" data-bs-toggle="modal" data-bs-stacked-modal="#meetingModal"  onclick="openModalMesyuarat(\'' . $row->MNo . '\',\'' . $row->MStatus . '\',\'' . $row->MSSentInd . '\')">
                ' . $row->MNo . '
                </a>';
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
            ->rawColumns(['mType', 'MNo','MDate','MTime','MStatus'])
            ->make(true);
    }

}
