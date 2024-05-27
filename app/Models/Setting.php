<?php

namespace App\Models;

use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'RefSetting';
    protected $guarded = ['SettingID'];
    protected $dates   = ['CreateDate, ModifyDate'];
	
	protected $primaryKey = 'SettingID';
    const CREATED_AT = 'CreateDate';
    const UPDATED_AT = 'ModifyDate';
}
