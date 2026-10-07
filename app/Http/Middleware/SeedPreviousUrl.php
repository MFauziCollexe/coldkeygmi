<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class SeedPreviousUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET')
            && $request->route() instanceof Route
            && ! $request->prefetch()
            && ! $request->isPrecognitive()
            && $request->hasSession()) {
            $request->session()->setPreviousUrl($request->fullUrl());
        }

        return $response;
    }
}