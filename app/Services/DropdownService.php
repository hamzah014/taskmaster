<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Project;
use App\Models\Role;
use App\User;

class DropdownService
{
    public function department(){

        $department = Department::get()->pluck('DPTName', 'DPTCode');

        return $department;

    }

    public function projectCategory()
    {
        $projectCategory = [
            '' => '',
            '00' => 'Business',
            '01' => 'Sport',
            '02' => 'Technology',
            '03' => 'Entertainment',
        ];

        return $projectCategory;
    }

    public function priorityLevel()
    {
        $priorityLevel = [
            '1' => '1',
            '2' => '2',
            '3' => '3',
        ];

        return $priorityLevel;
    }

    public function roleUser(){

        $roleUser = Role::where('RLActive', 1)->get()->pluck('RLName', 'RLCode');

        return $roleUser;

    }

    public function roleForUser(){

        $roleForUser = Role::where('RLActive', 1)->where('RLAdmin', 0)->get()->pluck('RLName', 'RLCode');

        return $roleForUser;

    }

    public function roleMandatory(){

        $roleUser = Role::where('RLActive', 1)
        ->where('RLMandatory', 1)
        ->get()->pluck('RLName', 'RLCode');

        return $roleUser;

    }

    public function project(){

        $project = Project::get()->pluck('PJName', 'PJCode')
        ->map(function ($item, $key) {
            return  $item . " ( ".$key." )"; // Appending MACode to MA_MOFCode
        });

        return $project;

    }

    public function userProject($userCode){

        $project = Project::where('PJCB', $userCode)
        ->where('PJStatus', 'PROGRESS')
        ->get()->pluck('PJName', 'PJCode')
        ->map(function ($item, $key) {
            return  $item . " ( ".$key." )";
        });

        return $project;

    }


    public function users(){

        $users = User::get()->pluck('USName', 'USCode')
        ->map(function ($item, $key) {
            $user = User::where('USCode', $key)->first();
            return  $item . " ( ".$user->USEmail." )";
        });

        return $users;

    }

    public function taskStatus()
    {
        $taskStatus = [
            'PENDING' => 'Pending',
            'PROGRESS' => 'In-Progress',
            'SUBMIT' => 'Review',
            'COMPLETE' => 'Complete',
            'CANCEL' => 'Cancel',
        ];

        return $taskStatus;
    }

    public function projectStatus()
    {
        $projectStatus = [
            'PENDING' => 'Pending',
            'CANCEL' => 'Cancelled',
            'IDEA' => 'Request Idea',
            'IDEA-ALS' => 'Idea Analysis',
            'IDEA-SCR' => 'Idea Scoring',
            'PROJ-ALS' => 'Project Analysis',
            'RISK' => 'Project Risk',
            'PROGRESS-PD' => 'Project Design',
            'PROGRESS-FD' => 'Future Design',
            'PROGRESS-PC' => 'Project Closure',
            'COMPLETE' => 'Completed',
            'CLOSED' => 'Project Closed',
        ];

        return $projectStatus;
    }

    public function moscowType()
    {
        $moscowType = [
            'M' => 'Must Have',
            'S' => 'Should Have',
            'C' => 'Could have',
            'W' => 'Wont Have',
        ];

        return $moscowType;
    }

    public function requirementType()
    {
        $requirementType = [
            'F' => 'Functional',
            'NF' => 'Non-Functional',
        ];

        return $requirementType;
    }

    public function taskType()
    {
        $taskType = [
            'PD' => 'Project Design',
            'FD' => 'Further Development',
            'PC' => 'Project Closure', //final product,acceptance,deployment,maintenance
        ];

        return $taskType;
    }

    public function taskStatusType($type)
    {

        $taskStatusPC = [
            'FIP' => 'Final Product',
            'ACP' => 'Acceptance',
            'DEP' => 'Deployment',
            'MAN' => 'Maintenance'
        ];

        if($type == 'PC')
        {
            return $taskStatusPC;
        }

        return $this->taskStatus();

    }

}
