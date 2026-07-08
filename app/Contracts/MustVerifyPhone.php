<?php

namespace App\Contracts;

interface MustVerifyPhone
{
    /**
     * Determine if the model has verified their phone number.
     */
    public function hasVerifiedPhone(): bool;

    /**
     * Mark the model's phone number as verified.
     */
    public function markPhoneAsVerified(): bool;

    /**
     * Send the phone verification notification.
     */
    public function sendPhoneVerificationNotification(): void;

    /**
     * Get the phone number that should be verified.
     */
    public function getPhoneForVerification(): ?string;

    /**
     * Get the email address that should receive the verification code.
     */
    public function getEmailForVerification(): ?string;
}
