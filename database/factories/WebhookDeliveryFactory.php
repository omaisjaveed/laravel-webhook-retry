<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    public function definition(): array
    {
        return [
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'event_id' => (string) Str::uuid(),
            'event_name' => 'order.created',
            'payload' => ['order_id' => 1001, 'total' => 49.99],
            'status' => WebhookDeliveryStatus::Pending,
            'attempt_count' => 0,
            'max_attempts' => 3,
        ];
    }
}
