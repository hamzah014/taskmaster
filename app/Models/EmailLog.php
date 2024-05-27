<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TREmailLog';
    protected $guarded = ['ELID'];
    protected $dates   = ['ELCD, ELMD'];
	
	protected $primaryKey = 'ELID';
    const CREATED_AT = 'ELCD';
    const UPDATED_AT = 'ELMD';
}
