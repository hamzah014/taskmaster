<?php

namespace App\Http\Controllers\Setting;

use Carbon\Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Validator;
use Auth;


class RolesController extends Controller
{
    public function index(){
		
		$user = auth::user();
        $roles = Role::orderby('RLName','asc')->get();
        $permissions = Permission::orderby('PMName','asc')->get()->groupBy(['PMMainGroup','PMSubGroup']);
		
        return view('setting.roles.index', compact('roles', 'permissions', 'user'));
    }

    public function create(Request $request){
		
        $messages = [
            'RLName.required' 	=> trans('message.RLName.required'),
            'RLName.max' 		=> trans('message.RLName.max'),
        ];

        $validation = [
            'RLName' => 'required|max:255'
        ];

        $request->validate($validation, $messages);

        // Reject if role name equal to developer or superadmin
        if(trim(strtolower($request->name)) == 'developer' || trim(strtolower($request->name)) == 'administrator') {
            return response()->json([
				'error' => '1', 
				'message' => trans('message.another.role')
			], 401);
        }

        if($request->RLCode) {
            $role = Role::where('RLCode',$request->RLCode)->first();
        } else {
            $role = new Role;
        }
        $role->RLCode = $request->RLCode;
        $role->RLName = $request->RLName;
        $role->save();

        return response()->json([
			'success' => '1', 
			'redirect' => route('setting.rolesAndPermissions.index'),
			'message' => trans('message.role.create')
		]);
    }

    public function storePermission(Request $request){
		
		log::debug($request->permissions);
		
        $role = Role::find($request->id);
		
		$findPermission = Permission::join('MSRolePermission','MSRolePermission.RP_PMCode', 'MSPermission.PMCode')
									->where('RP_RLCode', $role->RLCode)
									->pluck('MSPermission.PMCode')
									->toArray();

        $insertedPermission = [];

		if(isset($request->permissions)) {
			foreach ($request->permissions as $i => $row) {
				//log::debug($row);
				$insertedPermission[$i] = $row;
				
				$permission = Permission::where('PMCode', $row)->first();
				
				if ($permission != null){
					$rolePermission = RolePermission::where('RP_RLCode',$role->RLCode)->where('RP_PMCode',$permission->PMCode)->first();
						
					if ($rolePermission == null){
						// Begin inserting data
						$details = new RolePermission();
						$details->RP_RLCode 	= $role->RLCode;
						$details->RP_PMCode 	= $permission->PMCode;
						$details->save();
					}
				}
			}

			// Compare data (Get deleted person)
			$deletedPermission= array_diff($findPermission, $insertedPermission);

			// Begin deletion
			foreach($deletedPermission as $row) {
				$permission = Permission::where('PMCode', $row)->first();
				if ($permission != null){
					RolePermission::where('RP_RLCode',$role->RLCode)->where('RP_PMCode',$permission->PMCode)->delete();
				}
			}

		} else {
			// Delete all permission
			//RolePermission::where('RoleID', $role->RoleID)->delete();
		}

        return response()->json([
			'success' => '1', 
			'redirect' => route('setting.rolesAndPermissions.index'), 
			'message' => trans('message.permission.update')
		]);
    }

    public function delete(Request $request) {
		
        $role = Role::find($request->id)->delete();

        return response()->json([
			'success' => '1', 
			'redirect' => route('setting.rolesAndPermissions.index'), 
			'message' => trans('message.role.delete')
		]);
    }
}