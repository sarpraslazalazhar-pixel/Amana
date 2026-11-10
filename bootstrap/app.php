<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $maxPost = ini_get('post_max_size') ?: 'server';
            $message = "Ukuran file atau data yang dikirim melebihi batas maksimal server ({$maxPost}). Perkecil ukuran berkas dan coba lagi.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            return back()->with('error', $message);
        });
    })->create();
