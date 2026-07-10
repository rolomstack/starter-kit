<?php

namespace App\Actions\Businesses;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteBusiness
{
    public function execute(User $user, Business $business): void
    {
        $fallbackBusiness = $user->isCurrentBusiness($business)
            ? $user->fallbackBusiness($business)
            : null;

        DB::transaction(function () use ($user, $business) {
            User::where('current_business_id', $business->id)
                ->where('id', '!=', $user->id)
                ->each(fn (User $affectedUser) => $affectedUser->switchBusiness($affectedUser->personalBusiness()));

            $business->invitations()->delete();
            $business->memberships()->delete();
            $business->delete();
        });

        if ($fallbackBusiness) {
            $user->switchBusiness($fallbackBusiness);
        }
    }
}
