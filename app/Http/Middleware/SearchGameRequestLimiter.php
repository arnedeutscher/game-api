<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\RateLimiter;

class SearchGameRequestLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
		$executed = RateLimiter::attempt(
			'ip:' . $request->ip(),
			$perMinute = 5,
			function() {}
		);
		 
		if (!$executed) { return response()->json(['error' => 'Too many requests!'], 403); } // Forbidden

        return $next($request);
    }
}
