<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Country;
use Carbon\Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Validator;
use Auth;


class CountryController extends Controller
{
    public function index(){
		
        return view('masterData.country.index');
    }

    public function create(){

        $isActive = [
            1 => trans('message.yes') ,
            0 => trans('message.no')
        ];

        $roles = Role::whereNotIn('RLName',['ADMIN'])->get()->pluck('RLName','RLID');

        return view('masterData.country.form',compact('isActive','roles'));
    }

    public function store(Request $request){

        $messages = [
            'countryCode.required' 	         => trans('Country Code is required'),
            'countryName.required' 	         => trans('Country Name is required'),
        ];

        $validation = [
            'countryCode' 	        => 'required',
            'countryName' 	        => 'required',
        ];

        $request->validate($validation, $messages);

        $country = Country::where('CTCode',$request->countryCode)->first();
        if ($country != null){
            return response()->json([
                'error' => '1',
                'message' => trans('Country already exist !')
            ], 400);
        }

        try {
            DB::beginTransaction();

            $country = new Country();
            $country->CTCode        = $request->countryCode ?? '';
            $country->CTDesc        = $request->countryName ?? '';
            $country->CTActive      = $request->isActive;
            $country->CTCB          = Auth::user()->USCode;

            $country->save();

            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('Add new country was failed !').$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.country.index'),
            'message' => trans('Add new country was successful ! !')
        ]);
    }

    public function edit($id){

        $isActive = [
            1 => trans('message.yes') ,
            0 => trans('message.no')
        ];

        $country = Country::find($id);

        return view('masterData.country.form',compact('country','isActive'));
    }

    public function update(Request $request,$id) {

        $messages = [
            'countryCode.required' 	         => trans('Country Code is required'),
            'countryName.required' 	         => trans('Country Name is required'),
        ];

        $validation = [
            'countryCode' 	        => 'required',
            'countryName' 	        => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $country = Country::find($id);

            $country->CTCode        = $request->countryCode ?? '';
            $country->CTDesc        = $request->countryName ?? '';
            $country->CTActive      = $request->isActive;
            $country->CTMB          = Auth::user()->USCode;

            $country->save();

            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('Update was failed !').$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.country.index'),
            'message' => trans('Update was successful !')
        ]);
    }

    public function datatable(){
        $countryData = Country::orderby('CTID','asc')->get();

        $data = [];

        if(isset($countryData) && count($countryData)>0) {
            foreach ($countryData as $x => $getData) {

                array_push($data, [
                    'countryID'		    => (float) $getData->CTID ,
                    'code'		        => $getData->CTCode ?? '',
                    'desc'			    => $getData->CTDesc ?? '',
                    'isActive'		    => $getData->CTActive ?? 0,
                    'createdBy'		    => $getData->CTCB ?? 0,
                    'createdDate'	    => isset($getData->CTCD) ? carbon::parse($getData->CTCD)->format('d-m-Y H:i') : null,
                    'modifyBy'		    => $getData->CTMB ?? 0,
                    'modifyDate'	    => isset($getData->CTMD) ? carbon::parse($getData->CTMD)->format('d-m-Y H:i') : null,
                ]);
            }
        }

        return datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('code', function ($row) {
                return '<a href="'.route('masterData.country.edit', [$row['countryID'] ]).'">'.$row['code'] .'</a>';
            })
            ->addColumn('isActive', function ($row) {
                if($row['isActive'] == 1) {
                    return '<a class="mb-6 btn-floating waves-effect waves-light gradient-45deg-green-teal"><i class="material-icons">check</i></a>';
                }else{
                    return '<a class="btn-floating mb-6 btn-flat waves-effect waves-light red darken-4 white-text"><i class="material-icons">clear</i></a>';
                }
            })
            ->addColumn('action', function ($row) {
                $data = '<a type="button" class="btn-floating mb-1 btn-flat waves-effect waves-light red darken-4 white-text" 
                    id="delete" data-id="' . $row['countryID'] . '" data-url="' . route('masterData.country.delete', [$row['countryID']]) . '">
                         <i class="material-icons">delete</i>
                         </a>';
                return $data;
            })
            ->rawColumns(['code','isActive', 'action'])
            ->make(true);
    }


    public function delete(Request $request){
        try {
            DB::beginTransaction();

            $country = Country::find($request->id);
            $country->delete();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('Country delete failed !').$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.country.index'),
            'message' => trans('Country delete was successful !')
        ]);
    }

}