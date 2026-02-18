<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\LogUserActivity::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Check for delayed tasks every 5 minutes for immediate alerts when time is exceeded
        $schedule->command('tasks:check-delayed')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
