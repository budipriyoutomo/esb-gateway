/**
 * Mirrors App\Authorization\Permission and App\Authorization\Role.
 *
 * Kept in sync by PermissionTypesInSyncTest, which fails the build if this
 * file and the PHP enums disagree. Update both together.
 */

export type Permission =
    | 'dashboard.view'
    | 'esb-connections.view'
    | 'esb-connections.manage'
    | 'esb-connections.test'
    | 'endpoints.view'
    | 'endpoints.manage'
    | 'integration-logs.view'
    | 'esb.invoke'
    | 'purchase-requests.approve'
    | 'purchase-requests.manage'
    | 'purchase-invoices.approve'
    | 'purchase-invoices.manage'
    | 'sales-pushes.view'
    | 'sales-pushes.retry'
    | 'users.view'
    | 'users.manage'
    | 'roles.view'
    | 'roles.manage'
    | 'audit-logs.view'
    | 'settings.manage';

export type RoleName =
    | 'Super Admin'
    | 'IT Admin'
    | 'Integration Operator'
    | 'Viewer';
