<?php

namespace Tests\Feature;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('webhooks.dashboard.index'))
            ->assertRedirect(route('webhooks.dashboard.login'));
    }

    public function test_dashboard_login_and_manual_retry(): void
    {
        Queue::fake();

        config([
            'webhooks.dashboard.username' => 'admin',
            'webhooks.dashboard.password' => 'secret',
        ]);

        $endpoint = WebhookEndpoint::factory()->create();
        $delivery = WebhookDelivery::factory()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'status' => WebhookDeliveryStatus::PermanentlyFailed,
            'attempt_count' => 3,
            'max_attempts' => 3,
        ]);

        $this->post(route('webhooks.dashboard.login.submit'), [
            'username' => 'admin',
            'password' => 'secret',
        ])->assertRedirect(route('webhooks.dashboard.index'));

        $this->get(route('webhooks.dashboard.index'))->assertOk()->assertSee('Webhook Deliveries');

        $this->get(route('webhooks.dashboard.show', $delivery))
            ->assertOk()
            ->assertSee($delivery->event_id);

        $this->post(route('webhooks.dashboard.retry', $delivery))
            ->assertRedirect(route('webhooks.dashboard.show', $delivery));

        $this->assertSame(WebhookDeliveryStatus::Pending, $delivery->fresh()->status);
        Queue::assertPushed(\App\Jobs\DeliverWebhookJob::class);
    }
}
