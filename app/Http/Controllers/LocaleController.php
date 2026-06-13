<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $availableLocales = array_keys(config('app.available_locales', ['fr' => 'Français', 'en' => 'English']));

        abort_unless(in_array($locale, $availableLocales, true), 404);

        if ($request->user()) {
            $request->user()->forceFill([
                'preferred_locale' => $locale,
            ])->save();
        }

        $request->session()->put('locale', $locale);

        return back();
    }
}
