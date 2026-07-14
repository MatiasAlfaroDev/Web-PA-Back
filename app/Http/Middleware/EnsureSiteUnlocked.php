<?php

namespace App\Http\Middleware;

use App\Models\SiteLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Defense in depth for the 423 case — the student layout should already keep
// students off these routes via a proactive GET /site-lock check. Teachers
// bypass this so they can always manage the lock (and their own courses).
class EnsureSiteUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isTeacher()) {
            return $next($request);
        }

        $until = SiteLock::activeUntil();
        if ($until) {
            return response()->json([
                'message' => 'El sitio está bloqueado temporalmente.',
                'locked_until' => $until->toJSON(),
            ], 423);
        }

        return $next($request);
    }
}
