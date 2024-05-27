<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class GenerateReportType extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSGenerateReportType';
    protected $guarded = ['GRTID'];
    protected $dates   = ['GRTCD, GRTMD'];
	
	protected $primaryKey = 'GRTID';
    const CREATED_AT = 'GRTCD';
    const UPDATED_AT = 'GRTMD';
}
