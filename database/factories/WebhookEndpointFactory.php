<?php

namespace Database\Factories;

use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    protected $model = WebhookEndpoint::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Webhook',
            'url' => 'https://example.com/webhooks/receive',
            'secret' => 'test-secret',
            'auth_headers' => [
                'Authorization' => 'Bearer test-token',
            ],
            'subscribed_events' => null,
            'is_active' => true,
            'timeout' => 5,
            'max_attempts' => 3,
            'retry_intervals' => [1, 2],
        ];
    }
}
