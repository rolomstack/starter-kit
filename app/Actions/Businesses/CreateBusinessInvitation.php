<?php

namespace App\Actions\Businesses;

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Models\User;
use App\Notifications\Businesses\BusinessInvitation as BusinessInvitationNotification;
use Illuminate\Support\Facades\Notification;

class CreateBusinessInvitation
{
    /**
     * Create a new business invitation and send a notification.
     */
    public function execute(Business $business, string $email, string $role, User $invitedBy): BusinessInvitation
    {
        $invitation = $business->invitations()->create([
            'email' => $email,
            'role' => BusinessRole::from($role),
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new BusinessInvitationNotification($invitation));

        return $invitation;
    }
}
