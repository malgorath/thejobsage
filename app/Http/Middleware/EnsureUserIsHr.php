<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsHr
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || (! auth()->user()->isHr() && ! auth()->user()->isAdmin())) {
            abort(403, 'Unauthorized. HR access required.');
        }

        return $next($request);
    }
}
