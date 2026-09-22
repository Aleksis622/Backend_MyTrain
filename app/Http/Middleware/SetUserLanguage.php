<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class SetUserLanguage
{
    public function handle($request, Closure $next)
    {
        
        $user = $request->user();

        
        $lang =
            $request->header('X-Language') ??
            $request->get('lang') ??
            ($user ? $user->language : 'lv');

        App::setLocale($lang);

        return $next($request);
    }
}
