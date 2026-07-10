<?php

namespace App\Actions\Businesses;

use App\Models\Business;
use App\Models\User;

class RemoveBusinessMember
{
    public function execute(Business $business, User $user): void
    {
        abort_if($business->owner()?->is($user), 403, __('The business owner cannot be removed.'));

        $business->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($user->isCurrentBusiness($business)) {
            $user->switchBusiness($user->personalBusiness());
        }
    }
}
