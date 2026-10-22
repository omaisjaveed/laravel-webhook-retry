@extends('webhooks.dashboard.layout')

@section('title', 'Login')

@section('content')
    <div class="card login-box">
        <h1>Webhook Dashboard</h1>
        <p class="muted">Sign in with the credentials from your <code>.env</code> file.</p>

        @if ($errors->any())
            <div class="flash error">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('webhooks.dashboard.login.submit') }}" class="stack">
            @csrf
            <div>
                <label for="username">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus style="width:100%">
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required style="width:100%">
            </div>
            <button class="btn" type="submit">Sign in</button>
        </form>
    </div>
@endsection
