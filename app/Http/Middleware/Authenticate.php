<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Session;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {

			if (Session::get('page') == 'publicUser'){
				return route('publicUser.login.index');
			}else if (Session::get('page') == 'project'){
				return route('project.login.index');
			}else{
				return route('login.index');
			}
        }
    }
}
