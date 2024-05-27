<?php


namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Department extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSDepartment';
    protected $guarded = ['DPTID'];
    protected $dates   = ['DPTCD, DPTMD'];

	protected $primaryKey = 'DPTID';
    const CREATED_AT = 'DPTCD';
    const UPDATED_AT = 'DPTMD';


}

