<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'interval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'integer',
        ];
    }

    /**
     * @return HasMany<PlanFeature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function calculateNextBillingDate(?Carbon $from = null): Carbon
    {
        $baseDate = $from ? $from->copy() : Carbon::now();

        return match ($this->interval) {
            'monthly' => $baseDate->addMonth(),
            'yearly' => $baseDate->addYear(),
            default => $baseDate->addMonth(),
        };
    }
}
