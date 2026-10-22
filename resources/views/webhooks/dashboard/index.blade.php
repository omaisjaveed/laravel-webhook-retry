@extends('webhooks.dashboard.layout')

@section('title', 'Deliveries')

@section('content')
    <header class="appbar">
        <h1>Webhook Deliveries</h1>
        <form method="post" action="{{ route('webhooks.dashboard.logout') }}">
            @csrf
            <button type="submit" class="linkish">Logout</button>
        </form>
    </header>

    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    <div class="card stats">
        <div class="stat"><div class="label">Endpoints</div><div class="value">{{ $stats['endpoints'] }}</div></div>
        <div class="stat"><div class="label">Total deliveries</div><div class="value">{{ $stats['total'] }}</div></div>
        <div class="stat"><div class="label">In progress / retrying</div><div class="value">{{ $stats['pending'] }}</div></div>
        <div class="stat"><div class="label">Succeeded</div><div class="value">{{ $stats['succeeded'] }}</div></div>
        <div class="stat"><div class="label">Permanently failed</div><div class="value">{{ $stats['permanently_failed'] }}</div></div>
    </div>

    <div class="card">
        <form method="get" class="filters">
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    @foreach (\App\Enums\WebhookDeliveryStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="event">Event name</label>
                <input id="event" type="text" name="event" value="{{ $event }}" placeholder="order.created">
            </div>
            <div>
                <button class="btn" type="submit">Filter</button>
                <a class="btn secondary" href="{{ route('webhooks.dashboard.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Event</th>
                <th>Endpoint</th>
                <th>Status</th>
                <th>Attempts</th>
                <th>HTTP</th>
                <th>Updated</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($deliveries as $delivery)
                <tr>
                    <td>#{{ $delivery->id }}</td>
                    <td>
                        <div>{{ $delivery->event_name }}</div>
                        <div class="muted" style="font-size:0.8rem">{{ $delivery->event_id }}</div>
                    </td>
                    <td>{{ $delivery->endpoint?->name ?? '—' }}</td>
                    <td><span class="badge {{ $delivery->status->value }}">{{ $delivery->status->label() }}</span></td>
                    <td>{{ $delivery->attempt_count }} / {{ $delivery->max_attempts }}</td>
                    <td>{{ $delivery->last_http_status ?? '—' }}</td>
                    <td>{{ $delivery->updated_at?->format('Y-m-d H:i') }}</td>
                    <td><a href="{{ route('webhooks.dashboard.show', $delivery) }}">Details</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No deliveries found.</td></tr>
            @endforelse
            </tbody>
        </table>

        @if ($deliveries->hasPages())
            <div class="pagination" style="display:flex;gap:12px;align-items:center;margin-top:16px">
                @if ($deliveries->onFirstPage())
                    <span class="muted">Previous</span>
                @else
                    <a href="{{ $deliveries->previousPageUrl() }}">Previous</a>
                @endif
                <span class="muted">Page {{ $deliveries->currentPage() }} of {{ $deliveries->lastPage() }}</span>
                @if ($deliveries->hasMorePages())
                    <a href="{{ $deliveries->nextPageUrl() }}">Next</a>
                @else
                    <span class="muted">Next</span>
                @endif
            </div>
        @endif
    </div>
@endsection
