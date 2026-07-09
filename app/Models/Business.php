<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueBusinessSlugs;
use App\Concerns\HasPhoneVerification;
use App\Contracts\MustVerifyPhone;
use App\Enums\BusinessRole;
use App\Enums\Country;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_personal
 * @property string|null $phone
 * @property Country|null $phone_country
 * @property Carbon|null $phone_verified_at
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, BusinessInvitation> $invitations
 * @property-read Collection<int, Membership> $memberships
 * @property-read Collection<int, User> $members
 */
#[Fillable(['name', 'slug', 'is_personal', 'phone', 'phone_country', 'email'])]
class Business extends Model implements MustVerifyPhone
{
    /** @use HasFactory<BusinessFactory> */
    use GeneratesUniqueBusinessSlugs, HasFactory, HasPhoneVerification, Notifiable, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Business $business) {
            if (empty($business->slug)) {
                $business->slug = static::generateUniqueBusinessSlug($business->name);
            }
        });

        static::updating(function (Business $business) {
            if ($business->isDirty('name')) {
                $business->slug = static::generateUniqueBusinessSlug($business->name, $business->id);
            }
        });

        static::saving(function (Business $business) {
            if ($business->phone && empty($business->phone_country)) {
                $business->phone_country = Country::detect($business->phone);
            }
        });
    }

    /**
     * Get the business owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', BusinessRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this business.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_members', 'business_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this business.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this business.
     *
     * @return HasMany<BusinessInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(BusinessInvitation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
            'phone_verified_at' => 'datetime',
            'phone_country' => Country::class,
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
