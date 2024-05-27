<?php


namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class ProjectRisk extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRProjectRisk';
    protected $guarded = ['PRID'];
    protected $dates   = ['PRCD, PRMD'];

	protected $primaryKey = 'PRID';
    const CREATED_AT = 'PRCD';
    const UPDATED_AT = 'PRMD';

    public function project()
    {
        return $this->hasOne(Project::class, 'PJCode', 'PR_PJCode');
    }

}

