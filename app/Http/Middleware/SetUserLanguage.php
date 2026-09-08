<?php

namespace App\Http\Middleware;

use Closure;

class SetUserLanguage
{
    public function handle($request, Closure $next)
    {
        if ($request->user()) {
            app()->setLocale($request->user()->language);
        }

        return $next($request);
    }
}
