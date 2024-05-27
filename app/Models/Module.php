<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Module extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSModule';
    protected $guarded = ['MDID'];
    protected $dates   = ['MDCD, MDMD'];

	protected $primaryKey = 'MDID';
    const CREATED_AT = 'MDCD';
    const UPDATED_AT = 'MDMD';

}
