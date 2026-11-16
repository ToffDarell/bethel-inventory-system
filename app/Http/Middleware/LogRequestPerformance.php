<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        if (config('app.debug') || app()->environment('local', 'testing')) {
            DB::enableQueryLog();
        }

        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $queryCount = count(DB::getQueryLog());
        $memoryMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        $response->headers->set('X-Response-Time-Ms', (string) $durationMs);

        // Avoid noise for static asset requests
        $path = $request->path();
        if (! str_starts_with($path, '_') && ! str_ends_with($path, '.js') && ! str_ends_with($path, '.css')) {
            Log::info(sprintf(
                '[PERF] %s /%s took %.1fms (%d queries, %.2fMB)',
                $request->method(),
                ltrim($path, '/'),
                $durationMs,
                $queryCount,
                $memoryMb
            ));
        }

        return $response;
    }
}
