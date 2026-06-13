<?php

namespace App\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = array_keys(config('app.available_locales', ['fr' => 'Français', 'en' => 'English']));
        $locale = config('app.locale');
        $userLocale = $request->user()?->preferred_locale;
        $sessionLocale = $request->session()->get('locale');

        if (in_array($userLocale, $availableLocales, true)) {
            $locale = $userLocale;
        } elseif (in_array($sessionLocale, $availableLocales, true)) {
            $locale = $sessionLocale;
        }

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);
        CarbonImmutable::setLocale($locale);

        return $next($request);
    }
}
