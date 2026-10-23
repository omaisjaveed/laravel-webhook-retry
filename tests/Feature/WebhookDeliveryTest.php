<?php

namespace Tests\Feature;

use App\Enums\WebhookDeliveryStatus;
use App\Jobs\DeliverWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_delivery_is_recorded(): void
    {
        Http::fake([
            'https://receiver.test/*' => Http::response(['ok' => true], 200),
        ]);

        $endpoint = WebhookEndpoint::factory()->create([
            'url' => 'https://receiver.test/hooks',
            'max_attempts' => 3,
            'retry_intervals' => [1, 2],
        ]);

        $delivery = WebhookDelivery::factory()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_name' => 'order.created',
            'payload' => ['order_id' => 55],
            'max_attempts' => 3,
        ]);

        (new DeliverWebhookJob($delivery->id))->handle(app(\App\Services\WebhookDeliveryService::class));

        $delivery->refresh();

        $this->assertSame(WebhookDeliveryStatus::Succeeded, $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
        $this->assertSame(200, $delivery->last_http_status);
        $this->assertDatabaseCount('webhook_attempts', 1);
        $this->assertDatabaseHas('webhook_attempts', [
            'webhook_delivery_id' => $delivery->id,
            'successful' => 1,
            'http_status' => 200,
        ]);

        Http::assertSent(function ($request) use ($delivery) {
            return $request->url() === 'https://receiver.test/hooks'
                && $request['order_id'] === 55
                && $request->hasHeader(config('webhooks.event_id_header'), $delivery->event_id)
                && $request->hasHeader(config('webhooks.event_name_header'), 'order.created');
        });
    }

    public function test_failed_delivery_is_retried_with_delay(): void
    {
        Queue::fake();

        Http::fake([
            'https://receiver.test/*' => Http::response('nope', 500),
        ]);

        $endpoint = WebhookEndpoint::factory()->create([
            'url' => 'https://receiver.test/hooks',
            'max_attempts' => 3,
            'retry_intervals' => [10, 20],
        ]);

        $delivery = WebhookDelivery::factory()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'max_attempts' => 3,
        ]);

        (new DeliverWebhookJob($delivery->id))->handle(app(\App\Services\WebhookDeliveryService::class));

        $delivery->refresh();

        $this->assertSame(WebhookDeliveryStatus::Failed, $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
        $this->assertNotNull($delivery->next_retry_at);

        Queue::assertPushed(DeliverWebhookJob::class, function (DeliverWebhookJob $job) use ($delivery) {
            return $job->deliveryId === $delivery->id && $job->delay !== null;
        });
    }

    public function test_maximum_attempts_marks_permanently_failed(): void
    {
        Queue::fake();

        Http::fake([
            'https://receiver.test/*' => Http::response('down', 503),
        ]);

        $endpoint = WebhookEndpoint::factory()->create([
            'url' => 'https://receiver.test/hooks',
            'max_attempts' => 2,
            'retry_intervals' => [1],
        ]);

        $delivery = WebhookDelivery::factory()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'max_attempts' => 2,
            'attempt_count' => 1,
            'status' => WebhookDeliveryStatus::Failed,
        ]);

        (new DeliverWebhookJob($delivery->id))->handle(app(\App\Services\WebhookDeliveryService::class));

        $delivery->refresh();

        $this->assertSame(WebhookDeliveryStatus::PermanentlyFailed, $delivery->status);
        $this->assertSame(2, $delivery->attempt_count);
        $this->assertNotNull($delivery->failed_at);

        Queue::assertNotPushed(DeliverWebhookJob::class);
    }

    public function test_manual_retry_requeues_permanently_failed_delivery(): void
    {
        Queue::fake();

        $endpoint = WebhookEndpoint::factory()->create([
            'max_attempts' => 3,
        ]);

        $delivery = WebhookDelivery::factory()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'status' => WebhookDeliveryStatus::PermanentlyFailed,
            'attempt_count' => 3,
            'max_attempts' => 3,
            'failed_at' => now(),
        ]);

        $retried = app(WebhookDispatcher::class)->retry($delivery);

        $this->assertSame(WebhookDeliveryStatus::Pending, $retried->status);
        $this->assertNull($retried->failed_at);
        $this->assertGreaterThan(3, $retried->max_attempts);

        Queue::assertPushed(DeliverWebhookJob::class, function (DeliverWebhookJob $job) use ($delivery) {
            return $job->deliveryId === $delivery->id;
        });
    }

    public function test_duplicate_event_id_for_same_endpoint_is_ignored(): void
    {
        Queue::fake();

        $endpoint = WebhookEndpoint::factory()->create();
        $eventId = 'evt-fixed-123';

        $dispatcher = app(WebhookDispatcher::class);

        $first = $dispatcher->dispatch('order.created', ['a' => 1], $eventId, [$endpoint->id]);
        $second = $dispatcher->dispatch('order.created', ['a' => 2], $eventId, [$endpoint->id]);

        $this->assertCount(1, $first);
        $this->assertCount(0, $second);
        $this->assertDatabaseCount('webhook_deliveries', 1);
    }

    public function test_dispatcher_queues_job_for_matching_endpoint(): void
    {
        Queue::fake();

        $endpoint = WebhookEndpoint::factory()->create([
            'subscribed_events' => ['inventory.updated'],
        ]);

        WebhookEndpoint::factory()->create([
            'subscribed_events' => ['order.created'],
            'is_active' => true,
        ]);

        $deliveries = app(WebhookDispatcher::class)->dispatch(
            'inventory.updated',
            ['sku' => 'ABC'],
            null,
            null
        );

        $this->assertCount(1, $deliveries);
        $this->assertSame($endpoint->id, $deliveries->first()->webhook_endpoint_id);

        Queue::assertPushed(DeliverWebhookJob::class);
    }
}
