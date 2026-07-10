<?php

namespace App\Models;

use App\Contracts\Billable;
use App\Contracts\Payable;
use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon|null $ends_at
 * @property Carbon|null $trial_ends_at
 * @property SubscriptionStatus $status
 * @property Plan $plan
 */
class Subscription extends Model implements Payable
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'subscribable_type',
        'subscribable_id',
        'plan_id',
        'payment_method_id',
        'status',
        'trial_ends_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => SubscriptionStatus::class,
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function active(): bool
    {
        return $this->status->active() || $this->onTrial();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function canceled(): bool
    {
        return $this->ends_at ? true : false;
    }

    public function getPayer(): Model
    {
        /** @var Billable&Model $payer */
        $payer = $this->subscribable;

        return $payer;
    }
}
