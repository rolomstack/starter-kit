<?php

namespace App\Actions\Businesses;

use App\Models\Business;

class VerifyBusinessPhone
{
    public function execute(Business $business): void
    {
        $business->codes()
            ->phoneVerification()
            ->delete();

        $business->markPhoneAsVerified();
    }
}
