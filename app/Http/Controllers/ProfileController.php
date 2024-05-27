<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Helper\Custom;
use App\Models\Role;
use App\Models\FileAttach;
use App\User;
use Illuminate\Http\Request;
use App\Models\WebSetting;
use App\Models\TacLog;
use App\Http\Controllers\Controller;
use App\Services\DropdownService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
Use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Auth;
use Session;

class ProfileController extends Controller
{

    public function index(){

        $dropdownService = new DropdownService();

        $user = Auth::user();

        $profilePhotoURL = $user->getProfileURL() ?? null;

        $department = $dropdownService->department();

        return view('profile',
        compact(
            'user','profilePhotoURL','department'
        ));
    }

    public function update(Request $request){

        // dd($request);
        // name
        // staffID
        // department
        // email
        // userCode

        $messages = [
            'name.required'       => 'Name required.',
            'email.required'      => 'Email required.',

        ];

        $validation = [
            'name' => 'required',
            'email' => 'required',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $user= User::where('USCode',$request->userCode)->first();

            if($user == null){
				return response()->json([
					'error' => '1',
					'message' => 'User does not exists.'
				], 400);
            }

            if($request->file('avatar')){

                $fileType = "PP";
                $documentFile = $request->file('avatar');

                $result = $this->saveFile($documentFile, $fileType, $user->USCode);

            }

            $user->USName = $request->name;
            $user->US_StaffID = $request->staffID;
            $user->USDepartment = $request->department;
            $user->save();

            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' =>  trans('message.password.fail').$e->getMessage()
            ], 400);
        }

		return response()->json([
			'success' => '1',
			'redirect' => route('profile.index'),
			'message' => 'Your successfully changed your profile',
		]);
    }

    public function resetPassword(Request $request){

        $messages = [
            'newPassword.required'          => 'New Password required.',
            'confirmPassword.required'      => 'Confirm Password required.',

        ];

        $validation = [
            'newPassword' => 'required|string',
            'confirmPassword' => 'required|string',
        ];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            if($request->newPassword !== $request->confirmPassword){

                return response()->json([
                    'error' => '1',
                    'message' => 'Password do not matched.'
                ], 400);
            }

            $user= User::where('USCode',$request->userCode)->first();

            if($user == null){
				return response()->json([
					'error' => '1',
					'message' => 'User does not exists.'
				], 400);
            }

            $user->USPwd = Hash::make($request->newPassword);
            $user->save();

            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' =>  trans('message.password.fail').$e->getMessage()
            ], 400);
        }

		return response()->json([
			'success' => '1',
			'redirect' => route('profile.index'),
			'message' => 'Your successfully changed your password',
		]);
    }

}

