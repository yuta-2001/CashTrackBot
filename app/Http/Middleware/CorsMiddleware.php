<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedUrls = [
            config('line.liff_urls.lending_and_borrowing'),
            config('line.liff_urls.opponent'),
            config('line.frontend_domain'),
        ];
        $origin = $request->headers->get('Origin');

        if (in_array($origin, $allowedUrls)) {
            return $next($request)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type');
        }

        return response('Unauthorized', 401);
    }
}
