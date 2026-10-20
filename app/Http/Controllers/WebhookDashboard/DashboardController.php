<?php

namespace App\Http\Controllers\WebhookDashboard;

use App\Enums\WebhookDeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $event = $request->string('event')->toString();

        $query = WebhookDelivery::query()
            ->with('endpoint')
            ->latest('id');

        if ($status !== '' && WebhookDeliveryStatus::tryFrom($status)) {
            $query->where('status', $status);
        }

        if ($event !== '') {
            $query->where('event_name', 'like', '%'.$event.'%');
        }

        $deliveries = $query->paginate(20)->withQueryString();

        $stats = [
            'endpoints' => WebhookEndpoint::count(),
            'total' => WebhookDelivery::count(),
            'pending' => WebhookDelivery::whereIn('status', [
                WebhookDeliveryStatus::Pending,
                WebhookDeliveryStatus::Processing,
                WebhookDeliveryStatus::Failed,
            ])->count(),
            'succeeded' => WebhookDelivery::where('status', WebhookDeliveryStatus::Succeeded)->count(),
            'permanently_failed' => WebhookDelivery::where('status', WebhookDeliveryStatus::PermanentlyFailed)->count(),
        ];

        return view('webhooks.dashboard.index', compact('deliveries', 'stats', 'status', 'event'));
    }

    public function show(WebhookDelivery $delivery): View
    {
        $delivery->load(['endpoint', 'attempts']);

        return view('webhooks.dashboard.show', compact('delivery'));
    }

    public function retry(WebhookDelivery $delivery, WebhookDispatcher $dispatcher): RedirectResponse
    {
        try {
            $dispatcher->retry($delivery);
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('webhooks.dashboard.show', $delivery)
            ->with('success', 'Delivery queued for manual retry.');
    }
}
