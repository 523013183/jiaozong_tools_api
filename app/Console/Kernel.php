<?php

namespace App\Console;

use App\Base\Services\SysEmailTaskService;
use App\Console\Commands\CacheCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CacheCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // 自动邮件通知
        $schedule->call(function () {
            $emailTaskService = app()->make(SysEmailTaskService::class);
            $emailTaskService->send();
        })->everyMinute()
            ->name('EmailTaskNew')
            ->runInBackground()
            ->withoutOverlapping(600);
    }
}
