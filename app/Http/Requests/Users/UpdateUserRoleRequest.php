<?php

namespace App\Http\Requests\Users;

use App\Authorization\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assignRole', $this->targetUser()) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(array_column(Role::cases(), 'value'))],
        ];
    }

    public function targetUser(): User
    {
        $user = $this->route('user');

        abort_unless($user instanceof User, 404);

        return $user;
    }

    public function role(): Role
    {
        return Role::from($this->string('role')->toString());
    }
}
