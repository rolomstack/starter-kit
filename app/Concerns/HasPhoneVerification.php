<?php

namespace App\Concerns;

use App\Models\Code;
use App\Notifications\VerifyPhoneNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPhoneVerification
{
    /**
     * Get the model's verification/OTP codes.
     *
     * @return MorphMany<Code, $this>
     */
    public function codes(): MorphMany
    {
        return $this->morphMany(Code::class, 'tokenable');
    }

    /**
     * Determine if the model has verified their phone number.
     */
    public function hasVerifiedPhone(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Mark the model's phone number as verified.
     */
    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Send the phone verification notification.
     */
    public function sendPhoneVerificationNotification(): void
    {
        $token = (string) random_int(100000, 999999);

        $this->codes()->updateOrCreate(
            ['purpose' => Code::PURPOSE_PHONE_VERIFICATION],
            [
                'recipient' => $this->getPhoneForVerification(),
                'code' => $token,
                'expires_at' => now()->addMinutes(15),
            ]
        );

        $this->notify(new VerifyPhoneNotification($token));
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): ?string
    {
        return $this->getEmailForVerification();
    }

    /**
     * Route notifications for the sms channel.
     */
    public function routeNotificationForSms(): ?string
    {
        return $this->getPhoneForVerification();
    }

    /**
     * Get the phone number that should be verified.
     */
    public function getPhoneForVerification(): ?string
    {
        return $this->phone;
    }

    /**
     * Get the email address that should receive the verification code.
     */
    public function getEmailForVerification(): ?string
    {
        return $this->email;
    }
}
