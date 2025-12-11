<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $minutes = 5): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Skip caching for authenticated user-specific data
        if ($request->is('api/customer/*') || $request->is('api/admin/*')) {
            return $next($request);
        }

        // Generate cache key based on URL and query parameters
        $cacheKey = 'api_response_' . md5($request->fullUrl());

        // Try to get from cache
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse !== null) {
            return response()->json($cachedResponse)->header('X-Cache', 'HIT');
        }

        // Process request
        $response = $next($request);

        // Cache successful JSON responses only
        if ($response->status() === 200 && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
            Cache::put($cacheKey, $content, now()->addMinutes($minutes));
            $response->header('X-Cache', 'MISS');
        }

        return $response;
    }
}
