<?php

namespace App\Http\Controllers\Perolehan\Announcement;

use App\Http\Controllers\Controller;
use App\Models\SSMCompany;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Illuminate\Support\Facades\Storage;


use App\Http\Requests;
use App\Models\Announcement;
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
use App\Models\Notification;
use App\Models\Project;
use App\Models\VariantOrder;
use App\Models\VariantOrderDet;
use App\Models\VariantOrderSpec;
use Yajra\DataTables\DataTables;
use Mail;

class AnnouncementController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){

        return view('perolehan.announcement.index');
    }

    public function create(){

        $statusActive = $this->dropdownService->statusActive();

        return view('perolehan.announcement.create',compact('statusActive'));
    }

    public function store(Request $request){

        $messages = [
            'title.required'        => 'Tajuk berita diperlukan.',
            'description.required'  => 'Keterangan berita diperlukan.',
            'publishDate.required'  => 'Tarikh terbitan diperlukan.',
            'status.required'       => 'Status berita diperlukan.',
        ];

        $validation = [
            'title' => 'required',
            'description' => 'required',
            'publishDate' => 'required',
            'status' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $berita = new Announcement();
            $berita->ACTitle = $request->title;
            $berita->ACDesc = $request->description;
            $berita->ACDate = $request->publishDate;
            $berita->ACActive = $request->status;
            $berita->ACCB = $user->USCode;
            $berita->ACMB = $user->USCode;
            $berita->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.berita.edit', [$berita->ACID]),
                'message' => 'Maklumat berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }

    public function edit($id){

        $berita = Announcement::where('ACID',$id)->first();
        $berita->publishDate = Carbon::parse($berita->ACDate)->format('Y-m-d');

        $statusActive = $this->dropdownService->statusActive();

        return view('perolehan.announcement.edit',
        compact('berita','statusActive')
        );

    }

    public function update(Request $request,$id){

        $messages = [
            'title.required'        => 'Tajuk berita diperlukan.',
            'description.required'  => 'Keterangan berita diperlukan.',
            'publishDate.required'  => 'Tarikh terbitan diperlukan.',
            'status.required'       => 'Status berita diperlukan.',
        ];

        $validation = [
            'title' => 'required',
            'description' => 'required',
            'publishDate' => 'required',
            'status' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $berita = Announcement::where('ACID',$id)->first();
            $berita->ACTitle = $request->title;
            $berita->ACDesc = $request->description;
            $berita->ACDate = $request->publishDate;
            $berita->ACActive = $request->status;
            $berita->ACMB = $user->USCode;
            $berita->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.berita.edit', [$berita->ACID]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function delete(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $berita = Announcement::where('ACID',$request->beritaID)->first();
            $berita->delete();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.berita.index'),
                'message' => 'Maklumat berjaya dipadam.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dipadam!'.$e->getMessage()
            ], 400);
        }

    }

    // {{--Working Code Datatable--}}
    public function announcementDatatable(){

        $query = Announcement::orderBy('ACDate', 'DESC')->get();

        return DataTables::of($query)
            ->editColumn('ACDate', function($row) {
                $carbonDatetime = Carbon::parse($row->ACDate);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })

            ->editColumn('ACTitle', function($row) {
                $route = route('perolehan.berita.edit',[$row->ACID]);
                $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light">'.$row->ACTitle.'</a>';
                return $result;
            })
            ->editColumn('ACCD', function($row) {


                // Create a Carbon instance from the MySQL datetime value
                $carbonDatetime = Carbon::parse($row->ACCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i');

                return $formattedDate;

            })
            ->editColumn('ACActive', function($row) {
                return $row->ACActive == 1 ? 'Aktif' : 'Tidak Aktif';

            })
            ->addColumn('action', function($row) {

                $routeDelete = route('perolehan.berita.delete',[$row->ACID]);
                $result = "";

                if($row->ACActive == 0){

                    $result = '<button onclick="deleteBerita(\' '.$row->ACID.' \')" class="btn btn-sm btn-danger"><i class="ki-solid ki-trash fs-2"></i></button>';

                }
                return $result;

            })
            ->rawColumns(['ACDate','ACTitle','ACActive','ACCD','action'])
            ->make(true);
    }

}
