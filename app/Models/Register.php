<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Register extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRRegister';
    protected $guarded = ['RGID'];
    protected $dates   = ['RGCD, RGMD'];

	protected $primaryKey = 'RGID';
    const CREATED_AT = 'RGCD';
    const UPDATED_AT = 'RGMD';

}
