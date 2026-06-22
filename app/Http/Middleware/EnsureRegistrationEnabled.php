<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.allow_public_registration', false)) {
            return redirect()
                ->route('login')
                ->withErrors(['error' => 'Registration is disabled. Contact an administrator for access.']);
        }

        return $next($request);
    }
}
