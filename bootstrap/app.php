<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('app:database-backup')
            ->dailyAt('23:01')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\LogModuleAction::class,
            \App\Http\Middleware\SeedPreviousUrl::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\NoStoreHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if (! $request->header('X-Inertia')
                || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return null;
            }

            return redirect()->back()
                ->withErrors(['request' => 'Sesi request kadaluarsa. Refresh halaman lalu coba lagi.'])
                ->setStatusCode(303);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->header('X-Inertia')
                || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
                || ! in_array($exception->getStatusCode(), [403, 404], true)) {
                return null;
            }

            $message = $exception->getStatusCode() === 403
                ? 'Anda tidak memiliki izin untuk melakukan aksi ini.'
                : 'Data yang diminta tidak ditemukan.';

            return redirect()->back()
                ->withErrors(['request' => $message])
                ->setStatusCode(303);
        });
    })->create();
