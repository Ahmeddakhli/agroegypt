<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard language, chosen from the user menu and kept in the session; falls back to the browser's preference.
 */
class SetAdminLocale
{
    /** @var array<string, string> code => native name */
    public const LOCALES = ['en' => 'English', 'ar' => 'العربية'];

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($request->session()->get('admin_locale') ?? $request->getPreferredLanguage(array_keys(self::LOCALES)));

        return $next($request);
    }
}
