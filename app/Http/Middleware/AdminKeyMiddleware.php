<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Admin-Key') ?? $request->query('admin_key');

        if ($key !== config('app.admin_key')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
