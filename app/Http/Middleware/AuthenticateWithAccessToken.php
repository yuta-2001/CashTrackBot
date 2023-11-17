<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Trait\LineLoginApiAuthTrait;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithAccessToken
{
    use LineLoginApiAuthTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = $request->query('accesstoken');

        $lineUserId = $this->getLineUserIdFromAccessToken($accessToken);
        $user = User::where('line_user_id', $lineUserId)->first();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->attributes->add(['user' => $user]);

        return $next($request);
    }
}
