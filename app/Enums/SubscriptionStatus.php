<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Canceled = 'canceled';
    case PastDue = 'past_due';

    public function pending(): bool
    {
        return $this === self::Pending;
    }

    public function active(): bool
    {
        return $this === self::Active;
    }

    public function canceled(): bool
    {
        return $this === self::Canceled;
    }

    public function pastDue(): bool
    {
        return $this === self::PastDue;
    }
}
