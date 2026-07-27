<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureFullAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || Auth::user()->permission_level < 9) {
            abort(403, 'Brak uprawnień. Sekcja dostępna tylko dla administratorów.');
        }

        return $next($request);
    }
}
