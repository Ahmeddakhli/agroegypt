<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-controlled maintenance mode for the public site; signed-in staff can still browse it.
 */
class SiteMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('general.maintenance', '0') === '1' && ! $request->user()?->roles()->exists()) {
            return response()->view('errors.maintenance', status: 503)->header('Retry-After', '3600');
        }

        return $next($request);
    }
}
