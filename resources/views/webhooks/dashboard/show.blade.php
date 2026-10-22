@extends('webhooks.dashboard.layout')

@section('title', 'Delivery #'.$delivery->id)

@section('content')
    <header class="appbar">
        <h1>Delivery #{{ $delivery->id }}</h1>
        <div>
            <a href="{{ route('webhooks.dashboard.index') }}">← Back</a>
            &nbsp;|&nbsp;
            <form method="post" action="{{ route('webhooks.dashboard.logout') }}" style="display:inline">
                @csrf
                <button type="submit" class="linkish">Logout</button>
            </form>
        </div>
    </header>

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    <div class="card">
        <p>
            <span class="badge {{ $delivery->status->value }}">{{ $delivery->status->label() }}</span>
            &nbsp; Attempts: {{ $delivery->attempt_count }} / {{ $delivery->max_attempts }}
        </p>
        <p><strong>Event:</strong> {{ $delivery->event_name }}</p>
        <p><strong>Event ID:</strong> <code>{{ $delivery->event_id }}</code></p>
        <p><strong>Endpoint:</strong> {{ $delivery->endpoint?->name }} — <code>{{ $delivery->endpoint?->url }}</code></p>
        <p><strong>Last HTTP:</strong> {{ $delivery->last_http_status ?? '—' }}</p>
        <p><strong>Last error:</strong> {{ $delivery->last_error ?? '—' }}</p>
        <p><strong>Next retry:</strong> {{ $delivery->next_retry_at?->toDateTimeString() ?? '—' }}</p>

        @if ($delivery->status->isRetryable())
            <form method="post" action="{{ route('webhooks.dashboard.retry', $delivery) }}" onsubmit="return confirm('Queue a manual retry for this delivery?')">
                @csrf
                <button class="btn danger" type="submit">Manual retry</button>
            </form>
        @endif
    </div>

    <div class="card">
        <h2 style="margin-top:0">Payload</h2>
        <pre>{{ json_encode($delivery->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>

    @if ($delivery->last_response_body)
        <div class="card">
            <h2 style="margin-top:0">Last response body</h2>
            <pre>{{ $delivery->last_response_body }}</pre>
        </div>
    @endif

    <div class="card">
        <h2 style="margin-top:0">Attempt log</h2>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>When</th>
                <th>HTTP</th>
                <th>Duration</th>
                <th>Result</th>
                <th>Error / response</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($delivery->attempts as $attempt)
                <tr>
                    <td>{{ $attempt->attempt_number }}</td>
                    <td>{{ $attempt->attempted_at?->toDateTimeString() }}</td>
                    <td>{{ $attempt->http_status ?? '—' }}</td>
                    <td>{{ $attempt->duration_ms !== null ? $attempt->duration_ms.' ms' : '—' }}</td>
                    <td>
                        <span class="badge {{ $attempt->successful ? 'succeeded' : 'failed' }}">
                            {{ $attempt->successful ? 'OK' : 'Failed' }}
                        </span>
                    </td>
                    <td>
                        <div>{{ $attempt->error_message ?? '—' }}</div>
                        @if ($attempt->response_body)
                            <pre style="margin-top:8px">{{ $attempt->response_body }}</pre>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No attempts recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
