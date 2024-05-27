<?php


namespace App\Models;

use App\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class ProjectTeam extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRProjectTeam';
    protected $guarded = ['PTID'];
    protected $dates   = ['PTCD, PTMD'];

	protected $primaryKey = 'PTID';
    const CREATED_AT = 'PTCD';
    const UPDATED_AT = 'PTMD';
    
    public function user(){
        return $this->hasOne(User::class, 'USCode', 'PT_USCode');
    }

    public function project(){
        return $this->hasOne(Project::class, 'PJCode', 'PT_PJCode');
    }

}

