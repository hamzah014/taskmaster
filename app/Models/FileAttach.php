<?php

namespace App\Models;

use App\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class FileAttach extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRFileAttach';
    protected $guarded = ['FAID'];
    protected $dates   = ['FACD', 'FAMD', 'FADD'];

	protected $primaryKey = 'FAID';
    const CREATED_AT = 'FACD';
    const UPDATED_AT = 'FAMD';


    public function submitBy(){
        return $this->hasOne(User::class, 'USCode' ,'FACB');
    }


}
