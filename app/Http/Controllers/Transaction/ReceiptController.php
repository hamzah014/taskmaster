<?php

namespace App\Http\Controllers\Transaction;

use Carbon\Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Mail\SendReceipt;
use App\Models\AutoNumberDet;
use App\Models\ServiceApplication;
use App\Models\Receipt;
use App\Models\PaymentType;
use App\Models\ServicePrice;
use App\Models\AppStatus;
use App\Models\Country;
use App\Models\Embassy;
use App\Models\ServiceType;
use App\Models\Service;
use App\Models\Gender;
use App\Models\Religion;
use App\Models\DocType;
use App\Models\ResidentStatus;
use App\Models\FileAttach;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Validator;
use Auth;
use Illuminate\Support\Facades\Mail;
use PDF;


class ReceiptController extends Controller
{
    public function index()
    {
        $paymentType = PaymentType::where('PTActive', 1)->orderby('PTDesc', 'asc')->get()->pluck('PTDesc', 'PTCode');
        $serviceTypes = ServiceType::orderBy('STDesc', 'asc')->get();
        foreach ($serviceTypes as $serviceType) {
            $service[$serviceType->STDesc] =  Service::where('SVActive', 1)->where('SV_STCode', $serviceType->STCode)->orderby('SVDesc', 'asc')->get()->pluck('SVDesc', 'SVCode');
        }

        return view('transaction.receipt.index', compact('paymentType', 'service'));
    }

    public function create(Request $request, $id)
    {
        $receiptPage = '';
        $serviceApp = ServiceApplication::find($id);
        $country = Country::join('MSEmbassy', 'CTCode', 'EM_CTCode')->where('EMCode', $serviceApp->SA_EMCode)->First();
        $servicePrice = ServicePrice::where('SP_CTCode', $country->CTCode)->where('SP_SVCode', $serviceApp->SA_SVCode)->first();

        $currency = $country->CTCurrency ?? '';
        $serviceFee = $servicePrice->SPServiceFee ?? 0;
        $gstPercent = $country->CTGSTPercent ?? 0;
        $gstFee = $serviceFee * ($gstPercent / 100);
        $totalFee = $serviceFee + $gstFee;

        $receipt = null;
        $paymentType = PaymentType::where('PTActive', 1)->OrderBy('PTDesc', 'asc')->get()->pluck('PTDesc', 'PTCode');

        return view('transaction.receipt.form', compact('serviceApp', 'receipt', 'paymentType', 'currency', 'serviceFee', 'gstFee', 'totalFee', 'receiptPage'));
    }

