<?php

namespace App\Actions\Businesses;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateBusiness
{
    /**
     * Update the business details and handle phone verification state.
     * Returns an array containing the updated business and a boolean indicating if verification is required.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: Business, 1: bool}
     */
    public function execute(User $user, Business $business, array $data): array
    {
        $oldPhone = $business->phone;
        $newPhone = $data['phone'] ?? null;
        $phoneChanged = $newPhone !== $oldPhone;

        if ($phoneChanged) {
            abort_unless($user->ownsBusiness($business), 403, 'Only the business owner can update the phone number.');
        }

        $hasUsedPhoneBefore = false;

        $business = DB::transaction(function () use ($data, $business, $phoneChanged, $newPhone, $user, &$hasUsedPhoneBefore) {
            $business = Business::whereKey($business->id)->lockForUpdate()->firstOrFail();

            $business->fill([
                'name' => $data['name'] ?? $business->name,
                'email' => $data['email'] ?? $business->email,
                'phone' => $newPhone,
                'phone_country' => $data['phone_country'] ?? null,
            ]);

            if ($phoneChanged) {
                $business->phone_verified_at = null;
            }

            $business->save();

            if ($phoneChanged && $newPhone) {
                $hasUsedPhoneBefore = $user->businesses()
                    ->where('phone', $newPhone)
                    ->whereNotNull('phone_verified_at')
                    ->exists();

                if ($hasUsedPhoneBefore) {
                    $business->markPhoneAsVerified();
                }
            }

            return $business;
        });

        $requiresVerification = $phoneChanged && $newPhone && ! $hasUsedPhoneBefore;

        if ($requiresVerification) {
            $business->sendPhoneVerificationNotification();
        }

        return [$business, $requiresVerification];
    }
}
