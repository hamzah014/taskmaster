<?php

namespace App\Http\Controllers\Inquiry;

use Carbon\Carbon;
use App\Http\Requests;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\EmployeeVaccine;
use App\Models\ComplaintCase;
use App\Models\CaseStatus;
use App\Models\Category;
use App\Models\Country;
use App\Models\FileAttach;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Validator;
use Auth;


class ComplaintCaseInfoController extends Controller{
  
    public function index()  {
		
        $caseStatus = CaseStatus::get()->pluck('CSDesc','CSCode');
		$nationality  = Country::where('CTActive', 1)->get()->pluck('CTDesc', 'CTCode');
		
        return view('inquiry.complaintCaseInfo.index',compact('caseStatus','nationality'));
   
    }

    public function validation(Request $request) {
      
	  //log::debug( $request);

        $messages = [
        ];

        $validation = [
            'compName' 	=> 'nullable',
            'ssmNo' 	=> 'nullable',
        ];

        $request->validate($validation, $messages);
		
	   $total = 0;
		
        return $total;
    }

    public function datatable(Request $request){
		
		$dateFrom 	= $request->dateFrom;
		$dateTo 	= $request->dateTo;
		$caseStatus = $request->caseStatus;
		$compName	= $request->compName;
		$ssmNo		= $request->ssmNo;
		$name		= $request->name;
		$passportNo = $request->passportNo;
		
        $complaintCaseData = ComplaintCase::leftjoin('MSEmployee','EECode','CC_EECode')
										->leftjoin('MSEmployer','ERCode','CC_ERCode')
										->leftjoin('MSCaseStatus','CSCode','CC_CSCode')
										->leftjoin('MSCategory','CGCode','CC_CGCode')
										->leftjoin('MSCountry','CTCode','EENationality_CTCode')
										->when($caseStatus != null, function ($query) use ($caseStatus) {
											$query->where('CC_CSCode','=',$caseStatus);
										})
										->when($name != null, function ($query) use ($name) {
											$query->whereRaw("concat(EEFirstName, ' ', EELastName) like '%" .$name. "%' ");
										})
										->when($passportNo != null, function ($query) use ($passportNo) {
											$query->where('EEPassportNo', '=', $passportNo);
										})
										->when($compName != null, function ($query) use ($compName) {
											$query->where('ERCompName', 'LIKE', '%'.$compName.'%');
										})
										->when($ssmNo != null, function ($query) use ($ssmNo) {
											$query->where('ERSSMNo', '=', $ssmNo);
										})
										->when($dateFrom == null && $dateTo == null, function ($query) {
											$query->whereDate('CCDate', Carbon::today());
										})
										->when((isset($dateFrom) && $dateFrom != null) && (isset($dateTo) && $dateTo != null), function ($query) use ($dateFrom, $dateTo) {
											$query->where('CCDate', '>=', Carbon::parse($dateFrom)->startOfDay())
												  ->where('CCDate', '<=', Carbon::parse($dateTo)->endOfDay());
										})
										->when((isset($dateFrom) && $dateFrom != null) && (isset($dateTo) && $request->dateTo == null), function ($query) use ($dateFrom) {
											$query->where('CCDate', '>=', Carbon::parse($dateFrom)->startOfDay());
										})
										->when((isset($dateTo) && $dateTo != null) && (isset($dateFrom) && $dateFrom == null), function ($query) use ($dateTo) {
											$query->where('CCDate', '<=', Carbon::parse($dateTo)->endOfDay());
										})
										->orderBy('CCNo','asc')
										->get();
							
		$data = [];
		
		//log::debug(count($complaintCaseData));
		
        if(isset($complaintCaseData) && count($complaintCaseData)>0) {
            foreach ($complaintCaseData as $x => $complaintCase) {
				array_push($data, [
					'CCID'			=> (float) $complaintCase->CCID ,
					'caseNo'		=> $complaintCase->CCNo ,
					'caseType'		=> $complaintCase->CCType ,
					'caseDate'		=> isset($complaintCase->CCDate) ? carbon::parse($complaintCase->CCDate)->format('Y-m-d') : null,
					'caseStatus'	=> $complaintCase->CSDesc ,
					'caseCategory'	=> $complaintCase->CGDesc ,
					'compName'		=> $complaintCase->ERCompName ?? '',
					'ssmNo'			=> $complaintCase->ERSSMNo ?? '',
					'firstName'		=> $complaintCase->EEFirstName ?? '',
					'lastName'		=> $complaintCase->EELastName ?? '',
					'passportNo'	=> $complaintCase->EEPassportNo ?? '',
					'nationality'	=> $complaintCase->CTDesc ,
				]);
			}
		}
	  
        $dt = datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('caseNo', function ($row) {
               return '<a target="_blank" href="'.route('inquiry.complaintCaseInfo.info', [$row['CCID'] ]).'">'.$row['caseNo'] .'</a>';
			})->addColumn('reportedBy', function ($row) {
                if ($row['caseType'] == 'EE'){
					return 'Employee';
				}else{
					return 'Employer';
				}
            })->addColumn('fullName', function ($row) {
                return $row['firstName'] . ' '. $row['lastName'];       
            })->editColumn('caseDate', function ($row) {
				if ($row['caseDate'] != null){
					return [
						'display' =>e(carbon::parse($row['caseDate'])->format('d/m/Y')),
						'timestamp' =>carbon::parse($row['caseDate'])->timestamp
					];
				}else{
					return [
						'display' =>'',
						'timestamp' =>0
					];
				}
			})->addColumn('action', function ($row) {
                    $data = '<a type="button" class="btn-floating mb-1 btn-flat waves-effect waves-light red darken-4 white-text" id="delete" data-id="'.$row['CCID'].'" data-url="'.route('transaction.complaintCase.delete',[$row['CCID']]).'">
                         <i class="material-icons">delete</i>
                         </a>';
                return $data;
            })->rawColumns(['caseNo','action']);

        return $dt->make(true);
    }
	
    public function info($id) {
        $reportBy = [
            'EE' => 'Employee' ,
            'ER' => 'Employer',
        ];
		
        $nationality  = Country::where('CTActive', 1)->get()->pluck('CTDesc', 'CTCode');
       
        $complaintCase = ComplaintCase::leftjoin('MSEmployee','EECode','CC_EECode')
										->leftjoin('MSEmployer','ERCode','CC_ERCode')
										->leftjoin('MSCaseStatus','CSCode','CC_CSCode')
										->leftjoin('MSCategory','CGCode','CC_CGCode')
										->leftjoin('MSCountry','CTCode','EENationality_CTCode')
										->leftjoin('MSUserType','TypeCode','CCType')
										->where('CCID',$id)
										->first();
										
		//$complaintCaseLog = ComplaintCaseLog::where('CCL_CCNo',$complaintCase->CCNo)->where('CCLActive',1)->get();
		//$employer = Employer::where('ERActive',1)->get()->pluck('ERCompName','ERCode');
		//$caseStatus = CaseStatus::get()->pluck('CSDesc','CSCode');
		//$employee = Employee::select('EECode',DB::raw("CONCAT(EEFirstName,' ',EELastName) AS EEName"))->where('EE_ERCode',$complaintCase->CC_ERCode)->where('EEActive',1)->get()->pluck('EEName','EECode');
		//$category = Category::where('CGType',$complaintCase->CCType)->get()->pluck('CGDesc','CGCode');
      
        return view('inquiry.complaintCaseInfo.info',compact('complaintCase'));
   
    }	
	
}