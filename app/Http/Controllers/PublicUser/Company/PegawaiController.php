<?php

namespace App\Http\Controllers\PublicUser\Company;

use App\Helper\Custom;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Symfony\Component\HttpFoundation\Response;
use Validator;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use Yajra\DataTables\DataTables;

use App\Http\Requests;
use App\Models\AutoNumber;
use App\Models\ContractorAuth;
use App\Models\ContractorAuthUser;
use App\Models\Role;
use App\Models\Customer;
use App\Models\EmailLog;
use App\Models\PaymentLog;
use App\Models\SSMCompany;
use App\Models\WhatsappMessage;
use App\Services\DropdownService;
use Mail;

class PegawaiController extends Controller
{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        $user = Auth::user();

        return view('publicUser.company.pegawai.index'
        );

    }

    public function create(){

        $user = Auth::user();

        return view('publicUser.company.pegawai.create'
        );

    }

    public function store(Request $request){

        $messages = [
            'cauth_name.required'       => 'Tajuk Mesyuarat diperlukan.',
            'cauth_ic.required'         => 'Tajuk Mesyuarat diperlukan.',
            'cauth_email.required'      => 'Tajuk Mesyuarat diperlukan.',
            'cauth_email.email'         => 'EMAIL WOII.',
            'cauth_phone.required'      => 'Tajuk Mesyuarat diperlukan.',
        ];

        $validation = [
            'cauth_name' => 'required|string',
            'cauth_ic' => 'required|string',
            'cauth_email' => 'required|string|email',
            'cauth_phone' => 'required|string',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();
            $custom = new Custom();

            $user = Auth::user();
            $COANo = $this->autoNumber->generateCOANo();
            $verifyCode = $this->autoNumber->generateActivationCode();

            $cauth_name     = $request->cauth_name;
            $cauth_ic       = $request->cauth_ic;
            $cauth_email    = $request->cauth_email;
            $cauth_phone    = $request->cauth_phone;

            $searchEmail = ContractorAuthUser::where('CAUEmail',$cauth_email)->first();

            if($searchEmail){
                
                return response()->json([
                    'error' => 1,
                    'message' => 'Emel telah wujud. Sila guna Emel yang lain untuk pengesahan pegawai.'
                ],400);
            }

            $contractAuthUser = new ContractorAuthUser();
            $contractAuthUser->CAUNo                 = $COANo;
            $contractAuthUser->CAU_CONo              = $user->USCode;
            $contractAuthUser->CAUIDNo               = $cauth_ic;
            $contractAuthUser->CAUName               = $cauth_name;
            $contractAuthUser->CAUEmail              = $cauth_email;
            $contractAuthUser->CAUPhoneNo            = $cauth_phone;
            $contractAuthUser->CAUVerificationCode   = $verifyCode;
            $contractAuthUser->CAUStatus             = 'PENDING';
            $contractAuthUser->CAUCB                 = $user->USCode;
            $contractAuthUser->CAUMB                 = $user->USCode;
            $contractAuthUser->save();

            $contractor = $contractAuthUser->contractor;
            $company = $contractor->COName ?? "";

            //send whatsapp
            if($cauth_phone){
                $whatsappMessage = new WhatsappMessage();
                $sendType = 'S';
                $message = $company;
    
                try {
                    $result = $custom->sendWhatsappDirector($cauth_phone,$company,$verifyCode);
    
                    $whatsappMessage->WMMessage = $message;
                    $whatsappMessage->WMPhoneNoT = $cauth_phone;
                    $whatsappMessage->WMRespond = $result;
                    $whatsappMessage->WMType = $sendType;
                } catch (\Exception $e) {
                    $whatsappMessage->WMMessage = $message;
                    $whatsappMessage->WMPhoneNoT = $cauth_phone;
                    $whatsappMessage->WMRespond = $e->getMessage();
                    $whatsappMessage->WMType = $sendType;
                }
                $whatsappMessage->save();
            }

            //send email
            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCode;
            $emailLog->ELType 	= 'Contractor Authorize User';
            $emailLog->ELSentTo =  $cauth_email;

            $emailData = array(
                'id' => $contractAuthUser->CAUID,
                'name'  => $cauth_name,
                'email' => $cauth_email,
                'dataCO' => $user,
                'dataCOA' => $contractAuthUser,
                'domain' => env('APP_URL'),
            );

            try {
                Mail::send(['html' => 'email.authorizeUserNotification'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pengesahan Pegawai Bagi Pengguna/Kontraktor');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
            } catch (\Exception $e) {
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
            }

            $emailLog->save();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.company.pegawai.index'),
                'message' => 'Maklumat pegawai berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat pegawai tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }
    }

    public function viewPegawai(Request $request){

        $id = $request->pegawaiID;

        $contractAuthUser = ContractorAuthUser::where('CAUNo',$id)->first();

        $activeAcc = ($contractAuthUser->CAU_USCode != "") ? 1 : 0;

        return view('publicUser.company.pegawai.viewPegawai',
        compact('contractAuthUser','activeAcc')
        );


    }

    public function sendVerificationCode(Request $request){

        $messages = [
            'cauth_name.required'       => 'Nama pegawai diperlukan.',
            'cauth_ic.required'         => 'No IC/Password pegawai diperlukan.',
            'cauth_email.required'      => 'Emel pegawai diperlukan.',
            'cauth_email.email'         => 'Sila masukkan emel dalam format emel.',
            'cauth_phone.required'      => 'No. Telefon diperlukan.',
        ];

        $validation = [
            'cauth_name' => 'required|string',
            'cauth_ic' => 'required|string',
            'cauth_email' => 'required|string|email',
            'cauth_phone' => 'required|string',
        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $verifyCode = $this->autoNumber->generateActivationCode();

            $cauth_no       = $request->cauth_no;
            $cauth_name     = $request->cauth_name;
            $cauth_ic       = $request->cauth_ic;
            $cauth_email    = $request->cauth_email;
            $cauth_phone    = $request->cauth_phone;

            $searchEmail = ContractorAuthUser::where('CAUEmail',$cauth_email)->where('CAUNo', '!=' ,$cauth_no)->first();

            if($searchEmail){
                
                return response()->json([
                    'error' => 1,
                    'message' => 'Emel telah wujud. Sila guna Emel yang lain untuk pengesahan pegawai.'
                ],400);
            }

            $contractAuthUser = ContractorAuthUser::where('CAUNo',$cauth_no)->first();
            $contractAuthUser->CAUIDNo               = $cauth_ic;
            $contractAuthUser->CAUName               = $cauth_name;
            $contractAuthUser->CAUEmail              = $cauth_email;
            $contractAuthUser->CAUPhoneNo            = $cauth_phone;
            $contractAuthUser->CAUVerificationCode   = $verifyCode;
            $contractAuthUser->CAUStatus             = 'PENDING';
            $contractAuthUser->CAUMB                 = $user->USCode;
            $contractAuthUser->save();

            $result = $this->sendVerificationNotify($contractAuthUser->CAUNo);

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('publicUser.company.pegawai.index'),
                'message' => 'Maklumat pegawai berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat pegawai tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }
    }

    public function sendVerificationNotify($CAUNo){

        try{
            $user = Auth::user();
            $custom = new Custom();

            $contractAuthUser = ContractorAuthUser::where('CAUNo',$CAUNo)->first();
            $contractor = $contractAuthUser->contractor;

            $cauth_no       = $contractAuthUser->CAUNo;
            $cauth_name     = $contractAuthUser->CAUName;
            $cauth_ic       = $contractAuthUser->CAUIDNo;
            $cauth_email    = $contractAuthUser->CAUEmail;
            $cauth_phone    = $contractAuthUser->CAUPhoneNo;
            $cauth_CONo     = $contractAuthUser->CAU_CONo;
            $verifyCode     = $contractAuthUser->CAUVerificationCode;

            //send emai
            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCode ?? $cauth_CONo;
            $emailLog->ELType 	= 'Contractor Authorize User';
            $emailLog->ELSentTo =  $cauth_email;

            $emailData = array(
                'id' => $contractAuthUser->CAUID,
                'name'  => $cauth_name,
                'email' => $cauth_email,
                'dataCO' => $contractor,
                'dataCOA' => $contractAuthUser,
                'domain' => env('APP_URL'),
            );

            try {
                Mail::send(['html' => 'email.authorizeUserNotification'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['name'])->subject('Pengesahan Pegawai Bagi Pengguna/Kontraktor');
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
            } catch (\Exception $e) {
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
            }

            $emailLog->save();
            
            $company = $contractor->COName ?? "";

            //send whatsapp
            if($cauth_phone){
                $whatsappMessage = new WhatsappMessage();
                $sendType = 'S';
                $message = $company;
    
                try {
                    $result = $custom->sendWhatsappDirector($cauth_phone,$company,$verifyCode);
    
                    $whatsappMessage->WMMessage = $message;
                    $whatsappMessage->WMPhoneNoT = $cauth_phone;
                    $whatsappMessage->WMRespond = $result;
                    $whatsappMessage->WMType = $sendType;
                } catch (\Exception $e) {
                    $whatsappMessage->WMMessage = $message;
                    $whatsappMessage->WMPhoneNoT = $cauth_phone;
                    $whatsappMessage->WMRespond = $e->getMessage();
                    $whatsappMessage->WMType = $sendType;
                }
                $whatsappMessage->save();
            }

            return $emailLog;


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat pegawai tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }


    }

    //{{--Working Code Datatable--}}
    public function pegawaiDatatable(Request $request){

        $user = Auth::user();
        $usercode = $user->USCode;

        $query = ContractorAuthUser::where('CAU_CONo',$usercode)->get();

        $count = 0;

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('CAUName', function($row){

                $route = "#viewModal";

                $result = '<a onclick="viewPegawai(\''.$row->CAUNo.'\')" class="new modal-trigger text-decoration-underline" href="'.$route.'">'.$row->CAUName.' </a>';

                return $result;

            })
            ->editColumn('CAUStatus', function($row){

                switch ($row->CAUStatus) {
                    case 'ACTIVE':
                        $status = 'AKTIF';
                        break;

                    case 'PENDING':
                        $status = 'MENUNGGU PENGAKTIFAN';
                        break;

                    case 'INACTIVE':
                        $status = 'TIDAK AKTIF';
                        break;
                    
                    default:
                        $status = '-';
                        break;
                }

                return $status;

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','CAUIDNo','CAUName','CAUStatus'])
            ->make(true);

    }



}
