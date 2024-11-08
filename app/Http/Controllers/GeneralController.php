<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Services\DropdownService;
use Mail;

class GeneralController extends Controller{

    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

}
