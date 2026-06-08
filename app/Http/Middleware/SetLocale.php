<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = config('locales.session_key', 'locale');
        $locale = $request->session()->get($sessionKey);

        app()->setLocale(Locales::resolve(is_string($locale) ? $locale : null));

        return $next($request);
    }
}
