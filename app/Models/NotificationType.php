<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class NotificationType extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSNotificationType';
    protected $guarded = ['NTID'];
    protected $dates   = ['NTCD, NTMD'];

	protected $primaryKey = 'NTID';
    const CREATED_AT = 'NTCD';
    const UPDATED_AT = 'NTMD';
}
