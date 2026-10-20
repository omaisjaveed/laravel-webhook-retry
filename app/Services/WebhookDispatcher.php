<?php

namespace App\Services;

use App\Enums\WebhookDeliveryStatus;
use App\Jobs\DeliverWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WebhookDispatcher
{
    /**
     * Queue webhook deliveries for matching active endpoints.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int>|null  $endpointIds
     * @return Collection<int, WebhookDelivery>
     */
    public function dispatch(string $eventName, array $payload, ?string $eventId = null, ?array $endpointIds = null): Collection
    {
        $eventName = trim($eventName);

        if ($eventName === '') {
            throw new InvalidArgumentException('Event name is required.');
        }

        $eventId = $eventId ?: (string) Str::uuid();

        $endpoints = $this->resolveEndpoints($eventName, $endpointIds);

        $deliveries = collect();

        foreach ($endpoints as $endpoint) {
            $delivery = $this->createDelivery($endpoint, $eventName, $eventId, $payload);

            if ($delivery === null) {
                continue;
            }

            DeliverWebhookJob::dispatch($delivery->id);
            $deliveries->push($delivery);
        }

        return $deliveries;
    }

    /**
     * Manually re-queue a permanently failed (or failed) delivery.
     */
    public function retry(WebhookDelivery $delivery): WebhookDelivery
    {
        if ($delivery->status === WebhookDeliveryStatus::Succeeded) {
            throw new InvalidArgumentException('Successful deliveries cannot be retried.');
        }

        if ($delivery->status === WebhookDeliveryStatus::Processing) {
            throw new InvalidArgumentException('Delivery is already being processed.');
        }

        $delivery->forceFill([
            'status' => WebhookDeliveryStatus::Pending,
            'next_retry_at' => null,
            'failed_at' => null,
            'max_attempts' => max(
                $delivery->max_attempts,
                $delivery->attempt_count + $delivery->endpoint->resolvedMaxAttempts()
            ),
        ])->save();

        DeliverWebhookJob::dispatch($delivery->id);

        return $delivery->fresh();
    }

    /**
     * @param  array<int>|null  $endpointIds
     * @return Collection<int, WebhookEndpoint>
     */
    protected function resolveEndpoints(string $eventName, ?array $endpointIds = null): Collection
    {
        $query = WebhookEndpoint::query()->where('is_active', true);

        if ($endpointIds !== null) {
            $query->whereIn('id', $endpointIds);
        }

        return $query->get()->filter(fn (WebhookEndpoint $endpoint) => $endpoint->listensFor($eventName))->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function createDelivery(
        WebhookEndpoint $endpoint,
        string $eventName,
        string $eventId,
        array $payload
    ): ?WebhookDelivery {
        try {
            return WebhookDelivery::create([
                'webhook_endpoint_id' => $endpoint->id,
                'event_id' => $eventId,
                'event_name' => $eventName,
                'payload' => $payload,
                'status' => WebhookDeliveryStatus::Pending,
                'attempt_count' => 0,
                'max_attempts' => $endpoint->resolvedMaxAttempts(),
            ]);
        } catch (QueryException $exception) {
            // Unique (endpoint_id, event_id) — skip duplicate dispatch.
            if ($this->isUniqueConstraintViolation($exception)) {
                return null;
            }

            throw $exception;
        }
    }

    protected function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $code = (string) ($exception->errorInfo[1] ?? $exception->getCode());

        return in_array($code, ['1062', '23000', '19'], true)
            || str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
