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
    | 'purchase-orders.approve'
    | 'purchase-orders.manage'
    | 'simple-purchases.approve'
    | 'simple-purchases.manage'
    | 'document-templates.manage'
    | 'units.manage'
    | 'product-categories.manage'
    | 'product-sub-categories.manage'
    | 'supplier-categories.manage'
    | 'suppliers.manage'
    | 'coa.manage'
    | 'pricelists.approve'
    | 'pricelists.manage'
    | 'products.manage'
    | 'goods-transfer-requests.approve'
    | 'goods-transfer-requests.manage'
    | 'goods-deliveries.approve'
    | 'goods-deliveries.manage'
    | 'goods-receipts.approve'
    | 'goods-receipts.manage'
    | 'item-journals.approve'
    | 'item-journals.manage'
    | 'simple-transfers.approve'
    | 'simple-transfers.manage'
    | 'disbursements.approve'
    | 'disbursements.manage'
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
