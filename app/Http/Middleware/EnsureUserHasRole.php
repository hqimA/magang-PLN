<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if(
            $user === null,
            403,
        );

        $allowed = array_filter(array_map(
            static fn (string $role): string => strtoupper(trim($role)),
            $roles,
        ));

        abort_unless(in_array(strtoupper((string) $user->peran), $allowed, true), 403);

        return $next($request);
    }
}
