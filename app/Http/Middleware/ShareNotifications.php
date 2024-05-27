<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use Illuminate\Support\Facades\View;

class ShareNotifications
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        $PUCode     = 'PU';     //PUBLIC USER
        $COCode     = 'CO';     //Contractor
        $POCode     = 'PO';     //Perolehan
        $SOCode     = 'SO';     //Pelaksana/Boss
        $OSCCode    = 'OSC';    //OSC
        $MGCode     = 'MG';     //Management
        $FOCode     = 'FO';     //Kewangan

        $notifications = [];
        $unreadCount = "";

        $PUNotifications = [];
        $PUUnreadCount = "";
        $CONotifications = [];
        $COUnreadCount = "";
        $PONotifications = [];
        $POUnreadCount = "";
        $SONotifications = [];
        $SOUnreadCount = "";
        $OSCNotifications = [];
        $OSCUnreadCount = "";
        $FONotifications = [];
        $FOUnreadCount = "";
        

        if ($user != null) {
            $userCode = $user->USCode;

            //ALL
            $notifications = Notification::where('NOActive', 1)
                ->where('NO_RefCode', $userCode)
                ->latest()
                ->limit(3)
                ->get();

            $unreadCount = Notification::where('NORead', 0)
                ->where('NO_RefCode', $userCode)
                ->where('NOActive', 1)
                ->count();

            if ($unreadCount == 0) {
                $unreadCount = "";
            }

            //PUBLIC USER
            $PUNotifications = Notification::where('NOActive', 1)
                ->where('NO_RefCode', $userCode)
                ->where('NOType', $PUCode)
                ->latest()
                ->limit(3)
                ->get();

            $PUUnreadCount = Notification::where('NORead', 0)
                ->where('NO_RefCode', $userCode)
                ->where('NOType', $PUCode)
                ->where('NOActive', 1)
                ->count();

            if ($PUUnreadCount == 0) {
                $PUUnreadCount = "";
            }

            //Contractor
            $CONotifications = Notification::where('NOActive', 1)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $COCode)
                ->latest()
                ->limit(3)
                ->get();

            $COUnreadCount = Notification::where('NORead', 0)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $COCode)
                ->where('NOActive', 1)
                ->count();

            if ($COUnreadCount == 0) {
                $COUnreadCount = "";
            }


            //Perolehan
            $PONotifications = Notification::where('NOActive', 1)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $POCode)
                ->latest()
                ->limit(3)
                ->get();

            $POUnreadCount = Notification::where('NORead', 0)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $POCode)
                ->where('NOActive', 1)
                ->count();

            if ($POUnreadCount == 0) {
                $POUnreadCount = "";
            }

            //Pelaksana/Boss
            $SONotifications = Notification::where('NOActive', 1)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $SOCode)
                ->latest()
                ->limit(3)
                ->get();

            $SOUnreadCount = Notification::where('NORead', 0)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $SOCode)
                ->where('NOActive', 1)
                ->count();

            if ($SOUnreadCount == 0) {
                $SOUnreadCount = "";
            }

            //OSC
            $OSCNotifications = Notification::where('NOActive', 1)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $OSCCode)
                ->latest()
                ->limit(3)
                ->get();

            $OSCUnreadCount = Notification::where('NORead', 0)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $OSCCode)
                ->where('NOActive', 1)
                ->count();

            if ($OSCUnreadCount == 0) {
                $OSCUnreadCount = "";
            }

            //Management
            $MGNotifications = Notification::where('NOActive', 1)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $MGCode)
                ->latest()
                ->limit(3)
                ->get();

            $MGUnreadCount = Notification::where('NORead', 0)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $MGCode)
                ->where('NOActive', 1)
                ->count();

            if ($MGUnreadCount == 0) {
                $MGUnreadCount = "";
            }
            
            //Kewangan
            $FONotifications = Notification::where('NOActive', 1)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $FOCode)
                ->latest()
                ->limit(3)
                ->get();

            $FOUnreadCount = Notification::where('NORead', 0)
                ->where('NO_RefCode', $userCode)
                ->where('NO_RefCode', $FOCode)
                ->where('NOActive', 1)
                ->count();

            if ($FOUnreadCount == 0) {
                $FOUnreadCount = "";
            }




        }
        View::share('notifications', $notifications);
        View::share('unreadCount', $unreadCount);

        View::share('PUNotifications', $PUNotifications);
        View::share('PUUnreadCount', $PUUnreadCount);

        View::share('CONotifications', $CONotifications);
        View::share('COUnreadCount', $COUnreadCount);

        View::share('PONotifications', $PONotifications);
        View::share('POUnreadCount', $POUnreadCount);

        View::share('SONotifications' , $SONotifications);
        View::share('SOUnreadCount' , $SOUnreadCount);

        View::share('OSCNotifications' , $OSCNotifications);
        View::share('OSCUnreadCount' , $OSCUnreadCount);
        
        View::share('FONotifications' , $FONotifications);
        View::share('FOUnreadCount' , $FOUnreadCount);

        return $next($request);
    }
}
