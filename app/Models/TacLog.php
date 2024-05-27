<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class TacLog extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRTacLog';
    protected $guarded = ['TACID'];
    protected $dates   = ['TACCD, TACMD'];
	
	protected $primaryKey = 'TACID';
    const CREATED_AT = 'TACCD';
    const UPDATED_AT = 'TACMD';
}
