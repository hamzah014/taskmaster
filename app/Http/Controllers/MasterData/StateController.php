<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Country;
use App\Models\State;
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


class StateController extends Controller
{
    public function index(){
		
        return view('masterData.state.index');
    }

    public function create(){

        $isActive = [
            1 => trans('message.yes') ,
            0 => trans('message.no')
        ];

        $roles      = Role::whereIn('RLName',['EMBADMIN','EMBSTAFF'])->get()->pluck('RLName','RLID');
        $country    = Country::where('CTActive', 1)->get()->pluck('CTDesc', 'CTCode');

        return view('masterData.state.form',compact('isActive','roles', 'country'));
    }

    public function store(Request $request){

        $messages = [
            'stateCode.required' 	    => trans('State Code is required'),
            'country.required' 	        => trans('Country is required'),
            'stateDesc.required' 	    => trans('State Name is required'),
        ];

        $validation = [
            'stateCode' 	            => 'required',
            'stateDesc' 	            => 'required',
            'country' 	                => 'required',
        ];

        $request->validate($validation, $messages);

        $state = State::where('StateCode',$request->stateCode)->first();
        if ($state != null){
            return response()->json([
                'error' => '1',
                'message' => trans('State already exist !')
            ], 400);
        }

        try {
            DB::beginTransaction();

            $state = new State();
            $state->StateCode        = $request->stateCode ?? '';
            $state->StateDesc        = $request->stateDesc ?? '';
            $state->State_CTCode     = $request->country;
            $state->StateActive      = $request->isActive;
            $state->StateCB          = Auth::user()->USCode;

            $state->save();

            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('Add new state was failed !').$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.state.index'),
            'message' => trans('Add new state was successful ! !')
        ]);
    }

    public function edit($id){

        $isActive = [
            1 => trans('message.yes') ,
            0 => trans('message.no')
        ];

        $state      = State::where('StateID',$id)->first();
        $curCountry = Country::where('CTCode',$state->State_CTCode)->first();
        $country    = Country::where('CTActive', 1)->get()->pluck('CTDesc', 'CTCode');

        return view('masterData.state.form',compact('state','isActive', 'curCountry', 'country'));
    }

    public function update(Request $request,$id) {

        $messages = [
            'stateCode.required' 	    => trans('State Code is required'),
            'country.required' 	        => trans('Country is required'),
            'stateDesc.required' 	    => trans('State Name is required'),
        ];

        $validation = [
            'stateCode' 	            => 'required',
            'stateDesc' 	            => 'required',
            'country' 	                => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $state = State::find($id);

            $state->StateCode        = $request->stateCode ?? '';
            $state->StateDesc        = $request->stateDesc ?? '';
            $state->State_CTCode     = $request->country;
            $state->StateActive     = $request->isActive;
            $state->StateMB         = Auth::user()->USCode;

            $state->save();

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
            'redirect' => route('masterData.state.index'),
            'message' => trans('Update was successful !')
        ]);
    }

    public function datatable(){
        $stateData = State::orderby('StateID','asc')->get();

        $data = [];

        if(isset($stateData) && count($stateData)>0) {
            foreach ($stateData as $x => $getData) {

                $countryData = Country::where('CTCode', $getData->State_CTCode)->first();

                array_push($data, [
                    'stateID'		    => (float) $getData->StateID ,
                    'code'		        => $getData->StateCode ?? '',
                    'country'			=> $countryData->CTDesc ?? '',
                    'desc'			    => $getData->StateDesc ?? '',
                    'isActive'		    => $getData->StateActive ?? 0,
                    'createdBy'		    => $getData->StateCB ?? 0,
                    'createdDate'	    => isset($getData->StateCD) ? carbon::parse($getData->StateCD)->format('d-m-Y H:i') : null,
                    'modifyBy'		    => $getData->StateMB ?? 0,
                    'modifyDate'	    => isset($getData->StateMD) ? carbon::parse($getData->StateMD)->format('d-m-Y H:i') : null,
                ]);
            }
        }

        return datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('code', function ($row) {
                return '<a href="'.route('masterData.state.edit', [$row['stateID'] ]).'">'.$row['code'] .'</a>';
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
                    id="delete" data-id="' . $row['stateID'] . '" data-url="' . route('masterData.state.delete', [$row['stateID']]) . '">
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

            $state = State::find($request->id);
            $state->delete();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('State delete failed !').$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.state.index'),
            'message' => trans('State delete was successful !')
        ]);
    }

}