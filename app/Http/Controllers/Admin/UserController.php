<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role as RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\ManageUserService;
use App\Support\Pages;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private ManageUserService $users) {}

    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $accounts = User::query()
            ->with('roles')
            ->orderBy('first_name')
            ->orderBy('name')
            ->paginate(Pages::PerPage)
            ->through(fn (User $account) => [
                'id' => $account->id,
                'name' => $account->displayName(),
                'email' => $account->email,
                'roles' => $account->roles
                    ->map(fn ($role) => $role->slug instanceof RoleName ? $role->slug->value : (string) $role->slug)
                    ->values(),
            ]);

        $labels = Role::query()->get()->mapWithKeys(
            fn (Role $role) => [$role->slug->value => $role->name],
        );

        return Inertia::render('admin/users/index', [
            'accounts' => $accounts,
            'roleOptions' => collect(RoleName::cases())
                ->map(fn (RoleName $role) => [
                    'value' => $role->value,
                    'label' => $labels[$role->value] ?? $role->value,
                ])
                ->all(),
        ]);
    }

    public function update(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $this->users->syncRoles($user, $request->validated('roles'), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rôles enregistrés.']);

        return to_route('admin.users.index');
    }
}
