<?php

namespace App\Enums;

enum WebhookDeliveryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case PermanentlyFailed = 'permanently_failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Succeeded => 'Succeeded',
            self::Failed => 'Failed (retrying)',
            self::PermanentlyFailed => 'Permanently Failed',
        };
    }

    public function isRetryable(): bool
    {
        return in_array($this, [self::Failed, self::PermanentlyFailed], true);
    }
}
