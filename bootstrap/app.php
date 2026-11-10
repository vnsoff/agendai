<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Vercel terminates TLS at its edge; trust it so URLs and cookies are https.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Vercel keeps only the tail of very long log lines, which cuts the message off the top of
        // Laravel's stack trace. Log a one-line summary first so the cause is always readable.
        if (getenv('VERCEL')) {
            $exceptions->report(function (Throwable $e): void {
                error_log(sprintf('%s: %s at %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
            });
        }

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// On Vercel only /tmp is writable: keep compiled views, logs and framework caches there.
if (getenv('VERCEL')) {
    $app->useStoragePath('/tmp/storage');

    foreach (['framework/views', 'framework/cache/data', 'logs', 'app'] as $dir) {
        if (! is_dir('/tmp/storage/'.$dir)) {
            mkdir('/tmp/storage/'.$dir, 0775, true);
        }
    }
}

return $app;
