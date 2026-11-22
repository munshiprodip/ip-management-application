<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request with Bearer token authentication and per-token rate limiting.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Missing or invalid Authorization header. Expected: Bearer <token>',
                ],
            ], 401);
        }

        $plainToken = trim(substr($header, 7));

        if ($plainToken === '') {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Empty Bearer token provided.',
                ],
            ], 401);
        }

        $token = ApiToken::findValidToken($plainToken);

        if ($token === null) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'The provided API token is invalid, expired, or has been revoked.',
                ],
            ], 401);
        }

        // Per-token Rate Limiting
        $rateLimitKey = 'api_token:' . $token->id;
        $maxAttempts = $token->rate_limit_per_minute;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => "API rate limit of {$maxAttempts} requests/min exceeded. Please retry in {$retryAfter} seconds.",
                    'retry_after' => $retryAfter,
                ],
            ], 429, [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60);

        // Record usage metadata
        $token->recordUsage($request->ip() ?? '127.0.0.1');

        // Attach token and authenticate user in request context
        $request->attributes->set('api_token', $token);

        if ($token->user !== null) {
            Auth::setUser($token->user);
        }

        return $next($request);
    }
}
