<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFamilyScope
{
    /**
     * Family-only accounts are restricted to the family app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->isFamilyOnly()) {
            if (! $request->routeIs('keluarga*') && ! $request->routeIs('logout')) {
                return redirect()->route('keluarga.app');
            }
        }

        return $next($request);
    }
}
