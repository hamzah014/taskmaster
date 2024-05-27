<?php


namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class IdeaScoring extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRPIScoring';
    protected $guarded = ['PISID'];
    protected $dates   = ['PISCD, PISMD'];

	protected $primaryKey = 'PISID';
    const CREATED_AT = 'PISCD';
    const UPDATED_AT = 'PISMD';

    public function projectIdea(){
        return $this->hasOne(ProjectIdea::class, 'PICode', 'PIS_PICode');
    }

    public function project(){
        return $this->hasOne(Project::class, 'PJCode', 'PIS_PJCode');
    }

}

