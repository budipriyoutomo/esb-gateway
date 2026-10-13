<?php

use App\Exceptions\EsbExceptionRenderer;
use App\Http\Middleware\AuthenticateApiClient;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Integrations\ESB\Exceptions\EsbException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'api-client' => AuthenticateApiClient::class,
        ]);

        // The sales-push-api limiter keys on the client this middleware
        // identifies, so it must run before throttling.
        $middleware->prependToPriorityList(
            before: ThrottleRequests::class,
            prepend: AuthenticateApiClient::class,
        );

        // /api bodies are forwarded to the ESB unchanged (ADR-022). The ESB
        // requires some attributes to be present as "" and treats a null as
        // missing, so neither trimming nor ""-to-null may touch them.
        $isApi = fn (Request $request): bool => $request->is('api/*');
        $middleware->convertEmptyStringsToNull(except: [$isApi]);
        $middleware->trimStrings(except: [$isApi]);

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
