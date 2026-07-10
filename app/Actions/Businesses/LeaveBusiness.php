<?php

namespace App\Actions\Businesses;

use App\Models\Business;
use App\Models\User;

class LeaveBusiness
{
    public function execute(User $user, Business $business): void
    {
        $fallbackBusiness = $user->isCurrentBusiness($business)
            ? $user->fallbackBusiness($business)
            : null;

        $business->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($fallbackBusiness) {
            $user->switchBusiness($fallbackBusiness);
        }
    }
}
