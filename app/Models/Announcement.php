<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model implements Auditable
{
    //
    use AuditableTrait;

    protected $table   = 'TRAnnouncement';
    protected $guarded = ['ACID'];
    protected $dates   = ['ACCD, ACMD'];
	
	protected $primaryKey = 'ACID';
    const CREATED_AT = 'ACCD';
    const UPDATED_AT = 'ACMD';

}
