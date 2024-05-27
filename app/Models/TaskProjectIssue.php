<?php


namespace App\Models;

use App\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class TaskProjectIssue extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TR_TPIssue';
    protected $guarded = ['TPIID'];
    protected $dates   = ['TPICD, TPMD'];

	protected $primaryKey = 'TPIID';
    const CREATED_AT = 'TPICD';
    const UPDATED_AT = 'TPIMD';

    public function task(){
        return $this->hasOne(TaskProject::class, 'TPCode', 'TPI_TPCode');
    }

    public function user(){
        return $this->hasOne(User::class, 'USCode', 'TPICB');
    }

    public function fileAttach(){
        return $this->hasOne(FileAttach::class, 'FARefNo', 'TPICode');
    }

}

