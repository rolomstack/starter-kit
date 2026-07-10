<?php

namespace App\Actions\Businesses;

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;

class UpdateBusinessMemberRole
{
    public function execute(Business $business, User $user, string $role): void
    {
        $newRole = BusinessRole::from($role);

        $business->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['role' => $newRole]);
    }
}
