<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRNotification';
    protected $guarded = ['NOID'];
    protected $dates   = ['NOCD, NOMD'];
	
	protected $primaryKey = 'NOID';
    const CREATED_AT = 'NOCD';
    const UPDATED_AT = 'NOMD';
}
