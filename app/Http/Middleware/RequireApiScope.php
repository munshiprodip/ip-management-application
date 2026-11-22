<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiScope
{
    /**
     * Verifies that the authenticated API token possesses the required scope.
     */
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        /** @var ApiToken|null $token */
        $token = $request->attributes->get('api_token');

        if ($token === null) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'API token required.',
                ],
            ], 401);
        }

        if (! $token->hasScope($scope)) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN_SCOPE',
                    'message' => "Insufficient permissions: Token lacks the required '{$scope}' scope.",
                    'required_scope' => $scope,
                    'granted_scopes' => $token->scopes_json,
                ],
            ], 403);
        }

        return $next($request);
    }
}
