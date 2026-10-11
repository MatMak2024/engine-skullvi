<?php

namespace App\Actions\Fortify;

use App\Concerns\ProfileValidationRules;
use App\Enums\Role as RoleName;
use App\Mail\SetPasswordMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, $this->profileRules(), [], $this->profileAttributes())->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'first_name' => $input['first_name'],
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'] ?? null,
                'address' => $input['address'] ?? null,
                'city' => $input['city'] ?? null,
                'state' => $input['state'] ?? null,
                'zip' => $input['zip'] ?? null,
                'country' => $input['country'] ?? null,
            ]);

            $role = Role::query()->firstOrCreate(
                ['slug' => RoleName::User],
                ['name' => 'Utilisateur'],
            );
            $user->roles()->syncWithoutDetaching([$role->id]);

            Mail::to($user)->send(new SetPasswordMail(
                $user,
                URL::temporarySignedRoute('password.set', now()->addHours(24), $user),
            ));

            return $user;
        });
    }
}
