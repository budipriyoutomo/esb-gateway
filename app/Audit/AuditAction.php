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

    case MasterDataImported = 'master-data.imported';

    /**
     * A list was downloaded as CSV (ADR-031). The metadata names the list,
     * its filters and the row count -- never the rows.
     */
    case ListExported = 'list.exported';

    case MasterDataImportUnconfirmed = 'master-data.import-unconfirmed';

    /**
     * A create, update, authorize, reject, delete, or import was sent but the
     * ESB gave no definite answer. The metadata names the operation and
     * carries the correlation id needed to trace it.
     *
     * Rows written before 2026-10-22 record an unconfirmed authorize/reject
     * as `purchase-request.decision-unconfirmed` with a `decision` key; they
     * are left as they are.
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
     * A master data record was deleted, deactivated, or restored in the ERP
     * through the gateway (TODO-modules.md §2, S2). The ERP sees the shared
     * service credential, so these rows are the record of which person did
     * it. `*.write-unconfirmed` means the ESB gave no definite answer; its
     * metadata names the operation and carries the correlation id.
     */
    case UnitDeleted = 'unit.deleted';

    case UnitRestored = 'unit.restored';

    case UnitCreated = 'unit.created';

    case UnitUpdated = 'unit.updated';

    case UnitWriteUnconfirmed = 'unit.write-unconfirmed';

    case ProductCategoryDeleted = 'product-category.deleted';

    case ProductCategoryDeactivated = 'product-category.deactivated';

    case ProductCategoryRestored = 'product-category.restored';

    case ProductCategoryCreated = 'product-category.created';

    case ProductCategoryUpdated = 'product-category.updated';

    case ProductCategoryWriteUnconfirmed = 'product-category.write-unconfirmed';

    case ProductSubCategoryDeleted = 'product-sub-category.deleted';

    case ProductSubCategoryDeactivated = 'product-sub-category.deactivated';

    case ProductSubCategoryRestored = 'product-sub-category.restored';

    case ProductSubCategoryCreated = 'product-sub-category.created';

    case ProductSubCategoryUpdated = 'product-sub-category.updated';

    case ProductSubCategoryWriteUnconfirmed = 'product-sub-category.write-unconfirmed';

    case SupplierCategoryDeleted = 'supplier-category.deleted';

    case SupplierCategoryRestored = 'supplier-category.restored';

    case SupplierCategoryCreated = 'supplier-category.created';

    case SupplierCategoryUpdated = 'supplier-category.updated';

    case SupplierCategoryWriteUnconfirmed = 'supplier-category.write-unconfirmed';

    case SupplierDeactivated = 'supplier.deactivated';

    case SupplierRestored = 'supplier.restored';

    case SupplierAttachmentsUploaded = 'supplier.attachments-uploaded';

    case SupplierAttachmentsDeleted = 'supplier.attachments-deleted';

    case SupplierWriteUnconfirmed = 'supplier.write-unconfirmed';

    /**
     * A COA account (and, by the vendor's rule, its active sub-accounts) was
     * deactivated or restored.
     */
    case CoaDeactivated = 'coa.deactivated';

    case CoaRestored = 'coa.restored';

    case CoaMoved = 'coa.moved';

    case CoaWriteUnconfirmed = 'coa.write-unconfirmed';

    /**
     * A temporary pricelist was authorized (its prices became active and
     * the proposal was removed), rejected, or deleted in the ERP.
     */
    case PricelistAuthorized = 'pricelist.authorized';

    case PricelistRejected = 'pricelist.rejected';

    case PricelistDeleted = 'pricelist.deleted';

    case PricelistCreated = 'pricelist.created';

    case PricelistUpdated = 'pricelist.updated';

    case PricelistWriteUnconfirmed = 'pricelist.write-unconfirmed';

    case ProductDeleted = 'product.deleted';

    case ProductRestored = 'product.restored';

    case ProductCreated = 'product.created';

    case ProductUpdated = 'product.updated';

    case ProductWriteUnconfirmed = 'product.write-unconfirmed';

    /**
     * An inventory document was authorized, rejected, or deleted in the ERP
     * through the gateway (TODO-modules.md §3, S2), or -- a goods transfer
     * request -- finished or reopened. The ERP records the gateway's
     * credential, so this row is the record of the person. A write the ESB
     * did not confirm is *.write-unconfirmed with the operation and the
     * correlation id.
     */
    case GoodsTransferRequestAuthorized = 'goods-transfer-request.authorized';

    case GoodsTransferRequestRejected = 'goods-transfer-request.rejected';

    case GoodsTransferRequestDeleted = 'goods-transfer-request.deleted';

    case GoodsTransferRequestFinished = 'goods-transfer-request.finished';

    case GoodsTransferRequestReopened = 'goods-transfer-request.reopened';

    case GoodsTransferRequestWriteUnconfirmed = 'goods-transfer-request.write-unconfirmed';

    /**
     * An inventory document was created or replaced through the gateway
     * (S3, [ESB-035]). The metadata carries the number (when the ERP gave
     * one) and how many lines, never the lines themselves.
     */
    case GoodsTransferRequestCreated = 'goods-transfer-request.created';

    case GoodsTransferRequestUpdated = 'goods-transfer-request.updated';

    case ItemJournalCreated = 'item-journal.created';

    case ItemJournalUpdated = 'item-journal.updated';

    case SimpleTransferCreated = 'simple-transfer.created';

    case SimpleTransferUpdated = 'simple-transfer.updated';

    /**
     * Files were attached to, or every attachment removed from, an inventory
     * document (S4, [ESB-036]). The metadata names each file with its size
     * and SHA-256, never its contents.
     */
    case GoodsTransferRequestAttachmentsUploaded = 'goods-transfer-request.attachments-uploaded';

    case GoodsTransferRequestAttachmentsDeleted = 'goods-transfer-request.attachments-deleted';

    case GoodsDeliveryAttachmentsUploaded = 'goods-delivery.attachments-uploaded';

    case GoodsDeliveryAttachmentsDeleted = 'goods-delivery.attachments-deleted';

    case ItemJournalAttachmentsUploaded = 'item-journal.attachments-uploaded';

    case ItemJournalAttachmentsDeleted = 'item-journal.attachments-deleted';

    case SimpleTransferAttachmentsUploaded = 'simple-transfer.attachments-uploaded';

    case GoodsDeliveryAuthorized = 'goods-delivery.authorized';

    case GoodsDeliveryRejected = 'goods-delivery.rejected';

    case GoodsDeliveryDeleted = 'goods-delivery.deleted';

    case GoodsDeliveryWriteUnconfirmed = 'goods-delivery.write-unconfirmed';

    case GoodsReceiptAuthorized = 'goods-receipt.authorized';

    case GoodsReceiptRejected = 'goods-receipt.rejected';

    case GoodsReceiptDeleted = 'goods-receipt.deleted';

    case GoodsReceiptWriteUnconfirmed = 'goods-receipt.write-unconfirmed';

    case ItemJournalAuthorized = 'item-journal.authorized';

    case ItemJournalRejected = 'item-journal.rejected';

    case ItemJournalDeleted = 'item-journal.deleted';

    case ItemJournalWriteUnconfirmed = 'item-journal.write-unconfirmed';

    case SimpleTransferAuthorized = 'simple-transfer.authorized';

    case SimpleTransferRejected = 'simple-transfer.rejected';

    case SimpleTransferDeleted = 'simple-transfer.deleted';

    case SimpleTransferWriteUnconfirmed = 'simple-transfer.write-unconfirmed';

    /**
     * A disbursement was authorized, rejected (with the reason, when one was
     * given) or deleted through the gateway ([ESB-038]). The ERP records the
     * shared service credential, so this row is the record of the person.
     */
    case DisbursementAuthorized = 'disbursement.authorized';

    case DisbursementRejected = 'disbursement.rejected';

    case DisbursementDeleted = 'disbursement.deleted';

    /**
     * A disbursement was created or replaced through the gateway
     * ([ESB-039]). The metadata carries the number (when the ERP gave one),
     * the date, the payment method and how many lines -- never the amounts.
     */
    case DisbursementCreated = 'disbursement.created';

    case DisbursementUpdated = 'disbursement.updated';

    /**
     * Files were attached to a disbursement ([ESB-040]): each file's name,
     * size and SHA-256, never its contents.
     */
    case DisbursementAttachmentsUploaded = 'disbursement.attachments-uploaded';

    /**
     * A disbursement write the ERP did not confirm either way, with its
     * correlation id.
     */
    case DisbursementWriteUnconfirmed = 'disbursement.write-unconfirmed';

    /**
     * A memorial journal was authorized, rejected (with the reason, when one
     * was given) or deleted through the gateway ([ESB-044]). The ERP records
     * the shared service credential, so this row is the record of the person.
     */
    case MemorialJournalAuthorized = 'memorial-journal.authorized';

    case MemorialJournalRejected = 'memorial-journal.rejected';

    case MemorialJournalDeleted = 'memorial-journal.deleted';

    /**
     * A memorial journal was created or replaced through the gateway
     * ([ESB-045]). The metadata carries the number (when the ERP gave one),
     * the date and how many lines -- never the amounts.
     */
    case MemorialJournalCreated = 'memorial-journal.created';

    case MemorialJournalUpdated = 'memorial-journal.updated';

    /**
     * An image was attached to a memorial journal ([ESB-046]): its name,
     * size and SHA-256, never its contents.
     */
    case MemorialJournalImageUploaded = 'memorial-journal.image-uploaded';

    /**
     * A memorial journal write the ERP did not confirm either way, with its
     * correlation id.
     */
    case MemorialJournalWriteUnconfirmed = 'memorial-journal.write-unconfirmed';

    /**
     * A POS menu category was created or replaced through the gateway
     * ([ESB-048]); the metadata carries its id (when the ERP gave one) and
     * name.
     */
    case MenuCategoryCreated = 'menu-category.created';

    case MenuCategoryUpdated = 'menu-category.updated';

    case MenuCategoryWriteUnconfirmed = 'menu-category.write-unconfirmed';

    /**
     * A POS promotion was created through the gateway ([ESB-050]); the
     * metadata carries its id (when the ERP gave one), type, code and notes.
     */
    case PromotionCreated = 'promotion.created';

    case PromotionWriteUnconfirmed = 'promotion.write-unconfirmed';

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
