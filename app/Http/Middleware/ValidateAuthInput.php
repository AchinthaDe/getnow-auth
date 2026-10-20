<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAuthInput
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        // Fortify normalizes these values before running its action validators.
        if ($request->routeIs('login.store', 'register.store')) {
            $request->validate([
                'email' => ['sometimes', 'nullable', 'string'],
                'password' => ['sometimes', 'nullable', 'string'],
            ]);
        }

        return $next($request);
    }
}
