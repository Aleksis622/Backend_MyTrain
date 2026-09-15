<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class SetUserLanguage
{
    public function handle($request, Closure $next)
    {
        
        $lang = $request->header('X-Language')
            ?? $request->get('lang')
            ?? ($request->user()->language ?? 'lv');

        App::setLocale($lang);

        return $next($request);
    }
}
