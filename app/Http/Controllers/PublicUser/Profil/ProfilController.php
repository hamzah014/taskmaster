<?php

namespace App\Http\Controllers\PublicUser\Profil;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileController;
use App\Models\ContractorCIDB;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Requests;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use Yajra\DataTables\DataTables;

use App\Models\Role;
use App\Models\Customer;
use App\Models\MOF;
use App\Models\FileAttach;
use App\Models\AutoNumber;
use App\Models\Contractor;
use App\Models\CIDB;
use App\Models\RPCIDB;
use App\Models\Staff;

class ProfilController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    // FUNCTION SYARIKAT
    public function editSyarikat(){

        $jenis_penyertaan = $this->dropdownService->jenis_penyertaan();
        $jenis_pendaftaran = $this->dropdownService->jenis_pendaftaran();
        $jenis_milikan_pejabat = $this->dropdownService->jenis_milikan_pejabat();

        $negeri = $this->dropdownService->negeri();
        //$negeri = db::table('SSM_STATE')->get()->pluck('SSMSTCode', 'SSMSTDesc');
        $parlimen = $this->dropdownService->parlimen();
        $gred = $this->dropdownService->gred();
        $jenis = $this->dropdownService->jenis();
        $status_bumiputra = $this->dropdownService->status_bumiputra();
        $kod_bidang = $this->dropdownService->kod_bidang();
        $kod_bidang_kkm = $this->dropdownService->kod_bidang();

        $user = Auth::user();
        $contractor = Contractor::where('CONo', $user->USCode)->first();

        if ($contractor->COReg_StateCode == 'R'){//PERLIS
            $contractor->COReg_StateCode = 'MY-09';
        }
        elseif ($contractor->COReg_StateCode == 'K'){//KEDAH
            $contractor->COReg_StateCode = 'MY-02';
        }
        elseif ($contractor->COReg_StateCode == 'P'){//PP
            $contractor->COReg_StateCode = 'MY-07';
        }
        elseif ($contractor->COReg_StateCode == 'D'){//KEL
            $contractor->COReg_StateCode = 'MY-03';
        }
        elseif ($contractor->COReg_StateCode == 'T'){//TER
            $contractor->COReg_StateCode = 'MY-11';
        }
        elseif ($contractor->COReg_StateCode == 'A'){//PERAK
            $contractor->COReg_StateCode = 'MY-08';
        }
        elseif ($contractor->COReg_StateCode == 'B'){//SEL
            $contractor->COReg_StateCode = 'MY-10';
        }
        elseif ($contractor->COReg_StateCode == 'C'){//PAH
            $contractor->COReg_StateCode = 'MY-06';
        }
        elseif ($contractor->COReg_StateCode == 'N'){//NEG9
            $contractor->COReg_StateCode = 'MY-05';
        }
        elseif ($contractor->COReg_StateCode == 'M'){//MEL
            $contractor->COReg_StateCode = 'MY-04';
        }
        elseif ($contractor->COReg_StateCode == 'J'){//JHR
            $contractor->COReg_StateCode = 'MY-01';
        }
        elseif ($contractor->COReg_StateCode == 'X'){//SAB
            $contractor->COReg_StateCode = 'MY-12';
        }
        elseif ($contractor->COReg_StateCode == 'Y'){//SAR
            $contractor->COReg_StateCode = 'MY-13';
        }
        elseif ($contractor->COReg_StateCode == 'L'){//LAB
            $contractor->COReg_StateCode = 'MY-15';
        }
        elseif ($contractor->COReg_StateCode == 'W'){//WP
            $contractor->COReg_StateCode = 'MY-14';
        }
        elseif ($contractor->COReg_StateCode == 'U'){//PUT
            $contractor->COReg_StateCode = 'MY-16';
        }
        // elseif ($contractor->COReg_StateCode == 'Q'){//SG
        //     $contractor->COReg_StateCode = '';
        // }


        //BusCode
        if ($contractor->COBus_StateCode == 'R'){//PERLIS
            $contractor->COBus_StateCode = 'MY-09';
        }
        elseif ($contractor->COBus_StateCode == 'K'){//KEDAH
            $contractor->COBus_StateCode = 'MY-02';
        }
        elseif ($contractor->COBus_StateCode == 'P'){//PP
            $contractor->COBus_StateCode = 'MY-07';
        }
        elseif ($contractor->COBus_StateCode == 'D'){//KEL
            $contractor->COBus_StateCode = 'MY-03';
        }
        elseif ($contractor->COBus_StateCode == 'T'){//TER
            $contractor->COBus_StateCode = 'MY-11';
        }
        elseif ($contractor->COBus_StateCode == 'A'){//PERAK
            $contractor->COBus_StateCode = 'MY-08';
        }
        elseif ($contractor->COBus_StateCode == 'B'){//SEL
            $contractor->COBus_StateCode = 'MY-10';
        }
        elseif ($contractor->COBus_StateCode == 'C'){//PAH
            $contractor->COBus_StateCode = 'MY-06';
        }
        elseif ($contractor->COBus_StateCode == 'N'){//NEG9
            $contractor->COBus_StateCode = 'MY-05';
        }
        elseif ($contractor->COBus_StateCode == 'M'){//MEL
            $contractor->COBus_StateCode = 'MY-04';
        }
        elseif ($contractor->COBus_StateCode == 'J'){//JHR
            $contractor->COBus_StateCode = 'MY-01';
        }
        elseif ($contractor->COBus_StateCode == 'X'){//SAB
            $contractor->COBus_StateCode = 'MY-12';
        }
        elseif ($contractor->COBus_StateCode == 'Y'){//SAR
            $contractor->COBus_StateCode = 'MY-13';
        }
        elseif ($contractor->COBus_StateCode == 'L'){//LAB
            $contractor->COBus_StateCode = 'MY-15';
        }
        elseif ($contractor->COBus_StateCode == 'W'){//WP
            $contractor->COBus_StateCode = 'MY-14';
        }
        elseif ($contractor->COBus_StateCode == 'U'){//PUT
            $contractor->COBus_StateCode = 'MY-16';
        }
        // elseif ($contractor->COBus_StateCode == 'Q'){//SG
        //     $contractor->COBus_StateCode = '';
        // }


        $contractorCode = $contractor->CONo;

        $icType = 'RG-IC';
        $frType = 'RG-FR';
        $f9Type = 'RG-FORM9';

        $fileIC = FileAttach::where('FA_USCode',$contractorCode)
                ->where('FARefNo',$contractorCode)
                ->where('FAFileType',$icType)
                ->first();

        $fileFR = FileAttach::where('FA_USCode',$contractorCode)
                ->where('FARefNo',$contractorCode)
                ->where('FAFileType',$frType)
                ->first();

        $fileF9 = optional(FileAttach::where('FA_USCode',$contractorCode)
                ->where('FARefNo',$contractorCode)
                ->where('FAFileType',$f9Type)
                ->first());


        $contractor['fileF9'] = $fileF9->FAGuidID ?? null;

        return view('publicUser.profil.syarikat.edit',
            compact('jenis_penyertaan', 'jenis_pendaftaran', 'jenis_milikan_pejabat', 'negeri', 'parlimen', 'gred', 'jenis',
                'kod_bidang', 'status_bumiputra', 'kod_bidang_kkm', 'contractor')
        );
    }

    public function updateSyarikat(Request $request){

        $messages = [
            // 'nama_syarikat.required'        => 'Ruangan Nama Syarikat diperlukan.',
            // 'ssm_no.required'               => 'Ruangan No. SSM Syarikat diperlukan.',
            // 'ssm_no_new.required'           => 'Ruangan No. SSM Syarikat diperlukan.',
            'no_cukai.required'             => 'Ruangan No. Cukai diperlukan.',
            // 'jenis_penyertaan.required'     => 'Ruangan Jenis Penyertaan diperlukan.',
            // 'jenis_pendaftaran.required'    => 'Ruangan Jenis Pendaftaran diperlukan.',
            // 'tarikh_ditubuhkan.required'    => 'Ruangan Tarikh Ditubuhkan diperlukan.',
            // 'jenis_milikan_pejabat.required'=> 'Ruangan Jenis Milikan Pejabat diperlukan.',
            'hp_no.required'                => 'Ruangan No. Telefon Bimbit diperlukan.',
            'office_no.required'            => 'Ruangan No. Telefon Pejabat diperlukan.',
            //'fax_no.required'               => 'Ruangan No. Faks diperlukan.',
            //'parlimen_syarikat.required'    => 'Ruangan Parlimen Syarikat diperlukan.',
            'alamat1.required'              => 'Ruangan Alamat 1 (Alamat Pendaftaran) diperlukan.',
            //'alamat2.required'              => 'Ruangan Alamat 2 (Alamat Pendaftaran) diperlukan.',
            'poskod.required'               => 'Ruangan Poskod (Alamat Pendaftaran) diperlukan.',
            'bandar.required'               => 'Ruangan Bandar (Alamat Pendaftaran) diperlukan.',
            'negeri.required'               => 'Ruangan Negeri (Alamat Pendaftaran) diperlukan.',
            'business_alamat1.required'     => 'Ruangan Alamat 1 (Alamat Perniagaan) diperlukan.',
            //'business_alamat2.required'     => 'Ruangan Alamat 2 (Alamat Perniagaan) diperlukan.',
            'business_poskod.required'      => 'Ruangan Poskod (Alamat Perniagaan) diperlukan.',
            'business_bandar.required'      => 'Ruangan Bandar (Alamat Perniagaan) diperlukan.',
            'business_negeri.required'      => 'Ruangan Negeri (Alamat Perniagaan) diperlukan.',
            'contact_name.required'         => 'Ruangan Nama (Pegawai 1) diperlukan.',
            'contact_jawatan.required'      => 'Ruangan Jawatan (Pegawai 1) diperlukan.',
            'contact_hp_no.required'        => 'Ruangan No. Telefon (Pegawai 1) diperlukan.',
            // 'contact_email.required'        => 'Ruangan Alamat Emel (Pegawai 1) diperlukan.',
            // 'contact_name2.required'         => 'Ruangan Nama (Pegawai 2) diperlukan.',
            // 'contact_jawatan2.required'      => 'Ruangan Jawatan (Pegawai 2) diperlukan.',
            // 'contact_hp_no2.required'        => 'Ruangan No. Telefon (Pegawai 2) diperlukan.',
            // 'contact_email2.required'        => 'Ruangan Alamat Emel (Pegawai 2) diperlukan.',

        ];

        $validation = [
        //    'nama_syarikat'         => 'required|string',
        //    'ssm_no' 	            => 'required',
        //    'ssm_no_new'            => 'required',
            'no_cukai' 	            => 'required',
        //    'jenis_penyertaan'      => 'required',
        //    'jenis_pendaftaran'     => 'required',
        //    'tarikh_ditubuhkan'     => 'required',
        //    'jenis_milikan_pejabat' => 'required',
            'hp_no'                 => 'required',
            'office_no'             => 'required',
        //    'fax_no'                => 'required',
        //    'parlimen_syarikat'     => 'required',
            'alamat1'               => 'required',
        //    'alamat2'               => 'required',
            'poskod'                => 'required',
            'bandar'                => 'required',
            'negeri'                => 'required',
            'business_alamat1'      => 'required',
        //    'business_alamat2'      => 'required',
            'business_poskod'       => 'required',
            'business_bandar'       => 'required',
            'business_negeri'       => 'required',
            'contact_name'          => 'required',
            'contact_jawatan'       => 'required',
            'contact_hp_no'         => 'required',
        //    'contact_email'         => 'required',
        //    'contact_name2'         => 'required',
        //    'contact_jawatan2'      => 'required',
        //    'contact_hp_no2'        => 'required',
        //    'contact_email2'        => 'required',


        ];
        $request->validate($validation, $messages);


        try {
            DB::beginTransaction();

            $user = Auth::user();

            //Update MSContractor
            $update_contractor = Contractor::where('CONo', $user->USCode)->first();



            $update_contractor->CORegAddr          = $request->alamat1;
            $update_contractor->CORegPostcode      = $request->poskod;
            $update_contractor->CORegCity          = $request->bandar;
            $update_contractor->COReg_StateCode    = $request->negeri;
            $update_contractor->COBusAddr          = $request->business_alamat1;
            $update_contractor->COBusPostcode      = $request->business_poskod;
            $update_contractor->COBusCity          = $request->business_bandar;
            $update_contractor->COBus_StateCode    = $request->business_negeri;
            $update_contractor->COPhone            = $request->hp_no;
            $update_contractor->COOfficePhone      = $request->office_no;
            $update_contractor->COFax             	= $request->fax_no;
            $update_contractor->COPICName          = $request->contact_name;
            $update_contractor->COPICPosition      = $request->contact_jawatan;
            $update_contractor->COPICICNo          = $request->email;
            $update_contractor->COPICPhone         = $request->contact_hp_no;
            $update_contractor->COPICName2         = $request->contact_name2;
            $update_contractor->COPICPosition2     = $request->contact_jawatan2;
            $update_contractor->COPICPhone2        = $request->contact_hp_no2;
            $update_contractor->COActive           = '1';
            $update_contractor->COMB               = $user->USCode;
            $update_contractor->save();

            $autoNumber = new AutoNumber();

            if ($request->hasFile('dok_form9')) {

                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

                $dok_FAFileType		    = "RG-FORM9";

                $file = $request->file('dok_form9');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = FileAttach::where('FA_USCode',$user->USCode)
                                        ->where('FARefNo',$user->USCode)
                                        ->where('FAFileType',$fileCode)
                                        ->first();
                if ($fileAttach == null){
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB 		= $user->USCode;
                    $fileAttach->FAFileType 	= $fileCode;
                }else{

                    $filename   = $fileAttach->FAFileName;
                    $fileExt    = $fileAttach->FAFileExtension ;

                    Storage::disk('fileStorage')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);

                }
                $fileAttach->FARefNo     	    = $user->USCode;
                $fileAttach->FA_USCode     	    = $user->USCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = $user->USCode;
                $fileAttach->save();
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.syarikat.index'),
                'message' => 'Maklumat syarikat telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat syarikat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    //    FUNCTION KKM
    public function listKKM(){

        $user = Auth::user();
        $mof = MOF::where('COMOF_CONo', $user->USCode)->get();

        return view('publicUser.profil.kkm.index',
        compact('mof')
        );
    }

    public function createKKM(){

        $status_bumiputra = $this->dropdownService->status_bumiputra();
        $kod_bidang_kkm = $this->dropdownService->kod_bidang();

        return view('publicUser.profil.kkm.create',
            compact('status_bumiputra', 'kod_bidang_kkm')
        );
    }

    public function storeKKM(Request $request){

        $messages = [
            'no_rujukan_pendaftaran.required'   => 'No Rujukan Pendaftaran diperlukan.',
            'status_bumiputra.required'         => 'Status bumiputra diperlukan.',
            // 'tarikh_mula.required'              => 'Tarikh mula diperlukan.',
            // 'tarikh_tamat.required'             => 'Tarikh tamat diperlukan.',
            // 'kod_bidang_kkm.required'           => 'Kod Bidang KKM diperlukan.',
            // 'dokumen.required'                  => 'Fail muat-turun diperlukan.',

        ];

        $validation = [
            'no_rujukan_pendaftaran'         => 'required|string',
            'status_bumiputra' 	            => 'required',
            // 'tarikh_mula.required'   => 'required',
            // 'tarikh_tamat.required'  => 'required',
            // 'kod_bidang_kkm.required'=> 'required',
            // 'dokumen.required'       => 'required',

        ];

        $request->validate($validation, $messages);

        $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

        try {
            DB::beginTransaction();

			$autoNumber = new AutoNumber();
			$KKMCode = $autoNumber->generateMOFNo();

            $bidangCode = "";

            if(is_array($request->kod_bidang_kkm) && count($request->kod_bidang_kkm) > 0){

                $bidangCode = implode(',',$request->kod_bidang_kkm);

            }

            $user = Auth::user();

            $kkm = new MOF();
            $kkm->COMOFNo             = $KKMCode;
            $kkm->COMOFRegNo          = $request->no_rujukan_pendaftaran;
            $kkm->COMOF_CONo          = $user->USCode;
            $kkm->COMOFBumiStatus     = $request->status_bumiputra;
            $kkm->COMOFStartDate      = $request->tarikh_mula;
            $kkm->COMOFEndDate        = $request->tarikh_tamat;
            $kkm->COMOF_MOFCode       = $bidangCode;
            $kkm->COMOFCB             = $user->USCode;
            $kkm->COMOFMB             = $user->USCode;
            $kkm->save();

            //FILE UPLOAD
            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');
                $fileType		    = "MOF";
                $refNo		    = $KKMCode;

                $this->saveFileUpload($request,$fileType,$refNo);
            }

            DB::commit();


            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.kkm.index'),
                'message' => 'Maklumat KKM telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat KKM tidak berjaya didaftar!'.$e->getMessage()
            ], 400);
        }

    }

    public function editKKM($id){

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

        $bidangcode = explode(',',$mof->COMOF_MOFCode);

        return view('publicUser.profil.kkm.edit',
            compact('status_bumiputra', 'kod_bidang_kkm','mof','showhide','route','startDate','endDate','bidangcode')
        );
    }


    public function updateKKM(Request $request){


        $messages = [
            'no_rujukan_pendaftaran.required'   => 'No Rujukan Pendaftaran diperlukan.',
            'status_bumiputra.required'         => 'Status bumiputra diperlukan.',
            // 'tarikh_mula.required'              => 'Tarikh mula diperlukan.',
            // 'tarikh_tamat.required'             => 'Tarikh tamat diperlukan.',
            // 'kod_bidang_kkm.required'           => 'Kod Bidang KKM diperlukan.',
            // 'dokumen.required'                  => 'Fail muat-turun diperlukan.',

        ];

        $validation = [
            'no_rujukan_pendaftaran'         => 'required|string',
            'status_bumiputra' 	            => 'required',
            // 'tarikh_mula.required'   => 'required',
            // 'tarikh_tamat.required'  => 'required',
            // 'kod_bidang_kkm.required'=> 'required',
            // 'dokumen.required'       => 'required',

        ];

        $request->validate($validation, $messages);

        $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

        try {
            DB::beginTransaction();


            $bidangCode = "";

            if(is_array($request->kod_bidang_kkm) && count($request->kod_bidang_kkm) > 0){

                $bidangCode = implode(',',$request->kod_bidang_kkm);

            }

            $user = Auth::user();

            $KKMCode = $request->COMOFNo;

            $kkm = MOF::where('COMOFNo',$KKMCode)->first();

            $kkm->COMOFRegNo          = $request->no_rujukan_pendaftaran;
            $kkm->COMOFBumiStatus     = $request->status_bumiputra;
            $kkm->COMOFStartDate      = $request->tarikh_mula;
            $kkm->COMOFEndDate        = $request->tarikh_tamat;
            $kkm->COMOF_MOFCode       = $bidangCode;
            $kkm->COMOFMB             = $user->USCode;
            $kkm->save();

            //FILE UPLOAD
            if ($request->hasFile('dokumen')) {

                $file = $request->file('dokumen');
                $fileType		    = "MOF";
                $refNo		    = $KKMCode;

                $this->saveFileUpload($request,$fileType,$refNo);

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.kkm.index'),
                'message' => 'Maklumat KKM telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat KKM tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }




    }

    //{{--Working Code Datatable--}}
    public function KKMDatatable(Request $request){
        $user = Auth::user();

        $query = MOF::where('COMOF_CoNo', $user->USCode)
                    ->with('fileAttach')
                    ->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('COMOFRegNo', function($row) {

                $route = route('publicUser.profil.kkm.edit',[$row->COMOFNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->COMOFRegNo.'</a>';

                return $result;
            })
            ->editColumn('COMOFBumiStatus', function($row) {

                $status_bumiputra = $this->dropdownService->status_bumiputra();
                return $status_bumiputra[$row->COMOFBumiStatus];
            })
            ->addColumn('tempoh', function($row) {

                $startDate = carbon::parse($row->COMOFStartDate);
                $endDate = carbon::parse($row->COMOFEndDate);

                $tempoh = $startDate->diff($endDate);

                $years = $tempoh->y;
                $months = $tempoh->m;
                $days = $tempoh->d;

                // Build the formatted string
                $formattedDuration = '';
                if ($years > 0) {
                    $formattedDuration .= $years . ' year' . ($years > 1 ? 's' : '') . ' ';
                }
                if ($months > 0) {
                    $formattedDuration .= $months . ' month' . ($months > 1 ? 's' : '') . ' ';
                }
                if ($days > 0) {
                    $formattedDuration .= $days . ' day' . ($days > 1 ? 's' : '');
                }

                return $formattedDuration;
            })
            ->editColumn('COMOFStartDate', function($row) {

                if(empty($row->COMOFStartDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->COMOFStartDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;
            })
            ->editColumn('COMOFEndDate', function($row) {

                if(empty($row->COMOFEndDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->COMOFEndDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;
            })
            ->addColumn('fail_redirect', function($row) {

                // Get all registered routes
                $result = '';
                if(!empty($row->fileAttach)){
                    // $showhide = "";

                    // $folderPath	 = $row->fileAttach->FAFilePath;
                    // $newFileName = $row->fileAttach->FAFileName;
                    // $newFileExt	 = $row->fileAttach->FAFileExtension;

                    // $refNo	 = $row->fileAttach->FAFileExtension;

                    // $filePath = $folderPath.'\\'.'.'.$newFileExt;

                    $fileguid = $row->fileAttach->FAGuidID;

                    $route = route('file.view', ['fileGuid' => $fileguid]);

                    $result = '<a class="btn btn-light-primary btn-sm" target="_blank" href="'.$route.'">Papar Fail</a>';

                }

                return $result;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['tempoh','COMOFBumiStatus','fail_redirect','COMOFRegNo','indexNo'])
            ->make(true);
    }

    //FUNCTION PPK
    public function listPPK(){

        $user = Auth::user();
        $ppk = ContractorCIDB::where('COCIDBCONo', $user->USCode)->get();

        return view('publicUser.profil.ppk.index', compact('ppk'));

    }

    public function createPPK(){

        $gred = $this->dropdownService->gred();
        $jenis = $this->dropdownService->jenis();
        $kod_bidang = $this->dropdownService->kod_bidang();

        return view('publicUser.profil.ppk.create',
            compact('gred', 'jenis', 'kod_bidang')
        );
    }

    public function storePPK(Request $request){

        // no_pendaftaran_ppk
        // gred
        // jenis
        // tarikh_mula_ppk
        // tarikh_luput_ppk
        // tarikh_mula_sppk
        // tarikh_luput_sppk
        // tarikh_mula_stb
        // tarikh_luput_stb
        // wakil_nama
        // wakil_ic_no

        $messages = [
            'no_pendaftaran_ppk.required'   => 'No Rujukan Pendaftaran diperlukan.',
            // 'gred.required'                 => 'No Rujukan Pendaftaran diperlukan.',
            // 'jenis.required'                => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_mula_ppk.required'      => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_luput_ppk.required'     => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_mula_sppk.required'     => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_luput_sppk.required'    => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_mula_stb.required'      => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_luput_stb.required'     => 'No Rujukan Pendaftaran diperlukan.',
            // 'wakil_nama.required'           => 'No Rujukan Pendaftaran diperlukan.',
            // 'wakil_ic_no.required'          => 'No Rujukan Pendaftaran diperlukan.',
        ];

        $validation = [
            'no_pendaftaran_ppk' => 'required|string',
            // 'gred'               => 'required',
            // 'jenis'              => 'required',
            // 'tarikh_mula_ppk'    => 'required',
            // 'tarikh_luput_ppk'   => 'required',
            // 'tarikh_mula_sppk'   => 'required',
            // 'tarikh_luput_sppk'  => 'required',
            // 'tarikh_mula_stb'    => 'required',
            // 'tarikh_luput_stb'   => 'required',
            // 'wakil_nama'         => 'required',
            // 'wakil_ic_no'        => 'required',

        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

			$autoNumber = new AutoNumber();
			$PPKCode = $autoNumber->generateCIDBNo();


            $bidangCode = "";

            if(is_array($request->kod_bidang) && count($request->kod_bidang) > 0){

                $bidangCode = implode(',',$request->kod_bidang);

            }

            $user = Auth::user();

            $ppk = new ContractorCIDB();
            $ppk->COCIDBNo            = $PPKCode;
            $ppk->COCIDBRegNo         = $request->no_pendaftaran_ppk;
            $ppk->COCIDBGred          = $request->gred;
            $ppk->COCIDBType          = $request->jenis;
            $ppk->COCIDBCONo          = $user->USCode;
            $ppk->COCIDB_MOFCode    = $bidangCode;

            //PPK Data
            $ppk->COCIDBStartDate     = $request->tarikh_mula_ppk;
            $ppk->COCIDBEndDate       = $request->tarikh_luput_ppk;

            //SPPK Data
            $ppk->COCIDBGovStartDate     = $request->tarikh_mula_sppk;
            $ppk->COCIDBGovEndDate       = $request->tarikh_luput_sppk;

            //SPPK Data
            $ppk->COCIDBBumiStartDate     = $request->tarikh_mula_stb;
            $ppk->COCIDBBumiEndDate       = $request->tarikh_luput_stb;

            $ppk->COCIDBCB             = $user->USCode;
            $ppk->COCIDBMB             = $user->USCode;
            $ppk->save();

            //file upload  here  borang_ppk, borang_sppk, borang_stb
            //FILE UPLOAD
            if ($request->hasFile('borang_ppk')) {

                $file = $request->file('borang_ppk');
                $fileType		    = "CIDB-PPK";
                $refNo		    = $PPKCode;

                $this->saveFileUpload($request,$fileType,$refNo);
            }

            if ($request->hasFile('borang_sppk')) {

                $file = $request->file('borang_sppk');
                $fileType		    = "CIDB-SPPK";
                $refNo		    = $PPKCode;

                $this->saveFileUpload($request,$fileType,$refNo);
            }

            if ($request->hasFile('borang_stb')) {

                $file = $request->file('borang_stb');
                $fileType		    = "CIDB-STB";
                $refNo		    = $PPKCode;

                $this->saveFileUpload($request,$fileType,$refNo);
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.ppk.index'),
                'message' => 'Maklumat PPK,SPKK & STB telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Makluma PPK,SPKK & STB tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function editPPK($id){

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

        $ppkshowhide = "hide";
        $ppkroute = "";

        $sppkshowhide = "hide";
        $sppkroute = "";

        $stbshowhide = "hide";
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

        return view('publicUser.profil.ppk.edit',
            compact('gred', 'jenis', 'kod_bidang','ppk',
            'startDate','endDate','govStartDate','govendDate','bumiStartDate','bumiendDate',
            'ppkshowhide','ppkroute',
            'sppkshowhide','sppkroute',
            'stbshowhide','stbroute',
            'bidangcode')
        );
    }

    public function updatePPK(Request $request){

        $messages = [
            'no_pendaftaran_ppk.required'   => 'No Rujukan Pendaftaran diperlukan.',
            // 'gred.required'                 => 'No Rujukan Pendaftaran diperlukan.',
            // 'jenis.required'                => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_mula_ppk.required'      => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_luput_ppk.required'     => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_mula_sppk.required'     => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_luput_sppk.required'    => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_mula_stb.required'      => 'No Rujukan Pendaftaran diperlukan.',
            // 'tarikh_luput_stb.required'     => 'No Rujukan Pendaftaran diperlukan.',
            // 'wakil_nama.required'           => 'No Rujukan Pendaftaran diperlukan.',
            // 'wakil_ic_no.required'          => 'No Rujukan Pendaftaran diperlukan.',
        ];

        $validation = [
            'no_pendaftaran_ppk' => 'required|string',
            // 'gred'               => 'required',
            // 'jenis'              => 'required',
            // 'tarikh_mula_ppk'    => 'required',
            // 'tarikh_luput_ppk'   => 'required',
            // 'tarikh_mula_sppk'   => 'required',
            // 'tarikh_luput_sppk'  => 'required',
            // 'tarikh_mula_stb'    => 'required',
            // 'tarikh_luput_stb'   => 'required',
            // 'wakil_nama'         => 'required',
            // 'wakil_ic_no'        => 'required',

        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

			$autoNumber = new AutoNumber();

            $bidangCode = "";

            if(is_array($request->kod_bidang) && count($request->kod_bidang) > 0){

                $bidangCode = implode(',',$request->kod_bidang);

            }

            $PPKCode = $request->COCIDBNo;

            $user = Auth::user();

            $ppk = ContractorCIDB::where('COCIDBNo',$PPKCode)->first();
            $ppk->COCIDBRegNo         = $request->no_pendaftaran_ppk;
            $ppk->COCIDBGred          = $request->gred;
            $ppk->COCIDBType          = $request->jenis;
            $ppk->COCIDB_MOFCode      = $bidangCode;

            //PPK Data
            $ppk->COCIDBStartDate     = $request->tarikh_mula_ppk;
            $ppk->COCIDBEndDate       = $request->tarikh_luput_ppk;

            //SPPK Data
            $ppk->COCIDBGovStartDate     = $request->tarikh_mula_sppk;
            $ppk->COCIDBGovEndDate       = $request->tarikh_luput_sppk;

            //SPPK Data
            $ppk->COCIDBBumiStartDate     = $request->tarikh_mula_stb;
            $ppk->COCIDBBumiEndDate       = $request->tarikh_luput_stb;

            $ppk->COCIDBMB                 = $user->USCode;
            $ppk->save();

            //file upload  here  borang_ppk, borang_sppk, borang_stb
            //FILE UPLOAD
            if ($request->hasFile('borang_ppk')) {

                $file = $request->file('borang_ppk');
                $fileType		    = "CIDB-PPK";
                $refNo		    = $PPKCode;

                $this->saveFileUpload($request,$fileType,$refNo);
            }

            if ($request->hasFile('borang_sppk')) {

                $file = $request->file('borang_sppk');
                $fileType		    = "CIDB-SPPK";
                $refNo		    = $PPKCode;

                $this->saveFileUpload($request,$fileType,$refNo);
            }

            if ($request->hasFile('borang_stb')) {

                $file = $request->file('borang_stb');
                $fileType		    = "CIDB-STB";
                $refNo		    = $PPKCode;

                $this->saveFileUpload($request,$fileType,$refNo);
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.ppk.index'),
                'message' => 'Maklumat PPK,SPKK & STB telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Makluma PPK,SPKK & STB tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    //{{--Working Code Datatable--}}
    public function PPKDatatable(Request $request){

        $user = Auth::user();

        $query = ContractorCIDB::where('COCIDBCONo', $user->USCode) ->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('COCIDBRegNo', function($row) {

                $route = route('publicUser.profil.ppk.edit',[$row->COCIDBNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->COCIDBRegNo.' </a>';

                return $result;
            })
            ->editColumn('COCIDBGred', function($row) {
                $result = '';

                $dropdownService = new DropdownService();

                $gred = $dropdownService->gred();
                if(isset($row->COCIDBGred)){
                   $result = $gred[$row->COCIDBGred];
                }

                return $result;

            })
            ->editColumn('COCIDBType', function($row) {
                $result = '';

                $dropdownService = new DropdownService();

                $jenis = $dropdownService->jenis();
                if(isset($row->COCIDBType)){
                    $result = $jenis[$row->COCIDBType];
                }

                return $result;
            })
            ->editColumn('COCIDBStartDate', function($row) {

                if(empty($row->COCIDBStartDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->COCIDBStartDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;
            })
            ->editColumn('COCIDBEndDate', function($row) {

                if(empty($row->COCIDBEndDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->COCIDBEndDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;
            })
            ->editColumn('COCIDBGovStartDate', function($row) {

                if(empty($row->COCIDBGovStartDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->COCIDBGovStartDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;
            })
            ->editColumn('COCIDBGovEndDate', function($row) {

                if(empty($row->COCIDBGovEndDate)){

                    $formattedDate = "";

                }else{

                    // Create a Carbon instance from the MySQL datetime value
                    $carbonDatetime = Carbon::parse($row->COCIDBGovEndDate);

                    // Format the Carbon instance to get the date in 'Y-m-d' format
                    $formattedDate = $carbonDatetime->format('d/m/Y');

                }

                return $formattedDate;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['COCIDBRegNo','COCIDBGred','COCIDBType','indexNo'])
            ->make(true);
    }



// FUNCTION KAKITANGAN
    public function listKakitangan(){

        $user = Auth::user();
        $kakitangan = Staff::where('COST_CONo', $user->USCode)->get();

        return view('publicUser.profil.kakitangan.index', compact('kakitangan') );
    }

    public function createKakitangan(){

        $status_bumiputra = $this->dropdownService->status_bumiputra();
        $warganegara = $this->dropdownService->warganegara();

        return view('publicUser.profil.kakitangan.create',
            compact('status_bumiputra', 'warganegara')
        );
    }


    public function storeKakitangan(Request $request){


        $messages = [
            'nama.required'                 => 'Nama diperlukan.',
            'jawatan.required'              => 'Jawatan diperlukan.',
            'warganegara.required'          => 'Warganegara diperlukan.',
            'status_bumiputra.required'     => 'Status Bumiputra diperlukan.',
            'hp_no.required'                => 'Hp No. diperlukan.',
            'email.required'                => 'Email diperlukan.',
        ];

        $validation = [
            'nama' => 'required|string',
            'jawatan' => 'required|string',
            'warganegara' => 'required',
            'status_bumiputra' => 'required',
            'hp_no' => 'required',
            'email' => 'required|email',

        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

			$autoNumber = new AutoNumber();
			$staffCode = $autoNumber->generateStaffCode();
            $user = Auth::user();

            $kakitangan = new Staff();
            $kakitangan->COSTNo           = $staffCode;
            $kakitangan->COST_CONo        = $user->USCode;
            $kakitangan->COSTName         = $request->nama;
            $kakitangan->COSTPosition     = $request->jawatan;
            $kakitangan->COSTCitizen      = $request->warganegara;
            $kakitangan->COSTBumi         = $request->status_bumiputra;
            $kakitangan->COSTPhone        = $request->hp_no;
            $kakitangan->COSTEmail        = $request->email;

            $kakitangan->COSTCB             = $user->USCode;
            $kakitangan->COSTMB             = $user->USCode;
            $kakitangan->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.kakitangan.index'),
                'message' => 'Maklumat Kakitangan telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Makluma Kakitangan tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function editKakitangan($id){

        $status_bumiputra = $this->dropdownService->status_bumiputra();
        $warganegara = $this->dropdownService->warganegara();

        $user = Auth::user();
        $kakitangan = Staff::where('COSTNo', $id)->first();

        return view('publicUser.profil.kakitangan.edit',
            compact('status_bumiputra', 'warganegara','kakitangan')
        );
    }

    public function updateKakitangan(Request $request){


        $messages = [
            'nama.required'                 => 'Nama diperlukan.',
            'jawatan.required'              => 'Jawatan diperlukan.',
            'warganegara.required'          => 'Warganegara diperlukan.',
            'status_bumiputra.required'     => 'Status Bumiputra diperlukan.',
            'hp_no.required'                => 'Hp No. diperlukan.',
            'email.required'                => 'Email diperlukan.',
        ];

        $validation = [
            'nama' => 'required|string',
            'jawatan' => 'required|string',
            'warganegara' => 'required',
            'status_bumiputra' => 'required',
            'hp_no' => 'required',
            'email' => 'required|email',

        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

			$autoNumber = new AutoNumber();
			$staffCode = $request->COSTNo;

            $user = Auth::user();

            $kakitangan = Staff::where('COSTNo',$request->COSTNo)->first();
            $kakitangan->COSTName         = $request->nama;
            $kakitangan->COSTPosition     = $request->jawatan;
            $kakitangan->COSTCitizen      = $request->warganegara;
            $kakitangan->COSTBumi         = $request->status_bumiputra;
            $kakitangan->COSTPhone        = $request->hp_no;
            $kakitangan->COSTEmail        = $request->email;

            $kakitangan->COSTMB           = $user->USCode;
            $kakitangan->save();


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.profil.kakitangan.index'),
                'message' => 'Maklumat Kakitangan telah berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Makluma Kakitangan tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }


    //{{--Working Code Datatable--}}
    public function KakitanganDatatable(Request $request){

        $user = Auth::user();

        $query = Staff::where('COST_CONo', $user->USCode)->get();

       $status_bumiputra = $this->dropdownService->status_bumiputra();
       $warganegara = $this->dropdownService->warganegara();

        return DataTables::of($query)
            ->editColumn('COSTName', function($row) {

                $route = route('publicUser.profil.kakitangan.edit',[$row->COSTNo]);

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->COSTName.' </a>';

                return $result;
            })
            ->editColumn('COSTCitizen', function($row)  use ($warganegara){

                return '';//$warganegara[$row->COSTCitizen];

            })
            ->editColumn('COSTBumi', function($row) {
                $status_bumiputra = $this->dropdownService->status_bumiputra();

                return $status_bumiputra[$row->COSTBumi] ?? '';

            })
            ->rawColumns(['COSTCitizen','COSTBumi','COSTName'])
            ->make(true);
    }


    // FUNCTION SAHAM
    public function listSaham(){


        return view('publicUser.profil.saham.index'
        );
    }

    public function createSaham(){
        $status_bumiputra = $this->dropdownService->status_bumiputra();

        $warganegara =  $this->dropdownService->warganegara();

        return view('publicUser.profil.saham.create',
            compact('status_bumiputra', 'warganegara')
        );
    }

// FUNCTION KEWANGAN
    public function editKewangan(){

        return view('publicUser.profil.kewangan.edit'
        );
    }

// FUNCTION ASET
    public function listAset(){


        return view('publicUser.profil.aset.index'
        );
    }

    public function createAset(){


        return view('publicUser.profil.aset.create'
        );
    }

// FUNCTION PROJEK
    public function listProjek(){


        return view('publicUser.profil.projek.index'
        );
    }

    public function createProjek(){
        $tempoh = [
            0 => 'Hari',
            1 => 'Minggu',
            2 => 'Bulan',
            3 => 'Tahun',
        ];

        $status_projek = [
            0 => 'Dalam Proses',
            1 => 'Selesai',
            2 => 'Singapura',
        ];

        return view('publicUser.profil.projek.create',
            compact('tempoh', 'status_projek')
        );
    }

// FUNCTION PRODUK
    public function listProduk(){


        return view('publicUser.profil.produk.index'
        );
    }

    public function createProduk(){


        return view('publicUser.profil.produk.create'
        );
    }

// FUNCTION ANUGERAH
    public function listAnugerah(){


        return view('publicUser.profil.anugerah.index'
        );
    }

    public function createAnugerah(){


        return view('publicUser.profil.anugerah.create'
        );
    }

// FUNCTION LAMPIRAN
    public function listLampiran(){


        return view('publicUser.profil.lampiran.index'
        );
    }

    public function createLampiran(){
        $jenis_lampiran = [
            0 => 'Sijil SSM',
            1 => 'Sijil KKM',
            2 => 'Sijil PKK',
            3 => 'Sijil CIDB',
        ];

        return view('publicUser.profil.lampiran.create',
            compact('jenis_lampiran')
        );
    }

    public function saveFileUpload(Request $request,$fileType,$refNo){

        try{
            DB::beginTransaction();

            $user = Auth::user();
            $dok_FAUSCode = $user->USCode;
            $dok_FARefNo = $refNo;

            if($fileType == 'MOF'){
                $file = $request->file('dokumen');

            }elseif($fileType == 'CIDB-PPK'){
                $file = $request->file('borang_ppk');

            }elseif($fileType == 'CIDB-SPPK'){
                $file = $request->file('borang_sppk');

            }elseif($fileType == 'CIDB-STB'){
                $file = $request->file('borang_stb');

            }

            $generateRandomSHA256 = $this->autoNumber->generateRandomSHA256();

            $folderPath = Carbon::now()->format('ymd');
            $originalName =  $file->getClientOriginalName();
            $newFileExt = $file->getClientOriginalExtension();
            $newFileName = strval($generateRandomSHA256);

            $fileCode = $fileType;

            $fileContent = file_get_contents($file->getRealPath());

            Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

            $fileAttach = FileAttach::where('FA_USCode',$dok_FAUSCode)
                                    ->where('FARefNo',$dok_FARefNo)
                                    ->where('FAFileType',$fileCode)
                                    ->first();
            if ($fileAttach == null){
                $fileAttach = new FileAttach();
                $fileAttach->FACB 		= Auth::user()->USCode;
                $fileAttach->FAFileType 	= $fileCode;
            }else{

                $filename   = $fileAttach->FAFileName;
                $fileExt    = $fileAttach->FAFileExtension ;

                Storage::disk('fileStorage')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);

            }
            $fileAttach->FARefNo     	    = $dok_FARefNo;
            $fileAttach->FA_USCode     	    = $dok_FAUSCode;
            $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
            $fileAttach->FAFileName 	    = $newFileName;
            $fileAttach->FAOriginalName 	= $originalName;
            $fileAttach->FAFileExtension    = strtolower($newFileExt);
            $fileAttach->FAMB 			    = Auth::user()->USCode;
            $fileAttach->save();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }




    }


}
