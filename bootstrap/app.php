<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //

        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'zinute' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'zinute' => 'Neprisijungęs',
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'zinute' => 'Neturite teisių.',
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'zinute' => 'Nerasta',
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'zinute' => 'Nerasta',
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            $status = $e->getStatusCode();
            $msg = $e->getMessage() ?: 'Klaida';

            return response()->json([
                'zinute' => $msg,
            ], $status);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'zinute' => 'Serverio klaida',
            ], 500);
        });
    })->create();
