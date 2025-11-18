<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class CheckTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();

            if ($token) {
                $expiryTime = 60; // 30 menit dalam detik
                $tokenAge = now()->diffInSeconds($token->last_used_at ?? $token->created_at);

                if ($tokenAge > $expiryTime) {

                    $token->delete();

                    return response()->json([
                        'success' => false,
                        'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                        'error' => 'TOKEN_EXPIRED'
                    ], 401);
                }

                // Update last_used_at untuk reset timer
                $token->forceFill([
                    'last_used_at' => now(),
                ])->save();
            }
        }

        return $next($request);
    }
}