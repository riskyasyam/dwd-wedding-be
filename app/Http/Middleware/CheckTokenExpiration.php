<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the token from Authorization header
        $token = $request->bearerToken();
        
        if ($token) {
            // Find the token in database
            $accessToken = PersonalAccessToken::findToken($token);
            
            if ($accessToken) {
                // Check if token has expired (10 minutes from last_used_at)
                $expirationMinutes = config('sanctum.expiration', 10);
                $lastUsed = $accessToken->last_used_at ?? $accessToken->created_at;
                
                if ($lastUsed && $lastUsed->addMinutes($expirationMinutes)->isPast()) {
                    // Token has expired, delete it
                    $accessToken->delete();
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Token has expired. Please login again.',
                        'error' => 'token_expired'
                    ], 401);
                }
            }
        }
        
        return $next($request);
    }
}
