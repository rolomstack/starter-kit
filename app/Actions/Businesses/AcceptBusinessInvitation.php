<?php

namespace App\Actions\Businesses;

use App\Models\BusinessInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcceptBusinessInvitation
{
    /**
     * Accept the given business invitation for the user.
     */
    public function handle(User $user, BusinessInvitation $invitation): void
    {
        DB::transaction(function () use ($user, $invitation) {
            $business = $invitation->business;

            $business->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);

            $user->switchBusiness($business);
        });
    }
}
