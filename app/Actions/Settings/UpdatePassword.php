<?php

namespace App\Actions\Settings;

use App\Models\User;

class UpdatePassword
{
    public function execute(User $user, string $password): void
    {
        $user->update([
            'password' => $password,
        ]);
    }
}
