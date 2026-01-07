<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected $commands = [
        // هنا يمكنك إضافة الأوامر الخاصة بك
        // \App\Console\Commands\BackupDatabase::class,
    ];

    /**
     * Define the application's command schedule.
     */
   protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule)
{
    $schedule->command('backup:run')->dailyAt();
$schedule->command('backup:clean')->dailyAt();

}


    /**
     * Register the commands.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
