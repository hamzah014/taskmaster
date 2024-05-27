<?php

namespace App\Http\Middleware;

//use App\Models\ActivityLog;
use App\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Session\Store;
use Auth;
use Session;

class SessionExpired
{
    protected $session;
    protected $timeout = 5;

    public function __construct(Store $session){
        $this->session = $session;
    }

    public function handle($request, Closure $next)
    {
        // If user is not logged in...
        if (!Auth::check()) {
            return $next($request);
        }else{
            $request->session()->invalidate();
            return $next($request);
		}

  /*
        $user = Auth::guard()->user();

        $now = Carbon::now();

        $userUpdate = User::find($user->USCode);
        $userUpdate->USLastLogin = Carbon::now()->format('Y-m-d H:i:s');
        $userUpdate->save();


        //$last_seen = Carbon::parse($user->last_seen_at);

        $absence = $now->diffInMinutes($last_seen);

        // If user has been inactivity longer than the allowed inactivity period
        if ($absence > config('session.lifetime')) {

            $log = new ActivityLog();
            $log -> user_id = Auth::user()->id;
            $log -> start_time = $userUpdate ->LastLogin;
            $log -> end_time = $now->format('Y-m-d H:i:s');
            $log->save();

            Auth::guard()->logout();

            $request->session()->invalidate();
            return $next($request);

        }
        */


        return $next($request);
    }
}
