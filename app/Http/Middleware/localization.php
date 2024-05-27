<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;

class localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check header request and determine localizaton
        $local = ($request->hasHeader('X-localization')) ? $request->header('X-localization') : 'enphp artisan cache:clear';
        // set laravel localization

        if(Session::has('locale')){
            App::setLocale(Session::get('locale'));
        }else{
			app()->setLocale($local);
		}

         App::setLocale('en');
        // continue request
        return $next($request);
    }
}
