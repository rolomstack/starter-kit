<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['purpose', 'recipient', 'code', 'expires_at'])]
class Code extends Model
{
    public const PURPOSE_PHONE_VERIFICATION = 'phone_verification';

    /**
     * Get the owning tokenable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include phone verification codes.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePhoneVerification(Builder $query): Builder
    {
        return $query->where('purpose', self::PURPOSE_PHONE_VERIFICATION);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
