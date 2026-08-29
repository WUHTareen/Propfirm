<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows only back-office staff (admin / support / finance) through.
 * Traders hitting a staff route are sent to their own dashboard.
 */
class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStaff()) {
            abort(403, 'This area is for staff only.');
        }

        return $next($request);
    }
}
