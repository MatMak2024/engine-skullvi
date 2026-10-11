<?php

namespace App\Services\Admin;

use App\Enums\Role as RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ManageUserService
{
    /**
     * @param  list<string>  $slugs
     */
    public function syncRoles(User $account, array $slugs, User $actor): void
    {
        $slugs = array_values(array_unique($slugs));

        if ($account->is($actor) && ! in_array(RoleName::SuperAdmin->value, $slugs, true)) {
            throw ValidationException::withMessages([
                'roles' => 'Vous devez conserver le rôle super administrateur sur votre compte.',
            ]);
        }

        $roles = Role::query()->whereIn('slug', $slugs)->get();

        if ($roles->count() !== count($slugs)) {
            throw ValidationException::withMessages([
                'roles' => 'Un rôle indiqué n’est pas disponible.',
            ]);
        }

        $account->roles()->sync($roles->modelKeys());
    }
}
