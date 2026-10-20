<?php

namespace App\Facades;

use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection dispatch(string $eventName, array $payload, ?string $eventId = null, ?array $endpointIds = null)
 * @method static \App\Models\WebhookDelivery retry(\App\Models\WebhookDelivery $delivery)
 *
 * @see \App\Services\WebhookDispatcher
 */
class Webhook extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WebhookDispatcher::class;
    }
}
