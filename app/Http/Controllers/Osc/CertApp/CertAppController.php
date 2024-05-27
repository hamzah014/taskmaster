<?php

namespace App\Http\Controllers\Osc\CertApp;

use App\Http\Controllers\Controller;
use App\Models\CertApp;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\CertApplication;
use App\Models\Contractor;
use App\Models\ContractorCIDB;
use App\Models\Role;
use App\Models\Customer;
use App\Models\MOF;
use App\Models\Staff;
use App\Models\AutoNumber;
use App\Services\DropdownService;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class CertAppController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('osc.certApp.index'
        );
    }

    public function list(Request $request, $id){


        $gred = $this->dropdownService->gred();
        $jenis = $this->dropdownService->jenis();
        $status_bumiputra = $this->dropdownService->status_bumiputra();
        $kod_bidang = $this->dropdownService->kod_bidang();
        $warganegara = $this->dropdownService->warganegara();
        $negeri = $this->dropdownService->negeri();

        $certApp = CertApplication::where('CANo', $id)->first();
        $contractor = Contractor::where('CONo', $certApp->CA_CONo)->first();

        $contractorMOF = MOF::where('COMOF_CONo', $certApp->CA_CONo)->get();
        //dd($contractorMOF);
        $contractorCIDB = ContractorCIDB::where('COCIDBCONo', $certApp->CA_CONo)->get();

        $kakitangans = Staff::where('COST_CONo' , $certApp->CA_CONo)->get();

        //$bidangcodeMOF = explode(',',$certApp->contractorMOF->COMOF_MOFCode);

        //$bidangcodePKK = explode(',',$certApp->contractorCIDB->COCIDB_MOFCode);

        $bidangcodeMOF = [];

        if (!empty($certApp->contractorMOF->COMOF_MOFCode)) {
            $bidangcodeMOF = explode(',', $certApp->contractorMOF->COMOF_MOFCode);
        }

        $bidangcodePKK = [];

        if (!empty($certApp->contractorCIDB->COCIDB_MOFCode)) {
            $bidangcodePKK = explode(',', $certApp->contractorCIDB->COCIDB_MOFCode);
        }

        $ppkshowhide = "disabled";
        $ppkroute = "";

        $sppkshowhide = "disabled";
        $sppkroute = "";

        $stbshowhide = "disabled";
        $stbroute = "";


        //CHECK FILE ATTACH
        if(!empty($contractorCIDB->fileAttach)){

            foreach($contractorCIDB->fileAttach as $file){

                $fileCode = $file->FAFileType;

                if($fileCode == 'CIDB-PPK'){

                    $ppkshowhide = "";

                    $folderPath	 = $file->FAFilePath;
                    $newFileName = $file->FAFileName;
                    $newFileExt	 = $file->FAFileExtension;

                    $refNo	 = $file->FAFileExtension;

                    $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

                    $fileguid = $file->FAGuidID;

                    $ppkroute = route('file.view', ['fileGuid' => $fileguid]);

                }else if($fileCode == 'CIDB-SPPK'){

                    $sppkshowhide = "";

                    $folderPath	 = $file->FAFilePath;
                    $newFileName = $file->FAFileName;
                    $newFileExt	 = $file->FAFileExtension;

                    $refNo	 = $file->FAFileExtension;

                    $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

                    $fileguid = $file->FAGuidID;

                    $sppkroute = route('file.view', ['fileGuid' => $fileguid]);

                }else if($fileCode == 'CIDB-STB'){

                    $stbshowhide = "";

                    $folderPath	 = $file->FAFilePath;
                    $newFileName = $file->FAFileName;
                    $newFileExt	 = $file->FAFileExtension;

                    $refNo	 = $file->FAFileExtension;

                    $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

                    $fileguid = $file->FAGuidID;

                    $stbroute = route('file.view', ['fileGuid' => $fileguid]);

                }


            }

        }


        return view('osc.certApp.list' , compact('certApp', 'contractor', 'contractorMOF', 'contractorCIDB' , 'status_bumiputra' , 'bidangcodeMOF' , 'kod_bidang'
        , 'contractorCIDB' , 'gred' , 'jenis' , 'bidangcodePKK' , 'kakitangans' , 'ppkroute' , 'sppkroute'
        , 'stbroute' , 'ppkshowhide' , 'sppkshowhide' , 'stbshowhide' , 'warganegara', 'negeri'));
    }

    // public function view(Request $request, $id){

    //     $gred = $this->dropdownService->gred();
    //     $jenis = $this->dropdownService->jenis();
    //     $status_bumiputra = $this->dropdownService->status_bumiputra();
    //     $kod_bidang = $this->dropdownService->kod_bidang();
    //     $warganegara = $this->dropdownService->warganegara();

    //     $certApp = CertApplication::where('CANo', $id)->first();

    //     $contractorMOF = MOF::where('COMOF_CONo', $certApp->CA_CONo)->latest()->first();

    //     $contractorCIDB = ContractorCIDB::where('COCIDBCONo', $certApp->CA_CONo)->latest()->first();

    //     $kakitangans = Staff::where('COST_CONo' , $certApp->CA_CONo)->get();

    //     $bidangcodeMOF = explode(',',$certApp->contractorMOF->COMOF_MOFCode);

    //     $bidangcodePKK = explode(',',$certApp->contractorCIDB->COCIDB_MOFCode);

    //     $ppkshowhide = "disabled";
    //     $ppkroute = "";

    //     $sppkshowhide = "disabled";
    //     $sppkroute = "";

    //     $stbshowhide = "disabled";
    //     $stbroute = "";


    //     //CHECK FILE ATTACH
    //     if(!empty($contractorCIDB->fileAttach)){

    //         foreach($contractorCIDB->fileAttach as $file){

    //             $fileCode = $file->FAFileType;

    //             if($fileCode == 'CIDB-PPK'){

    //                 $ppkshowhide = "";

    //                 $folderPath	 = $file->FAFilePath;
    //                 $newFileName = $file->FAFileName;
    //                 $newFileExt	 = $file->FAFileExtension;

    //                 $refNo	 = $file->FAFileExtension;

    //                 $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

    //                 $fileguid = $file->FAGuidID;

    //                 $ppkroute = route('file.view', ['fileGuid' => $fileguid]);

    //             }else if($fileCode == 'CIDB-SPPK'){

    //                 $sppkshowhide = "";

    //                 $folderPath	 = $file->FAFilePath;
    //                 $newFileName = $file->FAFileName;
    //                 $newFileExt	 = $file->FAFileExtension;

    //                 $refNo	 = $file->FAFileExtension;

    //                 $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

    //                 $fileguid = $file->FAGuidID;

    //                 $sppkroute = route('file.view', ['fileGuid' => $fileguid]);

    //             }else if($fileCode == 'CIDB-STB'){

    //                 $stbshowhide = "";

    //                 $folderPath	 = $file->FAFilePath;
    //                 $newFileName = $file->FAFileName;
    //                 $newFileExt	 = $file->FAFileExtension;

    //                 $refNo	 = $file->FAFileExtension;

    //                 $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

    //                 $fileguid = $file->FAGuidID;

    //                 $stbroute = route('file.view', ['fileGuid' => $fileguid]);

    //             }


    //         }

    //     }


    //     return view('osc.certApp.view' , compact('certApp', 'status_bumiputra' , 'bidangcodeMOF' , 'kod_bidang'
    //     , 'contractorCIDB' , 'gred' , 'jenis' , 'bidangcodePKK' , 'kakitangans' , 'ppkroute' , 'sppkroute'
    //     , 'stbroute' , 'ppkshowhide' , 'sppkshowhide' , 'stbshowhide' , 'warganegara'));
    // }

    public function viewKKM($caid,$id){

        $status_bumiputra = $this->dropdownService->status_bumiputra();
        $kod_bidang_kkm = $this->dropdownService->kod_bidang();

        $user = Auth::user();
        $mof = MOF::where('COMOFNo', $id)->with('fileAttach')->first();

        $showhide = "hide";
        $route = "";

        if(!empty($mof->fileAttach)){
            $showhide = "";

            $folderPath	 = $mof->fileAttach->FAFilePath;
            $newFileName = $mof->fileAttach->FAFileName;
            $newFileExt	 = $mof->fileAttach->FAFileExtension;

            $refNo	 = $mof->fileAttach->FAFileExtension;

            $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

            $fileguid = $mof->fileAttach->FAGuidID;

            $route = route('file.view', ['fileGuid' => $fileguid]);

        }

        $startDate = "";

        if(!empty($mof->COMOFStartDate)){

            // Create a Carbon instance from the MySQL datetime value
            $carbonDatetime = Carbon::parse($mof->COMOFStartDate);

            // Format the Carbon instance to get the date in 'Y-m-d' format
//            $startDate = $carbonDatetime->format('d/m/Y');
            $startDate = $carbonDatetime;

        }

        $endDate = "";

        if(!empty($mof->COMOFEndDate)){

            // Create a Carbon instance from the MySQL datetime value
            $carbonDatetime = Carbon::parse($mof->COMOFEndDate);

            // Format the Carbon instance to get the date in 'Y-m-d' format
//            $endDate = $carbonDatetime->format('d/m/Y');
            $endDate = $carbonDatetime;

        }

        //$bidangcode = explode(',',$mof->COMOF_MOFCode);

        $bidangcode = [];

        if (!empty($mof->COMOF_MOFCode)) {
            $bidangcode = explode(',', $mof->COMOF_MOFCode);
        }
        $certAppNo = $caid;

        return view('osc.certApp.include.viewKKM',
            compact('status_bumiputra', 'kod_bidang_kkm','mof','showhide','route','startDate','endDate','bidangcode' ,'certAppNo' )
        );

    }

    public function viewPPK($caid, $id){
        $gred = $this->dropdownService->gred();
        $jenis = $this->dropdownService->jenis();
        $kod_bidang = $this->dropdownService->kod_bidang();

        $user = Auth::user();
        $ppk = ContractorCIDB::where('COCIDBNo', $id)->with('fileAttach')->first();

        //PPK Date
        $startDate = "";
        $endDate = "";

        if(!empty($ppk->COCIDBStartDate)){

            $carbonDatetime = Carbon::parse($ppk->COCIDBStartDate);
//            $startDate = $carbonDatetime->format('d/m/Y');
            $startDate = $carbonDatetime;
        }

        if(!empty($ppk->COCIDBEndDate)){

            $carbonDatetime = Carbon::parse($ppk->COCIDBEndDate);
//            $endDate = $carbonDatetime->format('d/m/Y');
            $endDate = $carbonDatetime;
        }

        //SPKK Date
        $govStartDate = "";
        $govendDate = "";

        if(!empty($ppk->COCIDBGovStartDate)){

            $carbonDatetime = Carbon::parse($ppk->COCIDBGovStartDate);
//            $govStartDate = $carbonDatetime->format('d/m/Y');
            $govStartDate = $carbonDatetime;
        }

        if(!empty($ppk->COCIDBGovEndDate)){

            $carbonDatetime = Carbon::parse($ppk->COCIDBGovEndDate);
//            $govendDate = $carbonDatetime->format('d/m/Y');
            $govendDate = $carbonDatetime;
        }

        //STB Date
        $bumiStartDate = "";
        $bumiendDate = "";

        if(!empty($ppk->COCIDBBumiStartDate)){

            $carbonDatetime = Carbon::parse($ppk->COCIDBBumiStartDate);
//            $bumiStartDate = $carbonDatetime->format('d/m/Y');
            $bumiStartDate = $carbonDatetime;
        }

        if(!empty($ppk->COCIDBBumiEndDate)){

            $carbonDatetime = Carbon::parse($ppk->COCIDBBumiEndDate);
//            $bumiendDate = $carbonDatetime->format('d/m/Y');
            $bumiendDate = $carbonDatetime;
        }

        $ppkshowhide = "disabled";
        $ppkroute = "";

        $sppkshowhide = "disabled";
        $sppkroute = "";

        $stbshowhide = "disabled";
        $stbroute = "";


        //CHECK FILE ATTACH
        if(!empty($ppk->fileAttach)){

            foreach($ppk->fileAttach as $file){

                $fileCode = $file->FAFileType;

                if($fileCode == 'CIDB-PPK'){

                    $ppkshowhide = "";

                    $folderPath	 = $file->FAFilePath;
                    $newFileName = $file->FAFileName;
                    $newFileExt	 = $file->FAFileExtension;

                    $refNo	 = $file->FAFileExtension;

                    $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

                    $fileguid = $file->FAGuidID;

                    $ppkroute = route('file.view', ['fileGuid' => $fileguid]);

                }else if($fileCode == 'CIDB-SPPK'){

                    $sppkshowhide = "";

                    $folderPath	 = $file->FAFilePath;
                    $newFileName = $file->FAFileName;
                    $newFileExt	 = $file->FAFileExtension;

                    $refNo	 = $file->FAFileExtension;

                    $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

                    $fileguid = $file->FAGuidID;

                    $sppkroute = route('file.view', ['fileGuid' => $fileguid]);

                }else if($fileCode == 'CIDB-STB'){

                    $stbshowhide = "";

                    $folderPath	 = $file->FAFilePath;
                    $newFileName = $file->FAFileName;
                    $newFileExt	 = $file->FAFileExtension;

                    $refNo	 = $file->FAFileExtension;

                    $filePath = $folderPath.'\\'.$newFileName.'.'.$newFileExt;

                    $fileguid = $file->FAGuidID;

                    $stbroute = route('file.view', ['fileGuid' => $fileguid]);

                }


            }

        }

        $bidangcode = explode(',',$ppk->COCIDB_MOFCode);
        $certAppNo = $caid;

        return view('osc.certApp.include.viewPPK',
            compact('gred', 'jenis', 'kod_bidang','ppk',
            'startDate','endDate','govStartDate','govendDate','bumiStartDate','bumiendDate',
            'ppkshowhide','ppkroute',
            'sppkshowhide','sppkroute',
            'stbshowhide','stbroute',
            'bidangcode' , 'certAppNo')
        );
    }

    public function certAppDatatable(Request $request){

        $query = CertApp::leftjoin('MSCertAppType','CATCode','CA_CATCode')
                            ->leftjoin('MSCertAppStatus','CASCode','CA_CASCode')
                            ->leftjoin('MSContractor','CONo','CA_CONo')
                            ->where('CA_CAPCode', 'RESULT')->orderby('CANo', 'desc')->get();

        $count = 0;

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('CANo', function($row) {

                $route = route('osc.certApp.list',[$row->CANo] );

                $result = '<a href="'.$route.'">'.$row->CANo.'</a>';

                return $result;
            })
            ->addColumn('action', function($row) {

                $route = route('publicUser.dashboard.permohonanSijil.result',[$row->CANo, 0] );

                $result = '<a class="btn btn-light-primary btn-lg" href="'.$route.'">Tolak</a>&nbsp&nbsp';

                $route = route('publicUser.dashboard.permohonanSijil.result',[$row->CANo, 1] );

                $result .= '<a class="btn btn-primary btn-lg" href="'.$route.'">Terima</a>';

                return $result;

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','CANo','action'])
            ->make(true);
    }
}
