<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))

    ->withMiddleware(function (Middleware $middleware) {

        
        $middleware->use([
            HandleCors::class,
        ]);

        
        $middleware->alias([
            'lang' => \App\Http\Middleware\SetUserLanguage::class,
        ]);
    })

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
    )

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