    public function store(Request $request)
    {

        $messages = [
            'embassy.required'         => trans('message.embassy.required'),
            'service.required'         => trans('message.service.required'),
        ];

        $validation = [
            'appNo'         => 'required',
            'paymentType'     => 'required',
            'refNo'         => 'required',
        ];

        $request->validate($validation, $messages);

        $user = Auth::user();

        $paymentType = PaymentType::where('PTCode', $request->paymentType)->where('PTActive', 1)->First();
        if ($paymentType == null) {
            return response()->json([
                'error' => '1',
                'message' => trans('message.invalid.paymentType')
            ], 400);
        }

        $serviceApp = ServiceApplication::where('SANo', $request->appNo)->where('SA_ASCode', 'VERIFY')->First();
        if ($serviceApp == null) {
            return response()->json([
                'error' => '1',
                'message' => trans('message.invalid.serviceApp')
            ], 400);
        }

        $country = Country::join('MSEmbassy', 'CTCode', 'EM_CTCode')->where('EMCode', $serviceApp->SA_EMCode)->First();
        if ($country == null) {
            return response()->json([
                'error' => '1',
                'message' => trans('message.invalid.country.setup')
            ], 400);
        }

        $servicePrice = ServicePrice::where('SP_CTCode', $country->CTCode)->where('SP_SVCode', $serviceApp->SA_SVCode)->first();
        if ($servicePrice == null) {
            return response()->json([
                'error' => '1',
                'message' => trans('message.invalid.servicePrice.setup')
            ], 400);
        }

        $currency = $country->CTCurrency;
        $serviceFee = $servicePrice->SPServiceFee;
        $gstPercent = $country->CTGSTPercent;
        $gstFee = $serviceFee * ($gstPercent / 100);
        $totalFee = $serviceFee + $gstFee;

        try {
            DB::beginTransaction();

            $autoNumberDet = new AutoNumberDet();
            $receiptNo = $autoNumberDet->generateReceiptNo();

            $receipt = new Receipt();
            $receipt->RCNo             = $receiptNo;
            $receipt->RCDate         = Carbon::now()->format('Y-m-d');
            $receipt->RC_DSCode     = 'RC-NEW';
            $receipt->RC_SANo         = $request->appNo;
            $receipt->RC_PTCode     = $request->paymentType;
            $receipt->RCRefNo         = $request->refNo;
            $receipt->RCCurrency    = $currency;
            $receipt->RCServiceFee     = $serviceFee;
            $receipt->RCGSTPercent    = $gstPercent;
            $receipt->RCGSTFee         = $gstFee;
            $receipt->RCTotalFee     = $totalFee;
            $receipt->RCCB             = $user->USCode;
            $receipt->RCCD            = Carbon::now();
            $receipt->Save();

            $serviceApp->SA_ASCode     = 'PAYMENT';
            $serviceApp->SA_RCNo     = $receiptNo;
            $serviceApp->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('message.receipt.fail') . $e->getMessage()
            ], 400);
        }

        // Get the customer from application
        $customer = ServiceApplication::join('MSCustomer', 'CSCode', 'SA_CSCode')->where('SANo', $request->appNo)->first();

        // Get the receipt data
        $receiptId = Receipt::where('RCNo', $receiptNo)->first()->RCID;

        $data = Receipt::select(
            'TRReceipt.*',
            'TRServiceApplication.*'
        )
            ->join('TRServiceApplication', 'SANo', 'RC_SANo')
            ->join('MSCustomer', 'CSCode', 'SA_CSCode')
            ->where('RCID', $receiptId)
            ->first();

        $reportSize = 'a5';
        $reportOrientation = 'landscape';
        $reportName = 'Receipt';
        // $headerHtml = \View::make('report.report_header', compact('reportName', 'reportOrientation', 'dateFrom', 'dateTo'))->render();

        $pdf = PDF::loadView('transaction.receipt..pdf', compact('data'))
            //->setOption('header-html', $headerHtml)
            ->setPaper($reportSize, $reportOrientation);

        $pdfFileName = $receiptNo . '.pdf';

        $message = new SendReceipt($data);
        $message->attachData($pdf->output(), $pdfFileName);

        Mail::to($customer->CSEmail)->send($message);


        // Send email to customer
        // Mail::to($customer->CSEmail)->send(new SendReceipt($data))->attachData($pdf->output(), "receipt.pdf");

        return response()->json([
            'success' => '1',
            'redirect' => route('trans.receipt.edit', $receipt->RCID),
            'message' => 'Receipt has been created successfully'
        ]);
    }

    public function editReceipt($id)
    {

        $receiptPage = 'Y';
        $receipt = Receipt::join('MSDocStatus', 'DSCode', 'RC_DSCode')->where('RCID', $id)->first();
        $serviceApp = ServiceApplication::where('SANo', $receipt->RC_SANo)->first();
        $paymentType = PaymentType::where('PTActive', 1)->OrderBy('PTDesc', 'asc')->get()->pluck('PTDesc', 'PTCode');

        return view('transaction.receipt.form', compact('receipt', 'paymentType', 'serviceApp', 'receiptPage'));
    }

    public function edit($id)
    {

        $receiptPage = 'N';
        $page = $request->page ?? '';
        $receipt = Receipt::join('MSDocStatus', 'DSCode', 'RC_DSCode')->where('RCID', $id)->first();
        $serviceApp = ServiceApplication::where('SANo', $receipt->RC_SANo)->first();
        $paymentType = PaymentType::where('PTActive', 1)->OrderBy('PTDesc', 'asc')->get()->pluck('PTDesc', 'PTCode');

        return view('transaction.receipt.form', compact('receipt', 'paymentType', 'serviceApp', 'receiptPage'));
    }

    public function update(Request $request, $id)
    {

        $messages = [
            'country.required'         => trans('message.country.required'),
            'embassy.required'         => trans('message.embassy.required'),
            'serviceType.required'  => trans('message.serviceType.required'),
            'service.required'         => trans('message.service.required'),
        ];

        $validation = [
            'appNo'         => 'required',
            'paymentType'     => 'required',
            'refNo'         => 'required',
        ];

        $request->validate($validation, $messages);

        $user = Auth::user();

        $receipt = Receipt::where('RCID', $id)->first();
        if ($receipt == null) {
            return response()->json([
                'error' => '1',
                'message' => trans('message.invalid.receipt')
            ], 400);
        }

        $paymentType = PaymentType::where('PTCode', $request->paymentType)->where('PTActive', 1)->First();
        if ($paymentType == null) {
            return response()->json([
                'error' => '1',
                'message' => trans('message.invalid.paymentType')
            ], 400);
        }

        $serviceApp = ServiceApplication::where('SANo', $receipt->RC_SANo)->First();
        if ($serviceApp == null) {
            return response()->json([
                'error' => '1',
                'message' => trans('message.invalid.serviceApp')
            ], 400);
        }
        $currency = $receipt->RCCurrency;
        $serviceFee = $receipt->RCServiceFee;
        $gstPercent = $receipt->RCGSTPercent;
        $gstFee = $serviceFee * ($gstPercent / 100);
        $totalFee = $serviceFee + $gstFee;

        try {
            DB::beginTransaction();

            $receipt->RC_SANo         = $request->appNo;
            $receipt->RC_PTCode     = $request->paymentType;
            $receipt->RCRefNo         = $request->refNo;
            $receipt->RCCurrency    = $currency;
            $receipt->RCServiceFee     = $serviceFee;
            $receipt->RCGSTPercent    = $gstPercent;
            $receipt->RCGSTFee         = $gstFee;
            $receipt->RCTotalFee     = $totalFee;
            $receipt->RCMB             = $user->USCode;
            $receipt->RCMD            = Carbon::now();
            $receipt->Save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('message.receipt.fail') . $e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('trans.receipt.edit', $id),
            'message' => 'Receipt has been updated successfully!'
        ]);
    }

    public function validation(Request $request)
    {

        //log::debug( $request);

        $messages = [];

        $validation = [
            //    'compName' 	=> 'nullable',
            //    'ssmNo' 	=> 'nullable',
        ];

        $request->validate($validation, $messages);

        $total = 0;

        return $total;
    }

    public function datatable(Request $request)
    {
        //log::debug($request);


        $dateFrom         = $request->dateFrom;
        $dateTo         = $request->dateTo;
        $paymentType     = $request->paymentType;
        $service         = $request->service;
        $receiptNo         = $request->receiptNo;

        $receipt = Receipt::leftjoin('TRServiceApplication', 'SANo', 'RC_SANo')
            ->leftjoin('MSEmbassy', 'EMCode', 'SA_EMCode')
            ->leftjoin('MSService', 'SVCode', 'SA_SVCode')
            ->leftjoin('MSPaymentType', 'PTCode', 'RC_PTCode')
            ->leftjoin('MSDocStatus', 'DSCode', 'RC_DSCode')
            ->when($paymentType != null, function ($query) use ($paymentType) {
                $query->where('RC_PTCode', '=', $paymentType);
            })
            ->when($service != null, function ($query) use ($service) {
                $query->where('SA_SVCode', '=', $service);
            })
            ->when($receiptNo != null, function ($query) use ($receiptNo) {
                $query->where('RCNo', '=', $receiptNo);
            })
            ->when((isset($dateFrom) && $dateFrom != null) && (isset($dateTo) && $dateTo != null), function ($query) use ($dateFrom, $dateTo) {
                $query->where('RCDate', '>=', Carbon::parse($dateFrom)->startOfDay())
                    ->where('RCDate', '<=', Carbon::parse($dateTo)->endOfDay());
            })
            ->where('RC_DSCode', 'RC-NEW')
            ->orderBy('RCNo', 'asc')
            ->get();

        return datatables()->of($receipt)
            ->addIndexColumn()
            ->editColumn('RCNo', function ($row) {
                return $row['RCNo'];
            })->editColumn('RCDate', function ($row) {
                return [
                    'display' => e(carbon::parse($row->RCDate)->format('d/m/Y')),
                    'timestamp' => carbon::parse($row->RCDate)->timestamp
                ];
            })->addColumn('action', function ($row) {
                $data =  '<b><a class="mb-6 waves-effect waves-light btn-small gradient-45deg-brown-brown" href="' . route('trans.receipt.editReceipt', [$row['RCID']]) . '" target="_blank">View</a></b>';
                //$data =  $data .'<b><a class="mb-6 waves-effect waves-light btn-small gradient-45deg-purple-deep-orange" href="'.route('trans.receipt.delete',[$row['RCID']]).'" target="_blank">Delete</a></b>';

                $data = $data . '<a target="_blank" type="button" class="mb-6 waves-effect waves-light btn-small gradient-45deg-purple-deep-orange" id="delete" data-id="' . $row['RCID'] . '" data-url="' . route('trans.receipt.delete', [$row['RCID']]) . '">Delete</a>';
                return $data;
            })->rawColumns(['SANo', 'action'])
            ->make(true);
    }

    public function delete(Request $request)
    {

        try {
            DB::beginTransaction();

            $receipt = Receipt::find($request->id);
            $receipt->RC_DSCode     = 'RC-DEL';
            $receipt->RCDelete        = 1;
            $receipt->RCDB             = Auth::user()->USCode;
            $receipt->RCDD            = Carbon::now();
            $receipt->Save();

            $serviceApp = ServiceApplication::where('SANo', $receipt->RC_SANo)->first();
            $serviceApp->SA_ASCode     = 'VERIFY';
            $serviceApp->SA_RCNo     = '';
            $serviceApp->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('message.receipt.fail') . $e->getMessage()
            ], 400);
        }

        $routeURL = '';

        if ($serviceApp->SA_SVCode == 'JPN-BR') {
            $routeURL = route('trans.serviceApplication.birthRegistration.edit', $serviceApp->SAID);
        } else if ($serviceApp->SA_SVCode == 'JPN-MR') {
            $routeURL = route('trans.serviceApplication.marriageRegistration.edit', $serviceApp->SAID);
        } else if ($serviceApp->SA_SVCode == 'JPN-DR') {
            $routeURL = route('trans.serviceApplication.deathRegistration.edit', $serviceApp->SAID);
        } else {
            $routeURL = route('trans.receipt.index');
        }

        return response()->json([
            'success' => '1',
            'redirect' => $routeURL,
            'message' => 'Receipt is deleted successfully'
        ]);
    }

    public function populateServiceApplication(Request $request)
    {

        $serviceApp = ServiceApplication::where('SANo', $request->appNo)->where('SA_ASCode', 'VERIFY')->first();
        if ($serviceApp == null) {
            return response()->json([
                'error' => '1',
                'message' => 'Application Number not found!'
            ], 400);
        }

        $country = Country::join('MSEmbassy', 'CTCode', 'EM_CTCode')->where('EMCode', $serviceApp->SA_EMCode)->First();
        /*if ($country == null){
			return response()->json([
				'error' => '1',
				'message' => trans('message.invalid.country.setup')
			], 400);
		}*/

        $servicePrice = ServicePrice::where('SP_CTCode', $country->CTCode)->where('SP_SVCode', $serviceApp->SA_SVCode)->first();
        /*if ($servicePrice == null){
			return response()->json([
				'error' => '1',
				'message' => trans('message.invalid.servicePrice.setup')
			], 400);
		}*/

        $currency = $country->CTCurrency ?? '';
        $serviceFee = $servicePrice->SPServiceFee ?? 0;
        $gstPercent = $country->CTGSTPercent ?? 0;
        $gstFee = $serviceFee * ($gstPercent / 100);
        $totalFee = $serviceFee + $gstFee;

        $data = array(
            'currency'     => $currency,
            'serviceFee' => $serviceFee,
            'gstFee'     => $gstFee,
            'totalFee'     => $totalFee,
        );

        return response()->json($data);
    }


    public function printForm(Request $request, $id)
    {

        $data = Receipt::select(
            'TRReceipt.*',
            'TRServiceApplication.*'
        )
            ->join('TRServiceApplication', 'SANo', 'RC_SANo')
            ->join('MSCustomer', 'CSCode', 'SA_CSCode')
            ->where('RCID', $id)
            ->first();

        /*
		if ($data->BRC_GDCode ='1'){
			$boy='X';
		}elseif($data->BRC_GDCode ='2'){
			$girl='X';
		}else{
			$doubt='X';
		}

		$param = array(
			'boy' 		=> $boy ?? '',
			'girl' 		=> $girl ?? '',
			'doubt'		=> $doubt ?? '',
		);*/


        $reportSize = 'a5';
        $reportOrientation = 'landscape';
        $reportName = 'Receipt';
        // $headerHtml = \View::make('report.report_header', compact('reportName', 'reportOrientation', 'dateFrom', 'dateTo'))->render();

        $pdf = PDF::loadView('transaction.receipt..pdf', compact('data'))
            //->setOption('header-html', $headerHtml)
            ->setPaper($reportSize, $reportOrientation);

        $pdfFileName = $data['BRNo'] . '.pdf';

        return $pdf->download($pdfFileName);
    }
}
