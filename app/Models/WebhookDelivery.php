<?php

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id',
        'event_id',
        'event_name',
        'payload',
        'status',
        'attempt_count',
        'max_attempts',
        'next_retry_at',
        'last_http_status',
        'last_response_body',
        'last_error',
        'succeeded_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => WebhookDeliveryStatus::class,
            'attempt_count' => 'integer',
            'max_attempts' => 'integer',
            'last_http_status' => 'integer',
            'next_retry_at' => 'datetime',
            'succeeded_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(WebhookAttempt::class)->orderBy('attempt_number');
    }

    public function hasAttemptsRemaining(): bool
    {
        return $this->attempt_count < $this->max_attempts;
    }

    public function markProcessing(): void
    {
        $this->forceFill([
            'status' => WebhookDeliveryStatus::Processing,
        ])->save();
    }

    public function markSucceeded(int $httpStatus, ?string $responseBody): void
    {
        $this->forceFill([
            'status' => WebhookDeliveryStatus::Succeeded,
            'last_http_status' => $httpStatus,
            'last_response_body' => $responseBody,
            'last_error' => null,
            'next_retry_at' => null,
            'succeeded_at' => now(),
            'failed_at' => null,
        ])->save();
    }

    public function markFailedForRetry(?int $httpStatus, ?string $responseBody, ?string $error, int $delaySeconds): void
    {
        $this->forceFill([
            'status' => WebhookDeliveryStatus::Failed,
            'last_http_status' => $httpStatus,
            'last_response_body' => $responseBody,
            'last_error' => $error,
            'next_retry_at' => now()->addSeconds($delaySeconds),
            'failed_at' => null,
        ])->save();
    }

    public function markPermanentlyFailed(?int $httpStatus, ?string $responseBody, ?string $error): void
    {
        $this->forceFill([
            'status' => WebhookDeliveryStatus::PermanentlyFailed,
            'last_http_status' => $httpStatus,
            'last_response_body' => $responseBody,
            'last_error' => $error,
            'next_retry_at' => null,
            'failed_at' => now(),
        ])->save();
    }
}
