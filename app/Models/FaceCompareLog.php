<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class FaceCompareLog extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRFaceCompareLog';
    protected $guarded = ['FCLID'];
    protected $dates   = ['FCLCD, FCLMD'];

	protected $primaryKey = 'FCLID';
    const CREATED_AT = 'FCLCD';
    const UPDATED_AT = 'FCLMD';

}
