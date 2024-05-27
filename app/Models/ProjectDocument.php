<?php


namespace App\Models;

use App\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class ProjectDocument extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRProjectDocument';
    protected $guarded = ['PDID'];
    protected $dates   = ['PDCD, PDMD'];

	protected $primaryKey = 'PDID';
    const CREATED_AT = 'PDCD';
    const UPDATED_AT = 'PDMD';

    public function project(){
        return $this->hasOne(Project::class, 'PJCode', 'PD_PJCode');
    }

    public function fileAttach(){
        return $this->hasOne(FileAttach::class, 'FARefNo', 'PDCode');
    }

}

