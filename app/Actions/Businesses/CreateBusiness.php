<?php

namespace App\Actions\Businesses;

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateBusiness
{
    /**
     * Create a new business and add the user as owner.
     */
    public function handle(User $user, string $name, string $email, ?string $phone = null, bool $isPersonal = false): Business
    {
        return DB::transaction(function () use ($email, $phone, $user, $name, $isPersonal) {
            $business = Business::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'is_personal' => $isPersonal,
            ]);

            $membership = $business->memberships()->create([
                'user_id' => $user->id,
                'role' => BusinessRole::Owner,
            ]);

            $user->switchBusiness($business);

            return $business;
        });
    }
}
