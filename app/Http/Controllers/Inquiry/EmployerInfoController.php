<?php

namespace App\Http\Controllers\Inquiry;

use Carbon\Carbon;
use App\Http\Requests;
use App\Models\Employer;
use App\Models\Employee;
use App\Models\EmployeeVaccine;
use App\Models\ComplaintCase;
use App\Models\FileAttach;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Validator;
use Auth;


class EmployerInfoController extends Controller{
  
    public function index()  {
        return view('inquiry.employerInfo.index');
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
		
		$compName	= $request->compName;
		$ssmNo = $request->ssmNo;
		
		$employerData = Employer::when($compName != null, function ($query) use ($compName) {
								$query->where('ERCompName', 'LIKE', '%'.$compName.'%');
							})
							->when($ssmNo != null, function ($query) use ($ssmNo) {
								$query->where('ERSSMNo', '=', $ssmNo);
							})
							->orderby('ERCompName','asc')
							->get();
		
		$data = [];
		
        if(isset($employerData) && count($employerData)>0) {
            foreach ($employerData as $x => $employer) {
				array_push($data, [
					'employerID'	=> (float) $employer->ERID ,
					'compName'		=> $employer->ERCompName ?? '',
					'ssmNo'			=> $employer->ERSSMNo ?? '',
					'regAddr1'		=> $employer->ERRegAddr1 ?? '',
					'regAddr2'		=> $employer->ERRegAddr2 ?? '',
					'regPostcode'	=> $employer->ERRegPostcode ?? '',
					'regCity'		=> $employer->ERRegCity ?? '',
					'regStateCode'	=> $employer->ERReg_StateCode ?? '',
					'compTelNo'		=> $employer->ERCompTelNo ?? '',
					'compFaxNo'		=> $employer->ERCompFaxNo ?? '',
					'compEmail'		=> $employer->ERCompEmail ?? '',
					'compAddr1'		=> $employer->ERCompAddr1 ?? '',
					'compAddr2'		=> $employer->ERCompAddr2 ?? '',
					'compPostcode'	=> $employer->ERCompPostcode ?? '',
					'compCity'		=> $employer->ERCompCity ?? '',
					'compStateCode'	=> $employer->ERComp_StateCode ?? '',
					'cpFirstName'	=> $employer->ERCPFirstName ?? '',
					'cpLastName'	=> $employer->ERCPLastName ?? '',
					'cpDesignation'	=> $employer->ERCPDesignation ?? '',
					'cpPhoneNo'		=> $employer->ERCPPhoneNo ?? '',
					'cpEmail'		=> $employer->ERCPEmail ?? '',
					'register'		=> $employer->ERRegister ?? 0,
					'registerDate'	=> isset($employer->ERRegisterDate) ? carbon::parse($employer->ERRegisterDate)->format('Y-m-d') : null,
				]);
			}
		}
	  
        $dt = datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('compName', function ($row) {
               return '<a target="_blank" href="'.route('inquiry.employerInfo.info', [$row['employerID'] ]).'">'.$row['compName'] .'</a>';
           })->editColumn('ssmNo', function ($row) {
                return $row['ssmNo'] ?? '';
            })->editColumn('registerDate', function ($row) {
				if ($row['registerDate'] != null){
					return [
						'display' =>e(carbon::parse($row['registerDate'])->format('d/m/Y')),
						'timestamp' =>carbon::parse($row['registerDate'])->timestamp
					];
				}else{
					return [
						'display' =>'',
						'timestamp' =>0
					];
				}
            })->rawColumns(['compName']);

        return $dt->make(true);
    }
	
