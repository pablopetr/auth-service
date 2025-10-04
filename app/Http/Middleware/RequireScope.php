<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireScope
{
    public function handle(Request $request, Closure $next, ...$required)
    {
        $claims = $request->attributes->get('jwt_claims', []);

        $raw = $claims['scope']
            ?? $claims['scopes']
            ?? $claims['scp']
            ?? $claims['roles']
            ?? [];

        if (is_string($raw)) {
            $raw = preg_split('/[\s,]+/', trim($raw)) ?: [];
        } elseif (! is_array($raw)) {
            $raw = [];
        }

        $granted = collect($raw)->map(fn ($s) => strtolower((string) $s));

        foreach ($required as $need) {
            if (! $granted->contains(strtolower($need))) {

                return response()->json([
                    'message' => 'insufficient_scope',
                    'required' => array_values($required),
                ], 403);
            }
        }

        return $next($request);
    }
}
