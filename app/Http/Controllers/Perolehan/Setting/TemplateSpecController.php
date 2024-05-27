<?php

namespace App\Http\Controllers\Perolehan\Setting;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\SSMCompany;
use App\Models\TemplateSpecDetail;
use App\Models\TemplateSpecHeader;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;

class TemplateSpecController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        return view('perolehan.setting.templateSpec.index');
    }

    public function create(){
        $departmentAll = $this->dropdownService->departmentAll();
        $statusActive = $this->dropdownService->statusActive();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $response_type = $this->dropdownService->response_type();

        return view('perolehan.setting.templateSpec.create',
            compact('departmentAll', 'statusActive', 'unitMeasurement', 'response_type')
        );
    }

    public function store(Request $request){

        $messages = [
            'title.required'        => 'Tajuk Templat Spesifikasi diperlukan.',
            'description.required'  => 'Keterangan Templat Spesifikasi diperlukan.',
            'jabatan.required'      => 'Jabatan diperlukan.',
            'status.required'       => 'Status berita diperlukan.',
        ];

        $validation = [
            'title' => 'required',
            'description' => 'required',
            'jabatan' => 'required',
            'status' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            DB::beginTransaction();

            $user = Auth::user();
            if(isset($request->TSDID)){
                $TSDIDs = $request->TSDID;
                $TSDIndexs = $request->TSDIndex;
                $TSDDescs = $request->TSDDesc;
                $TSDStockInds = $request->TSDStockInd;
                $TSD_UMCodes = $request->TSD_UMCode;
                $TSDQtys = $request->TSDQty;
                $TSDEstimatePrices = $request->TSDEstimatePrice;
                $TSDRespondTypes = $request->TSDRespondType;
                $TSDScoreMaxs = $request->TSDScoreMax;
            }
            else{
                $TSDIDs = [];
            }

            $autoNumber = new AutoNumber();
            $TSHNo = $autoNumber->generateTemplateSpecHeaderNo();

            $templateSpecHeader = new TemplateSpecHeader();
            $templateSpecHeader->TSHNo      = $TSHNo;
            $templateSpecHeader->TSHTitle   = $request->title;
            $templateSpecHeader->TSHDesc    = $request->description;
            $templateSpecHeader->TSH_DPTCode = $request->jabatan;
            $templateSpecHeader->TSHActive  = $request->status;
            $templateSpecHeader->TSHCB      = $user->USCode;
            $templateSpecHeader->TSHMB      = $user->USCode;
            $templateSpecHeader->save();
            if(count($TSDIDs) > 0){
                foreach ($TSDIDs as $key => $TSDID){

                    $templateSpecDetail = new TemplateSpecDetail();
                    $templateSpecDetail->TSD_TSHNo          = $TSHNo;
                    $templateSpecDetail->TSDSeq             = $key + 1;
                    $templateSpecDetail->TSDStockInd        = $TSDStockInds[$key];
                    $templateSpecDetail->TSDIndex           = $TSDIndexs[$key];
                    $templateSpecDetail->TSDDesc            = $TSDDescs[$key];
                    $templateSpecDetail->TSDQty             = $TSDQtys[$key];
                    $templateSpecDetail->TSD_UMCode         = $TSD_UMCodes[$key];
                    $templateSpecDetail->TSDRespondType     = $TSDRespondTypes[$key];
                    $templateSpecDetail->TSDEstimatePrice   = $TSDEstimatePrices[$key];
                    $templateSpecDetail->TSDScoreMax        = $TSDScoreMaxs[$key];
                    $templateSpecDetail->TSDCB              = $user->USCode;
                    $templateSpecDetail->TSDMB              = $user->USCode;
                    $templateSpecDetail->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateSpec.edit', [$TSHNo]),
                'message' => 'Maklumat berjaya ditambah.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya ditambah!'.$e->getMessage()
            ], 400);
        }

    }

    public function edit($id){

        $departmentAll = $this->dropdownService->departmentAll();
        $statusActive = $this->dropdownService->statusActive();
        $unitMeasurement = $this->dropdownService->unitMeasurement();
        $response_type = $this->dropdownService->response_type();

        $specHeader = TemplateSpecHeader::where('TSHNo',$id)->first();

        return view('perolehan.setting.templateSpec.edit',
            compact('specHeader','departmentAll', 'statusActive', 'unitMeasurement', 'response_type')
        );

    }

    public function update(Request $request,$id){

        $messages = [
            'title.required'        => 'Tajuk berita diperlukan.',
            'description.required'  => 'Keterangan berita diperlukan.',
            'jabatan.required'  => 'Jabatan diperlukan.',
            'status.required'       => 'Status berita diperlukan.',
        ];

        $validation = [
            'title' => 'required',
            'description' => 'required',
            'jabatan' => 'required',
            'status' => 'required',

        ];

        $request->validate($validation, $messages);

        try {

            $user = Auth::user();
            if(isset($request->TSDID)){
                $TSDIDs = $request->TSDID;
                $TSDIndexs = $request->TSDIndex;
                $TSDDescs = $request->TSDDesc;
                $TSDStockInds = $request->TSDStockInd;
                $TSD_UMCodes = $request->TSD_UMCode;
                $TSDQtys = $request->TSDQty;
                $TSDEstimatePrices = $request->TSDEstimatePrice;
                $TSDRespondTypes = $request->TSDRespondType;
                $TSDScoreMaxs = $request->TSDScoreMax;
            }
            else{
                $TSDIDs = [];
            }

            $templateSpecHeader = TemplateSpecHeader::where('TSHNo', $request->TSHNo)->first();

            $templateSpecHeader->TSHTitle   = $request->title;
            $templateSpecHeader->TSHDesc    = $request->description;
            $templateSpecHeader->TSH_DPTCode = $request->jabatan;
            $templateSpecHeader->TSHActive  = $request->status;
            $templateSpecHeader->TSHMB      = $user->USCode;
            $templateSpecHeader->save();

            $old_templateSpecDetail = TemplateSpecDetail::where('TSD_TSHNo', $request->TSHNo)->get();

            //ARRAY UPDATE MULTIPLE ROW
            if(count($old_templateSpecDetail) > 0){
                //IN DB ALREADY HAS 3 = 1,2,3 DATA -- NEW HAS 5 =1,3,4,5,6
                foreach($old_templateSpecDetail as $otemplateSpecDetail){
                    $exist = 0;
                    //CHECING WUJUD 3 DLM 5
                    foreach($TSDIDs as $TSDID){
                        if($otemplateSpecDetail->TSDID == $TSDID){
                            $exist = 1;
                        }
                    }

                    //DELETE 2
                    if($exist == 0){
                        $otemplateSpecDetail->delete();
                    }
                }

                //ADD NEW 4,5,6
                foreach($TSDIDs as $key => $TSDID){
                    $new_templateSpecDetail = TemplateSpecDetail::where('TSD_TSHNo', $request->TSHNo)->where('TSDID', $TSDID)->first();

                    if(!$new_templateSpecDetail){
                        $new_templateSpecDetail = new TemplateSpecDetail();
                        $new_templateSpecDetail->TSD_TSHNo      = $request->TSHNo;
                        $new_templateSpecDetail->TSDCB              = $user->USCode;
                    }
                    //KALAU NK EDIT 1, 3 TAMBAH ELSE
                    $new_templateSpecDetail->TSDSeq             = $key + 1;
                    $new_templateSpecDetail->TSDStockInd        = $TSDStockInds[$key];
                    $new_templateSpecDetail->TSDIndex           = $TSDIndexs[$key];
                    $new_templateSpecDetail->TSDDesc            = $TSDDescs[$key];
                    $new_templateSpecDetail->TSDQty             = $TSDQtys[$key];
                    $new_templateSpecDetail->TSD_UMCode         = $TSD_UMCodes[$key];
                    $new_templateSpecDetail->TSDRespondType     = $TSDRespondTypes[$key];
                    $new_templateSpecDetail->TSDEstimatePrice   = $TSDEstimatePrices[$key];
                    $new_templateSpecDetail->TSDScoreMax        = $TSDScoreMaxs[$key];
                    $new_templateSpecDetail->TSDMB              = $user->USCode;
                    $new_templateSpecDetail->save();
                }
            }
            else{
                if(count($TSDIDs) > 0){
                    foreach ($TSDIDs as $key => $TSDID){

                        $templateSpecDetail = new TemplateSpecDetail();
                        $templateSpecDetail->TSD_TSHNo          = $request->TSHNo;
                        $templateSpecDetail->TSDSeq             = $key + 1;
                        $templateSpecDetail->TSDStockInd        = $TSDStockInds[$key];
                        $templateSpecDetail->TSDDesc            = $TSDDescs[$key];
                        $templateSpecDetail->TSDQty             = $TSDQtys[$key];
                        $templateSpecDetail->TSD_UMCode         = $TSD_UMCodes[$key];
                        $templateSpecDetail->TSDRespondType     = $TSDRespondTypes[$key];
                        $templateSpecDetail->TSDEstimatePrice   = $TSDEstimatePrices[$key];
                        $templateSpecDetail->TSDScoreMax        = $TSDScoreMaxs[$key];
                        $templateSpecDetail->TSDCB              = $user->USCode;
                        $templateSpecDetail->TSDMB              = $user->USCode;
                        $templateSpecDetail->save();
                    }
                }
            }
//END HERE


            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateSpec.edit',[$request->TSHNo]),
                'message' => 'Maklumat berjaya dikemaskini.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }

    public function delete(Request $request){

        try {

            DB::beginTransaction();

            $user = Auth::user();

            $templateSpecHeader = TemplateSpecHeader::where('TSHNo',$request->idNo)->first();

            if(count($templateSpecHeader->templateSpecDetail) > 0){
                foreach ( $templateSpecHeader->templateSpecDetail as $item => $templateSpecDetail) {
                    $templateSpecDetail->delete();
                }
            }

            $templateSpecHeader->delete();

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('perolehan.setting.templateSpec.index'),
                'message' => 'Maklumat berjaya dipadam.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dipadam!'.$e->getMessage()
            ], 400);
        }

    }

    // {{--Working Code Datatable--}}
    public function templateSpecDataTable(){

        $query = TemplateSpecHeader::orderBy('TSHNo', 'DESC')->get();

        return datatables()->of($query)
            ->editColumn('TSHTitle', function($row) {
                $route = route('perolehan.setting.templateSpec.edit',[$row->TSHNo]);
                $result = '<a href="'.$route.'" class="new modal-trigger waves-effect waves-light">'.$row->TSHTitle.'</a>';
                return $result;
            })
            ->editColumn('TSH_DPTCode', function($row) {
                $departmentAll = $this->dropdownService->departmentAll();

                return $departmentAll[$row->TSH_DPTCode];
            })
            ->editColumn('TSHActive', function($row) {
                return $row->TSHActive == 1 ? 'Aktif' : 'Tidak Aktif';
            })
            ->editColumn('TSHCD', function($row) {


                // Create a Carbon instance from the MySQL datetime value
                $carbonDatetime = Carbon::parse($row->TSHCD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y H:i');

                return $formattedDate;

            })
            ->addColumn('action', function($row) {
                $routeDelete = route('perolehan.setting.templateSpec.delete',[$row->TSHNo]);
                $result = "";

                if($row->TSHActive == 0){

                    $result = '<button onclick="deleteTemplateSpec(\' '.$row->TSHNo.' \')" class="btn btn-light-primary"><i class="material-icons">delete</i></button>';

                }
                return $result;
            })
            ->rawColumns(['TSHTitle','TSH_DPTCode','TSHActive', 'TSHCD','action'])
            ->make(true);
    }

}
