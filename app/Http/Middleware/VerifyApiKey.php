<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    
    public function handle(Request $request, Closure $next)
    {
        $project = Project::where('PJActive',1)->where('PJApiKeyClient',$request->header('api-key'))->first();
        $apiKey = $project->PJApiKeyServer ?? '';

        if (empty($project))
            return response()->json(['error' => 'Invalid API key.'], 401);

        return $next($request);
    }
}
