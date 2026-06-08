<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locales::isEnabled($locale), 404);

        $request->session()->put(config('locales.session_key', 'locale'), $locale);

        return redirect()->back(fallback: url('/'));
    }
}
