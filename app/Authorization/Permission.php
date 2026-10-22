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
     * Permission to authorize or reject a purchase order in the ERP.
     *
     * Separate from approving purchase requests and invoices: authorizing an
     * order commits the company to a supplier, and the ERP sends it on from
     * there. No role holds it yet; which role approves orders is for the
     * business to confirm (docs/06).
     */
    case ApprovePurchaseOrders = 'purchase-orders.approve';

    /**
     * Permission to delete purchase orders in the ERP. Creating and editing
     * them is not built yet. No role holds it yet, for the same reason as
     * ApprovePurchaseOrders.
     */
    case ManagePurchaseOrders = 'purchase-orders.manage';

    /**
     * Permission to authorize or reject a simple purchase in the ERP.
     *
     * A simple purchase records goods bought and paid for in one step, so
     * approving it books both. Separate from the other purchasing approvals.
     * No role holds it yet; which role approves is for the business to
     * confirm (docs/06).
     */
    case ApproveSimplePurchases = 'simple-purchases.approve';

    /**
     * Permission to delete simple purchases in the ERP. Creating and editing
     * them is not built yet. No role holds it yet, for the same reason as
     * ApproveSimplePurchases.
     */
    case ManageSimplePurchases = 'simple-purchases.manage';

    /**
     * Permission to create document templates in the ERP. Changing and
     * deleting them is not built yet. A template decides which products
     * every branch it is assigned to can request, so esb.invoke alone does
     * not grant it. No role holds it yet: which role maintains templates is
     * for the business to confirm (docs/06).
     */
    case ManageDocumentTemplates = 'document-templates.manage';

    /**
     * Permission to delete, deactivate and restore master data records in
     * the ERP (TODO-modules.md §2, S2): units, product categories, product
     * sub-categories, supplier categories. One per module, because which
     * team maintains which list is for the business to decide. No role
     * holds them yet; only Super Admin can use them (docs/06).
     */
    case ManageUnits = 'units.manage';

    case ManageProductCategories = 'product-categories.manage';

    case ManageProductSubCategories = 'product-sub-categories.manage';

    case ManageSupplierCategories = 'supplier-categories.manage';

    /**
     * Permission to deactivate and restore suppliers in the ERP. A supplier
     * that is inactive can no longer be chosen on purchases. No role holds it
     * yet (docs/06).
     */
    case ManageSuppliers = 'suppliers.manage';

    /**
     * Permission to deactivate and restore chart-of-accounts entries in the
     * ERP. Deactivating an account also deactivates its active
     * sub-accounts, so this is kept apart from every other master data
     * permission. No role holds it yet (docs/06).
     */
    case ManageCoa = 'coa.manage';

    /**
     * Permission to authorize or reject a temporary (proposed) pricelist in
     * the ERP. Authorizing makes its prices the active supplier prices. No
     * role holds it yet (docs/06).
     */
    case ApprovePricelists = 'pricelists.approve';

    /**
     * Permission to delete a temporary pricelist in the ERP. No role holds
     * it yet (docs/06).
     */
    case ManagePricelists = 'pricelists.manage';

    /**
     * Permission to delete and restore products in the ERP. No role holds it
     * yet (docs/06).
     */
    case ManageProducts = 'products.manage';

    /**
     * Permission to authorize or reject an inventory document in the ERP
     * (TODO-modules.md §3, S2). Authorizing moves stock (or lets it move), so
     * each document has its own pair, kept apart from esb.invoke. No role
     * holds them yet; only Super Admin can use them (docs/06).
     */
    case ApproveGoodsTransferRequests = 'goods-transfer-requests.approve';

    /**
     * Permission to delete an inventory document in the ERP -- and, for a
     * goods transfer request, to finish and reopen it. No role holds them yet
     * (docs/06).
     */
    case ManageGoodsTransferRequests = 'goods-transfer-requests.manage';

    case ApproveGoodsDeliveries = 'goods-deliveries.approve';

    case ManageGoodsDeliveries = 'goods-deliveries.manage';

    case ApproveGoodsReceipts = 'goods-receipts.approve';

    case ManageGoodsReceipts = 'goods-receipts.manage';

    case ApproveItemJournals = 'item-journals.approve';

    case ManageItemJournals = 'item-journals.manage';

    case ApproveSimpleTransfers = 'simple-transfers.approve';

    case ManageSimpleTransfers = 'simple-transfers.manage';

    /**
     * Permission to authorize or reject a disbursement in the ERP
     * (TODO-modules.md §4.1, S2). Money goes out once it is authorized, so it
     * is kept apart from esb.invoke and from managing. No role holds it yet;
     * only Super Admin can use it (docs/06).
     */
    case ApproveDisbursements = 'disbursements.approve';

    /**
     * Permission to create, edit and delete disbursements, attach files to
     * them and import them (S2-S4). No role holds it yet (docs/06).
     */
    case ManageDisbursements = 'disbursements.manage';

    /**
     * Permission to authorize or reject a memorial journal in the ERP
     * (TODO-modules.md §5.4, S2). A manual journal changes the books once it
     * is authorized, so it is kept apart from esb.invoke and from managing.
     * No role holds it yet; only Super Admin can use it (docs/06).
     */
    case ApproveMemorialJournals = 'memorial-journals.approve';

    /**
     * Permission to create, edit and delete memorial journals and attach
     * images to them (S2-S4). No role holds it yet (docs/06).
     */
    case ManageMemorialJournals = 'memorial-journals.manage';

    /**
     * Permission to create and edit POS menu categories in the ERP
     * (TODO-modules.md §6.1, S3). No role holds it yet (docs/06).
     */
    case ManageMenuCategories = 'menu-categories.manage';

    /**
     * Permission to create POS promotions in the ERP (§6.5, S3). A
     * promotion changes what customers pay, so it is its own permission.
     * No role holds it yet (docs/06).
     */
    case ManagePromotions = 'promotions.manage';

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
