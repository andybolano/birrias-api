<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class ConditionalSanctumMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Skip Sanctum middleware for documentation routes
        $documentationRoutes = [
            'documentation*',
            'docs*', 
            'api/oauth2-callback',
            'api/documentation*',
            'api/docs*'
        ];

        $currentPath = $request->path();
        \Log::info('ConditionalSanctumMiddleware - Processing route: ' . $currentPath);

        foreach ($documentationRoutes as $pattern) {
            if ($request->is($pattern)) {
                \Log::info('ConditionalSanctumMiddleware - Skipping Sanctum for route: ' . $currentPath . ' (matched pattern: ' . $pattern . ')');
                return $next($request);
            }
        }

        \Log::info('ConditionalSanctumMiddleware - Applying Sanctum for route: ' . $currentPath);
        // Apply Sanctum middleware for all other routes
        $sanctumMiddleware = new EnsureFrontendRequestsAreStateful;
        return $sanctumMiddleware->handle($request, $next);
    }
} 