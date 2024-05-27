<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\RolePermission;
use Auth;
use Closure;

class CheckPermission
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
        $permission = Permission::where('PMName',$permission_name)->first();
        if($permission){
            
            //here you have to get logged in user role
            $user = Auth::user();
            
            $US_RLCode = $user->US_RLCode;

            ## so now check permission
            $check_permission = RolePermission::where('RP_RLCode',$US_RLCode)
            ->where('RP_PMCode',$permission->PMCode)
            ->whereHas('role',function($query){
                $query->where('RLActive', 1);

            })
            ->first();

            if(isset($check_permission)){
                return $next($request);
            }else{
                return redirect()->back();
            }
        }
        return redirect()->back();
    }
}
