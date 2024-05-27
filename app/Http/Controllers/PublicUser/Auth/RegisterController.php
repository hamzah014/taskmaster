<?php

namespace App\Http\Controllers\PublicUser\Auth;

use App\Models\WebSetting;
use App\Models\PaymentLog;
use App\Models\CertApp;
use App\Models\Contractor;
use App\Models\TacLog;
use App\Services\DropdownService;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\AutoNumber;
use App\Models\EmailLog;
use App\Models\IntegrationLog;
use App\User;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicUser\Company\PegawaiController;
use App\Models\ContractorAuthUser;
use App\Models\FileAttach;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;
use Illuminate\Support\Facades\Storage;


use RevenueMonster\SDK\Exceptions\ApiException;
use RevenueMonster\SDK\Exceptions\ValidationException;
use RevenueMonster\SDK\RevenueMonster;
use RevenueMonster\SDK\Request\WebPayment;

class RegisterController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

	public function index(Request $request){

        $jenis_penyertaan = $this->dropdownService->jenis_penyertaan();
        $jenis_pendaftaran = $this->dropdownService->jenis_pendaftaran();
        $jenis_milikan_pejabat = $this->dropdownService->jenis_milikan_pejabat();
        $negeri = $this->dropdownService->negeri();
        $parlimen = $this->dropdownService->parlimen();
        $gred = $this->dropdownService->gred();
        $jenis = $this->dropdownService->jenis();
        $status_bumiputra = $this->dropdownService->status_bumiputra();
        $kod_bidang = $this->dropdownService->kod_bidang();
        $kod_bidang_kkm = $this->dropdownService->kod_bidang();


        return view('publicUser.auth.register',
             compact('jenis_penyertaan', 'jenis_pendaftaran', 'jenis_milikan_pejabat', 'negeri', 'parlimen', 'gred', 'jenis',
                 'kod_bidang', 'status_bumiputra', 'kod_bidang_kkm')
         );

    }

    public function create(Request $request){

        //dd($request->input());
        $messages = [
            'email.required' 		        => 'Ruangan Alamat Emel diperlukan.',
            'email.email' 			        => 'Ruangan Alamat Emel tidak sah.',
            'ssm_no.required'               => 'Ruangan No. SSM Syarikat diperlukan.',
            'comp_name.required'            => 'Ruangan Nama Syarikat diperlukan.',
            'phone.required'                => 'Ruangan No. Telefon diperlukan.',
//            'phone.starts_with_01'          => 'Ruangan No. Telefon hanya No Telefon Bimbit sahaja (Contoh:01XXXXXXXX).',
            'otp.required'                  => 'Ruangan OTP diperlukan.',
            'contact_name.required'         => 'Ruangan Nama (Pengarah Syarikat) diperlukan.',
            'contact_jawatan.required'      => 'Ruangan Jawatan (Pengarah Syarikat) diperlukan.',
            'contact_hp_no.required'        => 'Ruangan No. Telefon (Pengarah Syarikat) diperlukan.',
            'contact_email.required'        => 'Ruangan E-mel (Pengarah Syarikat) diperlukan.',
            'contact_name2.required'        => 'Ruangan Nama (Pegawai Syarikat) diperlukan.',
            'contact_jawatan2.required'     => 'Ruangan Jawatan (Pegawai Syarikat) diperlukan.',
            'contact_hp_no2.required'       => 'Ruangan No. Telefon (Pegawai Syarikat) diperlukan.',
            'contact_email2.required'       => 'Ruangan E-mel (Pegawai Syarikat) diperlukan.',
            $request->existIC == 0 ? 'dok_ic.required': 'nothing' => $request->existIC == 0 ? 'Fail gambar kad pengenalan diperlukan.': 'Custom message if not required',
            $request->existIC == 0 ? 'dok_picture.required': 'nothing' => $request->existFR == 0 ? 'Fail gambar pengguna diperlukan.': 'Custom message if not required',
            $request->existF9 == 0 ? 'dok_form9.required': 'nothing' => $request->existF9 == 0 ? 'Fail Form 9 diperlukan.': 'Custom message if not required',
        ];

        $validation = [

            'email' 	            => 'required|email',
            'ssm_no' 	            => 'required',
            'comp_name' 	        => 'required',
            'phone'                 => 'required',
            'otp' 	                => 'required',
            'contact_name'          => 'required',
            'contact_jawatan'       => 'required',
            'contact_hp_no'         => 'required',
            'contact_name2'         => 'required',
            'contact_jawatan2'      => 'required',
            'contact_hp_no2'        => 'required',
            'contact_email' 	    => 'required|email',
            'contact_email2' 	    => 'required|email',
            $request->existIC == 0 ? 'dok_ic' : 'nothing' => $request->existIC == 0 ? 'required' : '',
            $request->existIC == 0 ? 'dok_picture' : 'nothing' => $request->existFR == 0 ? 'required' : '',
            $request->existF9 == 0 ? 'dok_form9' : 'nothing' => $request->existF9 == 0 ? 'required' : '',

        ];
        $request->validate($validation, $messages);

        $check_contractor = Contractor::where('COEmail',$request->email)->first();

        if ($check_contractor != null && $request->existID == '0'){
            return response()->json([
                'error' => '1',
                'message' => 'Email telah didaftarkan.'
            ], 400);
        }

        $check_otp_tac_log = TacLog::where('TACCode', $request->otp)->where('TACEmail', $request->email)->latest('TACCD')->first();

        if($check_otp_tac_log){

            $diffInSeconds = Carbon::now()->diffInSeconds($check_otp_tac_log->TACCD);

            if ($diffInSeconds >= 300) {
                return response()->json([
                    'error' => '1',
                    'message' => 'OTP telah tamat tempoh dan melebihi 5 minit. Sila dapatkan OTP yang baru.'
                ], 403);
            }
        }
        else{
            return response()->json([
                'error' => '1',
                'message' => 'OTP tidak sah.'
            ], 404);
        }

        try {
            DB::beginTransaction();

			$autoNumber = new AutoNumber();

            if($request->existID == '0'){

                $contractorCode = $autoNumber->generateContractorCode();

                $contractor = new Contractor();
                $contractor->CONo              	= $contractorCode;
                $contractor->COCB               = $contractorCode;

            }else{
			    $contractorCode = $request->existID;
                $contractor = Contractor::where('CONo',$contractorCode)->first();

            }
            $contractor->COEmail            = $request->email ?? '';
            $contractor->COName             = $request->comp_name ?? '';
            $contractor->COPhone            = $request->phone ?? '';
            $contractor->COBusinessNo       = $request->ssm_no ?? '';

            $contractor->COPICName          = $request->contact_name ?? '';
            $contractor->COPICICNo          = $request->contact_ic ?? '';
            $contractor->COPICPosition      = $request->contact_jawatan ?? '';
            $contractor->COPICPhone         = $request->contact_hp_no ?? '';
            $contractor->COPICEmail         = $request->contact_email ?? '';

            $contractor->COPICName2         = $request->contact_name2 ?? '';
            $contractor->COPICICNo2          = $request->contact_ic2 ?? '';
            $contractor->COPICPosition2     = $request->contact_jawatan2 ?? '';
            $contractor->COPICPhone2         = $request->contact_hp_no2 ?? '';
            $contractor->COPICEmail2         = $request->contact_email2 ?? '';

            $contractor->COActive           = '1';
            $contractor->COStatus           = 'NEW';

            $contractor->COMB               = $contractorCode;
            $contractor->save();

            //store the pengarah to ContractorAuthUser
            $COANo = $this->autoNumber->generateCOANo();
            $verifyCode = $this->autoNumber->generateActivationCode();

            $contractAuthUser = new ContractorAuthUser();
            $contractAuthUser->CAUNo                 = $COANo;
            $contractAuthUser->CAU_CONo              = $contractorCode;
            $contractAuthUser->CAUIDNo               = $request->contact_ic;
            $contractAuthUser->CAUName               = $request->contact_name;
            $contractAuthUser->CAUEmail              = $request->contact_email;
            $contractAuthUser->CAUPhoneNo            = $request->contact_hp_no;
            $contractAuthUser->CAUVerificationCode   = $verifyCode;
            $contractAuthUser->CAUStatus             = 'PENDING';
            $contractAuthUser->CAUCB                 = $contractorCode;
            $contractAuthUser->CAUMB                 = $contractorCode;
            $contractAuthUser->save();

            //UPLOAD IC AND FACE

            $dok_FAUSCode		    = $contractorCode;
            $dok_FARefNo		    = $contractorCode;

            if ($request->hasFile('dok_ic')) {

                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

                $dok_FAFileType		    = "RG-IC";

                $file = $request->file('dok_ic');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = FileAttach::where('FA_USCode',$dok_FAUSCode)
                                        ->where('FARefNo',$dok_FARefNo)
                                        ->where('FAFileType',$fileCode)
                                        ->first();
                if ($fileAttach == null){
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB 		= $contractorCode;
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
                $fileAttach->FAMB 			    = $contractorCode;
                $fileAttach->save();
            }

            if ($request->hasFile('dok_picture')) {

                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

                $dok_FAFileType		    = "RG-FR";

                $file = $request->file('dok_picture');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = FileAttach::where('FA_USCode',$dok_FAUSCode)
                                        ->where('FARefNo',$dok_FARefNo)
                                        ->where('FAFileType',$fileCode)
                                        ->first();
                if ($fileAttach == null){
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB 		= $contractorCode;
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
                $fileAttach->FAMB 			    = $contractorCode;
                $fileAttach->save();
            }

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

                $fileAttach = FileAttach::where('FA_USCode',$dok_FAUSCode)
                                        ->where('FARefNo',$dok_FARefNo)
                                        ->where('FAFileType',$fileCode)
                                        ->first();
                if ($fileAttach == null){
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB 		= $contractorCode;
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
                $fileAttach->FAMB 			    = $contractorCode;
                $fileAttach->save();
            }

			// $this->sendMail($request,$contractor);
            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Akaun anda tidak berjaya didaftar!'.$e->getMessage()
            ], 400);
        }
        //CONTINUE CODE
        return response()->json([
            'success' => '1',
            'redirect' => route('publicUser.register.registerPayment',[$contractorCode]),
//            'redirect' => route('publicUser.login'),
            'message' => 'Akaun anda telah berjaya didaftarkan. Sila lakukan pembayaran untuk pendaftaran berjaya sepenuhnya.'
        ]);
    }

    public function createV1(Request $request){

        $messages = [
            'email.required' 		        => 'Ruangan Alamat Emel diperlukan.',
            'email.email' 			        => 'Ruangan Alamat Emel tidak sah.',
            'otp.required'                  => 'Ruangan OTP diperlukan.',
            'password.required'             => 'Ruangan Kata Laluan diperlukan.',
            'password.string'               => 'Ruangan Kata Laluan mesti perkataan.',
            'password.confirmed'            => 'Ruangan Kata Laluan dan Sahkan Kata Laluan tidak sama.',
            'nama_syarikat.required'        => 'Ruangan Nama Syarikat diperlukan.',
            'ssm_no.required'               => 'Ruangan No. SSM Syarikat diperlukan.',
            // 'ssm_no_new.required'           => 'Ruangan No. SSM Syarikat diperlukan.',
            'no_cukai.required'             => 'Ruangan No. Cukai diperlukan.',
            // 'jenis_penyertaan.required'     => 'Ruangan Jenis Penyertaan diperlukan.',
            // 'jenis_pendaftaran.required'    => 'Ruangan Jenis Pendaftaran diperlukan.',
            // 'tarikh_ditubuhkan.required'    => 'Ruangan Tarikh Ditubuhkan diperlukan.',
            // 'jenis_milikan_pejabat.required'=> 'Ruangan Jenis Milikan Pejabat diperlukan.',
            'hp_no.required'                => 'Ruangan No. Telefon Bimbit diperlukan.',
            'office_no.required'            => 'Ruangan No. Telefon Pejabat diperlukan.',
            // 'fax_no.required'               => 'Ruangan No. Faks diperlukan.',
            // 'parlimen_syarikat.required'    => 'Ruangan Parlimen Syarikat diperlukan.',
            'alamat1.required'              => 'Ruangan Alamat 1 (Alamat Pendaftaran) diperlukan.',
            // 'alamat2.required'              => 'Ruangan Alamat 2 (Alamat Pendaftaran) diperlukan.',
            'poskod.required'               => 'Ruangan Poskod (Alamat Pendaftaran) diperlukan.',
            'bandar.required'               => 'Ruangan Bandar (Alamat Pendaftaran) diperlukan.',
            'negeri.required'               => 'Ruangan Negeri (Alamat Pendaftaran) diperlukan.',
            'business_alamat1.required'     => 'Ruangan Alamat 1 (Alamat Perniagaan) diperlukan.',
            // 'business_alamat2.required'     => 'Ruangan Alamat 2 (Alamat Perniagaan) diperlukan.',
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

            'email' 	            => 'required|email',
            'otp' 	                => 'required',
            'password' 	            => 'required|string|confirmed',
            'nama_syarikat'         => 'required|string',
            'ssm_no' 	            => 'required',
            // 'ssm_no_new'            => 'required',
            'no_cukai' 	            => 'required',
            // 'jenis_penyertaan'      => 'required',
            // 'jenis_pendaftaran'     => 'required',
            // 'tarikh_ditubuhkan'     => 'required',
            // 'jenis_milikan_pejabat' => 'required',
            'hp_no'                 => 'required',
            'office_no'             => 'required',
            // 'fax_no'                => 'required',
            // 'parlimen_syarikat'     => 'required',
            'alamat1'               => 'required',
            'alamat2'               => 'required',
            'poskod'                => 'required',
            'bandar'                => 'required',
            'negeri'                => 'required',
            'business_alamat1'      => 'required',
            'business_alamat2'      => 'required',
            'business_poskod'       => 'required',
            'business_bandar'       => 'required',
            'business_negeri'       => 'required',
            'contact_name'          => 'required',
            'contact_jawatan'       => 'required',
            'contact_hp_no'         => 'required',
            // 'contact_email'         => 'required',
            // 'contact_name2'         => 'required',
            // 'contact_jawatan2'      => 'required',
            // 'contact_hp_no2'        => 'required',
            // 'contact_email2'        => 'required',


        ];
        $request->validate($validation, $messages);

        $check_contractor = Contractor::where('COEmail',$request->email)->first();

        if ($check_contractor != null){
            return response()->json([
                'error' => '1',
                'message' => 'Email telah didaftarkan.'
            ], 400);
        }

        $check_otp_tac_log = TacLog::where('TACCode', $request->otp)->where('TACEmail', $request->email)->latest('TACCD')->first();

        if($check_otp_tac_log){

            $diffInSeconds = Carbon::now()->diffInSeconds($check_otp_tac_log->TACCD);

            if ($diffInSeconds >= 300) {
                return response()->json([
                    'error' => '1',
                    'message' => 'OTP telah tamat tempoh dan melebihi 5 minit. Sila dapatkan OTP yang baru.'
                ], 403);
            }
        }
        else{
            return response()->json([
                'error' => '1',
                'message' => 'OTP tidak sah.'
            ], 404);
        }

        try {
            DB::beginTransaction();

			$autoNumber = new AutoNumber();
			$contractorCode = $autoNumber->generateContractorCode();

            $contractor = new Contractor();
            $contractor->CONo              	= $contractorCode;
            $contractor->COName             = $request->nama_syarikat ?? '';
            $contractor->COCompNo           = $request->ssm_no ?? '';
            $contractor->COBusinessNo       = $request->ssm_no_new ?? '';
            $contractor->COTaxNo            = $request->no_cukai ?? '';
            $contractor->CORegAddr          = $request->alamat1 ?? '';
            // $contractor->CORegAddr2           = $request->alamat2 ?? '';
            $contractor->CORegPostcode      = $request->poskod ?? '';
            $contractor->CORegCity          = $request->bandar ?? '';
            $contractor->COReg_StateCode    = $request->negeri ?? '';
            $contractor->COBusAddr          = $request->business_alamat1 ?? '';
            $contractor->COBusAddr2           = $request->business_alamat2 ?? '';
            $contractor->COBusPostcode      = $request->business_poskod ?? '';
            $contractor->COBusCity          = $request->business_bandar ?? '';
            $contractor->COBus_StateCode    = $request->business_negeri ?? '';
            $contractor->COPhone            = $request->hp_no ?? '';
            $contractor->COOfficePhone      = $request->office_no ?? '';
            $contractor->COFax             	= $request->fax_no ?? '';
            $contractor->COEmail            = $request->email ?? '';
            $contractor->COPICName          = $request->contact_name ?? '';
            $contractor->COPICPosition      = $request->contact_jawatan ?? '';
            $contractor->COPICICNo          = $request->email ?? '';
            $contractor->COPICPhone         = $request->contact_hp_no ?? '';
            $contractor->COPICName2         = $request->contact_name2 ?? '';
            $contractor->COPICPosition2     = $request->contact_jawatan2 ?? '';
            // $contractor->COPICICNo2         = $request->email ?? '';
            $contractor->COPICPhone2        = $request->contact_hp_no2 ?? '';
            // $contractor->COCheckSSM         = $request->email ?? '';
            // $contractor->COCheckDREAMS      = $request->email ?? '';
            // $contractor->COCheckSPJ         = $request->email ?? '';
            // $contractor->COCheckCTOS        = $request->email ?? '';
            // $contractor->COCheckCIDB        = $request->email ?? '';
            // $contractor->COCheckDBKL        = $request->email ?? '';
            // $contractor->COScore            = $request->email ?? '';
            // $contractor->COTemporary        = $request->email ?? '';
            $contractor->COActive           = '1';
            $contractor->COCB               = $contractorCode;
            $contractor->COMB               = $contractorCode;
            $contractor->save();

            $user = new User();
            $user->USCode       = $contractorCode;
            $user->USName       = $request->nama_syarikat ?? '';
            $user->USPwd        = Hash::make($request->password);
            $user->USType       = 'CO';
            $user->USEmail      = $request->email ?? '';
            $user->USResetPwd   = 0;
            $user->USActive     = 1;
            $user->USCB         = $contractorCode;
            $user->USMB         = $contractorCode;
            $user->save();

            $autoNumber = new AutoNumber();
            $CANo = $autoNumber->generateCertAppNo();

            $create_CA = new CertApp();
            $create_CA->CANo = $CANo;
            $create_CA->CA_CONo = $contractorCode;
            $create_CA->CA_CASCode = 'NEW';
            $create_CA->CA_CATCode = 'NEW';
            $create_CA->CA_CAPCode = 'COMP';
            $create_CA->CAActive = 1;
            $create_CA->CACB = $contractorCode;
            $create_CA->save();

			$this->sendMailV1($request,$user,$contractor);

            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Register account was failed!'.$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('publicUser.index'),
            'message' => 'Akaun anda telah berjaya didaftarkan.'
        ]);
    }

	private function getActivationCode(){

	  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	  $randomString = '';

	  for ($i = 0; $i < 20; $i++) {
		$index = rand(0, strlen($characters) - 1);
		$randomString .= $characters[$index];
	  }

	  return $randomString;
	}

	public function activate(Request $request, $activationCode = null){

		$message = 'The account has been activated successfully!';

        $customer = Customer::where('CSActivationCode',$activationCode)->where('CSActive',1)->orderby('CSID','desc')->first();
        if ($customer == null){
			$message = 'Invalid activation link!';
			return view('publicUser.auth.login', compact('message'));
        }

		if ($customer->CSRegister == 1){
			$message = 'This account has been activated!';
			return view('publicUser.auth.login', compact('message'));
        }

        try {
            DB::beginTransaction();

            $customer->CSRegister 		= 1;
            $customer->CSRegisterDate	= carbon::now();
            $customer->save();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();
			$message = $e;
        }

		return view('publicUser.auth.login', compact('message'));
    }

    private function sendMail(Request $request,$customer){

		if($request->email != null) {
            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $customer->CONo;
            $emailLog->ELType 	= 'Register';

            // Send Email
            $emailData = array(
                'id' => $customer->CONo,
                'name'  => $request->name ?? '',
                'email' => $request->email,
                'activationCode' => $customer->CSActivationCode ?? '',
                'domain' => config('app.url'),
            );

            try {
                Mail::send(['html' => 'email.newUserPublic'], $emailData, function($message) use ($emailData) {
                   // $message->from('parking@example.com', 'noreply');
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pendaftaran Akaun');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
				$emailLog->save();
            } catch (\Exception $e) {
                // $e->getLine ()
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
				$emailLog->save();
				return response()->json([
					'error' => '1',
					'message' => 'Sent email is failed!'.$e->getMessage()
				], 400);
            }

        }
    }

    private function sendMailV1(Request $request,$user,$customer){

		if($request->email != null) {
            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCB;
            $emailLog->ELType 	= 'Register';

            // Send Email
            $emailData = array(
                'id' => $user->USID,
                'name'  => $request->name ?? '',
                'email' => $request->email,
                'activationCode' => $customer->CSActivationCode,
                'domain' => config('app.url'),
            );

            try {
                Mail::send(['html' => 'email.newUserPublic'], $emailData, function($message) use ($emailData) {
                   // $message->from('parking@example.com', 'noreply');
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pendaftaran Akaun');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
				$emailLog->save();
            } catch (\Exception $e) {
                // $e->getLine ()
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
				$emailLog->save();
				return response()->json([
					'error' => '1',
					'message' => 'Sent email is failed!'.$e->getMessage()
				], 400);
            }

        }
    }

    public function sendOTP(Request $request){

        $data = $request->json()->all();
        $existID = $data['existID'];
        $email = $data['email'];
        $phone = '+6'.$data['phone'];
        $businessNo = $data['ssm_no'];

        if (strlen($request->email) <=0 ){
            return response()->json([
                'error' => '1',
                'message' => 'Sila lengkapkan Alamat Emel.'
            ], 400);
        }

        $contractor = Contractor::where('COEmail',$request->email)->first();

        if ($contractor != null && $existID == '0'){
            return response()->json([
                'error' => '1',
                'message' => 'E-mail anda telah didaftarkan sebelum ini.'
            ], 400);
        }

        if (strlen($businessNo) != 12){
            return response()->json([
                'error' => '1',
                'message' => 'Syarikat SSM No Baru tidak lengkap 12 digits.'
            ], 400);
        }

        /*
        $contractor = Contractor::where('COBusinessNo',$request->businessNo)->first();

        if ($contractor != null){
            return response()->json([
                'error' => '1',
                'message' => 'Syarikat SSM No anda telah didaftarkan sebelum ini.'
            ], 400);
        }*/

        $check_tac_log = TacLog::where('TACEmail', $email)->latest('TACCD')->first();

        if($check_tac_log){
            $diffInSeconds = Carbon::now()->diffInSeconds($check_tac_log->TACCD);

            if ($diffInSeconds <= 60) {
                return response()->json([
                    'error' => '1',
                    'message' => 'Sila tunggu 60 saat sebelum memohon OTP lagi.'
                ], 400);
            }
        }

        $otp = mt_rand(100000, 999999);

        $tacLog = new TacLog();
        $tacLog->TACCode = $otp;
        $tacLog->TACType = 'REGISTER EMAIL';
        $tacLog->TACEmail = $email;
        $tacLog->TACPhone = $phone;
        $tacLog->save();

//        $this->sendWhatsappOTP($request, $tacLog);
        $this->sendMailOTP($request, $tacLog);

        return response()->json([
            'message' => 'OTP berjaya dihantar!'
        ], 200);
    }

    private function sendWhatsappOTP($request,$tacLog){

        if($tacLog->TACPhone != null) {

            $data = '';
            try {
                $body   = '{
                    "to": "'.$tacLog->TACPhone.'",
                    "type": "template",
                    "template": {
                        "namespace": "'.config::get('app.wa_namespace').'",
                        "name": "otp",
                        "language": {
                            "code": "en",
                            "policy": "deterministic"
                        },
                        "components": [
                            {
                                "type": "body",
                                "parameters": [
                                    {
                                        "type": "text",
                                        "text": "'.$tacLog->TACCode.'"
                                    }
                                ]
                            },
                            {
                                "type": "button",
                                "sub_type": "url",
                                "index": 0,
                                "parameters": [
                                    {
                                        "type": "text",
                                        "text": "'.$tacLog->TACCode.'"
                                    }
                                ]
                            }
                        ]
                    }
                }';


                $httpClient = new \GuzzleHttp\Client([
                    'base_uri' => "https://waba.360dialog.io",
                    'headers' => [
                        'Content-Type' 	=> 'application/json',
                        'Accept' 		=> 'application/json',
                        'D360-Api-Key' => config::get('app.wa_apikey')
                    ],
                    'body'   => $body
                ]);
                $url = 'v1/messages';
                $httpRequest = $httpClient->post($url);
                $responseData = $httpRequest->getBody()->getContents();

                $refNo = 'IL'.Carbon::now()->getPreciseTimestamp(3);
                $integrationLog = new IntegrationLog();
                $integrationLog->ILNo = $refNo;
                $integrationLog->ILRefNo = $tacLog->TACPhone;
                $integrationLog->ILType = 'WABA';
                $integrationLog->ILPayload = $body;
                $integrationLog->ILResponse = $responseData;
                $integrationLog->ILComplete = 1;
                $integrationLog->save();

                } catch(\GuzzleHttp\Exception\ConnectException $e){
                    /*
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Connection Error'
                    ]);*/
                }
            }

    }
    private function sendMailOTP($request,$create_tac_log){

        if($create_tac_log->TACEmail != null) {
            $emailLog = new EmailLog();
            $emailLog->ELCB 	= 'SYSTEM';
            $emailLog->ELType 	= 'RegisterOTP';

            // Send Email
            $emailData = array(
                'email' => $create_tac_log->TACEmail,
                'otp' => $create_tac_log->TACCode,
                'domain' => config('app.url'),
            );

            try {
                Mail::send(['html' => 'email.newUserPublicOTP'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['email'])->subject('Permohonan Kata Laluan Sekali');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
                $emailLog->save();
            } catch (\Exception $e) {
                // $e->getLine ()
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
                $emailLog->save();
                return response()->json([
                    'error' => '1',
                    'message' => 'Sent email is failed!'.$e->getMessage()
                ], 400);
            }

        }
    }

    public function checkEmail(Request $request){

        $email = $request->email;

        $contractor = Contractor::where('COEmail',$email)
                    ->where('COStatus','NEW')
                    ->first();

        if(!$contractor){
            $contractor = 0;

        }else{
            $contractor['dokumenIC'] = $contractor->dokumenIC ?? 0;
            $contractor['dokumenFR'] = $contractor->dokumenFR ?? 0;
        }


        return $contractor;



    }

	public function registerPayment($id){

        $CONo = $id;

        $contractor = Contractor::where('CONo',$CONo)->first();

        $webSetting = WebSetting::first();
        $regFee = (float) $webSetting->RegFee;
        $serviceRegFee = (float) $webSetting->ServiceRegFee;
        $subTotalFee = $regFee + $serviceRegFee;
        $taxFee = $subTotalFee * $webSetting->TaxPercent /100;
        $totalFee = $taxFee + $subTotalFee;

        return view('publicUser.auth.registerPayment',
            compact('contractor', 'regFee','serviceRegFee','subTotalFee','taxFee','totalFee')
         );

    }


    public function createPayment(Request $request){

        $contractor = Contractor::where('CONo', $request->contractorCode)->first();

        $webSetting = WebSetting::first();
        $regFee = (float) $webSetting->RegFee;
        $serviceRegFee = (float) $webSetting->ServiceRegFee;
        $subTotalFee = $regFee + $serviceRegFee;
        $taxFee = $subTotalFee * $webSetting->TaxPercent;
        $totalFee = $taxFee + $subTotalFee;

        try{
            DB::beginTransaction();

            //CHANGE TO FUNCTION AFTER PAYMENT GATEWAY
            $autoNumber = new AutoNumber();
            $PLNo = $autoNumber->generatePaymentLogNo();

            $paymentLog = new PaymentLog();
            $paymentLog->PLNo       	= $PLNo;
            $paymentLog->PL_CONo    	= $contractor->CONo;
            $paymentLog->PLRefNo    	= $contractor->CONo;
			$paymentLog->PL_PLTCode		= 'REGISTER';
            $paymentLog->PLDesc     	= 'Daftar Syarikat';
            $paymentLog->PL_PSCode		= '00';
            $paymentLog->PLPaymentFee	= $totalFee;
            $paymentLog->PLCB			= $contractor->CONo;
            $paymentLog->PLActive   	= 1;
            $paymentLog->save();

            $contractor->CORegFee      = $regFee;
            $contractor->COServiceFee  = $serviceRegFee;
            $contractor->COTaxFee      = $taxFee;
            $contractor->COTotalFee    = $totalFee;

            $contractor->COStatus    = 'PAID';
            $contractor->CO_PLNo     = $PLNo;
            $contractor->save();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat syarikat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

//		$fileLocation = Storage::disk('local')->path('rev_mon_private_key.pem');
//
//        $rm = new RevenueMonster([
//            'clientId' => config::get('app.rm_clientid'),
//            'clientSecret' => config::get('app.rm_clientsecret'),
//            'privateKey' => file_get_contents($fileLocation),
//            'version' => 'stable',
//            'isSandbox' => false,
//        ]);
//
//        $redirectUrl = route('publicUser.register.statusPembayaran');
//        $notifyUrl =  Config('app.url').'/api/paymentStatus';
//
//        $title = 'Daftar Syarikat';
//        $amount = 1;
//        $detail = 'Daftar Syarikat';
//        $checkOutID = '';
//        $checkOutURL= '';
//
//        // create Web payment
//        try {
//            $wp = new WebPayment;
//            $wp->order->id 				= $paymentLog->PLNo;
//            $wp->order->title 			= $title;
//            $wp->order->currencyType 	= 'MYR';
//            $wp->order->amount 			= $amount;
//            $wp->order->detail 			= $detail;
//            $wp->method 				= ["ALIPAY_CN","TNG_MY","SHOPEEPAY_MY","BOOST_MY","GRABPAY_MY","PRESTO_MY"];
//            $wp->type 					= "WEB_PAYMENT";
//            $wp->order->additionalData 	= $detail;
//			$wp->storeId 				= config::get('app.rm_storeid');
//            $wp->redirectUrl 			= $redirectUrl;
//            $wp->notifyUrl 				= $notifyUrl ;
//            $wp->layoutVersion 			= 'v3';
//
//            $response = $rm->payment->createWebPayment($wp);
//            $checkOutID= $response->checkoutId; // Checkout ID
//            $checkOutURL= $response->url; // Payment gateway url
//
//            $paymentLog->PLCheckOutID = $checkOutID;
//            $paymentLog->PLPaymentLink = $checkOutURL ;
//            $paymentLog->Save();
//
//        } catch(ApiException $e) {
//
//            return response()->json([
//                'error'  => '1',
//                'statusCode' => $e->getCode(),
//                'errorCode' => $e->getErrorCode(),
//                'message' => $e->getMessage(),
//            ], 400);
//        } catch(ValidationException $e) {
//            return response()->json([
//                'error'  => '1',
//                'message' => $e->getMessage(),
//            ], 400);
//        } catch(Exception $e) {
//            return response()->json([
//                'error'  => '1',
//                'message' => $e->getMessage(),
//            ], 400);
//        }

        return response()->json([
            'success' => '1',
//            'redirect' => $checkOutURL,
            'redirect' => route('publicUser.register.statusPembayaran', ['orderId'=>$PLNo, 'status' => 'SUCCESS']),
        ]);
    }

    Public function updatePayment(Request $request){

        $status 		= $request->status;
        $paymentLogNo 	= $request->orderId;

        $data = [];

        $paymentLog = PaymentLog::where('PLNo',$paymentLogNo)->first();
        if ($paymentLog == null) {
            return view('publicUser.register.statusPembayaran',compact('data'));
        }

        $contractor = Contractor::where('CONo', $paymentLog->PLRefNo)->first();

        // Retrieve Web Payment Record

        $paymentStatusCode = '';

        if($status == 'SUCCESS' ){
            $paymentStatusCode='01';
        }elseif($status == 'FAILED' ){
            $paymentStatusCode='02';
        }elseif($status == 'CANCELLED'){
            $paymentStatusCode='03';
        }

        try {
            DB::beginTransaction();

            $autoNumber = new AutoNumber();

            //UPDATE PAYMENT LOG
            $paymentLog->PL_PSCode	= $paymentStatusCode;
            $paymentLog->PLMD		= Carbon::now();
            $paymentLog->save();

            //PAYMENT SUCCESS
            if ($status == 'SUCCESS') {

                $contractor->COStatus  = 'PAID';
                $contractor->COVerifyStatus  = 'NEW';
                $contractor->CORegisterRefNo    = $autoNumber->generateRegRefNo();
                $contractor->CO_PLNo	= $paymentLog->PLNo;
                $contractor->save();

                $contractAuthUsers = $contractor->contractAuthUser;

                $pegawaiController = new PegawaiController($this->dropdownService, $this->autoNumber);

                foreach($contractor->contractAuthUser as $index => $contractAuthUser){

                    $result = $pegawaiController->sendVerificationNotify($contractAuthUser->CAUNo);

                }

                $this->sendMailPaymentStatus($contractor->CONo,$paymentLogNo);

            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollback();
            throw $e;
        }

        $data = array(
            'paymentLogNo' 		=> $paymentLog->PLNo ?? '',
            'paymentStatusCode' => $paymentLog->PL_PSCode ?? '',
            'paymentFee' 		=> (float) $paymentLog->PaymentFee,
            'redirectURL' 		=> "",
        );

        return view('publicUser.register.statusPembayaran',compact('data'));
    }

    public function statusPembayaran(Request $request){

        $status 		= $request->status;
        $paymentLogNo 	= $request->orderId;

        $paymentLog = PaymentLog::where('PLNo',$paymentLogNo)->first();
        if ($paymentLog == null) {
            return view('publicUser.auth.statusPembayaran');
        }

        $contractor = Contractor::where('CONo', $paymentLog->PLRefNo)->first();
        if ($contractor->COStatus != 'PAID'){
            // Retrieve Web Payment Record

            $paymentStatusCode = '';

            if($status == 'SUCCESS' ){
                $paymentStatusCode='01';
            }elseif($status == 'FAILED' ){
                $paymentStatusCode='02';
            }elseif($status == 'CANCELLED'){
                $paymentStatusCode='03';
            }

            try {
                DB::beginTransaction();

                $autoNumber = new AutoNumber();

                //UPDATE PAYMENT LOG
                $paymentLog->PL_PSCode	= $paymentStatusCode;
                $paymentLog->PLMD		= Carbon::now();
                $paymentLog->save();

                //PAYMENT SUCCESS
                if ($status == 'SUCCESS') {

                    $contractor->COStatus  = 'PAID';
                    $contractor->CO_PLNo	= $paymentLog->PLNo;
                    $contractor->CORegisterRefNo    = $autoNumber->generateRegRefNo();
                    $contractor->save();

                    $contractAuthUsers = $contractor->contractAuthUser;

                    $pegawaiController = new PegawaiController($this->dropdownService, $this->autoNumber);

                    foreach($contractAuthUsers as $index => $contractAuthUser){

                        $result = $pegawaiController->sendVerificationNotify($contractAuthUser->CAUNo);

                    }

                    $this->sendMailPaymentStatus($contractor,$paymentLogNo);

                }

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollback();
                throw $e;
            }
        }

        return view('publicUser.auth.statusPembayaran',compact('contractor','paymentLog'));
    }

    public function registerPaid(Request $request){

        $autoNumber = new AutoNumber();

        try {
            DB::beginTransaction();

            //PAYMENT TRANSACTION HERE

            $paymentLogNo = "PL99999999999-X";

            //PAYMENT END HERE

            $contractorCode = $request->contractorCode;

            $contractor = Contractor::where('CONo',$contractorCode)->first();
            $contractor->COStatus           = 'PAID';
            $contractor->CORegisterRefNo    = $autoNumber->generateRegRefNo();

            $contractor->COMB               = $contractorCode;
            $contractor->save();

            //SEND EMAIL AFTER PAYMENT SUCCESS
            $paymentStatusCode = '';
            $paymentStatusCode = "SUCCESS";

            if($paymentStatusCode == "SUCCESS"){

                $this->sendMailPaymentStatus($contractor,$paymentLogNo);
                //$this->checkSSM($contractorCode);
                //$this->checkFaceRecognition($contractorCode);

            }elseif($paymentStatusCode == 'FAILED' ){


            }elseif($paymentStatusCode == 'CANCELLED'){


            }
            DB::commit();

            //CONTINUE CODE
            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.login.index'),
                'message' => 'Akaun anda telah berjaya didaftarkan. Sila tunggu email setelah pengesahan akaun anda diproses. Terima kasih.'
            ]);


        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Akaun anda tidak berjaya didaftar!'.$e->getMessage()
            ], 400);
        }
    }


    private function sendMailPaymentStatus($contractor, $paymentLogNo){

        // $paymentLog = PaymentLog::where('PLNo',$paymentLogNo)->first();
        $PLNo = $paymentLogNo;

        $emailLog = new EmailLog();
        $emailLog->ELCB 	= $contractor->CONo;
        $emailLog->ELType 	= 'Register Payment';
        $emailLog->ELSentTo =  $contractor->COEmail;
        // Send Email

        $tokenResult = $contractor->createToken('Personal Access Token');
        $token = $tokenResult->token;

        $emailData = array(
            'id' => $contractor->COID,
            'name'  => $contractor->COName ?? '',
            'email' => $contractor->COEmail,
            'domain' => config::get('app.url'),
            'token' => $token->id,
            'paymentLogNo' => $PLNo,
            'now' => Carbon::now()->format('j F Y'),
            'contractor' => $contractor,
        );

        try {
            Mail::send(['html' => 'email.paymentRegistration'], $emailData, function($message) use ($emailData) {
                $message->to($emailData['email'] ,$emailData['name'])->subject('Status Pembayaran Pendaftaran');
            });

            $emailLog->ELMessage = 'Success';
            $emailLog->ELSentStatus = 1;
        } catch (\Exception $e) {
            $emailLog->ELMessage = $e->getMessage();
            $emailLog->ELSentStatus = 2;
        }

        $emailLog->save();

    }

    public function resitPDF($id){

        $data = PaymentLog::leftjoin('MSPaymentStatus','PSCode','PL_PSCode')->where('PLNo',$id)->with('contractor')->first();

        $template = "RESIT";
        $download = false; //true for download or false for view
        $templateName = "REGISTER"; // Specific template name to check in generalPDF
        $view = View::make('general.templatePDF',compact('data','template','templateName'));
        $response = $this->generatePDF($view,$download);

        return $response;
    }

    public function checkSSM($id){

        $contractorCode = $id;
        $contractor = Contractor::where('CONo',$contractorCode)->first();
        $SSMNo = $contractor->COCompNo;

        try {

            //INTEGRATED SSM CHECK HERE

            $statusSSMCheck = "YES";

            //END HERE

            if($statusSSMCheck == "YES"){

                $contractor->COName             = "Setia Kawan SDN BHD" ;
                $contractor->COTaxNo            = "TX-98741256-X" ;
                $contractor->CORegAddr          = "156, Jalan Ampang, Kuala Lumpur City Centre" ;
                $contractor->CORegPostcode      = "50400" ;
                $contractor->CORegCity          = "KUALA LUMPUR" ;
                $contractor->COReg_StateCode    = "MY-14" ;
                $contractor->COBusAddr          = "156, Jalan Ampang, Kuala Lumpur City Centre" ;
                $contractor->COBusPostcode      = "50400" ;
                $contractor->COBusCity          = "KUALA LUMPUR" ;
                $contractor->COBus_StateCode    = "MY-14" ;
                $contractor->COPhone            = "0123684579" ;
                $contractor->COOfficePhone      = "03741985632" ;
                $contractor->COFax             	= "03741985632" ;
                $contractor->COCheckSSM         = "1" ;
                $contractor->COIntegrateResult  = "OK" ;//if exist OK, if not NO

            }else{

                $contractor->COCheckSSM         = "1" ;
                $contractor->COIntegrateResult  = "NO" ;//if exist OK, if not NO

            }

            $contractor->save();
            DB::commit();

            return $contractor;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Akaun SSM tidak berjaya disemak!'.$e->getMessage()
            ], 400);
        }

    }

    public function checkFaceRecognition($id){

        $contractorCode = $id;
        $contractor = Contractor::where('CONo',$contractorCode)->first();

        try {

            //INTEGRATED FACE RECOGNITION CHECK HERE
            $dokumenIC = $contractor->dokumenIC; // get file information and path - IC
            $dokumenFR = $contractor->dokumenFR; // get file information and path - Face Recognition
            $faceScore      = 80;

            //END HERE

            if($faceScore >= 30){

                $contractor->COFaceScore        = $faceScore;
                $contractor->COVerifyResult     = "OK" ;//OK , KIV, APPROVE, REJECT

            }else{

                $contractor->COFaceScore        = $faceScore;
                $contractor->COVerifyResult     = "REJECT" ;//OK , KIV, APPROVE, REJECT

            }

            $contractor->save();
            DB::commit();

            return $contractor;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Fail pengecaman muka tidak berjaya disemak!'.$e->getMessage()
            ], 400);
        }




    }

}
