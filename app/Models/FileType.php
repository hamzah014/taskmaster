<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class FileType extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSFileType';
    protected $guarded = ['FTID'];
    protected $dates   = ['FTCD, FTMD'];
	
	protected $primaryKey = 'FTID';
    const CREATED_AT = 'FTCD';
    const UPDATED_AT = 'FTMD';

    public function FileAttachFT(){
        return $this->hasOne(FileAttach::class, 'FAFileType', 'FTCode');
    }
}