    public function datatableEmployee(Request $request){
		
		$name	= $request->name;
		$passportNo = $request->passportNo;
		
		$employeeData = DB::table('MSEmployee')
							->leftjoin('MSEmployer','ERCode','EE_ERCode')
							->leftjoin('MSGender','GDCode','EE_GDCode')
							->leftjoin('MSCountry','CTCode','EENationality_CTCode')
							->where('ERSSMNo',$request->ssmNo)
							->orderby('EEFirstName','asc')
							->get();
		
		$data = [];
		
        if(isset($employeeData) && count($employeeData)>0) {
            foreach ($employeeData as $x => $employee) {
				array_push($data, [
					'employeeID'		=> (float) $employee->EEID ,
					'passportNo'		=> $employee->EEPassportNo ?? '',
					'fullName'			=> trim($employee->EEFirstName.' '.$employee->EELastName) ?? '',
					'firstName'			=> $employee->EEFirstName ?? '',
					'lastName'			=> $employee->EELastName ?? '',
					'dob'				=> isset($employee->EEDOB) ? carbon::parse($employee->EEDOB)->format('Y-m-d') : null,
					'nationality'		=> $employee->CTDesc ?? '',
					'passportIssueDate'	=> $employee->EEPassportIssueDate ?? '',
					'passportExpDate'	=> $employee->EEPassportExpDate ?? '',
					'gender'			=> $employee->GDDesc ?? '',
					'nationalIDNo'		=> $employee->EENationalIDNo ?? '',
					'registerDate'		=> isset($employee->EERegisterDate) ? carbon::parse($employee->EERegisterDate)->format('Y-m-d') : null,
				]);
			}
		}
	  
        $dt = datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('passportNo', function ($row) {
               return '<a target="_blank" href="'.route('inquiry.employeeInfo.info', [$row['employeeID'] ]).'">'.$row['passportNo'] .'</a>';
           })->editColumn('fullName', function ($row) {
                return $row['fullName'] ?? '';
            })->editColumn('firstName', function ($row) {
                return $row['firstName'] ?? '';
            })->editColumn('lastName', function ($row) {
                return $row['lastName'] ?? '';
            })->editColumn('dob', function ($row) {
				if ($row['dob'] != null){
					return [
						'display' =>e(carbon::parse($row['dob'])->format('d/m/Y')),
						'timestamp' =>carbon::parse($row['dob'])->timestamp
					];
				}else{
					return [
						'display' =>'',
						'timestamp' =>0
					];
				}
            })->editColumn('nationality', function ($row) {
                return $row['nationality'] ?? '';
            })->editColumn('passportIssueDate', function ($row) {
				if ($row['passportIssueDate'] != null){
					return [
						'display' =>e(carbon::parse($row['passportIssueDate'])->format('d/m/Y')),
						'timestamp' =>carbon::parse($row['passportIssueDate'])->timestamp
					];
				}else{
					return [
						'display' =>'',
						'timestamp' =>0
					];
				}
            })->editColumn('passportExpDate', function ($row) {
				if ($row['registerDate'] != null){
					return [
						'display' =>e(carbon::parse($row['passportExpDate'])->format('d/m/Y')),
						'timestamp' =>carbon::parse($row['passportExpDate'])->timestamp
					];
				}else{
					return [
						'display' =>'',
						'timestamp' =>0
					];
				}
            })->editColumn('gender', function ($row) {
                return $row['gender'] ?? '';
            })->editColumn('registerDate', function ($row) {
				if ($row['registerDate'] != null){
					return [
						'display' =>e(carbon::parse($row['registerDate'])->format('d/m/Y')),
						'timestamp' =>carbon::parse($row['registerDate'])->timestamp
					];
				}else{
					return [
						'display' =>'',
						'timestamp' =>0
					];
				}
			})->editColumn('nationalIDNo', function ($row) {
                return $row['nationalIDNo'] ?? '';
            })->rawColumns(['passportNo']);

        return $dt->make(true);
    }
	
    public function datatableComplaintCase(Request $request){
		
		//log::debug($request);
		
        $complaintCases = ComplaintCase::leftjoin('MSEmployee','EECode','CC_EECode')
										->leftjoin('MSEmployer','ERCode','CC_ERCode')
										->leftjoin('MSCaseStatus','CSCode','CC_CSCode')
										->leftjoin('MSCategory','CGCode','CC_CGCode')
										->leftjoin('MSCountry','CTCode','EENationality_CTCode')
										->where('ERSSMNo',$request->ssmNo)
										->orderBy('CCNo','desc')
										->get();

        return datatables()->of($complaintCases)
            ->addIndexColumn()
            ->editColumn('CCNo', function ($row) {
                return '<a target="_blank" href="'.route('inquiry.complaintCaseInfo.info',[$row['CCID']]).'">'.$row['CCNo'] .'</a>'; 
            })	 
            ->addColumn('EEFullName', function ($row) {
                return $row['EEFirstName'].' '.$row['EELastName'];
            })
            ->editColumn('CCCD', function ($row) {
                return [
                    'display' =>e($row->CCCD->format('d/m/Y H:i')),
                    'timestamp' =>$row->CCCD->timestamp
                ];
            })
            ->editColumn('CCMD', function ($row) {
				return [
					'display' => is_null($row->CCMD) ? '' : e($row->CCMD->format('d/m/Y H:i')),
					'timestamp' =>is_null($row->CCMD) ? 0 : $row->CCMD->timestamp
				];
            })->rawColumns(['CCNo'])
            ->make(true);
    }
	
    public function info($id)  {
        $employer = Employer::select('MSEmployer.*','RS.StateDesc AS RegState','CS.StateDesc AS CompState')
								->leftjoin('MSState AS RS','RS.StateCode','ERReg_StateCode')
								->leftjoin('MSState AS CS','CS.StateCode','ERComp_StateCode')
								->where('ERID',$id)
								->first();
        $employee = Employee::where('EE_ERCode',$employer->ERCode)->get();
        $case = ComplaintCase::where('CC_ERCode',$employer->ERCode)->get();
		
        return view('inquiry.employerInfo.info',compact('employee','employer','case'));
    }

	
	
	}