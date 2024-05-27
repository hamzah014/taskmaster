<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Country extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSCountry';
    protected $guarded = ['CTID'];
    protected $dates   = ['CTCD, CTMD'];

	protected $primaryKey = 'CTID';
    const CREATED_AT = 'CTCD';
    const UPDATED_AT = 'CTMD';

    // relationship
    public function embassy()
    {
        return $this->hasMany(Embassy::class, 'EM_CTCode', 'CTCode');
    }
}
