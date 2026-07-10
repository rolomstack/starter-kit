<?php

namespace App\Actions\Settings;

use App\Models\User;

class DeleteProfile
{
    public function execute(User $user): void
    {
        $user->delete();
    }
}
