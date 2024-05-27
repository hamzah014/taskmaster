<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSRolePermission';
    protected $guarded = ['RPID'];
    protected $dates   = ['RPCD, RPMD'];
	
	protected $primaryKey = 'RPID';
    const CREATED_AT = 'RPCD';
    const UPDATED_AT = 'RPMD';

    public function role(){
        return $this->hasOne(Role::class, 'RLCode', 'RP_RLCode');
    }
}
