<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserLanguage
{
    public const SUPPORTED = ['lv', 'en', 'ru'];

    public const DEFAULT = 'lv';

    /**
     * Resolve the locale from the X-Language header, ?lang= query, or the user's saved language.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lang = $request->header('X-Language')
            ?? $request->query('lang')
            ?? $request->user()?->language;

        App::setLocale(in_array($lang, self::SUPPORTED, true) ? $lang : self::DEFAULT);

        return $next($request);
    }
}
