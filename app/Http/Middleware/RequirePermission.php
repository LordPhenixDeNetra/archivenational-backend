<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $code)
    {
        $user = $request->user();
        if (!$user || !$user->hasPermission($code)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}

