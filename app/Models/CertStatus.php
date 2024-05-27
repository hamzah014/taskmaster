<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class CertStatus extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSCertStatus';
    protected $guarded = ['CSID'];
    protected $dates   = ['CSCD, CSMD'];

	protected $primaryKey = 'CSID';
    const CREATED_AT = 'CSCD';
    const UPDATED_AT = 'CSMD';

}
