import { usePage } from '@inertiajs/react';
import type { Permission, RoleName } from '@/types';

/**
 * What the current user may do, as shared by HandleInertiaRequests.
 *
 * Use this to hide controls a user cannot act on. It is a usability
 * affordance, never a security boundary -- the server authorizes every route
 * and action independently (docs/06-authentication-authorization.md).
 */
export function usePermissions() {
    const { auth } = usePage().props;

    const permissions = auth.permissions ?? [];

    return {
        role: auth.role as RoleName | null,
        permissions,
        can: (permission: Permission): boolean =>
            permissions.includes(permission),
        canAny: (...candidates: Permission[]): boolean =>
            candidates.some((permission) => permissions.includes(permission)),
    };
}
