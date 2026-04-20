<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AdminTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?: $request->query('token');
        if (!$token) {
            abort(403, 'Neturite teisių.');
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            abort(403, 'Neturite teisių.');
        }

        $user = $accessToken->tokenable;
        $request->setUserResolver(fn () => $user);

        if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole('administratorius')) {
            abort(403, 'Neturite teisių.');
        }

        return $next($request);
    }
}

