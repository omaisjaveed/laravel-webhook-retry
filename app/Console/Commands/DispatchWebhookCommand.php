<?php

namespace App\Console\Commands;

use App\Services\WebhookDispatcher;
use Illuminate\Console\Command;

class DispatchWebhookCommand extends Command
{
    protected $signature = 'webhook:dispatch
        {event : Event name, e.g. order.created}
        {payload? : JSON payload string}
        {--event-id= : Optional stable event ID for idempotency}
        {--endpoint=* : Limit to specific endpoint IDs}';

    protected $description = 'Dispatch a webhook event to matching endpoints';

    public function handle(WebhookDispatcher $dispatcher): int
    {
        $payloadRaw = $this->argument('payload') ?? '{}';
        $payload = json_decode($payloadRaw, true);

        if (! is_array($payload)) {
            $this->error('Payload must be valid JSON object/array.');

            return self::FAILURE;
        }

        $endpointIds = $this->option('endpoint');
        $endpointIds = $endpointIds === [] ? null : array_map('intval', $endpointIds);

        $deliveries = $dispatcher->dispatch(
            $this->argument('event'),
            $payload,
            $this->option('event-id'),
            $endpointIds
        );

        if ($deliveries->isEmpty()) {
            $this->warn('No deliveries were queued (no matching endpoints, or duplicate event IDs).');

            return self::SUCCESS;
        }

        $this->info('Queued '.$deliveries->count().' delivery(ies):');

        foreach ($deliveries as $delivery) {
            $this->line(sprintf(
                ' - #%d event_id=%s endpoint_id=%d',
                $delivery->id,
                $delivery->event_id,
                $delivery->webhook_endpoint_id
            ));
        }

        $this->comment('Run a queue worker: php artisan queue:work database');

        return self::SUCCESS;
    }
}
