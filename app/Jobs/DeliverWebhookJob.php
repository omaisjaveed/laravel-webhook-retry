<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Services\WebhookDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(WebhookDeliveryService $deliveryService): void
    {
        $delivery = WebhookDelivery::with('endpoint')->find($this->deliveryId);

        if ($delivery === null || $delivery->endpoint === null) {
            return;
        }

        if ($delivery->status === WebhookDeliveryStatus::Succeeded) {
            return;
        }

        if (! $delivery->endpoint->is_active) {
            $delivery->markPermanentlyFailed(
                $delivery->last_http_status,
                $delivery->last_response_body,
                'Endpoint is inactive.'
            );

            return;
        }

        if (! $delivery->hasAttemptsRemaining() && $delivery->status !== WebhookDeliveryStatus::Pending) {
            $delivery->markPermanentlyFailed(
                $delivery->last_http_status,
                $delivery->last_response_body,
                $delivery->last_error ?? 'Maximum attempts reached.'
            );

            return;
        }

        $result = $deliveryService->attempt($delivery->fresh(['endpoint']));
        $delivery->refresh();

        if ($result['successful']) {
            $delivery->markSucceeded($result['http_status'], $result['response_body']);

            return;
        }

        if ($delivery->hasAttemptsRemaining()) {
            $nextAttempt = $delivery->attempt_count + 1;
            $delay = $delivery->endpoint->delayForAttempt($nextAttempt);

            $delivery->markFailedForRetry(
                $result['http_status'],
                $result['response_body'],
                $result['error'],
                $delay
            );

            self::dispatch($delivery->id)->delay(now()->addSeconds($delay));

            return;
        }

        $delivery->markPermanentlyFailed(
            $result['http_status'],
            $result['response_body'],
            $result['error'] ?? 'Maximum attempts reached.'
        );
    }
}
