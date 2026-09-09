<?php

namespace App\Http\Controllers\Users;

use App\Actions\Users\AssignUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserRoleController extends Controller
{
    /**
     * The User parameter is type-hinted so implicit route model binding
     * resolves it before UpdateUserRoleRequest authorizes the change.
     */
    public function update(
        UpdateUserRoleRequest $request,
        User $user,
        AssignUserRole $assignUserRole,
    ): RedirectResponse {
        $assignUserRole->handle($user, $request->role());

        return back()->with('status', 'role-updated');
    }
}
