<?php

namespace App\Http\Requests;

use App\Authorization\Permission;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared helpers for reading validated input as typed values.
 *
 * Introduced under ADR-015 once ListPurchaseRequestsRequest became the second
 * consumer of what ListProductsRequest already did. Only reads and a
 * permission check live here; rules and the choice of permissions stay on
 * each request.
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * A validated string, or null when absent or empty.
     *
     * Null means "omit this filter": the ESB narrows a result set to rows
     * matching an empty value, so an empty string must never reach it.
     */
    protected function validatedString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function validatedInt(string $key): ?int
    {
        $value = $this->validated($key);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Whether the user holds every one of the permissions.
     */
    protected function userHoldsAll(Permission ...$permissions): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (! $user->can($permission->value)) {
                return false;
            }
        }

        return true;
    }
}
