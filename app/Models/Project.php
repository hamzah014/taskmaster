<?php


namespace App\Models;

use App\Services\DropdownService;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

class Project extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'TRProject';
    protected $guarded = ['PJID'];
    protected $dates   = ['PJCD, PJMD'];

	protected $primaryKey = 'PJID';
    const CREATED_AT = 'PJCD';
    const UPDATED_AT = 'PJMD';

    public function projectTeam()
    {
        return $this->hasMany(ProjectTeam::class, 'PT_PJCode', 'PJCode');
    }

    public function myProjectRole($usercode){
        return $this->hasMany(ProjectTeam::class, 'PT_PJCode', 'PJCode')->where('PT_USCode', $usercode);
    }

    public function projectDocument()
    {
        return $this->hasMany(ProjectDocument::class, 'PD_PJCode', 'PJCode');
    }

    public function projectRisk(){
        return $this->hasOne(ProjectRisk::class, 'PR_PJCode', 'PJCode');
    }

    public function projectIdea(){
        return $this->hasMany(ProjectIdea::class, 'PI_PJCode', 'PJCode');
    }

    public function taskProject(){
        return $this->hasMany(TaskProject::class, 'TP_PJCode', 'PJCode');
    }

    public function myTaskProject($usercode){
        return $this->hasMany(TaskProject::class, 'TP_PJCode', 'PJCode')->where('TPAssignee', $usercode);
    }

    public function projectStatus(){

        $dropdownService = new DropdownService();
        $projectStatus = $dropdownService->projectStatus();

        return $projectStatus[$this->PJStatus] ?? "Pending";

    }

    public function projectPercent(){

        $dropdownService = new DropdownService();
        $projectStatus = $dropdownService->projectStatus();

        // Get the index of the current status
        $statusIndex = array_search($this->PJStatus, array_keys($projectStatus));

        if ($statusIndex === false) {
            return 0;
        }

        // Calculate the percentage based on the index
        $totalStatuses = count($projectStatus);
        $percentage = (($statusIndex + 1) / $totalStatuses) * 100;

        return $percentage ? number_format($percentage, 2) : 0;

    }

}

