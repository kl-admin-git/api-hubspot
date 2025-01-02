<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class VerifyApiTokenHubSpot
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization');
        $bearer = ENV("HUBSPOT_BR");

        if (!$token || !str_starts_with($token, 'Bearer '))
            return response()->json(['message' => 'Unauthorized'], 401);

        $token = substr($token, 7);

        if ($token != $bearer)
            return response()->json(['message' => 'Invalid token'], 401);

        return $next($request);
    }
}
