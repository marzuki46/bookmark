<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API responses may contain user or family data and must never be served from
 * an intermediary cache, including a Cloudflare cache rule configured broadly.
 */
final class PreventApiCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
        $response->headers->set('CDN-Cache-Control', 'no-store');
        $response->headers->set('Surrogate-Control', 'no-store');
        $response->headers->set('Vary', 'Authorization, Accept-Encoding');

        return $response;
    }
}
