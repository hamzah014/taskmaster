<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Log;
use Auth;

class Role extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSRole';
    protected $guarded = ['RLID'];
    protected $dates   = ['RLCD, RLMD'];

	protected $primaryKey = 'RLID';
    const CREATED_AT = 'RLCD';
    const UPDATED_AT = 'RLMD';


    public function hasPermissionTo($roleCode, $permissionCode): bool
    {
		$rolePermission = RolePermission::Where('RP_PMCode',$permissionCode)->where('RP_RLCode',$roleCode)->first();
		if ($rolePermission == null){
			return 0;
		}else{
			return 1;
		}
    }

    // Relationship

    // User
    public function user()
    {
        return $this->hasMany(User::class, 'US_RLCode');
    }

    public function rolePermission(){

        return $this->hasMany(RolePermission::class, 'RP_RLCode', 'RLCode')->pluck('RP_PMCode');

    }

}
