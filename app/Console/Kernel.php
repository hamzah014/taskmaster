<?php
namespace App\Console;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //     //$schedule->command('passport:purge')->dailyAt('03:00');
       // $schedule->call('App\Http\Controllers\Api\NotificationController@updateActive')->dailyAt('08:00');
        //$schedule->call('App\Http\Controllers\Api\NotificationController@sendPushNotification')->dailyAt('08:00');
		//$schedule->call('App\Http\Controllers\JobController@processToVAS')->everyMinute()->name('processToVAS')->withoutOverlapping()->appendOutputTo(storage_path('logs/processToVAS.log'));
		//$schedule->call('App\Http\Controllers\JobController@sendSMSVerification')->everyMinute()->name('sendSMSVerification')->withoutOverlapping()->appendOutputTo(storage_path('logs/sendSMSVerification.log'));
		//$schedule->call('App\Http\Controllers\JobController@checkPassportExpiry')->everyMinute()->name('checkPassportExpiry')->withoutOverlapping()->appendOutputTo(storage_path('logs/checkPassportExpiry.log'));
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
		$date = carbon::now()->format('Y-m-d');
        $schedule->command("queue:work --queue=report --memory=2G")->everyMinute()->withoutOverlapping()->appendOutputTo(storage_path('logs/report-'.$date.'.log'));
    }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
