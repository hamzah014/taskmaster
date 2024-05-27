<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRCertificate';
    protected $guarded = ['CEID'];
    protected $dates   = ['CECD', 'CEMD', 'CEEndDate', 'CEStartDate'];

	protected $primaryKey = 'CEID';
    const CREATED_AT = 'CECD';
    const UPDATED_AT = 'CEMD';

    public function project(){
        return $this->hasOne(Project::class, 'PJCode', 'CE_PJCode');
    }

    public function certStatus(){
        return $this->hasOne(CertStatus::class, 'CSCode', 'CE_CSCode');
    }


}
