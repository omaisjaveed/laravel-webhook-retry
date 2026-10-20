<?php

namespace App\Services;

use App\Models\WebhookAttempt;
use App\Models\WebhookDelivery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class WebhookDeliveryService
{
    /**
     * Perform a single HTTP delivery attempt and persist the attempt log.
     *
     * @return array{successful: bool, http_status: ?int, response_body: ?string, error: ?string}
     */
    public function attempt(WebhookDelivery $delivery): array
    {
        $delivery->loadMissing('endpoint');
        $endpoint = $delivery->endpoint;

        $attemptNumber = $delivery->attempt_count + 1;
        $started = hrtime(true);

        $delivery->forceFill(['attempt_count' => $attemptNumber])->save();
        $delivery->markProcessing();

        $headers = $this->buildHeaders($delivery);
        $timeout = $endpoint->resolvedTimeout();
        $bodyLimit = (int) config('webhooks.response_body_limit', 5000);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($endpoint->url, $delivery->payload);

            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
            $responseBody = $this->truncate((string) $response->body(), $bodyLimit);
            $successful = $this->isSuccessful($response);

            $this->logAttempt(
                $delivery,
                $attemptNumber,
                $response->status(),
                $responseBody,
                $successful ? null : 'Receiver returned a non-success HTTP status.',
                $durationMs,
                $successful
            );

            return [
                'successful' => $successful,
                'http_status' => $response->status(),
                'response_body' => $responseBody,
                'error' => $successful ? null : 'Receiver returned HTTP '.$response->status(),
            ];
        } catch (ConnectionException $exception) {
            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
            $error = $this->truncate($exception->getMessage(), $bodyLimit);

            $this->logAttempt($delivery, $attemptNumber, null, null, $error, $durationMs, false);

            return [
                'successful' => false,
                'http_status' => null,
                'response_body' => null,
                'error' => $error,
            ];
        } catch (Throwable $exception) {
            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
            $error = $this->truncate($exception->getMessage(), $bodyLimit);

            $this->logAttempt($delivery, $attemptNumber, null, null, $error, $durationMs, false);

            return [
                'successful' => false,
                'http_status' => null,
                'response_body' => null,
                'error' => $error,
            ];
        }
    }

    protected function buildHeaders(WebhookDelivery $delivery): array
    {
        $endpoint = $delivery->endpoint;
        $payloadJson = json_encode($delivery->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = array_merge(
            [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-Webhook-Retry/1.0',
                config('webhooks.event_id_header') => $delivery->event_id,
                config('webhooks.event_name_header') => $delivery->event_name,
            ],
            $endpoint->auth_headers ?? []
        );

        if (! empty($endpoint->secret)) {
            $headers[config('webhooks.signature_header')] = hash_hmac('sha256', (string) $payloadJson, $endpoint->secret);
        }

        return $headers;
    }

    protected function isSuccessful(Response $response): bool
    {
        return $response->successful();
    }

    protected function logAttempt(
        WebhookDelivery $delivery,
        int $attemptNumber,
        ?int $httpStatus,
        ?string $responseBody,
        ?string $error,
        int $durationMs,
        bool $successful
    ): void {
        WebhookAttempt::create([
            'webhook_delivery_id' => $delivery->id,
            'attempt_number' => $attemptNumber,
            'http_status' => $httpStatus,
            'response_body' => $responseBody,
            'error_message' => $error,
            'duration_ms' => $durationMs,
            'successful' => $successful,
            'attempted_at' => now(),
        ]);
    }

    protected function truncate(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit).'…';
    }
}
