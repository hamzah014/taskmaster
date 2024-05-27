<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Models\Permission;
use App\Models\RolePermission;

class AuthResource
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next,$permission_name)
    {
        //first check that name in your db
        $permission = Permission::where('PermissionName',$permission_name)->first();
            if($permission){
                //here you have to get logged in user role
                $roleID = Auth::user()->RoleID;

                ## so now check permission
                $check_permission = RolePermission::where('RoleID',$roleID)->where('PermissionID',$permission->PermissionID)->first();

                if(isset($check_permission)){
                    return $next($request);
                }else{
					return redirect()->back();
                }
                //if Permission not assigned for this user role show what you need
            }
            // if Permission name not in table then do what you need
            ## Ex1 : return 'Permission not in Database';
            ## Ex2 : return redirect()->back();

        }

}
