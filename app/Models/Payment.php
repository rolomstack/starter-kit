<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property PaymentStatus $status
 * @property array<string, mixed> $metadata
 */
class Payment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'metadata' => 'array',
        ];
    }

    /**
     * Get the parent payable model (Subscription, Order, etc.).
     *
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
