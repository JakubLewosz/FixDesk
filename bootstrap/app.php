<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php')
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (DomainException $exception, Request $request) {
            if ($ticket = $request->route('ticket')) {
                return redirect()->route('tickets.show', $ticket)->with('error', $exception->getMessage());
            }
        });
    })->create();
