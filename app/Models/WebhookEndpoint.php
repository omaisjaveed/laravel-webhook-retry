<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'secret',
        'auth_headers',
        'subscribed_events',
        'is_active',
        'timeout',
        'max_attempts',
        'retry_intervals',
    ];

    protected function casts(): array
    {
        return [
            'auth_headers' => 'array',
            'subscribed_events' => 'array',
            'retry_intervals' => 'array',
            'is_active' => 'boolean',
            'timeout' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function listensFor(string $eventName): bool
    {
        $events = $this->subscribed_events;

        if ($events === null || $events === []) {
            return true;
        }

        return in_array($eventName, $events, true);
    }

    public function resolvedTimeout(): int
    {
        return $this->timeout ?? (int) config('webhooks.timeout');
    }

    public function resolvedMaxAttempts(): int
    {
        return $this->max_attempts ?? (int) config('webhooks.max_attempts');
    }

    public function resolvedRetryIntervals(): array
    {
        $intervals = $this->retry_intervals ?? config('webhooks.retry_intervals', [60, 300, 900, 3600]);

        return array_values(array_map('intval', $intervals));
    }

    public function delayForAttempt(int $nextAttemptNumber): int
    {
        $intervals = $this->resolvedRetryIntervals();

        if ($nextAttemptNumber <= 1) {
            return 0;
        }

        $index = $nextAttemptNumber - 2;

        if ($index < 0) {
            return 0;
        }

        if ($index >= count($intervals)) {
            return (int) end($intervals);
        }

        return (int) $intervals[$index];
    }
}
