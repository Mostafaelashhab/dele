<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const SUPPORTED = ['ar', 'en'];

    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::SUPPORTED, true), 404);

        $request->session()->put('locale', $locale);

        // Persisting on the user means the choice survives a new device and
        // applies to the SMS and emails they receive, not just this browser.
        $request->user()?->forceFill(['locale' => $locale])->saveQuietly();

        return back();
    }
}
