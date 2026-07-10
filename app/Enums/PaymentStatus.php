<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';

    public function pending(): bool
    {
        return $this === self::Pending;
    }

    public function successful(): bool
    {
        return $this === self::Successful;
    }

    public function failed(): bool
    {
        return $this === self::Failed;
    }
}
