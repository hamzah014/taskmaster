<?php

namespace App\Http\Controllers\Info;

use App\Http\Controllers\Controller;
use Auth;

class InfoController extends Controller{

    public function about()
    {
        return view('info.about');
    }

    public function analysis()
    {
        return view('info.analysis');
    }

    public function risk()
    {
        return view('info.risk');
    }



}
