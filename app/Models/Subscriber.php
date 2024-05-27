<?php


namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSSubcriber';
    protected $guarded = ['SUID'];
    protected $dates   = ['SUCD, SUMD'];

	protected $primaryKey = 'SUID';
    const CREATED_AT = 'SUCD';
    const UPDATED_AT = 'SUMD';

}

