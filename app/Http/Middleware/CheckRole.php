<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $role = $user->role === \App\Models\User::ROLE_ADMIN
            && $user->headOfSalesForVehiclePermissions()
            ? \App\Models\User::ROLE_HEAD_OF_SALES : $user->role;

        if (empty($roles) || in_array($role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
