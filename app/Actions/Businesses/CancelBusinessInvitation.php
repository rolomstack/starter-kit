<?php

namespace App\Actions\Businesses;

use App\Models\BusinessInvitation;

class CancelBusinessInvitation
{
    /**
     * Cancel a business invitation.
     */
    public function execute(BusinessInvitation $invitation): void
    {
        $invitation->delete();
    }
}
