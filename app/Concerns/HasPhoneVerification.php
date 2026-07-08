<?php

namespace App\Concerns;

use App\Notifications\VerifyPhoneNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

trait HasPhoneVerification
{
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

        DB::table('phone_verification_tokens')->updateOrInsert(
            [
                'tokenable_type' => $this->getMorphClass(),
                'tokenable_id' => $this->getKey(),
            ],
            [
                'phone' => $this->getPhoneForVerification(),
                'token' => $token,
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
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
