<?php

namespace Application\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKeyIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $validKey = env('API_KEY', 'bombeiros-api-key-2026');

        if (!$apiKey || $apiKey !== $validKey) {
            return response()->json(['error' => 'Unauthorized. Invalid API Key.'], 401);
        }

        return $next($request);
    }
}
