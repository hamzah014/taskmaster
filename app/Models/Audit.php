<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'audits';
    protected $guarded = ['id'];
    protected $dates   = ['created_at, updated_at'];
	
	protected $primaryKey = 'id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
