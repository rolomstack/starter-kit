<?php

namespace App\Actions\Fortify;

use App\Actions\Businesses\AcceptBusinessInvitation;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\BusinessInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        private readonly AcceptBusinessInvitation $acceptBusinessInvitation,
    ) {
        //
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $invitationCode = request()->query('invitation');
        $invitation = null;
        if (is_string($invitationCode)) {
            $invitation = BusinessInvitation::query()
                ->with('business')
                ->where('code', $invitationCode)
                ->whereNull('accepted_at')
                ->where(fn ($query) => $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now()))
                ->first();
        }

        return DB::transaction(function () use ($input, $invitation) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            if ($invitation) {
                $this->acceptBusinessInvitation->handle($user, $invitation);
            }

            return $user;
        });
    }
}
