<?php

namespace App\Http\Controllers\Admin\Role;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
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

class RoleController extends Controller
{

    public function index(){

        $dropdownService = new DropdownService();
        $userRole = $dropdownService->roleForUser();

        return view('admin.role.index', compact('userRole'));

    }

    public function userDatatable(){

        $dropdownService = new DropdownService();

        $query = User::where('USType', 'US')->orderBy('USCD', 'DESC')->get();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('role', function($row){

                if($row->US_RLCode){
                    $result = '<span class="badge badge-outline badge-success">'.$row->role->RLName.'</span>';
                }else{
                    $result = '<span class="badge badge-outline badge-warning">Not Assigned</span>';

                }

                return $result;

            })
            ->addColumn('action', function($row){

                $result = '<a class="btn btn-secondary btn-sm" onclick="viewUser(\'' . $row->USCode . '\')" data-bs-toggle="modal" data-bs-target="#modal-user"><i class="text-dark fas fs-7 fa-eye"></i></a>';

                return $result;
            })
            ->rawColumns(['indexNo', 'role','action'])
            ->make(true);


    }

    public function checkUser(Request $request){

		$messages = [
            'userID.required' 	=> "User ID Required",
		];

		$validation = [
			'userID' 	=> 'required',
		];

        $request->validate($validation, $messages);

        try {

            $resultUser = User::where('USCode', $request->userID)->first();

            if($resultUser){

                return response()->json([
                    'success' => '1',
                    'user' => $resultUser
                ]);

            }
            else{
                return response()->json([
                    'success' => '0',
                    'user' => []
                ]);

            }

        }catch (\Throwable $e) {

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error occured!'.$e->getMessage()
            ], 400);
        }


    }

    public function saveUserRole(Request $request){

		$messages = [
            'resultID.required' 	=> "User ID Required",
            'resultName.required' 	=> "Name Required",
            'resultRole.required' 	=> "Role Required",
		];

		$validation = [
			'resultID' 	    => 'required',
			'resultName' 	=> 'required',
			'resultRole' 	=> 'required',
		];

        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();

            $resultUser = User::where('USCode', $request->resultID)->first();

            if($resultUser){

                $resultUser->US_RLCode = $request->resultRole;
                $resultUser->save();

            }
            else{

                return response()->json([
                    'error' => '1',
                    'message' => 'User not found.'
                ], 400);

            }

            DB::commit();

            return response()->json([
                'success' => '1',
                'redirect' => route('admin.role.index'),
                'message' => 'User role has been successfully updated.'
            ]);

        }catch (\Throwable $e) {

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error occured!'.$e->getMessage()
            ], 400);
        }

    }


}
