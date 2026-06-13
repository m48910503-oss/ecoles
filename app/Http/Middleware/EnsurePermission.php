<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED);
        }

        $requiredPermissions = collect(explode('|', $permissions))
            ->map(fn (string $permission) => trim($permission))
            ->filter()
            ->values()
            ->all();

        if (! $user->hasAnyPermission($requiredPermissions)) {
            abort(Response::HTTP_FORBIDDEN, "Vous n'avez pas l'autorisation d'effectuer cette action.");
        }

        return $next($request);
    }
}
