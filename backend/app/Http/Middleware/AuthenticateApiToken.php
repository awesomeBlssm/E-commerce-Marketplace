<?php

namespace App\Http\Middleware;

use App\Models\Auth\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $session = UserSession::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $session || ! $session->user || $session->user->trashed()) {
            return response()->json(['message' => 'Invalid or expired authentication token.'], 401);
        }

        $session->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($session->user);
        $request->setUserResolver(fn () => $session->user);
        $request->attributes->set('auth_session', $session);

        return $next($request);
    }
}
