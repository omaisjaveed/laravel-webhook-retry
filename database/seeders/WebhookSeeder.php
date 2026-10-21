<?php

namespace Database\Seeders;

use App\Models\WebhookEndpoint;
use Illuminate\Database\Seeder;

class WebhookSeeder extends Seeder
{
    public function run(): void
    {
        WebhookEndpoint::query()->firstOrCreate(
            ['name' => 'Demo Warehouse API'],
            [
                'url' => 'https://httpbin.org/post',
                'secret' => 'demo-secret-change-me',
                'auth_headers' => [
                    'Authorization' => 'Bearer demo-token',
                    'X-Warehouse-Client' => 'laravel-webhook-retry',
                ],
                'subscribed_events' => ['order.created', 'inventory.updated'],
                'is_active' => true,
                'timeout' => 30,
                'max_attempts' => 5,
                'retry_intervals' => [60, 300, 900, 3600],
            ]
        );
    }
}
