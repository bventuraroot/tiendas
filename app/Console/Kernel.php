<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Respaldo diario a las 2:00 AM
        $schedule->command('backup:auto --compress --keep=7')
                 ->dailyAt('02:00')
                 ->name('daily-backup')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Respaldo semanal los domingos a las 3:00 AM (sin comprimir para respaldo completo)
        $schedule->command('backup:auto --keep=4')
                 ->weeklyOn(0, '03:00')
                 ->name('weekly-backup')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Respaldo mensual el día 1 a las 4:00 AM
        $schedule->command('backup:auto --compress --keep=12')
                 ->monthlyOn(1, '04:00')
                 ->name('monthly-backup')
                 ->withoutOverlapping()
                 ->runInBackground();
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
