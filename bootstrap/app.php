<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware alias

        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // Alias untuk custom middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        
        // Jika perlu encrypt cookies (optional)
        // $middleware->encryptCookies(except: []);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle 404 Not Found untuk API
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'path' => $request->path()
                ], 404);
            }
        });

        // Handle 405 Method Not Allowed untuk API
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed',
                    'allowed_methods' => $e->getHeaders()['Allow'] ?? 'Unknown'
                ], 405);
            }
        });

        // Handle 401 Unauthenticated untuk API
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login first.'
                ], 401);
            }
        });

        // Handle 422 Validation Error untuk API
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handle 500 Internal Server Error untuk API
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') && !app()->environment('local')) {
                // Untuk production, jangan expose error detail
                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error'
                ], 500);
            }
        });

        // Untuk development, show detailed error
        if (app()->environment('local')) {
            $exceptions->shouldRenderJsonWhen(function (Request $request) {
                return $request->is('api/*');
            });
        }
    })
    ->create();
