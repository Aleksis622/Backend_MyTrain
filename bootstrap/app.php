<?php

use App\Http\Middleware\EnsureTrainTrackerToken;
use App\Http\Middleware\SetUserLanguage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
    )

    ->withMiddleware(function (Middleware $middleware) {

        // Sanctum SPA (cookie) authentication for the "api" middleware group.
        // Adds session + CSRF handling for requests coming from SANCTUM_STATEFUL_DOMAINS.
        // HandleCors is already part of Laravel's default global middleware.
        $middleware->statefulApi();

        $middleware->alias([
            'lang' => SetUserLanguage::class,
            'train.tracker' => EnsureTrainTrackerToken::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Always answer API requests with JSON errors instead of HTML error pages.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );
    })

    ->create();
