<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateUserRolesRequest extends AdminRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(Role::SuperAdmin);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'roles' => $this->input('roles', []),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in(array_map(fn (Role $role) => $role->value, Role::cases()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.*.in' => 'Ce rôle n’existe pas.',
        ];
    }
}
