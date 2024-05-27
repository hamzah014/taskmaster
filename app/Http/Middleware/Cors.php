<?php

namespace App\Http\Middleware;

use Closure;
use Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
	
		  header("Access-Control-Allow-Origin: *");
		  
		  //ALLOW OPTIONS METHOD
		  $headers = [
			 'Access-Control-Allow-Methods' => 'GET, HEAD, POST, PATCH, PUT, OPTIONS',
			 'Access-Control-Allow-Headers' => '*'//'Origin, Content-Type, Cookie, Accept, x-localization, Authentication',
		  ];
		  if ($request -> getMethod() == "OPTIONS") {
			 //The client-side application can set only headers allowed in Access-Control-Allow-Headers
			 return response() -> json('OK', 200, $headers);
		  }
		  $response = $next($request);
		  foreach($headers as $key => $value) {
			 //$response -> header($key, $value);
			 $response->headers->set($key, $value);
		  }
		  return $response;
		  
		/* return $next($request)
			->header('Access-Control-Allow-Methods', '*')
			->header('Access-Control-Allow-Headers', '*')
			->header('Access-Control-Allow-Origin', '*');*/
			
        /*$response = $next($request);
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, Accept, x-localization, Authentication');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');
        $response->header('Access-Control-Allow-Credentials', 'false');
        return $response;*/
		
		
        /*if ($request->isMethod('OPTIONS'))
        {
            return response()->json('{"method":"OPTIONS"}', 200, $headers);
        }

        $response = $next($request);
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:8080','http://localhost','https://edu.pilateswien.org');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');

        //return $response->withHeaders($headers);
        return $response;*/

    }
}
