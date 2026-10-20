<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [ConfigureClientSession::class]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->routeIs('vendor.*')) {
                return route('vendor.login', [
                    'subdomain' => $request->route('subdomain')
                        ?? Str::before($request->getHost(), '.'.config('app.domain')),
                ]);
            }

            return route('client.login');
        });

        $middleware->redirectUsersTo(function (Request $request): string {
            if ($request->routeIs('vendor.*')) {
                $subdomain = $request->route('subdomain')
                    ?? Str::before($request->getHost(), '.'.config('app.domain'));

                return route('vendor.admin.dashboard', ['subdomain' => $subdomain]);
            }

            if ($request->routeIs('client.*')) {
                return route('client.dashboard');
            }

            $client = $request->user('client');
            if ($client !== null) {
                return route('client.dashboard');
            }

            $vendor = $request->user('vendor')?->vendor;

            return $vendor
                ? route('vendor.admin.dashboard', ['subdomain' => $vendor->slug])
                : route('landing_page');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
