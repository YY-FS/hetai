<?php

namespace App\Console;

use App\Console\Commands\GenerateUserGroup;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\GenerateUserFilter;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        GenerateUserFilter::class,
        GenerateUserGroup::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('Generate:UserFilter')
            ->withoutOverlapping()
            ->dailyAt('04:15');
//            ->dailyAt('8:30')
//            ->dailyAt('12:30')
//            ->dailyAt('15:30')
//            ->dailyAt('18:30')
//            ->dailyAt('20:30');
        $schedule->command('Generate:UserGroup')->withoutOverlapping()->hourly();

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
