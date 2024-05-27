<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\GenerateReport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\WebSetting;
use App\Services\DropdownService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Session;
use Yajra\DataTables\DataTables;

class ReportController extends Controller
{
    
    public function index(){

        return view('report.general.index');

    }
    
    public function reportGeneralDatatable(Request $request){

        $user = Auth::user();

        $dropdownService = new DropdownService();
        
        $query = GenerateReport::where('GRCB', $user->USCode)->orderBy('GRID', 'DESC')->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('GRStatus', function($row){
                
                if($row->GRStatus == 1 || $row->GRStatus == "1"){
                    $result = "Complete Process";
                }else{
                    $result = "Waiting Process";

                }

                return $result;

            })
            ->editColumn('GR_GRTCode', function($row) use(&$dropdownService){

                $reportType = $dropdownService->generateReportType();
                return $reportType[$row->GR_GRTCode] ?? "-";

            })
            ->editColumn('GRCD', function($row){
                
                $result = Carbon::parse($row->GRCD)->format('d/m/Y');

                return $result;
            })
            ->editColumn('GRCB', function($row){
                
                $result = $row->userBy ? $row->userBy->USName : "-";

                return $result;
            })
            ->addColumn('action', function($row){

                $result = "";

                if($row->fileAttachPDF){
                    $route = route('file.view',[ $row->fileAttachPDF->FAGuidID]);

                    $pdfImg = asset('assets/images/icon/dashboard/icon-pdf.svg');

                    $result .= '<a target="_blank" class="w-10" href="'.$route.'" ><img src="'.$pdfImg.'"></a>';
                }

                if($row->fileAttachExcel){
                    $route = route('file.download',[ $row->fileAttachExcel->FAGuidID]);

                    $pdfImg = asset('assets/images/icon/dashboard/icon-excel.svg');

                    $result .= '<a target="_blank" class="w-10" href="'.$route.'" ><img src="'.$pdfImg.'"></a>';
                }
                

                return $result;
            })
        ->rawColumns(['indexNo','GRCB','GRParameter','GRStatus','GRCD','action'])
        ->make(true);

    }


}
