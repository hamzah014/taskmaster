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
use Yajra\DataTables\DataTables;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\LetterAcceptance;
use App\Models\LetterIntent;
use App\Models\Tender;
use App\Models\Project;
use App\Models\FileType;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class PerolehanController extends Controller
{

    public function index(){
        
        $currentYear = Carbon::now()->year;

        $startDate = $currentYear . '-01-01';
        $endDate = $currentYear . '-12-31'; 

        $jumlahtender = Tender::whereBetween('TDPublishDate', [$startDate, $endDate])
            ->orWhereBetween('TDClosingDate', [$startDate, $endDate])
            ->count();

        $iklantender = Tender::where('TD_TPCode' , 'PA')->count();
        $awardcontractor = Project::whereBetween('PTStartDate', [$startDate, $endDate])
            ->orWhereBetween('PTEndDate', [$startDate, $endDate])
            ->count();

        // dd($awardcontractor);

        //dd($jumlahtender , $iklantender);

        $bukapeti = Tender::where('TD_TPCode' , 'CA')->count();
        $mesyuaratTender = Tender::where('TD_TPCode' , 'IM')->count();
        $intentWaiting = LetterIntent::where('LIStatus' , 'SUBMIT')->count();
        $intentReject = LetterIntent::where('LIStatus' , 'REJECT')->count();
        $acceptanceWaiting = LetterAcceptance::where('LAStatus' , 'SUBMIT')->count();
        $acceptanceReject = LetterAcceptance::where('LAStatus' , 'REJECT')->count();


        return view('perolehan.index' , compact('jumlahtender','iklantender' , 'awardcontractor' ,'intentWaiting' , 'intentReject' , 'acceptanceWaiting'
                            , 'acceptanceReject' , 'bukapeti' , 'mesyuaratTender'));
    }

    public function index_auto(){

		if (Auth::attempt(['USCode' => 'PO1000', 'password' => '123456', 'USActive' => 1])) {
			// The user is active, not suspended, and exists.
			//return view('home');

            $currentYear = Carbon::now()->year;

            $startDate = $currentYear . '-01-01';
            $endDate = $currentYear . '-12-31'; 

            $jumlahtender = Tender::whereBetween('TDPublishDate', [$startDate, $endDate])
                ->orWhereBetween('TDClosingDate', [$startDate, $endDate])
                ->count();

            $iklantender = Tender::where('TD_TPCode' , 'PA')->count();
            $awardcontractor = Project::whereBetween('PTStartDate', [$startDate, $endDate])
                ->orWhereBetween('PTEndDate', [$startDate, $endDate])
                ->count();

            // dd($awardcontractor);

            //dd($jumlahtender , $iklantender);

            $bukapeti = Tender::where('TD_TPCode' , 'CA')->count();
            $mesyuaratTender = Tender::where('TD_TPCode' , 'IM')->count();
            $intentWaiting = LetterIntent::where('LIStatus' , 'SUBMIT')->count();
            $intentReject = LetterIntent::where('LIStatus' , 'REJECT')->count();
            $acceptanceWaiting = LetterAcceptance::where('LAStatus' , 'SUBMIT')->count();
            $acceptanceReject = LetterAcceptance::where('LAStatus' , 'REJECT')->count();

            

            //dd($x);

			Session::put('page', 'perolehan');
            return view('perolehan.index' , compact('jumlahtender','iklantender' , 'awardcontractor' ,'intentWaiting' , 'intentReject' , 'acceptanceWaiting'
                            , 'acceptanceReject' , 'bukapeti' , 'mesyuaratTender'));

		}
    }

    public function setting(){

        return view('perolehan.setting');
    }

    public function documentlist(){


        return view('perolehan.documentlist');
    }

    public function FileTypeDataTable(Request $request)
    {
        $query = FileType::whereIn('FTCode', ['PT-BAF', 'PT-AF', 'TFE', 'TDFE', 'TFD'])->get();

        //dd($query);
        $count = 0;

        return DataTables::of($query)
            ->addColumn('actions', function ($fileType) {
                return '<a href="#" onclick="openUploadModal('.$fileType->id.')" class="new modal-trigger waves-effect waves-light btn btn-light-primary">
                        <i class="material-icons">file_upload</i>
                        </a>
                        <button class="btn btn-light-primary" data-id="'.$fileType->id.'"><i class="material-icons left">visibility</i>Papar</button>';
            })
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            // ->addColumn('fail_redirect', function($row) {

            //     $result = '';
            //     if(!empty($row->fileAttach)){
            //         // $showhide = "";

            //         // $folderPath	 = $row->fileAttach->FAFilePath;
            //         // $newFileName = $row->fileAttach->FAFileName;
            //         // $newFileExt	 = $row->fileAttach->FAFileExtension;

            //         // $refNo	 = $row->fileAttach->FAFileExtension;

            //         // $filePath = $folderPath.'\\'.'.'.$newFileExt;

            //         // $fileguid = $row->fileAttach->FAGuidID;

            //         // $route = route('file.view', ['fileGuid' => $fileguid]);

            //         // $result = '<a class="btn btn-light-primary btn-sm" target="_blank" href="'.$route.'">Papar Fail</a>';

            //     }

            //     return $result;
            // })

            ->addColumn('document', function ($fileType) {
                return $fileType->FTDesc;
            })
            ->rawColumns(['actions'])
            ->make(true);
            
    }
}
