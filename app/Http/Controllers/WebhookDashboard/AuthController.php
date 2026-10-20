<?php

namespace App\Http\Controllers\WebhookDashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('webhooks.dashboard.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $expectedUser = (string) config('webhooks.dashboard.username');
        $expectedPass = (string) config('webhooks.dashboard.password');

        if (
            ! hash_equals($expectedUser, $credentials['username'])
            || ! hash_equals($expectedPass, $credentials['password'])
        ) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Invalid dashboard credentials.']);
        }

        $request->session()->regenerate();
        $request->session()->put('webhook_dashboard_authenticated', true);

        return redirect()->intended(route('webhooks.dashboard.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('webhook_dashboard_authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('webhooks.dashboard.login');
    }
}
