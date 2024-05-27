<?php


namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Project extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRProject';
    protected $guarded = ['PJID'];
    protected $dates   = ['PJCD, PJMD'];

	protected $primaryKey = 'PJID';
    const CREATED_AT = 'PJCD';
    const UPDATED_AT = 'PJMD';

    public function projectTeam()
    {
        return $this->hasMany(ProjectTeam::class, 'PT_PJCode', 'PJCode');
    }

    public function projectDocument()
    {
        return $this->hasMany(ProjectDocument::class, 'PD_PJCode', 'PJCode');
    }

    public function projectRisk(){
        return $this->hasOne(ProjectRisk::class, 'PR_PJCode', 'PJCode');
    }

}

