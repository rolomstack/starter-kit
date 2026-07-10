<?php

namespace App\Actions\Businesses;

use App\Enums\BusinessRole;
use App\Enums\Country;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateBusiness
{
    /**
     * Create a new business and add the user as owner.
     * Returns an array containing the new business and a boolean indicating if verification is required.
     *
     * @return array{0: Business, 1: bool}
     */
    public function execute(User $user, string $name, string $email, ?string $phone = null, ?Country $phoneCountry = null, bool $isPersonal = false): array
    {
        $hasUsedPhoneBefore = false;

        $business = DB::transaction(function () use ($email, $phone, $phoneCountry, $user, $name, $isPersonal, &$hasUsedPhoneBefore) {
            $business = Business::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'phone_country' => $phoneCountry,
                'is_personal' => $isPersonal,
            ]);

            $business->memberships()->create([
                'user_id' => $user->id,
                'role' => BusinessRole::Owner,
            ]);

            $user->switchBusiness($business);

            if ($business->phone) {
                $hasUsedPhoneBefore = $user->businesses()
                    ->where('phone', $business->phone)
                    ->whereNotNull('phone_verified_at')
                    ->exists();

                if ($hasUsedPhoneBefore) {
                    $business->markPhoneAsVerified();
                }
            }

            return $business;
        });

        $requiresVerification = $business->phone && ! $hasUsedPhoneBefore;

        if ($requiresVerification) {
            $business->sendPhoneVerificationNotification();
        }

        return [$business, $requiresVerification];
    }
}
