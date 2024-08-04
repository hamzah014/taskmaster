<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Spatie\Permission\Traits\HasRoles;
use Laravel\Passport\HasApiTokens;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\Permission;
use App\Models\Customer;
use App\Models\FileAttach;
use App\Models\Module;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as IlluminateAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Auth;


class User extends Authenticatable implements Auditable
{
    use HasApiTokens,Notifiable,AuditableTrait;

    protected $table = 'MSUser';
    protected $guarded = ['USID'];
    protected $dates   = ['USCD, USMD'];
	protected $primaryKey = 'USID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'USName', 'USEmail', 'USPwd',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */

	public function getAuthPassword()
	{
		return $this->USPwd;
	}

    protected $hidden = [
        'USPwd',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'USCD';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'USMD';


	public function hasPermission($permission): bool
	{

        //first check that name in your db
        $permission = Permission::where('PMName',$permission)->first();
        if($permission){

            //here you have to get logged in user role
            $user = Auth::user();

            $US_RLCode = $user->US_RLCode;

            ## so now check permission
            $check_permission = RolePermission::where('RP_RLCode',$US_RLCode)
            ->whereHas('role',function($query){
                $query->where('RLActive', 1);

            })
            ->where('RP_PMCode',$permission->PMCode)
            ->first();

            if(isset($check_permission)){
                return 1;
            }else{
                return 0;
            }
        }
        return 0;
	}

	public function hasModule($subGroup): bool
	{

        //first check that name in your db
        $permission = Permission::where('PMSubGroup',$subGroup)->get()->pluck('PMCode')->toArray();
        if($permission){

            //here you have to get logged in user role
            $user = Auth::user();

            $US_RLCode = $user->US_RLCode;

            ## so now check permission
            $check_permission = RolePermission::where('RP_RLCode',$US_RLCode)
            ->whereHas('role',function($query){
                $query->where('RLActive', 1);

            })
            ->whereIn('RP_PMCode',$permission)
            ->first();

            if(isset($check_permission)){
                return 1;
            }else{
                return 0;
            }
        }
        return 0;
	}

	public function getProfileURL()
	{
		$profilePhotoURL = null;

        $fileAttach = FileAttach::where('FAFileType','PP')->where('FARefNo', Auth::user()->USCode)->where('FAActive',1)->first();
		if ($fileAttach != null){
			$profilePhotoURL = route( 'file.view' ,$fileAttach->FAGuidID);
		}
        else{
            $profilePhotoURL = asset('assets/images/avatar/avatar-0.png');
        }
		return $profilePhotoURL;
	}

    // Role
    public function role()
    {
        return $this->hasOne(Role::class, 'RLCode', 'US_RLCode');
        // return $this->hasOne(Role::class, 'RLName', 'US_RLCode');
    }

    public function isAdmin(): bool
    {

        $user = Auth::user();

        $rlCode = $user->US_RLCode;
        $type = $user->USType;

        if($rlCode == 'RL01' && $type=='AD'){
            return 1;
        }else{
            return 0;
        }

    }

    public function generateToken(){

        $token = Str::random(60);

        return hash('sha256', $token);
    }

}
