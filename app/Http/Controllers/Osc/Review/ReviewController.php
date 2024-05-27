<?php

namespace App\Http\Controllers\Osc\Review;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SchedulerController;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Mail;


use App\Http\Requests;
use App\Models\AutoNumber;
use App\Models\CertApp;
use App\Models\Contractor;
use App\Models\ContractorComp;
use App\Models\ContractorCompOfficer;
use App\Models\ContractorCompShareholder;
use App\Models\PaymentLog;
use App\Models\EmailLog;
use App\Models\Role;
use App\Models\Customer;
use App\Models\FileAttach;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Session;
use App\Services\DropdownService;

class ReviewController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){


        return view('osc.review.index'
        );
    }

    public function view($id){

        $contractor = Contractor::where('CONo',$id)->first();

        $contractorCode = $contractor->CONo;

        $contractorComp = ContractorComp::select('MSContractorComp.*','SSMCSDesc AS COC_companyStatusDesc',
                            'SSMCTDesc AS COC_companyTypeDesc','SSMSCDesc AS COC_statusOfCompanyDesc',
                            'RA.SSMSTDesc AS COC_ra_stateDesc','BA.SSMSTDesc AS COC_ba_stateDesc',
                            'BS.SSMSTDesc AS COC_bs_auditFirmStateDesc')
                            ->leftjoin('SSM_COMPANY_STATUS','SSMCSCode','COC_companyStatus','' )
                            ->leftjoin('SSM_COMPANY_TYPE','SSMCTCode','COC_companyType' )
                            ->leftjoin('SSM_STATUS_OF_COMPANY','SSMSCCode','COC_statusOfCompany' )
                            ->leftjoin('SSM_STATE AS RA','RA.SSMSTCode','COC_ra_state' )
                            ->leftjoin('SSM_STATE AS BA','BA.SSMSTCode','COC_ba_state' )
                            ->leftjoin('SSM_STATE AS BS','BS.SSMSTCode','COC_bs_auditFirmState' )
                            ->where('COC_CONo', $id)->first();

        $contractorCompOfficer = ContractorCompOfficer::select('MSContractorCompOfficer.*','SSMSTDesc AS COCO_stateDesc')
                                                            ->leftjoin('SSM_STATE','SSMSTCode','COCO_state' )
                                                            ->where('COCO_CONo', $id)->get();
        $contractorCompShareholder = ContractorCompShareholder::where('COCS_CONo', $id)->get();
        $paymentLog = PaymentLog::leftjoin('MSPaymentStatus','PSCode','PL_PSCode')->where('PLRefNo', $id)->where('PL_PLTCode','REGISTER')->get();

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

        $contractor['fileIC'] = $fileIC->FAGuidID;
        $contractor['fileFR'] = $fileFR->FAGuidID;
        $contractor['fileF9'] = $fileF9->FAGuidID;

        $acceptStatus = $this->dropdownService->acceptStatus();

        return view('osc.review.view',
        compact('contractor','acceptStatus','contractorComp','contractorCompOfficer','contractorCompShareholder','paymentLog')
        );
    }

    public function paid($id)
    {
        try {

            $contractor = Contractor::where('CONo', $id)->first();
            $contractor->COStatus = 'PAID';
            $contractor->save();

            return redirect()->back()->with('success', 'Maklumat berjaya dikemaskini.');

//            return response()->json([
//                'success' => '1',
//                'redirect' => route('osc.review.view', [$id]),
//                'message' => 'Maklumat berjaya dikemaskini.'
//            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request){


        $messages = [
            'submitStatus.required' 		        => 'Status pengesahan diperlukan.',
        ];

        $validation = [

            'submitStatus' 	            => 'required',

        ];
        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $contractorCode = $request->existID;
            $submitStatus = $request->submitStatus;

            $contractor = Contractor::where('CONo',$contractorCode)->first();

            if($submitStatus == 'APPROVE' && $submitStatus != $contractor->COVerifyResult ){

                $user = User::where('USCode',$contractorCode)->first();
                if ($user == null){
                    $user = new User();
                    $user->USCB     = $contractorCode;
                }
                $user->USCode       = $contractorCode;
                $user->USName       = $contractor->COName ?? '';
                $user->USPwd        = Hash::make('123456');
                $user->USType       = 'CO';
                $user->USEmail      = $contractor->COEmail ?? '';
                $user->USResetPwd   = 0;
                $user->USActive     = 1;
                $user->USMB         = $contractorCode;
                $user->save();

                $autoNumber = new AutoNumber();
                $CANo = $autoNumber->generateCertAppNo();

                $certApp = CertApp::where('CA_CONo',$contractorCode)->first();
                if ($certApp == null){
                    $certApp = new CertApp();
                    $certApp->CACB  = $contractorCode;
                }
                $certApp->CANo = $CANo;
                $certApp->CA_CONo = $contractorCode;
                $certApp->CA_CASCode = 'NEW';
                $certApp->CA_CATCode = 'NEW';
                $certApp->CA_CAPCode = 'COMP';
                $certApp->CAActive = 1;
                $certApp->CACB = $contractorCode;
                $certApp->save();
            }

            $contractor->COStatus = $submitStatus;
            $contractor->COVerifyResult = $submitStatus;
            $contractor->save();

            DB::commit();

            if($submitStatus == 'APPROVE'){
                $user = User::where('USCode',$contractorCode)->first();
                $this->sendEmail($contractor,$user);
            }

            return response()->json([
                'success' => '1',
                'redirect' => route('osc.review.index'),
                'message' => 'Akaun kontraktor telah berjaya didaftarkan.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Akaun kontraktor Tidak Berjaya!'.$e->getMessage()
            ], 400);
        }


    }

    //{{--Working Code Datatable with indexNo--}}
    public function contractorDatatable(Request $request){

        $query = Contractor::orderby('COID', 'desc')
                //where('COVerifyResult', 'KIV')
                //->where('COStatus', 'PAID')
                //->where('COIntegrateResult', 'OK')
                ->get();

        $count = 0;

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('CONo', function($row) {

                $route = route('osc.review.view',[$row->CONo] );

                $result = '<a href="'.$route.'">'.$row->CONo.'</a>';

                return $result;
            })
            ->editColumn('COCD', function($row) {
                $carbonDatetime = Carbon::parse($row->COCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->addColumn('action', function($row) {

                $result = '<a class="btn btn-danger btn-lg" onclick="updateStatus(\''.$row->CONo.'\',\'1\')">Approve</a>&nbsp';

                $result .= '<a class="btn btn-primary btn-lg" onclick="updateStatus(\''.$row->CONo.'\',\'0\')">Reject</a>';

                return $result;

            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','CONo', 'COCD','action'])
            ->make(true);
    }


    private function sendEmail($contractor,$user){

        $emailLog = new EmailLog();
        $emailLog->ELCB 	= $contractor->CONo;
        $emailLog->ELType 	= 'Account Creation';
        $emailLog->ELSentTo =  $contractor->COEmail;
        // Send Email


        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        $emailData = array(
            'id' => $contractor->COID,
            'name'  => $contractor->COName ?? '',
            'email' => $contractor->COEmail,
            'domain' => Config::get('app.url'),
            'token' => $token->id,
        );

        try {
            Mail::send(['html' => 'email.resetPasswordPublic'], $emailData, function($message) use ($emailData) {
                $message->to($emailData['email'] ,$emailData['name'])->subject('Set Kata Laluan');
            });

            $emailLog->ELMessage = 'Success';
            $emailLog->ELSentStatus = 1;
        } catch (\Exception $e) {
            $emailLog->ELMessage = $e->getMessage();
            $emailLog->ELSentStatus = 2;
        }

        $emailLog->save();

    }
}
