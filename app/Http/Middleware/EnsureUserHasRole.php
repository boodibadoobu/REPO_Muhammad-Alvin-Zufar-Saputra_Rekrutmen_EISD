<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(
            $request->user() && $request->user()->hasAnyRole(...$roles),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
