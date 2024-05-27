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


class EmployeeInfoController extends Controller{
  
    protected $total;

    public function index()  {
		
		
        return view('inquiry.employeeInfo.index');
    }

    public function validation(Request $request) {
      
	  //log::debug( $request);

        $messages = [
        ];

        $validation = [
            'staffName' 	=> 'nullable',
            'passportNo' 	=> 'nullable',
        ];

        $request->validate($validation, $messages);
		
	   $total = 0;
		
        return $total;
    }

    public function datatable(Request $request){
		
		$name	= $request->name;
		$passportNo = $request->passportNo;
		
		$employeeData = DB::table('MSEmployee')
							->leftjoin('MSGender','GDCode','EE_GDCode')
							->leftjoin('MSCountry','CTCode','EENationality_CTCode')
							->when($name != null, function ($query) use ($name) {
								$query->whereRaw("concat(EEFirstName, ' ', EELastName) like '%" .$name. "%' ");
							})
							->when($passportNo != null, function ($query) use ($passportNo) {
								$query->where('EEPassportNo', '=', $passportNo);
							})
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
		
		
        $complaintCases = ComplaintCase::leftjoin('MSEmployee','EECode','CC_EECode')
										->leftjoin('MSEmployer','ERCode','CC_ERCode')
										->leftjoin('MSCaseStatus','CSCode','CC_CSCode')
										->leftjoin('MSCategory','CGCode','CC_CGCode')
										->leftjoin('MSCountry','CTCode','EENationality_CTCode')
										->where('EECode',$request->employeeCode)
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

    public function info($id) {
        $employee = Employee::leftjoin('MSCountry','CTCode','EENationality_CTCode')->where('EEID',$id)->first();
        $employer = Employer::where('ERCode',$id)->first();
        $vaccine = EmployeeVaccine::where('EV_EECode',$employee->EECode)->get();
        $case = ComplaintCase::where('CC_EECode',$employee->EECode)->get();
		
		$profilePhotoURL = '';
		$fileAttach = FileAttach::where('FA_EECode', $employee->EECode)->where('FAActive',1)->first();
		if ($fileAttach != null){
			$profilePhotoURL =  env('app_url').'/file/'. $fileAttach->FAFileName;
		}
		
        return view('inquiry.employeeInfo.info',compact('employee','employer','vaccine','case','profilePhotoURL'));
    }

	
	
	}