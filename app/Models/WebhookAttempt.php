<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_delivery_id',
        'attempt_number',
        'http_status',
        'response_body',
        'error_message',
        'duration_ms',
        'successful',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
            'successful' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(WebhookDelivery::class, 'webhook_delivery_id');
    }
}
