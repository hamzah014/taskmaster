<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class SignDocument extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRSignDocument';
    protected $guarded = ['SDID'];
    protected $dates   = ['SDCD, SDMD'];

	protected $primaryKey = 'SDID';
    const CREATED_AT = 'SDCD';
    const UPDATED_AT = 'SDMD';

    //SD-SF - sign form
    //SD-OF - ori form
    //SD-SI - sign image

    public function certificate(){
        return $this->hasOne(Certificate::class, 'CENo', 'SD_CENo');
    }

    public function fileAttach(){
        return $this->hasOne(FileAttach::class, 'FARefNo', 'SDNo')->where('FAFileType','SD-SF');
    }

    // public function fileAttachGL()
    // {
    //     return $this->hasOne(FileAttach::class, 'FARefNo', 'PGLNo')->where('FAFileType','GL');
    // }

}
