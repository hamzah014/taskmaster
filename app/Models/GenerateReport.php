<?php

namespace App\Models;

use App\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Log;
use Auth;

class GenerateReport extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRGenerateReport';
    protected $guarded = ['GRID'];
    protected $dates   = ['GRCD, GRMD'];

	protected $primaryKey = 'GRID';
    const CREATED_AT = 'GRCD';
    const UPDATED_AT = 'GRMD';

    public function userBy(){
        return $this->hasOne(User::class, 'USCode', 'GRCB');
    }

    public function fileAttachGR(){
        return $this->hasOne(FileAttach::class, 'FARefNo', 'GRNo');
    }

    public function fileAttachPDF(){
        return $this->hasOne(FileAttach::class, 'FARefNo', 'GRNo')->where('FAFileExtension' , 'pdf');
    }

    public function fileAttachExcel(){
        return $this->hasOne(FileAttach::class, 'FARefNo', 'GRNo')->where('FAFileExtension' , 'xlsx');
    }
}
