<?php


namespace App\Models;

use App\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class ProjectIdea extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRProjectIdea';
    protected $guarded = ['PIID'];
    protected $dates   = ['PICD, PIMD'];

	protected $primaryKey = 'PIID';
    const CREATED_AT = 'PICD';
    const UPDATED_AT = 'PIMD';

    public function user(){
        return $this->hasOne(User::class, 'USCode', 'PICB');
    }

    public function project(){
        return $this->hasOne(Project::class, 'PJCode', 'PI_PJCode');
    }

    public function ideaScoring(){
        return $this->hasOne(IdeaScoring::class, 'PIS_PICode', 'PICode');
    }

    public function fileAttach(){
        return $this->hasOne(FileAttach::class, 'FARefNo', 'PICode');
    }

}

