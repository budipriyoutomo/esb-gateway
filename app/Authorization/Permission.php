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

    /**
     * Permission to authorize or reject a purchase request in the ERP.
     *
     * A business decision, not an integration task, so esb.invoke alone does
     * not grant it. No role holds it yet: which role approves purchasing is
     * for the business to confirm (docs/06), and until then only Super Admin
     * has it, through Gate::before.
     */
    case ApprovePurchaseRequests = 'purchase-requests.approve';

    /**
     * Permission to create, change, delete, and import purchase requests in
     * the ERP.
     *
     * Separate from approving, so the person who raises a request need not be
     * the one who approves it. No role holds it yet, for the same reason as
     * ApprovePurchaseRequests: which role raises purchasing documents is for
     * the business to confirm (docs/06).
     */
    case ManagePurchaseRequests = 'purchase-requests.manage';

    /**
     * Permission to authorize or reject a purchase invoice in the ERP.
     *
     * Separate from approving purchase requests: approving a supplier invoice
     * commits the company to paying it, which is a finance decision rather
     * than a purchasing one. No role holds it yet; which role approves
     * invoices is for the business to confirm (docs/06).
     */
    case ApprovePurchaseInvoices = 'purchase-invoices.approve';

    /**
     * Permission to delete purchase invoices in the ERP. Creating and editing
     * them is not built yet. No role holds it yet, for the same reason as
     * ApprovePurchaseInvoices.
     */
    case ManagePurchaseInvoices = 'purchase-invoices.manage';

    /**
     * See the sales transactions POS clients pushed through the gateway, and
     * what the ESB said about each (ADR-022). Never the card details.
     */
    case ViewSalesPushes = 'sales-pushes.view';

    /**
     * Queue a failed or rejected sales push again. Separate from viewing:
     * sending sales data to the ERP is an operational action.
     */
    case RetrySalesPushes = 'sales-pushes.retry';

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
