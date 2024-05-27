<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSPermission';
    protected $guarded = ['PMID'];
    protected $dates   = ['PMCD, PMMD'];
	
	protected $primaryKey = 'PMID';
    const CREATED_AT = 'PMCD';
    const UPDATED_AT = 'PMMD';
}
