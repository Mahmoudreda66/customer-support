<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectHomeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('filament.admin.pages.dashboard') && ($role = auth()->user()->role) != 'manager') {

            if ($role === 'customer_support') {
                return to_route('filament.admin.resources.complains.index');
            } elseif ($role === 'maintenance') {
                return to_route('filament.admin.resources.orders.index');
            } elseif ($role === 'data_entry') {
                return to_route('filament.admin.resources.orders.create');
            }
        }

        return $next($request);
    }
}
