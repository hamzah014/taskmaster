<?php

namespace App\Http\Controllers\Role;

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
        $userRole = $dropdownService->userRole();

        return view('role.index', compact('userRole'));

    }

    public function roleDatatable(){

        $query = Role::get();

        return $this->getRoleDatatable($query);

        
    }
    
    public function searchRole(Request $request){ 

        try {
            
            $role = Role::query();

            if ($request->filled('search_role')) {
                $role->where('RLCode', $request->input('search_role'));
            }

            $query = $role->get();

            return $this->getRoleDatatable($query);

    

        }catch (\Throwable $e) {

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error occured!'.$e->getMessage()
            ], 400);
        }


    }
    
    public function getRoleDatatable($query){

        $dropdownService = new DropdownService();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('RLActive', function($row){
                
                if($row->RLActive == 0){
                    $result = '<span class="badge badge-outline badge-danger">Inactive</span>';
                }else{
                    $result = '<span class="badge badge-outline badge-success">Active</span>';

                }

                return $result;

            })
            ->addColumn('action', function($row){

                $route = route('role.edit',$row->RLCode);

                if( Auth::user()->hasPermission('edit_role') ){

                    $result = '<a class="btn btn-secondary" href="'.$route.'" ><i class="text-dark fas fs-7 fa-pen"></i></a>';

                }
                else{
                    $result = "";
                }
                

                return $result;
            })
            ->rawColumns(['indexNo', 'RLActive','action'])
            ->make(true);

    }

    public function create(){

        $dropdownService = new DropdownService();

        $userRole = $dropdownService->userRole();

        $permissions = Permission::get()->groupBy('PMSubGroup');

        return view('role.create', compact('userRole','permissions'));

    }

    public function store(Request $request){
        

        $messages = [
            'name.required' 		        => 'Name required.',
            'permissionCode.required'    => 'Please tick atleast one permission.',

        ];

        $validation = [
            'name' => 'required',
            'permissionCode' => 'required',
        ];
        
        $request->validate($validation, $messages);

        try {
            
            DB::beginTransaction();

            $userNow = Auth::user();

            $checkRole = Role::where('RLCode',$request->code)
                        ->first();

            if($checkRole){
                    
                return response()->json([
                    'error' => '1',
                    'message' => 'Your role code has been registered.'
                ], 400);

            }
            else{

                $role = new Role();
                $role->RLCode = $request->code;
                $role->RLName = $request->name;
                $role->RLCB = $userNow->USCode;
                $role->RLActive = 1;
                $role->save();

                foreach($request->permissionCode as $index => $permissions){

                    $rolePermission = new RolePermission();
                    $rolePermission->RP_RLCode = $role->RLCode;
                    $rolePermission->RP_PMCode = $permissions;
                    $rolePermission->save();

                }

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('role.index'),
                    'message' => 'Role successfully added.'
                ]);


            }

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error occured! '.$e->getMessage()
            ], 400);
        }

    }
    
    public function edit($id){

        $dropdownService = new DropdownService();

        $permissions = Permission::get()->groupBy('PMSubGroup');

        $statusActive = $dropdownService->statusActive();

        $role = Role::where('RLCode', $id)->first();

        $inputDisabled = "";

        if($role->RLActive == 0){
            $inputDisabled = "disabled";
        }

        $rolePermission = $role->rolePermission();

        return view('role.edit',compact('role', 'permissions' , 'statusActive' , 'inputDisabled', 'rolePermission'));

    }

    public function update(Request $request, $id){
        
        $messages = [
            'name.required' 		        => 'Name required.',
            'permissionCode.required'    => 'Please tick atleast one permission.',

        ];

        $validation = [
            'name' => 'required',
            'permissionCode' => 'required',
        ];
        
        $request->validate($validation, $messages);

        try {

            $userNow = Auth::user();
            
            DB::beginTransaction();

            $role = Role::where('RLCode',$request->code)
                        ->first();

            if(!$role){
                    
                return response()->json([
                    'error' => '1',
                    'message' => 'Role are not valid.'
                ], 400);

            }
            else{

                $role->RLName = $request->name;
                $role->RLCB = $userNow->USCode;
                $role->RLActive = $request->status;
                $role->save();

                $deleteOldPermission = RolePermission::where('RP_RLCode', $request->code)->delete();

                foreach($request->permissionCode as $index => $permissions){

                    $rolePermission = new RolePermission();
                    $rolePermission->RP_RLCode = $role->RLCode;
                    $rolePermission->RP_PMCode = $permissions;
                    $rolePermission->save();

                }

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('role.index'),
                    'message' => 'Role successfully updated.'
                ]);


            }

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Failed occured! '.$e->getMessage()
            ], 400);
        }
    }


}
