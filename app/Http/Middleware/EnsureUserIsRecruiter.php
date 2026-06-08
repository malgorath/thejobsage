<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsRecruiter
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || (! auth()->user()->isRecruiter() && ! auth()->user()->isAdmin())) {
            abort(403, 'Unauthorized. Recruiter access required.');
        }

        return $next($request);
    }
}
