<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class LogRequest
{
    /**
     * Write one request_logs row per web/api hit so the admin can audit which
     * URLs were accessed, by whom, and from where. Static assets, the health
     * check and CORS preflight are skipped to keep the table meaningful.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);

        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldLog($request, $response)) {
            return $response;
        }

        try {
            RequestLog::query()->create([
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'method' => $request->method(),
                'url' => Str::limit($request->fullUrl(), 480),
                'status_code' => $response->getStatusCode(),
                'user_agent' => Str::limit((string) $request->userAgent(), 480),
                'duration_ms' => (int) round((hrtime(true) - $start) / 1e6),
            ]);
        } catch (\Throwable) {
            // Logging must never break a request (e.g. DB hiccup).
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if ($request->isMethod('OPTIONS')) {
            return false;
        }

        if ($request->is('up')) {
            return false;
        }

        if (preg_match('~\.[a-z0-9]{2,6}$~i', (string) $request->path())) {
            return false;
        }

        return true;
    }
}
