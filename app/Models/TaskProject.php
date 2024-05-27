<?php


namespace App\Models;

use App\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class TaskProject extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRTaskProject';
    protected $guarded = ['TPID'];
    protected $dates   = ['TPCD, TPMD'];

	protected $primaryKey = 'TPID';
    const CREATED_AT = 'TPCD';
    const UPDATED_AT = 'TPMD';

    public function project(){
        return $this->hasOne(Project::class, 'PJCode', 'TP_PJCode');
    }

    public function assignee(){
        return $this->hasOne(User::class, 'USCode', 'TPAssignee');
    }

    public function taskIssue(){
        return $this->hasMany(TaskProjectIssue::class, 'TPI_TPCode', 'TPCode');
    }

    public function fileAttach(){
        return $this->hasOne(FileAttach::class, 'FARefNo', 'TPCode');
    }

}

