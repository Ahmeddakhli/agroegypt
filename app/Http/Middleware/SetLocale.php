<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the {locale} route prefix: only active languages from the database are accepted.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $language = Language::findByCode((string) $request->route('locale'));

        if (! $language?->is_active) {
            return Redirect::respondTo($request->path()) ?? abort(404);
        }

        app()->setLocale($language->code);
        config(['translatable.fallback_locale' => $language->fallbackCode(), 'app.fallback_locale' => $language->fallbackCode()]);
        app('translator')->setFallback($language->fallbackCode());
        URL::defaults(['locale' => $language->code]);
        View::share('currentLanguage', $language);

        // Controllers receive route parameters without the locale prefix.
        $request->route()->forgetParameter('locale');

        return $next($request);
    }
}
