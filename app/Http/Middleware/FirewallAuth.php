<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirewallAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('firewall_authenticated') === true) {
            return $next($request);
        }

        return redirect()->route('firewall.login.form');
    }
}
