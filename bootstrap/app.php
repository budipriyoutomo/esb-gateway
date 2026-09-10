<?php

use App\Exceptions\EsbExceptionRenderer;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Integrations\ESB\Exceptions\EsbException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Applied to every route, including the unauthenticated health check,
        // which is the most exposed surface in the application.
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // ESB failures are already normalized and already logged by EsbClient.
        // Render the safe message rather than letting an upstream message --
        // which can carry internal paths and document numbers -- reach a user.
        $exceptions->render(
            fn (EsbException $exception, Request $request) => app(EsbExceptionRenderer::class)
                ->render($exception, $request),
        );

        $exceptions->dontReport(EsbException::class);
    })->create();
