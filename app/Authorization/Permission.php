<?php

namespace App\Authorization;

/**
 * Every ability the application checks.
 *
 * Permissions are referenced by name in code (policies, gates, route
 * middleware), so they live here as a typed enum rather than as free-form
 * strings scattered through the codebase. Roles, by contrast, are data --
 * see the Role enum and RolePermissionSeeder.
 *
 * Adding a case here means adding it to a role in Role::permissions() and
 * re-running the seeder.
 */
enum Permission: string
{
    case ViewDashboard = 'dashboard.view';

    case ViewEsbConnections = 'esb-connections.view';
    case ManageEsbConnections = 'esb-connections.manage';
    case TestEsbConnections = 'esb-connections.test';

    case ViewEndpointCatalog = 'endpoints.view';
    case ManageEndpointCatalog = 'endpoints.manage';

    case ViewIntegrationLogs = 'integration-logs.view';

    /**
     * Permission to actually send an operation to the ERP ESB.
     *
     * Deliberately separate from the "manage" permissions: configuring the
     * gateway and running ERP transactions through it are different
     * responsibilities (docs/10-security.md, least privilege).
     */
    case InvokeEsbOperations = 'esb.invoke';

    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';

    case ViewRoles = 'roles.view';
    case ManageRoles = 'roles.manage';

    case ViewAuditLogs = 'audit-logs.view';

    case ManageSettings = 'settings.manage';

    /**
     * All permission names, for seeding and for exposing to the frontend.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
