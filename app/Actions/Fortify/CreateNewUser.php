<?php

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Rules\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private CreateTeam $createTeam)
    {
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
            'business_name' => ['required', 'string', 'max:50', 'min:3'],
            'phone_country' => ['required', 'string', 'in:US,CA,GB,AU,NZ,NG'],
            'phone' => ['required', 'string', new Phone('phone_country')],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $businessName = $input['business_name'];
            $this->createTeam->handle($user, $businessName, isPersonal: true, metadata: [
                'email' => $input['business_email'] ?? $input['email'],
                'phone' => $input['phone'],
            ]);

            return $user;
        });
    }
}
