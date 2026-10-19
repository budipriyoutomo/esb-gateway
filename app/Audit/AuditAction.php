<?php

namespace App\Audit;

/**
 * Auditable actions.
 *
 * Values are stable identifiers written to the database, so renaming a case
 * value rewrites history -- add a new case instead. The human-readable label
 * belongs in the UI, not here.
 */
enum AuditAction: string
{
    case RoleAssigned = 'user.role.assigned';

    /**
     * An ESB connection was repointed or its credential replaced (ADR-018).
     * The metadata names which fields changed; it never carries a value.
     */
    case EsbConnectionUpdated = 'esb-connection.updated';

    /** An ESB connection override was dropped, handing the family back to configuration. */
    case EsbConnectionReverted = 'esb-connection.reverted';

    /**
     * A purchase request was authorized or rejected in the ERP through the
     * gateway. The ERP records the gateway's shared service credential as the
     * actor, so this row is the only record of which person decided.
     */
    case PurchaseRequestAuthorized = 'purchase-request.authorized';

    case PurchaseRequestRejected = 'purchase-request.rejected';

    /**
     * A decision was sent but the ESB gave no definite answer. It may or may
     * not have been applied; the metadata carries the correlation id needed to
     * trace it.
     */
    case PurchaseRequestDecisionUnconfirmed = 'purchase-request.decision-unconfirmed';

    /**
     * A purchase request was created, replaced, or deleted in the ERP through
     * the gateway. As with decisions, the ERP sees the shared service
     * credential, so these rows are the record of which person did it.
     */
    case PurchaseRequestCreated = 'purchase-request.created';

    case PurchaseRequestUpdated = 'purchase-request.updated';

    case PurchaseRequestDeleted = 'purchase-request.deleted';

    /**
     * A spreadsheet of purchase requests was accepted by the ERP. The metadata
     * carries the file's name, size and SHA-256, never its contents.
     */
    case PurchaseRequestsImported = 'purchase-request.imported';

    /**
     * A create, update, delete, or import was sent but the ESB gave no
     * definite answer. The metadata names the operation and carries the
     * correlation id needed to trace it.
     */
    case PurchaseRequestWriteUnconfirmed = 'purchase-request.write-unconfirmed';

    /**
     * A purchase invoice was authorized, rejected, or deleted in the ERP
     * through the gateway. The ERP sees the shared service credential, so
     * these rows are the record of which person did it.
     */
    case PurchaseInvoiceAuthorized = 'purchase-invoice.authorized';

    case PurchaseInvoiceRejected = 'purchase-invoice.rejected';

    case PurchaseInvoiceDeleted = 'purchase-invoice.deleted';

    /**
     * A decision or delete on a purchase invoice was sent but the ESB gave no
     * definite answer. The metadata names the operation and carries the
     * correlation id needed to trace it.
     */
    case PurchaseInvoiceWriteUnconfirmed = 'purchase-invoice.write-unconfirmed';

    /**
     * A purchase order was authorized, rejected, or deleted in the ERP
     * through the gateway. The ERP sees the shared service credential, so
     * these rows are the record of which person did it. A rejection carries
     * the reason that was sent.
     */
    case PurchaseOrderAuthorized = 'purchase-order.authorized';

    case PurchaseOrderRejected = 'purchase-order.rejected';

    case PurchaseOrderDeleted = 'purchase-order.deleted';

    /**
     * A decision or delete on a purchase order was sent but the ESB gave no
     * definite answer. The metadata names the operation and carries the
     * correlation id needed to trace it.
     */
    case PurchaseOrderWriteUnconfirmed = 'purchase-order.write-unconfirmed';

    /**
     * A simple purchase was authorized, rejected, or deleted in the ERP
     * through the gateway. The ERP sees the shared service credential, so
     * these rows are the record of which person did it. A rejection carries
     * the reason that was sent.
     */
    case SimplePurchaseAuthorized = 'simple-purchase.authorized';

    case SimplePurchaseRejected = 'simple-purchase.rejected';

    case SimplePurchaseDeleted = 'simple-purchase.deleted';

    /**
     * A decision or delete on a simple purchase was sent but the ESB gave no
     * definite answer. The metadata names the operation and carries the
     * correlation id needed to trace it.
     */
    case SimplePurchaseWriteUnconfirmed = 'simple-purchase.write-unconfirmed';

    /**
     * A document template was created in the ERP through the gateway. The
     * ERP sees the shared service credential, so this row is the record of
     * which person did it.
     */
    case DocumentTemplateCreated = 'document-template.created';

    /**
     * A document template write was sent but the ESB gave no definite
     * answer. The metadata names the operation and carries the correlation id
     * needed to trace it.
     */
    case DocumentTemplateWriteUnconfirmed = 'document-template.write-unconfirmed';

    /**
     * A document template spreadsheet was uploaded and stored (ADR-023). The
     * metadata carries the file's name and SHA-256 and what was read from it,
     * never its contents.
     */
    case DocumentTemplateImportUploaded = 'document-template-import.uploaded';

    /** Someone asked for an import to be checked against the ERP. */
    case DocumentTemplateImportValidationRequested = 'document-template-import.validation-requested';

    /**
     * Someone asked for an import's ready templates to be sent. Each create
     * is audited on its own as document-template.created, with this person
     * as the actor.
     */
    case DocumentTemplateImportSyncRequested = 'document-template-import.sync-requested';

    /**
     * An API client (a POS) was issued a token, or had it revoked, from the
     * console (ADR-022). The metadata names the client, never the token.
     */
    case ApiClientIssued = 'api-client.issued';

    case ApiClientRevoked = 'api-client.revoked';

    /** A failed or rejected sales push was queued again by a person. */
    case SalesPushRetried = 'sales-push.retried';
}
