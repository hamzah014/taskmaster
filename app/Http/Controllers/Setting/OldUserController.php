<?php

namespace App\Http\Controllers\Setting;

use Carbon\Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Country;
use App\Models\FileAttach;
use App\User;
use Illuminate\Http\Request;
Use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Validator;
use Auth;


class OldUserController extends Controller
{
    public function index(){

        return view('setting.user.index');
    }

    public function create(){

        $isActive = [
            1 => trans('message.yes') ,
            0 => trans('message.no')
        ];

        $user = null;
		$country = Country::where('CTActive',1)->get()->pluck('CTDesc','CTCode');
		$roles = Role::whereNotIn('RLCode',['ADMIN'])->get()->pluck('RLName','RLCode');

        return view('setting.user.form',compact('user','isActive','roles','country'));
    }

    public function store(Request $request){

        $messages = [
            'userCode.required' 	=> trans('message.userCode.required'),
            'userName.required' 	=> trans('message.userName.required'),
            'email.required'        => trans('message.email.required'),
            'email.email'           => trans('message.email.email'),
            'email.max'             => trans('message.email.max'),
            'email.unique'          => trans('message.email.unique'),
            'isActive.required' 	=> trans('message.isActive.required'),
            'role.required' 		=> trans('message.role.required'),
            'password.confirmed'    => trans('message.password.confirmed')
		];

        $validation = [
            'userCode' 			=> 'required',
            'userName' 			=> 'required',
            'email'    			=> 'nullable|email:rfc,dns|max:255',
            'phone' 			=> 'nullable',
            'isActive' 			=> 'required',
            'country' 			=> 'nullable',
            'role' 				=> 'required',
			'password' 			=> 'nullable|min:6|required_with:password-confirm|same:password-confirm',
            'password-confirm'	=> 'nullable|min:6',
            'resetPassword' 	=> 'nullable',
        ];

        $request->validate($validation, $messages);

		$user = User::where('USCode',$request->userCode)->first();
		if ($user != null){
			return response()->json([
                'error' => '1',
				'message' => trans('message.duplicate.user')
            ], 400);
		}

		if ($request->country != null){
			$country = Country::where('CTCode',$request->country)->First();
			if ($country == null){
				return response()->json([
					'error' => '1',
					'message' => trans('message.invalid.country')
				], 400);
			}
		}

		$role = Role::where('RLCode',$request->role)->First();
		if ($role == null){
			return response()->json([
                'error' => '1',
				'message' => trans('message.invalid.role')
            ], 400);
		}

        try {
            DB::beginTransaction();

            $password = $request->password ?? '123456';

            $user = new User();
            $user->USCode 		= $request->userCode ?? '';
            $user->USName 		= $request->userName ?? '';
            $user->USEmail 		= $request->email ?? '';
            $user->USPhoneNo 	= $request->phone ?? '';
            $user->USPwd 		= Hash::make($password);
            $user->USActive 	= $request->isActive;
            $user->US_CTCode 	= $country->CTCode ?? '';
            $user->US_RLCode 	= $role->RLCode;
			$user->USCB			= Auth::user()->USCode;

           if ($request->file != null){

				//*** SAVE FILE TO STORAGE ***
				$folderPath = carbon::now()->format('ymd').'\\user\\'.$user->USCode;
				$randomNumber = substr(str_shuffle("abcdefghijklmnpqrstuvwxyz0123456789"), 0, 4);
				$timeStamp = Carbon::now()->format('ymdHis');
				$newFileExt = request()->file->getClientOriginalExtension();
				$newFileName  = $complaint->CCNo.'_'.$timeStamp.$randomNumber.'.'.$newFileExt;


				//SAVE PHOTO
				$fileContent = file_get_contents( $request->file );
				Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

				$fileAttach = FileAttach::where('FA_USCode',$user->USCode)->first();
				if ($fileAttach == null){
					$fileAttach = new FileAttach();
					$fileAttach->FACB 		= Auth::user()->USCode;
				}
				$fileAttach->FAFileType 	= 'MU';
				$fileAttach->FA_UScode 		= $user->USCode;
				$fileAttach->FAFilePath 	= $folderPath.'\\'.$newFileName;
				$fileAttach->FAFileName 	= $newFileName;
				$fileAttach->FAFileExtension = $newFileExt;
				$fileAttach->FAMB 			= Auth::user()->USCode;
				$fileAttach->save();

				$profilePhotoURL =  env('app_url').'/file/'. $fileAttach->FAFileName;

			}


            if($request->resetPassword == '1'){
                $user->USPwd = Hash::make($password);
            }

            $user->save();


            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
				'message' => trans('message.user.fail').$e->getMessage()
            ], 400);
        }

		return response()->json([
			'success' => '1',
			'redirect' => route('setting.user.index'),
			'message' => trans('message.user.create')
		]);
    }

    public function edit($id){

        $isActive = [
            1 => trans('message.yes') ,
            0 => trans('message.no')
        ];

        $user = User::find($id);
        $country = Country::where('CTActive',1)->get()->pluck('CTDesc','CTCode');
        $roles = Role::whereNotIn('RLCode',['ADMIN'])->get()->pluck('RLName','RLCode');

        $profilePhotoURL= '';

        $fileAttach = FileAttach::where('FA_USCode', $user->USCode)->where('FAActive',1)->first();
        if ($fileAttach != null){
            $profilePhotoURL =  env('app_url').'/file/'. $fileAttach->FAFileName;
        }

        return view('setting.user.form',compact('user','isActive','roles','country','profilePhotoURL'));
    }

    public function update(Request $request,$id) {

        $messages = [
            'userCode.required' 	=> trans('message.userCode.required'),
            'userName.required' 	=> trans('message.userName.required'),
            'email.required'        => trans('message.email.required'),
            'email.email'           => trans('message.email.email'),
            'email.max'             => trans('message.email.max'),
            'email.unique'          => trans('message.email.unique'),
            'isActive.required' 	=> trans('message.isActive.required'),
            'role.required' 		=> trans('message.role.required'),
        ];

        $validation = [
            'userCode' 			=> 'required',
            'userName' 			=> 'required',
            'email'    			=> 'nullable|email:rfc,dns|max:255',
            'phone' 			=> 'nullable',
            'isActive' 			=> 'required',
            'country' 			=> 'nullable',
            'role' 				=> 'required',
			'password' 			=> 'nullable|min:6|required_with:password-confirm|same:password-confirm',
            'password-confirm'	=> 'nullable|min:6',
            'resetPassword' 	=> 'nullable',
        ];

        $request->validate($validation, $messages);

		$user = User::where('USCode',$request->userCode)->where('USID','!=',$id)->first();
		if ($user != null){
			return response()->json([
                'error' => '1',
				'message' => trans('message.duplicate.user')
            ], 400);
		}

		if ($request->country != null){
			$country = Country::where('CTCode',$request->country)->First();
			if ($country == null){
				return response()->json([
					'error' => '1',
					'message' => trans('message.invalid.country')
				], 400);
			}
		}

		$role = Role::where('RLCode',$request->role)->First();
		if ($role == null){
			return response()->json([
                'error' => '1',
				'message' => trans('message.invalid.role')
            ], 400);
		}

        try {
            DB::beginTransaction();

            $user = User::find($id);

            $user->USCode 		= $request->userCode ?? '';
            $user->USName 		= $request->userName ?? '';
            $user->USEmail 		= $request->email ?? '';
            $user->USPhoneNo 	= $request->phone ?? '';
            $user->USActive 	= $request->isActive;
            $user->US_CTCode 	= $country->CTCode ?? '';
            $user->US_RLCode 	= $role->RLCode;
			$user->USMB			= Auth::user()->USCode;
            if ($request->file != null){

				//*** SAVE FILE TO STORAGE ***
				$folderPath = carbon::now()->format('ymd').'\\user\\'.$user->USCode;
				$randomNumber = substr(str_shuffle("abcdefghijklmnpqrstuvwxyz0123456789"), 0, 4);
				$timeStamp = Carbon::now()->format('ymdHis');
				$newFileExt = request()->file->getClientOriginalExtension();
				$newFileName  = $complaint->CCNo.'_'.$timeStamp.$randomNumber.'.'.$newFileExt;

				//SAVE PHOTO
				$fileContent = file_get_contents( $request->file );
				Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

				$fileAttach = FileAttach::where('FA_USCode',$user->USCode)->first();
				if ($fileAttach == null){
					$fileAttach = new FileAttach();
					$fileAttach->FACB 		= Auth::user()->USCode;
				}
				$fileAttach->FAFileType 	= 'MU';
				$fileAttach->FA_UScode 		= $user->USCode;
				$fileAttach->FAFilePath 	= $folderPath.'\\'.$newFileName;
				$fileAttach->FAFileName 	= $newFileName;
				$fileAttach->FAFileExtension = $newFileExt;
				$fileAttach->FAMB 			= Auth::user()->USCode;
				$fileAttach->save();

				$profilePhotoURL =  env('app_url').'/file/'. $fileAttach->FAFileName;

			}

            if($request->resetPassword == '1'){
				$password 	= $request->password ?? '123456';
                $user->USPwd = Hash::make($password);
            }

            $user->save();

            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
				'message' => trans('message.user.fail').$e->getMessage()
            ], 400);
        }

		return response()->json([
			'success' => '1',
			'redirect' => route('setting.user.index'),
			'message' => trans('message.user.update')
		]);
    }

    public function datatable(){

        $user = User::leftjoin('MSCountry','CTCode','US_CTCode')
						->leftjoin('MSRole','RLCode','US_RLCode')
						->whereNotIn('USCode',['sa'])
						->where('USType','MU')
						->orderBy('USCode','asc')
						->get();

        return datatables()->of($user)
            ->addIndexColumn()
            ->editColumn('USCode', function ($row) {
                return '<a href="'.route('setting.user.edit',[$row['USID']]).'">
                             '.$row['USCode'] .'</a>';
            })
            ->editColumn('USName', function ($row) {
                return $row['USName'] ?? '';
            })
            ->editColumn('USPhoneNo', function ($row) {
                return $row['USPhoneNo'] ?? '';
            })
            ->editColumn('USEmail', function ($row) {
                return $row['USEmail'] ?? '';
            })
            ->editColumn('CTDesc', function ($row) {
                return $row['CTDesc'] ?? '';
            })
            ->editColumn('RLName', function ($row) {
                return $row['RLName'] ?? '';
            })
			->editColumn('USActive', function ($row) {
                if($row['USActive'] == 1) {
                    $data = '<a class="mb-6 btn-floating waves-effect waves-light gradient-45deg-green-teal">
                                                        <i class="material-icons">check</i>
                                                    </a>';
                }else{
                    $data = '<a class="btn-floating mb-6 btn-flat waves-effect waves-light red darken-4 white-text">
                                                        <i class="material-icons">clear</i>
                                                    </a>';
                }
                return $data;
            })
            ->editColumn('USCD', function ($row) {
                return $row['USCD']->format('Y-m-d H:i') ?? '';
            })
			->addColumn('action', function ($row) {
                    $data = '<a type="button" class="btn-floating mb-1 btn-flat waves-effect waves-light red darken-4 white-text" id="delete" data-id="'.$row['USID'].'" data-url="'.route('setting.user.delete',[$row['USID']]).'">
                         <i class="material-icons">delete</i>
                         </a>';
                return $data;
            })->rawColumns(['USCode','USActive','action'])
            ->make(true);
    }

    public function delete(Request $request){
        try {
            DB::beginTransaction();
			$user = User::find($request->id);
			FileAttach::where('FA_USCode',$user->USCode)->delete();
			$user->delete();
            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
				'message' => trans('message.user.fail').$e->getMessage()
            ], 400);
        }

        return response()->json([
			'success' => '1',
			'redirect' => route('setting.user.index'),
			'message' => trans('message.user.delete')
		]);
    }

}
