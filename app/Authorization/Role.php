<?php

namespace App\Authorization;

/**
 * The application's roles and the permissions each one grants.
 *
 * These four roles come from docs/06-authentication-authorization.md, where
 * they are explicitly marked as starting points that still require business
 * confirmation. They are seeded as data so that changing the set is a seeder
 * change rather than a schema change -- but the enum cases are referenced in
 * code, so removing one is still a code change. Confirm the role set with the
 * business before treating it as final.
 */
enum Role: string
{
    case SuperAdmin = 'Super Admin';
    case ItAdmin = 'IT Admin';
    case IntegrationOperator = 'Integration Operator';
    case Viewer = 'Viewer';

    /**
     * Permissions granted to this role.
     *
     * Super Admin is intentionally empty: it is granted everything by a
     * Gate::before hook, so it does not need explicit permissions and stays
     * correct when new permissions are added later.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [],

            self::ItAdmin => [
                Permission::ViewDashboard,
                Permission::ViewEsbConnections,
                Permission::ManageEsbConnections,
                Permission::TestEsbConnections,
                Permission::ViewEndpointCatalog,
                Permission::ManageEndpointCatalog,
                Permission::ViewIntegrationLogs,
                Permission::ViewSalesPushes,
                Permission::ViewUsers,
                Permission::ManageUsers,
                Permission::ViewRoles,
                Permission::ManageRoles,
                Permission::ViewAuditLogs,
                Permission::ManageSettings,
            ],

            self::IntegrationOperator => [
                Permission::ViewDashboard,
                Permission::ViewEsbConnections,
                Permission::TestEsbConnections,
                Permission::ViewEndpointCatalog,
                Permission::ViewIntegrationLogs,
                Permission::InvokeEsbOperations,
                Permission::ViewSalesPushes,
                Permission::RetrySalesPushes,
            ],

            self::Viewer => [
                Permission::ViewDashboard,
                Permission::ViewEsbConnections,
                Permission::ViewEndpointCatalog,
                Permission::ViewIntegrationLogs,
            ],
        };
    }

    /**
     * Permission names granted to this role.
     *
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return array_map(
            static fn (Permission $permission): string => $permission->value,
            $this->permissions(),
        );
    }
}
