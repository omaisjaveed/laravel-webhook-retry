<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookDashboardAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('webhook_dashboard_authenticated') === true) {
            return $next($request);
        }

        return redirect()->guest(route('webhooks.dashboard.login'));
    }
}
