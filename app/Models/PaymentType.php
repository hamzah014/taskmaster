<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSPaymentType';
    protected $guarded = ['PTID'];
    protected $dates   = ['PTCD, PTMD'];
	
	protected $primaryKey = 'PTID';
    const CREATED_AT = 'PTCD';
    const UPDATED_AT = 'PTMD';
}
