<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Role;
use App\User;

class GeneralService
{
    public function totalRisk($projectRisk)
    {

        $arrayColumn = [
            'PR_Security1',
            'PR_Security2',
            'PR_Operational1',
            'PR_Operational2',
            'PR_Architect',
            'PR_Regulatory',
            'PR_Reputation1',
            'PR_Reputation2',
            'PR_Financial',
            'PR_BuildApp',
            'PR_Integrate',
            'PR_UICreate',
        ];

        $riskData = $projectRisk->toArray();

        $totalRisk = 0;

        foreach($arrayColumn as $index => $column){

            $type = $riskData[$column];

            if($type == 'H'){
                $totalRisk += 10;
            }
            elseif($type == 'M'){
                $totalRisk += 5;
            }
            else{
                $totalRisk += 0;
            }

        }


        return $totalRisk;
    }

}
