# ESB Core API — Endpoint Catalog (v2.0.0)

> Generated from the official OpenAPI description (`https://developers.esb.co.id/esb-core/openapi.json`) on 2026-09-30 by `docs/esb/generate-catalog.py`. This is a mechanical transcription of the vendor documentation, not a design document. Do not hand-edit — regenerate it, and record contract changes in `docs/05-api-integration.md`.

Official documentation: https://developers.esb.co.id/esb-core/

**Total operations:** 1224 across 116 groups.

Base URLs declared by the document:

- Production: `https://services.esb.co.id/core`
- Staging: `https://stg7.esb.co.id/core-stg`
- Staging (INT): `https://stg7.esb.co.id/core-int`

Paths below are relative to those base URLs unless a group says otherwise. OpenAPI has no per-operation version, so the Version column carries the document's version (2.0.0).

## Groups

- [Advance Payment](#advance-payment) — 15 operations
- [Advance Settlement](#advance-settlement) — 6 operations
- [Approval](#approval) — 17 operations
- [Asset](#asset) — 60 operations
- [Authentication](#authentication) — 6 operations
- [Bank Reconciliation](#bank-reconciliation) — 1 operations
- [Branch Product](#branch-product) — 7 operations
- [Branch Setting](#branch-setting) — 1 operations
- [Budget Adjustment](#budget-adjustment) — 7 operations
- [Budget Allocate](#budget-allocate) — 7 operations
- [Budget Detail](#budget-detail) — 13 operations
- [Budget Plan](#budget-plan) — 12 operations
- [Cash Flow Category](#cash-flow-category) — 13 operations
- [Chart of Account](#chart-of-account) — 26 operations
- [Close Delivery](#close-delivery) — 14 operations
- [Common](#common) — 26 operations
- [Company](#company) — 4 operations
- [Company Bank](#company-bank) — 8 operations
- [Cross Transaction](#cross-transaction) — 1 operations
- [Customer Advance](#customer-advance) — 20 operations
- [Customer Bank](#customer-bank) — 1 operations
- [Customer Branch](#customer-branch) — 1 operations
- [Customer Category](#customer-category) — 1 operations
- [Customer Category Pricelist](#customer-category-pricelist) — 10 operations
- [Customer Invoice](#customer-invoice) — 1 operations
- [Customer Limit Receivable](#customer-limit-receivable) — 1 operations
- [Customer Receivable](#customer-receivable) — 8 operations
- [Depreciation Period](#depreciation-period) — 4 operations
- [Disbursement](#disbursement) — 11 operations
- [Disbursement Online](#disbursement-online) — 5 operations
- [Email Recipient](#email-recipient) — 7 operations
- [Employee](#employee) — 1 operations
- [Employee Advance Payment](#employee-advance-payment) — 9 operations
- [Employee Reimbursement](#employee-reimbursement) — 9 operations
- [End Period](#end-period) — 11 operations
- [Food Cost Calculator](#food-cost-calculator) — 1 operations
- [General Journal](#general-journal) — 3 operations
- [General Ledger](#general-ledger) — 2 operations
- [Goods Delivery](#goods-delivery) — 14 operations
- [Goods Delivery Asset](#goods-delivery-asset) — 1 operations
- [Goods Delivery Expiration](#goods-delivery-expiration) — 1 operations
- [Goods Delivery Return](#goods-delivery-return) — 13 operations
- [Goods Delivery Return Expiration](#goods-delivery-return-expiration) — 1 operations
- [Goods Receipt](#goods-receipt) — 12 operations
- [Goods Receipt Expiration](#goods-receipt-expiration) — 1 operations
- [Goods Receipt Return](#goods-receipt-return) — 10 operations
- [Goods Receipt Return Expiration](#goods-receipt-return-expiration) — 1 operations
- [Goods Transfer Request](#goods-transfer-request) — 12 operations
- [Import Document](#import-document) — 8 operations
- [InterCash](#intercash) — 9 operations
- [Inventory Dashboard](#inventory-dashboard) — 11 operations
- [Inventory Revaluation](#inventory-revaluation) — 5 operations
- [Item Journal](#item-journal) — 9 operations
- [Location](#location) — 9 operations
- [Map Asset Images](#map-asset-images) — 2 operations
- [Map COA](#map-coa) — 8 operations
- [Master Bill of Material](#master-bill-of-material) — 24 operations
- [Master Category](#master-category) — 15 operations
- [Master Company](#master-company) — 11 operations
- [Master Cost Center](#master-cost-center) — 10 operations
- [Master Customer](#master-customer) — 17 operations
- [Master Customer Pricelist](#master-customer-pricelist) — 10 operations
- [Master Document Template](#master-document-template) — 22 operations
- [Master Pricelist](#master-pricelist) — 18 operations
- [Master Product](#master-product) — 35 operations
- [Master Sub Category](#master-sub-category) — 13 operations
- [Master Supplier](#master-supplier) — 16 operations
- [Master Unit](#master-unit) — 11 operations
- [Material Delivery](#material-delivery) — 11 operations
- [Memorial Journal](#memorial-journal) — 12 operations
- [Menu](#menu) — 3 operations
- [Mindmap Purchase](#mindmap-purchase) — 2 operations
- [Open Manufacturing](#open-manufacturing) — 9 operations
- [Operational](#operational) — 5 operations
- [Payable Settlement](#payable-settlement) — 8 operations
- [Petty Cash](#petty-cash) — 22 operations
- [Product Sales Invoice](#product-sales-invoice) — 14 operations
- [Product Sales Payment](#product-sales-payment) — 11 operations
- [Product Sales Return](#product-sales-return) — 10 operations
- [Production Order](#production-order) — 18 operations
- [Production Result](#production-result) — 10 operations
- [Production Return](#production-return) — 9 operations
- [Project](#project) — 7 operations
- [Purchase Dashboard](#purchase-dashboard) — 5 operations
- [Purchase Head](#purchase-head) — 1 operations
- [Purchase Invoice](#purchase-invoice) — 24 operations
- [Purchase Order](#purchase-order) — 19 operations
- [Purchase Payment](#purchase-payment) — 13 operations
- [Purchase Request](#purchase-request) — 19 operations
- [Purchase Return](#purchase-return) — 7 operations
- [Purpose](#purpose) — 11 operations
- [Receipt](#receipt) — 9 operations
- [Receivable Settlement](#receivable-settlement) — 7 operations
- [Recurring Journal](#recurring-journal) — 10 operations
- [Release Payment](#release-payment) — 12 operations
- [Report](#report) — 61 operations
- [Request Item List](#request-item-list) — 6 operations
- [Sales Order](#sales-order) — 14 operations
- [Sales Representative](#sales-representative) — 9 operations
- [Signature Setting](#signature-setting) — 1 operations
- [Simple Manufacturing](#simple-manufacturing) — 15 operations
- [Simple Purchase](#simple-purchase) — 11 operations
- [Simple Sales](#simple-sales) — 16 operations
- [Simple Transfer](#simple-transfer) — 10 operations
- [Stock](#stock) — 7 operations
- [Stock List](#stock-list) — 5 operations
- [Stock Opname](#stock-opname) — 16 operations
- [Stock Period](#stock-period) — 1 operations
- [Supplier Category](#supplier-category) — 8 operations
- [Supplier Payable](#supplier-payable) — 11 operations
- [Supplier Settlement](#supplier-settlement) — 1 operations
- [Tax](#tax) — 9 operations
- [Usage Management](#usage-management) — 11 operations
- [User](#user) — 29 operations
- [VAT Invoice Number](#vat-invoice-number) — 14 operations
- [View Pending Receipt](#view-pending-receipt) — 2 operations

## Advance Payment

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchase/advance-payment` | List advance payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment/get) |
| `POST` | `/purchase/advance-payment` | Create an advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment/post) |
| `GET` | `/purchase/advance-payment/available-amount` | Get available advance amount | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-available-amount/get) |
| `GET` | `/purchase/advance-payment/browse-purchase` | Browse purchase references | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-browse-purchase/get) |
| `GET` | `/purchase/advance-payment/browse-reference` | Browse advance payment references | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-browse-reference/get) |
| `GET` | `/purchase/advance-payment/current-settlement-available-amount` | Get available advance amount by settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-current-settlement-available-amount/get) |
| `GET` | `/purchase/advance-payment/purchase-advance-amount` | Get purchase advance amount | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-purchase-advance-amount/get) |
| `DELETE` | `/purchase/advance-payment/{advancePaymentNum}` | Delete an advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-advancePaymentNum/delete) |
| `GET` | `/purchase/advance-payment/{advancePaymentNum}` | Get an advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-advancePaymentNum/get) |
| `PUT` | `/purchase/advance-payment/{advancePaymentNum}` | Update an advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-advancePaymentNum/put) |
| `DELETE` | `/purchase/advance-payment/{advancePaymentNum}/attachment` | Delete advance payment attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-advancePaymentNum--attachment/delete) |
| `PATCH` | `/purchase/advance-payment/{advancePaymentNum}/attachment` | Upload advance payment attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-advancePaymentNum--attachment/patch) |
| `PATCH` | `/purchase/advance-payment/{advancePaymentNum}/authorize` | Authorize an advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-advancePaymentNum--authorize/patch) |
| `GET` | `/purchase/advance-payment/{advancePaymentNum}/references` | Get advance payment references | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-advancePaymentNum--references/get) |
| `PATCH` | `/purchase/advance-payment/{advancePaymentNum}/reject` | Reject an advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-advance-payment-advancePaymentNum--reject/patch) |

## Advance Settlement

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/advance-settlement` | Create advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/advance-settlement/post) |
| `DELETE` | `/advance-settlement/{advanceSettlementNum}` | Delete advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/advance-settlement-advanceSettlementNum/delete) |
| `GET` | `/advance-settlement/{advanceSettlementNum}` | Get advance settlement by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/advance-settlement-advanceSettlementNum/get) |
| `PUT` | `/advance-settlement/{advanceSettlementNum}` | Update advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/advance-settlement-advanceSettlementNum/put) |
| `PATCH` | `/advance-settlement/{advanceSettlementNum}/authorize` | Authorize advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/advance-settlement-advanceSettlementNum--authorize/patch) |
| `PATCH` | `/advance-settlement/{advanceSettlementNum}/reject` | Reject advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/advance-settlement-advanceSettlementNum--reject/patch) |

## Approval

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/approval-dashboard` | List approval dashboard items | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-dashboard/get) |
| `GET` | `/approval-flow` | List approval flows | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow/get) |
| `POST` | `/approval-flow` | Create approval flow | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow/post) |
| `PUT` | `/approval-flow` | Update approval flow | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow/put) |
| `GET` | `/approval-flow/validate-exist/creators` | Get approval flow by transaction, creator, and ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow-validate-exist-creators/get) |
| `GET` | `/approval-flow/validate-exist/creators/transaction-name` | Get creators with same transaction name | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow-validate-exist-creators-transaction-name/get) |
| `GET` | `/approval-flow/validate-exist/name` | Get approval flow by transaction and name | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow-validate-exist-name/get) |
| `DELETE` | `/approval-flow/{approvalFlowID}` | Delete approval flow | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow-approvalFlowID/delete) |
| `GET` | `/approval-flow/{approvalFlowID}` | Get approval flow head by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow-approvalFlowID/get) |
| `GET` | `/approval-flow/{approvalFlowID}/creators` | Get creators by approval ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow-approvalFlowID--creators/get) |
| `GET` | `/approval-flow/{approvalFlowID}/details` | Get approval flow details by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow-approvalFlowID--details/get) |
| `GET` | `/approval-flow/{approvalFlowID}/pending-transaction` | Get pending transactions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-flow-approvalFlowID--pending-transaction/get) |
| `GET` | `/approval/action-grid` | Get action grid approval | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-action-grid/get) |
| `GET` | `/approval/browse` | Browse approvals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-browse/get) |
| `GET` | `/approval/findByNum` | Get approval by reference number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-findByNum/get) |
| `GET` | `/approval/findOne` | Find one approval by reference number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-findOne/get) |
| `GET` | `/approval/has-access` | Check user access | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/approval-has-access/get) |

## Asset

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/asset/asset-data` | List assets | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data/get) |
| `GET` | `/asset/asset-data-draft` | List asset data drafts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-draft/get) |
| `GET` | `/asset/asset-data-draft/export` | Export asset data drafts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-draft-export/get) |
| `POST` | `/asset/asset-data-draft/publish` | Publish asset data drafts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-draft-publish/post) |
| `POST` | `/asset/asset-data-draft/upload` | Upload asset data from Excel file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-draft-upload/post) |
| `GET` | `/asset/asset-data-draft/{assetID}` | Get an asset data draft | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-draft-assetID/get) |
| `PUT` | `/asset/asset-data-draft/{assetID}` | Update asset data draft | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-draft-assetID/put) |
| `DELETE` | `/asset/asset-data-draft/{assetID}/image` | Delete asset image | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-draft-assetID--image/delete) |
| `POST` | `/asset/asset-data-draft/{assetID}/image` | Upload asset image | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-draft-assetID--image/post) |
| `GET` | `/asset/asset-data/browse-delivery` | Browse delivery assets | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-browse-delivery/get) |
| `GET` | `/asset/asset-data/browse-disposal` | Browse assets for disposal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-browse-disposal/get) |
| `GET` | `/asset/asset-data/browse-fiscal` | Browse fiscal assets | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-browse-fiscal/get) |
| `GET` | `/asset/asset-data/browse-multi` | Browse multiple assets | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-browse-multi/get) |
| `GET` | `/asset/asset-data/browse-simple-transfer` | Browse assets for simple transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-browse-simple-transfer/get) |
| `GET` | `/asset/asset-data/export` | Export assets to file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-export/get) |
| `GET` | `/asset/asset-data/goods-receipt` | Find assets by goods receipt number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-goods-receipt/get) |
| `PUT` | `/asset/asset-data/multi-activation` | Activate multiple assets | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-multi-activation/put) |
| `GET` | `/asset/asset-data/request-item-list` | Fetch request item list | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-request-item-list/get) |
| `POST` | `/asset/asset-data/search-receipt` | Search receipt assets | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-search-receipt/post) |
| `POST` | `/asset/asset-data/upload` | Upload asset data from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-upload/post) |
| `GET` | `/asset/asset-data/{assetID}` | Get asset details by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-assetID/get) |
| `PUT` | `/asset/asset-data/{assetID}` | Update asset data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-assetID/put) |
| `POST` | `/asset/asset-data/{assetID}/image` | Upload or delete asset image | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-data-assetID--image/post) |
| `GET` | `/asset/asset-disposal` | List asset disposals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-disposal/get) |
| `POST` | `/asset/asset-disposal` | Create an asset disposal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-disposal/post) |
| `GET` | `/asset/asset-disposal/validate-depreciation` | Validate assets for disposal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-disposal-validate-depreciation/get) |
| `DELETE` | `/asset/asset-disposal/{assetDisposalNum}` | Delete an asset disposal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-disposal-assetDisposalNum/delete) |
| `GET` | `/asset/asset-disposal/{assetDisposalNum}` | Get an asset disposal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-disposal-assetDisposalNum/get) |
| `PUT` | `/asset/asset-disposal/{assetDisposalNum}` | Update an asset disposal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-disposal-assetDisposalNum/put) |
| `PATCH` | `/asset/asset-disposal/{assetDisposalNum}/authorize` | Authorize an asset disposal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-disposal-assetDisposalNum--authorize/patch) |
| `PATCH` | `/asset/asset-disposal/{assetDisposalNum}/reject` | Reject an asset disposal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-disposal-assetDisposalNum--reject/patch) |
| `GET` | `/asset/asset-maintenance` | List asset maintenance records | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-maintenance/get) |
| `POST` | `/asset/asset-maintenance` | Create new asset maintenance record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-maintenance/post) |
| `DELETE` | `/asset/asset-maintenance/{assetMaintenanceNum}` | Delete asset maintenance record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-maintenance-assetMaintenanceNum/delete) |
| `GET` | `/asset/asset-maintenance/{assetMaintenanceNum}` | Get asset maintenance details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-maintenance-assetMaintenanceNum/get) |
| `PUT` | `/asset/asset-maintenance/{assetMaintenanceNum}` | Update asset maintenance record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-maintenance-assetMaintenanceNum/put) |
| `PATCH` | `/asset/asset-maintenance/{assetMaintenanceNum}/authorize` | Authorize asset maintenance record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-maintenance-assetMaintenanceNum--authorize/patch) |
| `PATCH` | `/asset/asset-maintenance/{assetMaintenanceNum}/reject` | Reject asset maintenance record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-maintenance-assetMaintenanceNum--reject/patch) |
| `GET` | `/asset/asset-revaluation` | List asset revaluations | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation/get) |
| `POST` | `/asset/asset-revaluation` | Create new asset revaluation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation/post) |
| `POST` | `/asset/asset-revaluation-fiscal` | Create an asset revaluation fiscal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-fiscal/post) |
| `DELETE` | `/asset/asset-revaluation-fiscal/{assetRevaluationNum}` | Delete an asset revaluation fiscal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-fiscal-assetRevaluationNum/delete) |
| `GET` | `/asset/asset-revaluation-fiscal/{assetRevaluationNum}` | Get an asset revaluation fiscal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-fiscal-assetRevaluationNum/get) |
| `PUT` | `/asset/asset-revaluation-fiscal/{assetRevaluationNum}` | Update an asset revaluation fiscal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-fiscal-assetRevaluationNum/put) |
| `PATCH` | `/asset/asset-revaluation-fiscal/{assetRevaluationNum}/authorize` | Authorize an asset revaluation fiscal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-fiscal-assetRevaluationNum--authorize/patch) |
| `PATCH` | `/asset/asset-revaluation-fiscal/{assetRevaluationNum}/reject` | Reject an asset revaluation fiscal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-fiscal-assetRevaluationNum--reject/patch) |
| `POST` | `/asset/asset-revaluation-fiscal/{assetRevaluationNum}/validate` | Validate asset revaluation fiscal before modification | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-fiscal-assetRevaluationNum--validate/post) |
| `DELETE` | `/asset/asset-revaluation/{assetRevaluationNum}` | Delete asset revaluation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-assetRevaluationNum/delete) |
| `GET` | `/asset/asset-revaluation/{assetRevaluationNum}` | Get asset revaluation details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-assetRevaluationNum/get) |
| `PUT` | `/asset/asset-revaluation/{assetRevaluationNum}` | Update asset revaluation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-assetRevaluationNum/put) |
| `PATCH` | `/asset/asset-revaluation/{assetRevaluationNum}/authorize` | Authorize asset revaluation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-assetRevaluationNum--authorize/patch) |
| `PATCH` | `/asset/asset-revaluation/{assetRevaluationNum}/reject` | Reject asset revaluation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-assetRevaluationNum--reject/patch) |
| `POST` | `/asset/asset-revaluation/{assetRevaluationNum}/validate` | Validate asset revaluation before modification | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-revaluation-assetRevaluationNum--validate/post) |
| `GET` | `/asset/asset-sales` | List asset sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-sales/get) |
| `POST` | `/asset/asset-sales` | Create asset sales record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-sales/post) |
| `DELETE` | `/asset/asset-sales/{assetSalesNum}` | Delete asset sales record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-sales-assetSalesNum/delete) |
| `GET` | `/asset/asset-sales/{assetSalesNum}` | Get asset sales details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-sales-assetSalesNum/get) |
| `PUT` | `/asset/asset-sales/{assetSalesNum}` | Update asset sales record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-sales-assetSalesNum/put) |
| `PATCH` | `/asset/asset-sales/{assetSalesNum}/authorize` | Authorize asset sales record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-sales-assetSalesNum--authorize/patch) |
| `PATCH` | `/asset/asset-sales/{assetSalesNum}/reject` | Reject asset sales record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/asset-asset-sales-assetSalesNum--reject/patch) |

## Authentication

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/auth/login` | Authenticate user with credentials | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/auth-login/post) |
| `POST` | `/auth/logout` | Logout user | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/auth-logout/post) |
| `GET` | `/auth/me` | Get current user information | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/auth-me/get) |
| `GET` | `/auth/refresh` | Refresh access token | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/auth-refresh/get) |
| `POST` | `/auth/refresh-token` | Refresh access token with refresh token | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/auth-refresh-token/post) |
| `POST` | `/auth/session` | Check session and validate credentials | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/auth-session/post) |

## Bank Reconciliation

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/bank-reconciliation/browse-unreconciled-statements` | List unreconciled bank statements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/bank-reconciliation-browse-unreconciled-statements/get) |

## Branch Product

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/branch-product` | List branch products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-product/get) |
| `PUT` | `/branch-product/pull` | Pull branch products from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-product-pull/put) |
| `PUT` | `/branch-product/push` | Push branch products to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-product-push/put) |
| `PATCH` | `/branch-product/{branchID}` | Update branch products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-product-branchID/patch) |
| `GET` | `/branch-product/{branchID}/location` | Get locations for a branch | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-product-branchID--location/get) |
| `POST` | `/branch-product/{branchID}/{locationID}/upload` | Upload branch products for a location | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-product-branchID---locationID--upload/post) |
| `GET` | `/branch-product/{branchID}/{locationID}/{categoryID}` | Get branch products by category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-product-branchID---locationID---categoryID/get) |

## Branch Setting

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/branch-setting` | Get branch setting | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-setting/get) |

## Budget Adjustment

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/budget/budget-adjustment` | List budget adjustments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-adjustment/get) |
| `POST` | `/budget/budget-adjustment` | Create a budget adjustment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-adjustment/post) |
| `DELETE` | `/budget/budget-adjustment/{budgetAdjustmentNum}` | Delete a budget adjustment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-adjustment-budgetAdjustmentNum/delete) |
| `GET` | `/budget/budget-adjustment/{budgetAdjustmentNum}` | Get a budget adjustment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-adjustment-budgetAdjustmentNum/get) |
| `PUT` | `/budget/budget-adjustment/{budgetAdjustmentNum}` | Update a budget adjustment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-adjustment-budgetAdjustmentNum/put) |
| `PATCH` | `/budget/budget-adjustment/{budgetAdjustmentNum}/authorize` | Authorize a budget adjustment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-adjustment-budgetAdjustmentNum--authorize/patch) |
| `PATCH` | `/budget/budget-adjustment/{budgetAdjustmentNum}/reject` | Reject a budget adjustment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-adjustment-budgetAdjustmentNum--reject/patch) |

## Budget Allocate

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/budget/budget-allocate` | List budget allocations | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-allocate/get) |
| `POST` | `/budget/budget-allocate` | Create a budget allocation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-allocate/post) |
| `DELETE` | `/budget/budget-allocate/{budgetAdjustmentNum}` | Delete a budget allocation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-allocate-budgetAdjustmentNum/delete) |
| `GET` | `/budget/budget-allocate/{budgetAdjustmentNum}` | Get a budget allocation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-allocate-budgetAdjustmentNum/get) |
| `PUT` | `/budget/budget-allocate/{budgetAdjustmentNum}` | Update a budget allocation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-allocate-budgetAdjustmentNum/put) |
| `PATCH` | `/budget/budget-allocate/{budgetAdjustmentNum}/authorize` | Authorize a budget allocation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-allocate-budgetAdjustmentNum--authorize/patch) |
| `PATCH` | `/budget/budget-allocate/{budgetAdjustmentNum}/reject` | Reject a budget allocation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-budget-allocate-budgetAdjustmentNum--reject/patch) |

## Budget Detail

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/budgets` | List budgets | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets/get) |
| `POST` | `/budgets` | Create budget | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets/post) |
| `POST` | `/budgets/amount` | Get budget detail amount | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-amount/post) |
| `GET` | `/budgets/browse` | Browse budgets paginated | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-browse/get) |
| `GET` | `/budgets/browse/detail` | Browse budget detail amounts paginated | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-browse-detail/get) |
| `POST` | `/budgets/export-form` | Export budget form | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-export-form/post) |
| `POST` | `/budgets/import-form` | Import budget form | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-import-form/post) |
| `DELETE` | `/budgets/{budgetNum}` | Delete budget | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-budgetNum/delete) |
| `GET` | `/budgets/{budgetNum}` | Get budget by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-budgetNum/get) |
| `PUT` | `/budgets/{budgetNum}` | Update budget | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-budgetNum/put) |
| `PATCH` | `/budgets/{budgetNum}/authorize` | Authorize budget | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-budgetNum--authorize/patch) |
| `GET` | `/budgets/{budgetNum}/dropdown` | Get budget COA branches and cost centers dropdown | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-budgetNum--dropdown/get) |
| `PATCH` | `/budgets/{budgetNum}/reject` | Reject budget | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budgets-budgetNum--reject/patch) |

## Budget Plan

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/budget-plan` | List budget plans | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan/get) |
| `POST` | `/budget-plan` | Create a budget plan | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan/post) |
| `GET` | `/budget-plan/browse` | Browse budget plans | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-browse/get) |
| `DELETE` | `/budget-plan/{budgetPlanNum}` | Delete a budget plan | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-budgetPlanNum/delete) |
| `GET` | `/budget-plan/{budgetPlanNum}` | Get a budget plan | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-budgetPlanNum/get) |
| `PUT` | `/budget-plan/{budgetPlanNum}` | Update a budget plan | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-budgetPlanNum/put) |
| `PATCH` | `/budget-plan/{budgetPlanNum}/authorize` | Authorize a budget plan | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-budgetPlanNum--authorize/patch) |
| `GET` | `/budget-plan/{budgetPlanNum}/branches` | List budget plan branches | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-budgetPlanNum--branches/get) |
| `GET` | `/budget-plan/{budgetPlanNum}/branches/details` | Get budget plan detail form by branch | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-budgetPlanNum--branches-details/get) |
| `GET` | `/budget-plan/{budgetPlanNum}/chart-of-accounts` | List budget plan chart of accounts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-budgetPlanNum--chart-of-accounts/get) |
| `GET` | `/budget-plan/{budgetPlanNum}/chart-of-accounts/details` | Get budget plan detail form by chart of account | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-budgetPlanNum--chart-of-accounts-details/get) |
| `PATCH` | `/budget-plan/{budgetPlanNum}/reject` | Reject a budget plan | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/budget-plan-budgetPlanNum--reject/patch) |

## Cash Flow Category

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/cash-flow-category` | List cash flow categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category/get) |
| `POST` | `/cash-flow-category` | Create cash flow category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category/post) |
| `GET` | `/cash-flow-category/chart-of-accounts` | Browse chart of accounts for cash flow category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-chart-of-accounts/get) |
| `GET` | `/cash-flow-category/list` | List all cash flow categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-list/get) |
| `GET` | `/cash-flow-category/pull` | Pull cash flow category master data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-pull/get) |
| `GET` | `/cash-flow-category/push` | Push cash flow category master data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-push/get) |
| `GET` | `/cash-flow-category/unsorted-data` | List unsorted cash flow categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-unsorted-data/get) |
| `PUT` | `/cash-flow-category/update-sort` | Update cash flow category sort order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-update-sort/put) |
| `DELETE` | `/cash-flow-category/{cashFlowCategoryID}` | Delete cash flow category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-cashFlowCategoryID/delete) |
| `GET` | `/cash-flow-category/{cashFlowCategoryID}` | Get cash flow category by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-cashFlowCategoryID/get) |
| `PUT` | `/cash-flow-category/{cashFlowCategoryID}` | Update cash flow category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-cashFlowCategoryID/put) |
| `GET` | `/cash-flow-category/{cashFlowCategoryID}/other-used-account` | List COA numbers used by other categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-cashFlowCategoryID--other-used-account/get) |
| `PATCH` | `/cash-flow-category/{cashFlowCategoryID}/restore` | Restore deleted cash flow category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cash-flow-category-cashFlowCategoryID--restore/patch) |

## Chart of Account

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/accounting/coa` | List all COA accounts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa/get) |
| `POST` | `/accounting/coa` | Create a COA account | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa/post) |
| `GET` | `/accounting/coa/active-module/list` | List active module COA accounts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-active-module-list/get) |
| `GET` | `/accounting/coa/active-non-module` | List active non-module COA accounts (paginated) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-active-non-module/get) |
| `GET` | `/accounting/coa/active/by-level/{coaLevel}` | Get active COA by level | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-active-by-level-coaLevel/get) |
| `GET` | `/accounting/coa/before-after/{parentCoaNo}/{orderList}/{orderDirection}` | Find COA before/after a position | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-before-after-parentCoaNo---orderList---orderDirection/get) |
| `GET` | `/accounting/coa/budget-plan` | Browse budget plan accounts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-budget-plan/get) |
| `POST` | `/accounting/coa/budget-plan` | Get budget plan by COA numbers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-budget-plan/post) |
| `GET` | `/accounting/coa/by-level/{coaLevel}` | Get COA by level | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-by-level-coaLevel/get) |
| `GET` | `/accounting/coa/by-map` | Get COA by mapping | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-by-map/get) |
| `GET` | `/accounting/coa/cash-bank` | Find cash/bank COA accounts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-cash-bank/get) |
| `GET` | `/accounting/coa/coa-by-key` | Get COA by key | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-coa-by-key/get) |
| `GET` | `/accounting/coa/export` | Export COA accounts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-export/get) |
| `GET` | `/accounting/coa/findOne` | Find one COA by number and description | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-findOne/get) |
| `GET` | `/accounting/coa/freight-cost` | Get freight cost COA accounts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-freight-cost/get) |
| `GET` | `/accounting/coa/index` | List COA accounts (index) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-index/get) |
| `GET` | `/accounting/coa/mapCoa/findOne` | Find one mapped COA by number and key | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-mapCoa-findOne/get) |
| `PUT` | `/accounting/coa/pull` | Pull COA from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-pull/put) |
| `PUT` | `/accounting/coa/push` | Push COA to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-push/put) |
| `PATCH` | `/accounting/coa/reorder` | Reorder COA accounts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-reorder/patch) |
| `GET` | `/accounting/coa/tax` | Get tax COA accounts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-tax/get) |
| `POST` | `/accounting/coa/upload` | Upload COA and account mapping (Excel) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-upload/post) |
| `DELETE` | `/accounting/coa/{coaNo}` | Delete a COA account | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-coaNo/delete) |
| `GET` | `/accounting/coa/{coaNo}` | Get a COA by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-coaNo/get) |
| `PUT` | `/accounting/coa/{coaNo}` | Update a COA account | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-coaNo/put) |
| `PATCH` | `/accounting/coa/{coaNo}/restore` | Restore a deleted COA account | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-coaNo--restore/patch) |

## Close Delivery

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/close-delivery` | List close deliveries | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery/get) |
| `POST` | `/close-delivery` | Create a close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery/post) |
| `GET` | `/close-delivery/goods-delivery-branch` | Get goods delivery branches | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-goods-delivery-branch/get) |
| `GET` | `/close-delivery/goods-delivery-reference/{goodsDeliveryNum}` | Get goods delivery reference | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-goods-delivery-reference-goodsDeliveryNum/get) |
| `GET` | `/close-delivery/initial/{refNum}` | Get initial data for close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-initial-refNum/get) |
| `GET` | `/close-delivery/validate/{goodsDeliveryNum}` | Validate a goods delivery for close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-validate-goodsDeliveryNum/get) |
| `DELETE` | `/close-delivery/{closeDeliveryNum}` | Delete a close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-closeDeliveryNum/delete) |
| `GET` | `/close-delivery/{closeDeliveryNum}` | Get a close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-closeDeliveryNum/get) |
| `PUT` | `/close-delivery/{closeDeliveryNum}` | Update a close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-closeDeliveryNum/put) |
| `DELETE` | `/close-delivery/{closeDeliveryNum}/attachment` | Delete attachment for close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-closeDeliveryNum--attachment/delete) |
| `PATCH` | `/close-delivery/{closeDeliveryNum}/attachment` | Upload attachment for close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-closeDeliveryNum--attachment/patch) |
| `PATCH` | `/close-delivery/{closeDeliveryNum}/authorize` | Authorize a close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-closeDeliveryNum--authorize/patch) |
| `PATCH` | `/close-delivery/{closeDeliveryNum}/reject` | Reject a close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-closeDeliveryNum--reject/patch) |
| `GET` | `/close-delivery/{closeDeliveryNum}/stock-check` | Check stock for close delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/close-delivery-closeDeliveryNum--stock-check/get) |

## Common

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/common/area/branches` | List branches by area IDs | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-area-branches/get) |
| `GET` | `/common/bom-type/findByIDs` | Get BOM types by IDs | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-bom-type-findByIDs/get) |
| `GET` | `/common/bom-type/{bomTypeID}` | Get BOM type by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-bom-type-bomTypeID/get) |
| `GET` | `/common/cash-flow-category-types/list` | List all cash flow category types | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-cash-flow-category-types-list/get) |
| `GET` | `/common/cash-flow-methods/list` | List cash flow methods | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-cash-flow-methods-list/get) |
| `GET` | `/common/cash-flow-types/list` | List all cash flow types | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-cash-flow-types-list/get) |
| `GET` | `/common/category-types/list` | List all category types | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-category-types-list/get) |
| `GET` | `/common/currency` | List currencies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency/get) |
| `POST` | `/common/currency` | Create a new currency | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency/post) |
| `GET` | `/common/currency/active` | Get active currencies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-active/get) |
| `GET` | `/common/currency/findOne` | Find single currency | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-findOne/get) |
| `GET` | `/common/currency/list` | List all currencies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-list/get) |
| `GET` | `/common/currency/other-currencies` | List other currencies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-other-currencies/get) |
| `PUT` | `/common/currency/pull` | Pull currencies from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-pull/put) |
| `PUT` | `/common/currency/push` | Push currencies to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-push/put) |
| `DELETE` | `/common/currency/{currencyID}` | Delete currency | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-currencyID/delete) |
| `GET` | `/common/currency/{currencyID}` | Get currency details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-currencyID/get) |
| `PUT` | `/common/currency/{currencyID}` | Update currency | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-currencyID/put) |
| `PATCH` | `/common/currency/{currencyID}/restore` | Restore currency | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-currency-currencyID--restore/patch) |
| `GET` | `/common/metrics` | List all metrics | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-metrics/get) |
| `GET` | `/common/recurring-type` | List all recurring types | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-recurring-type/get) |
| `GET` | `/common/reftax` | List all reference tax codes | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-reftax/get) |
| `GET` | `/common/revaluation-types/list` | List revaluation types | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-revaluation-types-list/get) |
| `GET` | `/common/status` | Get all statuses | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-status/get) |
| `GET` | `/common/status/mapping` | Get status mapping | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-status-mapping/get) |
| `GET` | `/common/transaction-print-type/layout-setting` | Get layout settings | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/common-transaction-print-type-layout-setting/get) |

## Company

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/setting/company-logo` | Get company logo URL | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/setting-company-logo/get) |
| `GET` | `/setting/detail` | Get company detail settings | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/setting-detail/get) |
| `PATCH` | `/setting/detail` | Update company detail settings | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/setting-detail/patch) |
| `GET` | `/setting/findOne` | Find one company setting by keys | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/setting-findOne/get) |

## Company Bank

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/company-bank` | List company banks (paginated) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/company-bank/get) |
| `POST` | `/company-bank` | Create a company bank | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/company-bank/post) |
| `GET` | `/company-bank/default` | Get default company bank | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/company-bank-default/get) |
| `GET` | `/company-bank/list` | List company banks | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/company-bank-list/get) |
| `DELETE` | `/company-bank/{companyBankID}` | Delete a company bank | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/company-bank-companyBankID/delete) |
| `GET` | `/company-bank/{companyBankID}` | Get a company bank | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/company-bank-companyBankID/get) |
| `PUT` | `/company-bank/{companyBankID}` | Update a company bank | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/company-bank-companyBankID/put) |
| `PATCH` | `/company-bank/{companyBankID}/restore` | Restore a company bank | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/company-bank-companyBankID--restore/patch) |

## Cross Transaction

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/cross-transaction` | List cross transactions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cross-transaction/get) |

## Customer Advance

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/customer-advance-settlement` | List customer advance settlements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-advance-settlement/get) |
| `POST` | `/customer-advance-settlement` | Create customer advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-advance-settlement/post) |
| `DELETE` | `/customer-advance-settlement/{customerAdvanceSettlementNum}` | Delete customer advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-advance-settlement-customerAdvanceSettlementNum/delete) |
| `GET` | `/customer-advance-settlement/{customerAdvanceSettlementNum}` | Get a customer advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-advance-settlement-customerAdvanceSettlementNum/get) |
| `PUT` | `/customer-advance-settlement/{customerAdvanceSettlementNum}` | Update customer advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-advance-settlement-customerAdvanceSettlementNum/put) |
| `PATCH` | `/customer-advance-settlement/{customerAdvanceSettlementNum}/authorize` | Authorize and approve customer advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-advance-settlement-customerAdvanceSettlementNum--authorize/patch) |
| `GET` | `/customer-advance-settlement/{customerAdvanceSettlementNum}/print` | Get printable customer advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-advance-settlement-customerAdvanceSettlementNum--print/get) |
| `PATCH` | `/customer-advance-settlement/{customerAdvanceSettlementNum}/reject` | Reject customer advance settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-advance-settlement-customerAdvanceSettlementNum--reject/patch) |
| `GET` | `/customer-advance/payment/product/{productSalesNum}` | Find customer advance payment by product sales number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-advance-payment-product-productSalesNum/get) |
| `GET` | `/customer-settlement` | List customer settlements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-settlement/get) |
| `GET` | `/sales/advance-sales` | List customer advance payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales/get) |
| `POST` | `/sales/advance-sales` | Create a customer advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales/post) |
| `POST` | `/sales/advance-sales/upload` | Upload customer advance payments from Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales-upload/post) |
| `DELETE` | `/sales/advance-sales/{customerAdvancePaymentNum}` | Delete a customer advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales-customerAdvancePaymentNum/delete) |
| `GET` | `/sales/advance-sales/{customerAdvancePaymentNum}` | Get a customer advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales-customerAdvancePaymentNum/get) |
| `PUT` | `/sales/advance-sales/{customerAdvancePaymentNum}` | Update a customer advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales-customerAdvancePaymentNum/put) |
| `DELETE` | `/sales/advance-sales/{customerAdvancePaymentNum}/attachment` | Delete attachment from customer advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales-customerAdvancePaymentNum--attachment/delete) |
| `PATCH` | `/sales/advance-sales/{customerAdvancePaymentNum}/attachment` | Upload attachments to customer advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales-customerAdvancePaymentNum--attachment/patch) |
| `PATCH` | `/sales/advance-sales/{customerAdvancePaymentNum}/authorize` | Authorize a customer advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales-customerAdvancePaymentNum--authorize/patch) |
| `PATCH` | `/sales/advance-sales/{customerAdvancePaymentNum}/reject` | Reject a customer advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-advance-sales-customerAdvancePaymentNum--reject/patch) |

## Customer Bank

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/customer/bank` | List customer banks | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-bank/get) |

## Customer Branch

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/customer/branch` | List all customer branches | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-branch/get) |

## Customer Category

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/customer/category` | List customer categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category/get) |

## Customer Category Pricelist

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/customer-category-pricelist` | List customer category pricelists | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist/get) |
| `POST` | `/customer-category-pricelist` | Create customer category pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist/post) |
| `GET` | `/customer-category-pricelist/check` | Check if customer category pricelist exists | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist-check/get) |
| `GET` | `/customer-category-pricelist/export` | Export customer category pricelist to XLSX file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist-export/get) |
| `POST` | `/customer-category-pricelist/import` | Import customer category pricelist from XLSX file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist-import/post) |
| `PUT` | `/customer-category-pricelist/pull` | Pull customer category pricelist from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist-pull/put) |
| `PUT` | `/customer-category-pricelist/push` | Push customer category pricelist to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist-push/put) |
| `DELETE` | `/customer-category-pricelist/{id}` | Delete customer category pricelist by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist-id/delete) |
| `GET` | `/customer-category-pricelist/{id}` | Get customer category pricelist by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist-id/get) |
| `PATCH` | `/customer-category-pricelist/{id}` | Update customer category pricelist by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-category-pricelist-id/patch) |

## Customer Invoice

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/customer-invoice/by-customer` | Get customer invoice by customer ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-invoice-by-customer/get) |

## Customer Limit Receivable

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/customer-limit-receivable/import` | Import customer limit receivable data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-limit-receivable-import/post) |

## Customer Receivable

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/customer-receivable` | List customer receivables | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-receivable/get) |
| `GET` | `/customer-receivable/available-limit` | Get available credit limit | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-receivable-available-limit/get) |
| `GET` | `/customer-receivable/details` | List customer receivable details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-receivable-details/get) |
| `GET` | `/customer-receivable/export` | Export customer receivables | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-receivable-export/get) |
| `GET` | `/customer-receivable/export-details` | Export customer receivable details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-receivable-export-details/get) |
| `GET` | `/customer-receivable/limit-details` | List credit limit transactions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-receivable-limit-details/get) |
| `GET` | `/customer-receivable/receivable-date/earliest` | Get earliest receivable date | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-receivable-receivable-date-earliest/get) |
| `GET` | `/customer-receivable/summary` | Get customer receivable summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-receivable-summary/get) |

## Depreciation Period

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/depreciation-period` | List depreciation periods | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/depreciation-period/get) |
| `POST` | `/depreciation-period` | Create depreciation period | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/depreciation-period/post) |
| `DELETE` | `/depreciation-period/{ID}` | Delete depreciation period | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/depreciation-period-ID/delete) |
| `GET` | `/depreciation-period/{ID}` | Get depreciation period details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/depreciation-period-ID/get) |

## Disbursement

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/finance/disbursement` | List disbursements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement/get) |
| `POST` | `/finance/disbursement` | Create disbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement/post) |
| `POST` | `/finance/disbursement/import` | Import disbursement data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-import/post) |
| `POST` | `/finance/disbursement/import/form` | Upload disbursement form | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-import-form/post) |
| `DELETE` | `/finance/disbursement/{disbursementNum}` | Delete disbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum/delete) |
| `GET` | `/finance/disbursement/{disbursementNum}` | Get disbursement by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum/get) |
| `PUT` | `/finance/disbursement/{disbursementNum}` | Update disbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum/put) |
| `PATCH` | `/finance/disbursement/{disbursementNum}/attachment` | Upload disbursement attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum--attachment/patch) |
| `PATCH` | `/finance/disbursement/{disbursementNum}/authorize` | Authorize disbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum--authorize/patch) |
| `PATCH` | `/finance/disbursement/{disbursementNum}/reject` | Reject disbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum--reject/patch) |
| `PUT` | `/finance/disbursement/{disbursementNum}/validate` | Validate disbursement update | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum--validate/put) |

## Disbursement Online

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/dashboard/disbursement-online` | Get disbursement online listings | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/dashboard-disbursement-online/get) |
| `GET` | `/dashboard/disbursement-online/aggregate` | Get disbursement aggregate statistics | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/dashboard-disbursement-online-aggregate/get) |
| `POST` | `/dashboard/disbursement-online/pending` | Get pending disbursements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/dashboard-disbursement-online-pending/post) |
| `GET` | `/dashboard/disbursement-online/{disburseNum}` | Get disbursement transactions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/dashboard-disbursement-online-disburseNum/get) |
| `GET` | `/dashboard/disbursement-online/{disburseNum}/summary` | Get disbursement summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/dashboard-disbursement-online-disburseNum--summary/get) |

## Email Recipient

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/email-recipient` | List email recipients | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/email-recipient/get) |
| `POST` | `/email-recipient` | Create email recipient | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/email-recipient/post) |
| `GET` | `/email-recipient/types` | List email recipient transaction types | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/email-recipient-types/get) |
| `DELETE` | `/email-recipient/{emailRecipientID}` | Delete email recipient | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/email-recipient-emailRecipientID/delete) |
| `GET` | `/email-recipient/{emailRecipientID}` | Get email recipient by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/email-recipient-emailRecipientID/get) |
| `PUT` | `/email-recipient/{emailRecipientID}` | Update email recipient | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/email-recipient-emailRecipientID/put) |
| `GET` | `/email-recipient/{emailRecipientID}/branches` | List branches of an email recipient | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/email-recipient-emailRecipientID--branches/get) |

## Employee

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/employee/all` | List all employees | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-all/get) |

## Employee Advance Payment

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/employee/employee-advance-payment` | List employee advance payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-advance-payment/get) |
| `POST` | `/employee/employee-advance-payment` | Create an employee advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-advance-payment/post) |
| `POST` | `/employee/employee-advance-payment/upload` | Bulk import employee advance payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-advance-payment-upload/post) |
| `DELETE` | `/employee/employee-advance-payment/{employeeAdvanceNum}` | Delete an employee advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-advance-payment-employeeAdvanceNum/delete) |
| `GET` | `/employee/employee-advance-payment/{employeeAdvanceNum}` | Get an employee advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-advance-payment-employeeAdvanceNum/get) |
| `PUT` | `/employee/employee-advance-payment/{employeeAdvanceNum}` | Update an employee advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-advance-payment-employeeAdvanceNum/put) |
| `PATCH` | `/employee/employee-advance-payment/{employeeAdvanceNum}/authorize` | Authorize an employee advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-advance-payment-employeeAdvanceNum--authorize/patch) |
| `PATCH` | `/employee/employee-advance-payment/{employeeAdvanceNum}/reject` | Reject an employee advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-advance-payment-employeeAdvanceNum--reject/patch) |
| `POST` | `/employee/employee-advance-payment/{refNum}/asset-images` | Upload asset images for an employee advance payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-advance-payment-refNum--asset-images/post) |

## Employee Reimbursement

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/employee/employee-reimbursement` | List employee reimbursements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-reimbursement/get) |
| `POST` | `/employee/employee-reimbursement` | Create new employee reimbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-reimbursement/post) |
| `DELETE` | `/employee/employee-reimbursement/{employeeReimbursementNum}` | Delete employee reimbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-reimbursement-employeeReimbursementNum/delete) |
| `GET` | `/employee/employee-reimbursement/{employeeReimbursementNum}` | Get employee reimbursement details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-reimbursement-employeeReimbursementNum/get) |
| `PUT` | `/employee/employee-reimbursement/{employeeReimbursementNum}` | Update employee reimbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-reimbursement-employeeReimbursementNum/put) |
| `PATCH` | `/employee/employee-reimbursement/{employeeReimbursementNum}/authorize` | Authorize employee reimbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-reimbursement-employeeReimbursementNum--authorize/patch) |
| `PATCH` | `/employee/employee-reimbursement/{employeeReimbursementNum}/print` | Mark reimbursement as printed | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-reimbursement-employeeReimbursementNum--print/patch) |
| `PATCH` | `/employee/employee-reimbursement/{employeeReimbursementNum}/reject` | Reject employee reimbursement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-reimbursement-employeeReimbursementNum--reject/patch) |
| `POST` | `/employee/employee-reimbursement/{refNum}/asset-images` | Upload reimbursement asset images | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/employee-employee-reimbursement-refNum--asset-images/post) |

## End Period

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/end-period` | List end periods | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period/get) |
| `POST` | `/end-period` | Create end period | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period/post) |
| `GET` | `/end-period-stock` | List end period stocks | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period-stock/get) |
| `POST` | `/end-period-stock` | Create end period stock | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period-stock/post) |
| `DELETE` | `/end-period-stock/{ID}` | Delete end period stock | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period-stock-ID/delete) |
| `GET` | `/end-period-stock/{ID}` | Get end period stock by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period-stock-ID/get) |
| `GET` | `/end-period/max-date-generate-fiscal` | Get max date for fiscal generation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period-max-date-generate-fiscal/get) |
| `GET` | `/end-period/validate` | Validate end period preconditions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period-validate/get) |
| `DELETE` | `/end-period/{ID}` | Delete end period | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period-ID/delete) |
| `GET` | `/end-period/{ID}` | Get end period by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period-ID/get) |
| `POST` | `/end-period/{ID}/generate-fiscal` | Generate fiscal data for end period | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/end-period-ID--generate-fiscal/post) |

## Food Cost Calculator

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/food-cost-calculator` | Create a bill of materials | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/food-cost-calculator/post) |

## General Journal

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/accounting/general-journal/export` | Export general journals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-general-journal-export/get) |
| `GET` | `/accounting/general-journal/search` | List general journals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-general-journal-search/get) |
| `GET` | `/accounting/general-journal/{journalHeadID}/detail` | Get general journal detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-general-journal-journalHeadID--detail/get) |

## General Ledger

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/accounting/general-ledger` | List general ledger entries | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-general-ledger/post) |
| `POST` | `/accounting/general-ledger/summary` | List general ledger summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-general-ledger-summary/post) |

## Goods Delivery

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/goods-delivery` | List goods deliveries with pagination | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery/get) |
| `GET` | `/inventory/goods-delivery/create/{refNum}` | Get goods delivery template for creation from reference | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-create-refNum/get) |
| `GET` | `/inventory/goods-delivery/get-details-outstanding-qty` | Get outstanding quantity per detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-get-details-outstanding-qty/get) |
| `POST` | `/inventory/goods-delivery/get-products` | Get products by product detail IDs | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-get-products/post) |
| `POST` | `/inventory/goods-delivery/multi-address` | Upload multi-address goods delivery from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-multi-address/post) |
| `DELETE` | `/inventory/goods-delivery/{goodsDeliveryNum}` | Delete a goods delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum/delete) |
| `GET` | `/inventory/goods-delivery/{goodsDeliveryNum}` | Get goods delivery by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum/get) |
| `PUT` | `/inventory/goods-delivery/{goodsDeliveryNum}` | Update a goods delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum/put) |
| `DELETE` | `/inventory/goods-delivery/{goodsDeliveryNum}/attachment` | Delete all attachments for a goods delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum--attachment/delete) |
| `PATCH` | `/inventory/goods-delivery/{goodsDeliveryNum}/attachment` | Upload attachments for goods delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum--attachment/patch) |
| `PATCH` | `/inventory/goods-delivery/{goodsDeliveryNum}/authorize` | Authorize a goods delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum--authorize/patch) |
| `PATCH` | `/inventory/goods-delivery/{goodsDeliveryNum}/reject` | Reject a goods delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum--reject/patch) |
| `PATCH` | `/inventory/goods-delivery/{goodsDeliveryNum}/upload/attachment` | Upload attachments for goods delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-goodsDeliveryNum--upload-attachment/patch) |
| `POST` | `/inventory/goods-delivery/{refNum}` | Create a new goods delivery from reference number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-delivery-refNum/post) |

## Goods Delivery Asset

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/goods-delivery-asset/{goodsDeliveryNum}` | Get goods delivery assets by goods delivery number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-asset-goodsDeliveryNum/get) |

## Goods Delivery Expiration

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/goods-delivery-expiration/{goodsDeliveryNum}` | Get expiration details by goods delivery number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-expiration-goodsDeliveryNum/get) |

## Goods Delivery Return

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/goods-delivery-return` | List goods delivery returns | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return/get) |
| `POST` | `/goods-delivery-return` | Create goods delivery return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return/post) |
| `GET` | `/goods-delivery-return/browse-detail` | Browse goods delivery return details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-browse-detail/get) |
| `GET` | `/goods-delivery-return/browse-goods-delivery` | Browse goods deliveries for return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-browse-goods-delivery/get) |
| `GET` | `/goods-delivery-return/fill-details` | Get return details for goods deliveries | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-fill-details/get) |
| `POST` | `/goods-delivery-return/template-multi-address/export` | Export multi-address template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-template-multi-address-export/post) |
| `POST` | `/goods-delivery-return/template-multi-address/upload` | Upload multi-address template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-template-multi-address-upload/post) |
| `DELETE` | `/goods-delivery-return/{goodsDeliveryReturnNum}` | Delete goods delivery return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-goodsDeliveryReturnNum/delete) |
| `GET` | `/goods-delivery-return/{goodsDeliveryReturnNum}` | Get goods delivery return details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-goodsDeliveryReturnNum/get) |
| `PUT` | `/goods-delivery-return/{goodsDeliveryReturnNum}` | Update goods delivery return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-goodsDeliveryReturnNum/put) |
| `PATCH` | `/goods-delivery-return/{goodsDeliveryReturnNum}/authorize` | Authorize goods delivery return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-goodsDeliveryReturnNum--authorize/patch) |
| `PATCH` | `/goods-delivery-return/{goodsDeliveryReturnNum}/reject` | Reject goods delivery return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-goodsDeliveryReturnNum--reject/patch) |
| `POST` | `/goods-delivery-return/{goodsDeliveryReturnNum}/validate` | Validate goods delivery return before save | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-goodsDeliveryReturnNum--validate/post) |

## Goods Delivery Return Expiration

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/goods-delivery-return-expiration/delivery-return/{goodsDeliveryReturnNum}` | Get expiration data for a goods delivery return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/goods-delivery-return-expiration-delivery-return-goodsDeliveryReturnNum/get) |

## Goods Receipt

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/goods-receipt` | List goods receipts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt/get) |
| `GET` | `/inventory/goods-receipt/browse-return` | Browse goods receipt return data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-browse-return/get) |
| `GET` | `/inventory/goods-receipt/initialize` | Get initial data for a goods receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-initialize/get) |
| `GET` | `/inventory/goods-receipt/return-details` | Get goods receipt return details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-details/get) |
| `GET` | `/inventory/goods-receipt/stock-check/{goodsReceiptNum}` | Check stock for a goods receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-stock-check-goodsReceiptNum/get) |
| `DELETE` | `/inventory/goods-receipt/{goodsReceiptNum}` | Delete a goods receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-goodsReceiptNum/delete) |
| `GET` | `/inventory/goods-receipt/{goodsReceiptNum}` | Get a goods receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-goodsReceiptNum/get) |
| `PUT` | `/inventory/goods-receipt/{goodsReceiptNum}` | Update a goods receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-goodsReceiptNum/put) |
| `PATCH` | `/inventory/goods-receipt/{goodsReceiptNum}/authorize` | Authorize a goods receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-goodsReceiptNum--authorize/patch) |
| `PATCH` | `/inventory/goods-receipt/{goodsReceiptNum}/reject` | Reject a goods receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-goodsReceiptNum--reject/patch) |
| `POST` | `/inventory/goods-receipt/{refNum}` | Create a goods receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-refNum/post) |
| `POST` | `/inventory/goods-receipt/{refNum}/asset-images` | Upload asset images for a goods receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-refNum--asset-images/post) |

## Goods Receipt Expiration

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/goods-receipt-expiration/receipt/{goodsReceiptNum}` | Get goods receipt expirations by receipt number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-expiration-receipt-goodsReceiptNum/get) |

## Goods Receipt Return

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/goods-receipt-return` | List goods receipt returns | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return/get) |
| `POST` | `/inventory/goods-receipt-return` | Create a goods receipt return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return/post) |
| `DELETE` | `/inventory/goods-receipt-return/{goodsReceiptReturnNum}` | Delete a goods receipt return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-goodsReceiptReturnNum/delete) |
| `GET` | `/inventory/goods-receipt-return/{goodsReceiptReturnNum}` | Get a goods receipt return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-goodsReceiptReturnNum/get) |
| `PUT` | `/inventory/goods-receipt-return/{goodsReceiptReturnNum}` | Update a goods receipt return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-goodsReceiptReturnNum/put) |
| `DELETE` | `/inventory/goods-receipt-return/{goodsReceiptReturnNum}/attachment` | Delete goods receipt return attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-goodsReceiptReturnNum--attachment/delete) |
| `PATCH` | `/inventory/goods-receipt-return/{goodsReceiptReturnNum}/attachment` | Upload goods receipt return attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-goodsReceiptReturnNum--attachment/patch) |
| `PATCH` | `/inventory/goods-receipt-return/{goodsReceiptReturnNum}/authorize` | Authorize a goods receipt return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-goodsReceiptReturnNum--authorize/patch) |
| `PATCH` | `/inventory/goods-receipt-return/{goodsReceiptReturnNum}/reject` | Reject a goods receipt return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-goodsReceiptReturnNum--reject/patch) |
| `POST` | `/inventory/goods-receipt-return/{goodsReceiptReturnNum}/validate` | Validate goods receipt return payload | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-goodsReceiptReturnNum--validate/post) |

## Goods Receipt Return Expiration

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/goods-receipt-return-expiration/receipt-return/{goodsReceiptReturnNum}` | Get expirations by goods receipt return number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-receipt-return-expiration-receipt-return-goodsReceiptReturnNum/get) |

## Goods Transfer Request

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/goods-transfer-request` | List goods transfer requests | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request/get) |
| `POST` | `/inventory/goods-transfer-request` | Create goods transfer request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request/post) |
| `POST` | `/inventory/goods-transfer-request/detail` | Get goods transfer request product details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-detail/post) |
| `DELETE` | `/inventory/goods-transfer-request/{transferNum}` | Delete goods transfer request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum/delete) |
| `GET` | `/inventory/goods-transfer-request/{transferNum}` | Get goods transfer request by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum/get) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}` | Update goods transfer request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum/patch) |
| `DELETE` | `/inventory/goods-transfer-request/{transferNum}/attachment` | Delete attachment from goods transfer request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--attachment/delete) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}/attachment` | Upload attachment to goods transfer request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--attachment/patch) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}/authorize` | Authorize goods transfer request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--authorize/patch) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}/finish` | Finish goods transfer request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--finish/patch) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}/reject` | Reject goods transfer request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--reject/patch) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}/unfinished` | Unfinish goods transfer request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-goods-transfer-request-transferNum--unfinished/patch) |

## Import Document

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/import-document` | List import documents | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/import-document/get) |
| `POST` | `/import-document` | Create an import document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/import-document/post) |
| `GET` | `/import-document-head/refnum/{refNum}` | Get import documents by reference number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/import-document-head-refnum-refNum/get) |
| `DELETE` | `/import-document/{importDocumentNum}` | Delete an import document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/import-document-importDocumentNum/delete) |
| `GET` | `/import-document/{importDocumentNum}` | Get an import document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/import-document-importDocumentNum/get) |
| `PUT` | `/import-document/{importDocumentNum}` | Update an import document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/import-document-importDocumentNum/put) |
| `PATCH` | `/import-document/{importDocumentNum}/authorize-or-reject` | Authorize or reject an import document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/import-document-importDocumentNum--authorize-or-reject/patch) |
| `GET` | `/import-document/{importDocumentNum}/validate` | Validate an import document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/import-document-importDocumentNum--validate/get) |

## InterCash

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/intercash` | Get paginated intercash transactions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/intercash/get) |
| `POST` | `/intercash` | Create a new intercash transaction | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/intercash/post) |
| `DELETE` | `/intercash/{intercashNum}` | Delete an intercash transaction | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/intercash-intercashNum/delete) |
| `GET` | `/intercash/{intercashNum}` | Get intercash transaction by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/intercash-intercashNum/get) |
| `PUT` | `/intercash/{intercashNum}` | Update an intercash transaction | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/intercash-intercashNum/put) |
| `DELETE` | `/intercash/{intercashNum}/attachment` | Delete attachment for intercash transaction | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/intercash-intercashNum--attachment/delete) |
| `PATCH` | `/intercash/{intercashNum}/attachment` | Upload attachment for intercash transaction | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/intercash-intercashNum--attachment/patch) |
| `PATCH` | `/intercash/{intercashNum}/authorize` | Authorize an intercash transaction | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/intercash-intercashNum--authorize/patch) |
| `PATCH` | `/intercash/{intercashNum}/reject` | Reject an intercash transaction | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/intercash-intercashNum--reject/patch) |

## Inventory Dashboard

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/inventory-dashboard` | Get inventory dashboard detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard/get) |
| `GET` | `/inventory/inventory-dashboard/branch-detail` | Get branch inventory detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-branch-detail/get) |
| `GET` | `/inventory/inventory-dashboard/branch-stock` | Get branch product stock quantity | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-branch-stock/get) |
| `GET` | `/inventory/inventory-dashboard/branch-stock-summary` | Get branch stock summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-branch-stock-summary/get) |
| `GET` | `/inventory/inventory-dashboard/export` | Export inventory dashboard detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-export/get) |
| `GET` | `/inventory/inventory-dashboard/goods-delivery-status` | Get goods delivery status | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-goods-delivery-status/get) |
| `GET` | `/inventory/inventory-dashboard/goods-receipt-status` | Get goods receipt status | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-goods-receipt-status/get) |
| `GET` | `/inventory/inventory-dashboard/list-branch/{branchID}` | Get branch selection for inventory dashboard | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-list-branch-branchID/get) |
| `GET` | `/inventory/inventory-dashboard/smart-feed/dead-stock` | Get smart feed dead stock | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-smart-feed-dead-stock/get) |
| `GET` | `/inventory/inventory-dashboard/smart-feed/overdue-po` | Get smart feed overdue purchase orders | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-smart-feed-overdue-po/get) |
| `GET` | `/inventory/inventory-dashboard/smart-feed/overdue-so` | Get smart feed overdue sales orders | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-dashboard-smart-feed-overdue-so/get) |

## Inventory Revaluation

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/inventory-revaluation` | List inventory revaluations | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-revaluation/get) |
| `POST` | `/inventory/inventory-revaluation` | Create inventory revaluation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-revaluation/post) |
| `GET` | `/inventory/inventory-revaluation/products` | List products for inventory revaluation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-revaluation-products/get) |
| `DELETE` | `/inventory/inventory-revaluation/{inventoryRevaluationNum}` | Delete inventory revaluation | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-revaluation-inventoryRevaluationNum/delete) |
| `GET` | `/inventory/inventory-revaluation/{inventoryRevaluationNum}` | Get inventory revaluation by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-inventory-revaluation-inventoryRevaluationNum/get) |

## Item Journal

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/item-journal` | List item journals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal/get) |
| `POST` | `/inventory/item-journal` | Create an item journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal/post) |
| `DELETE` | `/inventory/item-journal/{itemJournalNum}` | Delete an item journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum/delete) |
| `GET` | `/inventory/item-journal/{itemJournalNum}` | Get an item journal by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum/get) |
| `PUT` | `/inventory/item-journal/{itemJournalNum}` | Update an item journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum/put) |
| `DELETE` | `/inventory/item-journal/{itemJournalNum}/attachment` | Delete item journal attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum--attachment/delete) |
| `PATCH` | `/inventory/item-journal/{itemJournalNum}/attachment` | Upload item journal attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum--attachment/patch) |
| `PATCH` | `/inventory/item-journal/{itemJournalNum}/authorize` | Authorize an item journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum--authorize/patch) |
| `PATCH` | `/inventory/item-journal/{itemJournalNum}/reject` | Reject an item journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-item-journal-itemJournalNum--reject/patch) |

## Location

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/location` | List all locations | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/location/get) |
| `GET` | `/location/branch/{branchID}` | Get warehouse and kitchen locations by branch | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/location-branch-branchID/get) |
| `GET` | `/location/find-one` | Find a single location by filter | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/location-find-one/get) |
| `GET` | `/location/findOne` | Find one location by filters | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/location-findOne/get) |
| `GET` | `/location/list` | List locations by filter | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/location-list/get) |
| `GET` | `/location/non-production-area` | Get non-production area locations | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/location-non-production-area/get) |
| `GET` | `/location/user` | Get user's assigned locations | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/location-user/get) |
| `GET` | `/location/user/warehouse-kitchen` | Get user's warehouse and kitchen locations | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/location-user-warehouse-kitchen/get) |
| `GET` | `/location/{locationID}` | Get location by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/location-locationID/get) |

## Map Asset Images

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/map-assetimages/ref/{refNum}` | Get asset images by reference number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/map-assetimages-ref-refNum/get) |
| `POST` | `/map-assetimages/{transactionName}/attachment/{refNum}` | Save attachment to map asset images | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/map-assetimages-transactionName--attachment--refNum/post) |

## Map COA

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/accounting/coa/account-mapping` | List COA account mappings | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-account-mapping/get) |
| `POST` | `/accounting/coa/account-mapping` | Save COA account mappings | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-account-mapping/post) |
| `GET` | `/accounting/coa/account-mapping/dropdown` | List unselected required COA keys | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-account-mapping-dropdown/get) |
| `GET` | `/accounting/coa/account-mapping/key` | Find COA mapping by key | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-account-mapping-key/get) |
| `GET` | `/accounting/coa/account-mapping/key-type` | Find COA mapping by key and type | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-account-mapping-key-type/get) |
| `GET` | `/accounting/coa/account-mapping/map-dropdown` | Get COA mapping dropdown options | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-account-mapping-map-dropdown/get) |
| `GET` | `/accounting/coa/account-mapping/map-profit-loss` | Get profit/loss COA mapping options | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-account-mapping-map-profit-loss/get) |
| `GET` | `/accounting/coa/account-mapping/unselected` | List unselected required COA keys | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-coa-account-mapping-unselected/get) |

## Master Bill of Material

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/product/bom` | List bill of materials | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom/get) |
| `POST` | `/product/bom` | Create a bill of material | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom/post) |
| `GET` | `/product/bom/browse` | Browse bill of materials | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-browse/get) |
| `GET` | `/product/bom/check-bom-infinite` | Check for infinite BOM cycles | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-check-bom-infinite/get) |
| `GET` | `/product/bom/detail/{bomID}` | Get BOM details by BOM ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-detail-bomID/get) |
| `GET` | `/product/bom/export` | Export bill of materials | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-export/get) |
| `GET` | `/product/bom/export/template` | Export BOM update template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-export-template/get) |
| `PUT` | `/product/bom/item-material` | Update item material in BOM | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-item-material/put) |
| `GET` | `/product/bom/map-user` | Get BOMs accessible by current user | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-map-user/get) |
| `GET` | `/product/bom/menu-replacement` | Browse menu replacements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-menu-replacement/get) |
| `GET` | `/product/bom/product-material` | Browse product materials | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-product-material/get) |
| `GET` | `/product/bom/product-replacement` | Browse product replacements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-product-replacement/get) |
| `POST` | `/product/bom/pull` | Pull BOMs from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-pull/post) |
| `POST` | `/product/bom/push` | Push BOMs to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-push/post) |
| `GET` | `/product/bom/search-item` | Search items for BOM | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-search-item/get) |
| `PATCH` | `/product/bom/upload` | Upload BOM update template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-upload/patch) |
| `POST` | `/product/bom/upload` | Upload bill of materials | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-upload/post) |
| `POST` | `/product/bom/validate-replacement` | Validate BOM replacements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-validate-replacement/post) |
| `DELETE` | `/product/bom/{bomID}` | Delete a bill of material | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-bomID/delete) |
| `GET` | `/product/bom/{bomID}` | Get a bill of material | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-bomID/get) |
| `PUT` | `/product/bom/{bomID}` | Update a bill of material | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-bomID/put) |
| `GET` | `/product/bom/{bomID}/map-user` | Get user access for a specific BOM | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-bomID--map-user/get) |
| `PATCH` | `/product/bom/{bomID}/restore` | Restore a bill of material | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-bomID--restore/patch) |
| `GET` | `/product/bom/{bomID}/user-access` | Get selected user access for a BOM | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-bom-bomID--user-access/get) |

## Master Category

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/product/category` | List product categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category/get) |
| `POST` | `/product/category` | Create product category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category/post) |
| `GET` | `/product/category/active-multi-company` | List categories across multiple companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-active-multi-company/get) |
| `GET` | `/product/category/all` | List all categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-all/get) |
| `GET` | `/product/category/export` | Export categories to Excel file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-export/get) |
| `POST` | `/product/category/pull` | Pull categories from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-pull/post) |
| `POST` | `/product/category/push` | Push categories to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-push/post) |
| `POST` | `/product/category/upload` | Upload categories from Excel file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-upload/post) |
| `DELETE` | `/product/category/{categoryID}` | Delete product category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-categoryID/delete) |
| `GET` | `/product/category/{categoryID}` | Get product category by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-categoryID/get) |
| `PUT` | `/product/category/{categoryID}` | Update product category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-categoryID/put) |
| `PATCH` | `/product/category/{categoryID}/inactive` | Deactivate product category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-categoryID--inactive/patch) |
| `GET` | `/product/category/{categoryID}/products` | Get products by category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-categoryID--products/get) |
| `PATCH` | `/product/category/{categoryID}/restore` | Restore product category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-categoryID--restore/patch) |
| `GET` | `/product/category/{categoryID}/transactions` | Get transactions by category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-category-categoryID--transactions/get) |

## Master Company

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/branch` | Get all branches | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch/get) |
| `GET` | `/branch/active-petty-cash` | Find branches with active petty cash | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-active-petty-cash/get) |
| `GET` | `/branch/budget-plan` | Browse budget plans | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-budget-plan/get) |
| `POST` | `/branch/budget-plan` | Get budget plans for branches | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-budget-plan/post) |
| `GET` | `/branch/by-status` | List branches filtered by status | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-by-status/get) |
| `GET` | `/branch/list` | List all branches with pagination | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-list/get) |
| `GET` | `/branch/list/user` | List branches for current user with pagination | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-list-user/get) |
| `GET` | `/branch/supplier-branch` | Find supplier company branches | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-supplier-branch/get) |
| `GET` | `/branch/user` | Get branch by username | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-user/get) |
| `GET` | `/branch/user-branch` | Get user's branch | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-user-branch/get) |
| `GET` | `/branch/{branchID}` | Get branch by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/branch-branchID/get) |

## Master Cost Center

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/cost-center` | List all cost centers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center/get) |
| `POST` | `/cost-center` | Create a new cost center | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center/post) |
| `GET` | `/cost-center/budget-plan` | Browse cost center budget plans | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center-budget-plan/get) |
| `POST` | `/cost-center/budget-plan` | Get budget plans for cost centers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center-budget-plan/post) |
| `GET` | `/cost-center/index` | Browse cost centers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center-index/get) |
| `GET` | `/cost-center/user` | Get cost center info for current user | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center-user/get) |
| `DELETE` | `/cost-center/{ID}` | Delete a cost center | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center-ID/delete) |
| `GET` | `/cost-center/{ID}` | Get cost center by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center-ID/get) |
| `PUT` | `/cost-center/{ID}` | Update a cost center | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center-ID/put) |
| `PATCH` | `/cost-center/{ID}/restore` | Restore a deleted cost center | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/cost-center-ID--restore/patch) |

## Master Customer

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/customer` | List customers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer/get) |
| `POST` | `/customer` | Create a new customer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer/post) |
| `GET` | `/customer/active-multi-company-user-customer` | Get active customers for multi-company user | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-active-multi-company-user-customer/get) |
| `GET` | `/customer/by-customer-category` | List customers by category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-by-customer-category/get) |
| `POST` | `/customer/cross-customer` | Create cross-company customers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-cross-customer/post) |
| `GET` | `/customer/export` | Export customers to Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-export/get) |
| `GET` | `/customer/list` | List customers by filter | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-list/get) |
| `GET` | `/customer/list/branch` | List customer branches by filter | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-list-branch/get) |
| `POST` | `/customer/pull` | Pull customers from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pull/post) |
| `POST` | `/customer/push` | Push customers to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-push/post) |
| `POST` | `/customer/upload` | Upload customers from Excel file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-upload/post) |
| `DELETE` | `/customer/{customerID}` | Delete a customer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-customerID/delete) |
| `GET` | `/customer/{customerID}` | Get a customer by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-customerID/get) |
| `PUT` | `/customer/{customerID}` | Update a customer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-customerID/put) |
| `POST` | `/customer/{customerID}/asset-images` | Upload asset images for a customer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-customerID--asset-images/post) |
| `PATCH` | `/customer/{customerID}/restore` | Restore a deleted customer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-customerID--restore/patch) |
| `POST` | `/customer/{customerID}/validation-before-delete` | Validate customer before deletion | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-customerID--validation-before-delete/post) |

## Master Customer Pricelist

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/customer-pricelist` | List customer pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist/get) |
| `POST` | `/customer-pricelist` | Create customer pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist/post) |
| `GET` | `/customer-pricelist/check` | Check if customer pricelist exists | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist-check/get) |
| `GET` | `/customer-pricelist/export` | Export customer pricelist to Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist-export/get) |
| `POST` | `/customer-pricelist/import` | Import customer pricelist from Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist-import/post) |
| `PUT` | `/customer-pricelist/pull` | Pull customer pricelist from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist-pull/put) |
| `PUT` | `/customer-pricelist/push` | Push customer pricelist to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist-push/put) |
| `DELETE` | `/customer-pricelist/{id}` | Delete customer pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist-id/delete) |
| `GET` | `/customer-pricelist/{id}` | Get customer pricelist by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist-id/get) |
| `PATCH` | `/customer-pricelist/{id}` | Update customer pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/customer-pricelist-id/patch) |

## Master Document Template

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/document-template` | List document templates | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template/get) |
| `POST` | `/document-template` | Create a new document template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template/post) |
| `GET` | `/document-template/copy-template` | Find active templates available for copying | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-copy-template/get) |
| `GET` | `/document-template/detail/findOne` | Get a single template detail by product detail ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-detail-findOne/get) |
| `GET` | `/document-template/export` | Export document templates to Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-export/get) |
| `POST` | `/document-template/filter-data` | Search and filter document templates | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-filter-data/post) |
| `GET` | `/document-template/find-by-type` | Get active templates by type ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-find-by-type/get) |
| `GET` | `/document-template/findOne` | Get a single template head by template ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-findOne/get) |
| `GET` | `/document-template/list` | Get document template list for dropdown | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-list/get) |
| `POST` | `/document-template/pull` | Pull templates from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-pull/post) |
| `POST` | `/document-template/push` | Push templates to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-push/post) |
| `POST` | `/document-template/upload` | Upload document templates from Excel file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-upload/post) |
| `POST` | `/document-template/validate-requestable-products` | Validate product availability for templates | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-validate-requestable-products/post) |
| `DELETE` | `/document-template/{requestTemplateID}` | Delete a document template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID/delete) |
| `GET` | `/document-template/{requestTemplateID}` | Get a document template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID/get) |
| `PUT` | `/document-template/{requestTemplateID}` | Update a document template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID/put) |
| `GET` | `/document-template/{requestTemplateID}/check-branch` | Check branch assignments for a template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID--check-branch/get) |
| `GET` | `/document-template/{requestTemplateID}/cost-center` | Get cost centers for a document template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID--cost-center/get) |
| `GET` | `/document-template/{requestTemplateID}/details` | Get all details for a document template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID--details/get) |
| `GET` | `/document-template/{requestTemplateID}/request-template-detail` | Get product detail IDs for a template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID--request-template-detail/get) |
| `PATCH` | `/document-template/{requestTemplateID}/restore` | Restore a deleted document template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID--restore/patch) |
| `GET` | `/document-template/{requestTemplateID}/template-branch` | Get branches for a document template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID--template-branch/get) |

## Master Pricelist

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/pricelist` | List pricelists | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist/get) |
| `POST` | `/pricelist` | Create pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist/post) |
| `GET` | `/pricelist/export` | Export pricelist data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-export/get) |
| `GET` | `/pricelist/history/purchase-invoice` | Get last purchase invoice prices | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-history-purchase-invoice/get) |
| `POST` | `/pricelist/import` | Import pricelist from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-import/post) |
| `GET` | `/pricelist/last-price` | Get last price by product detail IDs | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-last-price/get) |
| `PUT` | `/pricelist/pull` | Pull pricelist changes | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-pull/put) |
| `PUT` | `/pricelist/push` | Push pricelist changes | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-push/put) |
| `GET` | `/pricelist/temp` | List temporary pricelists | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-temp/get) |
| `GET` | `/pricelist/temp/export` | Export temporary pricelist data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-export/get) |
| `DELETE` | `/pricelist/temp/{pricelistNum}` | Delete temporary pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum/delete) |
| `GET` | `/pricelist/temp/{pricelistNum}` | Get temporary pricelist by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum/get) |
| `PUT` | `/pricelist/temp/{pricelistNum}` | Update temporary pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum/put) |
| `PATCH` | `/pricelist/temp/{pricelistNum}/authorize` | Authorize temporary pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum--authorize/patch) |
| `PATCH` | `/pricelist/temp/{pricelistNum}/reject` | Reject temporary pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum--reject/patch) |
| `DELETE` | `/pricelist/{pricelistID}` | Delete pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-pricelistID/delete) |
| `GET` | `/pricelist/{pricelistID}` | Get pricelist by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-pricelistID/get) |
| `PUT` | `/pricelist/{pricelistID}` | Update pricelist | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pricelist-pricelistID/put) |

## Master Product

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/product` | List products with pagination | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product/get) |
| `POST` | `/product` | Create new product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product/post) |
| `GET` | `/product-detail/browse` | Browse product details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-detail-browse/get) |
| `GET` | `/product/active-base-unit` | Get active base unit | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-active-base-unit/get) |
| `PATCH` | `/product/authorize` | Authorize temporary products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-authorize/patch) |
| `GET` | `/product/branch-stock` | Get branch product stock quantities | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-branch-stock/get) |
| `GET` | `/product/browse-coretax-product-code` | Search CoreTax product codes | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-browse-coretax-product-code/get) |
| `GET` | `/product/browse-customer-pricelist` | Browse customer price lists | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-browse-customer-pricelist/get) |
| `GET` | `/product/browse-manufacture` | Browse products for manufacturing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-browse-manufacture/get) |
| `GET` | `/product/browse-product-from-category` | Browse products from category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-browse-product-from-category/get) |
| `GET` | `/product/browse-product-sales` | Browse products for sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-browse-product-sales/get) |
| `GET` | `/product/browse-purchase` | Browse products for purchase | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-browse-purchase/get) |
| `GET` | `/product/browse-transfer` | Browse products for transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-browse-transfer/get) |
| `GET` | `/product/browse-with-pricelist` | Browse product details with price list | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-browse-with-pricelist/get) |
| `GET` | `/product/detail/browse` | Search product details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-detail-browse/get) |
| `POST` | `/product/export` | Export products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-export/post) |
| `POST` | `/product/export/temp` | Export temporary products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-export-temp/post) |
| `GET` | `/product/find-one` | Find single product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-find-one/get) |
| `POST` | `/product/import` | Upload product create file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-import/post) |
| `PUT` | `/product/import` | Upload product update file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-import/put) |
| `GET` | `/product/list` | List all products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-list/get) |
| `GET` | `/product/pending/export` | Export pending products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-pending-export/get) |
| `GET` | `/product/pending/list` | List pending products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-pending-list/get) |
| `GET` | `/product/pending/{productID}` | Get pending product by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-pending-productID/get) |
| `PATCH` | `/product/reject` | Reject temporary products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-reject/patch) |
| `GET` | `/product/stock-location` | Get product stock by location | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-stock-location/get) |
| `GET` | `/product/temp` | List temporary products with pagination | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-temp/get) |
| `GET` | `/product/vat` | Get product VAT | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-vat/get) |
| `DELETE` | `/product/{productID}` | Delete product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-productID/delete) |
| `GET` | `/product/{productID}` | Get product details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-productID/get) |
| `PUT` | `/product/{productID}` | Update product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-productID/put) |
| `PATCH` | `/product/{productID}/restore` | Restore deleted product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-productID--restore/patch) |
| `DELETE` | `/product/{productID}/temp` | Delete temporary product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-productID--temp/delete) |
| `GET` | `/product/{productID}/temp` | Get temporary product details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-productID--temp/get) |
| `PUT` | `/product/{productID}/temp` | Update temporary product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-productID--temp/put) |

## Master Sub Category

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/product/sub-category` | List product sub-categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category/get) |
| `POST` | `/product/sub-category` | Create a product sub-category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category/post) |
| `GET` | `/product/sub-category/all` | Get all product sub-categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-all/get) |
| `GET` | `/product/sub-category/export` | Export product sub-categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-export/get) |
| `POST` | `/product/sub-category/pull` | Pull sub-categories from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-pull/post) |
| `POST` | `/product/sub-category/push` | Push sub-categories to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-push/post) |
| `POST` | `/product/sub-category/upload` | Upload product sub-categories from Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-upload/post) |
| `DELETE` | `/product/sub-category/{subCategoryID}` | Delete a product sub-category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-subCategoryID/delete) |
| `GET` | `/product/sub-category/{subCategoryID}` | Get a product sub-category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-subCategoryID/get) |
| `PUT` | `/product/sub-category/{subCategoryID}` | Update a product sub-category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-subCategoryID/put) |
| `PATCH` | `/product/sub-category/{subCategoryID}/inactive` | Deactivate a product sub-category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-subCategoryID--inactive/patch) |
| `GET` | `/product/sub-category/{subCategoryID}/products` | Get products in a sub-category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-subCategoryID--products/get) |
| `PATCH` | `/product/sub-category/{subCategoryID}/restore` | Restore a deactivated product sub-category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/product-sub-category-subCategoryID--restore/patch) |

## Master Supplier

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/supplier` | List suppliers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier/get) |
| `POST` | `/supplier` | Create a supplier | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier/post) |
| `GET` | `/supplier-banks/{supplierBankID}` | Get a supplier bank | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-banks-supplierBankID/get) |
| `GET` | `/supplier/bulk-supplier-vat` | List supplier VAT lock flags | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-bulk-supplier-vat/get) |
| `GET` | `/supplier/export/{mode}` | Export suppliers to Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-export-mode/get) |
| `GET` | `/supplier/list` | List all suppliers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-list/get) |
| `PATCH` | `/supplier/upload` | Upload suppliers from Excel (update) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-upload/patch) |
| `POST` | `/supplier/upload` | Upload suppliers from Excel (create) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-upload/post) |
| `DELETE` | `/supplier/{supplierID}` | Deactivate a supplier | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-supplierID/delete) |
| `GET` | `/supplier/{supplierID}` | Get a supplier | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-supplierID/get) |
| `PATCH` | `/supplier/{supplierID}` | Update a supplier | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-supplierID/patch) |
| `DELETE` | `/supplier/{supplierID}/attachment` | Delete supplier attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-supplierID--attachment/delete) |
| `PATCH` | `/supplier/{supplierID}/attachment` | Upload supplier attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-supplierID--attachment/patch) |
| `GET` | `/supplier/{supplierID}/banks` | List a supplier's banks | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-supplierID--banks/get) |
| `GET` | `/supplier/{supplierID}/contacts` | List supplier contacts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-supplierID--contacts/get) |
| `PATCH` | `/supplier/{supplierID}/restore` | Restore a supplier | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-supplierID--restore/patch) |

## Master Unit

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/units` | List units | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units/get) |
| `POST` | `/units` | Create a unit | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units/post) |
| `GET` | `/units/export` | Export units | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units-export/get) |
| `POST` | `/units/import` | Import units from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units-import/post) |
| `GET` | `/units/list` | List all units (dropdown) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units-list/get) |
| `GET` | `/units/pull` | Pull unit data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units-pull/get) |
| `GET` | `/units/push` | Push unit data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units-push/get) |
| `DELETE` | `/units/{uomID}` | Delete a unit | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units-uomID/delete) |
| `GET` | `/units/{uomID}` | Get unit by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units-uomID/get) |
| `PUT` | `/units/{uomID}` | Update a unit | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units-uomID/put) |
| `PATCH` | `/units/{uomID}/restore` | Restore a unit | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/units-uomID--restore/patch) |

## Material Delivery

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/production/material-delivery` | List material deliveries | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery/get) |
| `POST` | `/production/material-delivery` | Create a material delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery/post) |
| `GET` | `/production/material-delivery/browse-location` | Browse material delivery locations | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery-browse-location/get) |
| `GET` | `/production/material-delivery/group-material-delivery-details` | List grouped material delivery details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery-group-material-delivery-details/get) |
| `GET` | `/production/material-delivery/location` | Fill material delivery locations | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery-location/get) |
| `DELETE` | `/production/material-delivery/{materialDeliveryNum}` | Delete a material delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery-materialDeliveryNum/delete) |
| `GET` | `/production/material-delivery/{materialDeliveryNum}` | Get a material delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery-materialDeliveryNum/get) |
| `PUT` | `/production/material-delivery/{materialDeliveryNum}` | Update a material delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery-materialDeliveryNum/put) |
| `PATCH` | `/production/material-delivery/{materialDeliveryNum}/authorize` | Authorize a material delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery-materialDeliveryNum--authorize/patch) |
| `PATCH` | `/production/material-delivery/{materialDeliveryNum}/reject` | Reject a material delivery | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery-materialDeliveryNum--reject/patch) |
| `POST` | `/production/material-delivery/{materialDeliveryNum}/validate` | Validate a material delivery before save | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-material-delivery-materialDeliveryNum--validate/post) |

## Memorial Journal

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/accounting/memorial-journal` | List memorial journals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal/get) |
| `POST` | `/accounting/memorial-journal` | Create a memorial journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal/post) |
| `GET` | `/accounting/memorial-journal/access` | Check memorial journal access | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-access/get) |
| `GET` | `/accounting/memorial-journal/export` | Export memorial journals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-export/get) |
| `POST` | `/accounting/memorial-journal/form-upload` | Validate a memorial journal upload form | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-form-upload/post) |
| `POST` | `/accounting/memorial-journal/upload` | Upload memorial journals (bulk) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-upload/post) |
| `DELETE` | `/accounting/memorial-journal/{memorialJournalNum}` | Delete a memorial journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum/delete) |
| `GET` | `/accounting/memorial-journal/{memorialJournalNum}` | Get a memorial journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum/get) |
| `PUT` | `/accounting/memorial-journal/{memorialJournalNum}` | Update a memorial journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum/put) |
| `POST` | `/accounting/memorial-journal/{memorialJournalNum}/asset-images` | Upload asset images for a memorial journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum--asset-images/post) |
| `PATCH` | `/accounting/memorial-journal/{memorialJournalNum}/authorize` | Authorize a memorial journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum--authorize/patch) |
| `PATCH` | `/accounting/memorial-journal/{memorialJournalNum}/reject` | Reject a memorial journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum--reject/patch) |

## Menu

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/menu/browse` | List menus | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/menu-browse/get) |
| `GET` | `/menu/browse-for-product` | Browse menus for a product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/menu-browse-for-product/get) |
| `GET` | `/menu/validate-bom-usage` | Validate BOM usage | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/menu-validate-bom-usage/get) |

## Mindmap Purchase

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchase/mind-map/{transactionNumber}` | Get purchase mind map by transaction number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-mind-map-transactionNumber/get) |
| `GET` | `/purchase/process/{transactionNumber}` | Get purchase process by transaction number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-process-transactionNumber/get) |

## Open Manufacturing

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/production/open-manufacturing` | List open manufacturing orders | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-open-manufacturing/get) |
| `POST` | `/production/open-manufacturing` | Create an open manufacturing order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-open-manufacturing/post) |
| `DELETE` | `/production/open-manufacturing/{openManufacturingNum}` | Delete an open manufacturing order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-open-manufacturing-openManufacturingNum/delete) |
| `GET` | `/production/open-manufacturing/{openManufacturingNum}` | Get an open manufacturing order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-open-manufacturing-openManufacturingNum/get) |
| `PUT` | `/production/open-manufacturing/{openManufacturingNum}` | Update an open manufacturing order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-open-manufacturing-openManufacturingNum/put) |
| `POST` | `/production/open-manufacturing/{openManufacturingNum}/asset-images` | Upload asset images for an open manufacturing order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-open-manufacturing-openManufacturingNum--asset-images/post) |
| `PATCH` | `/production/open-manufacturing/{openManufacturingNum}/authorize` | Authorize an open manufacturing order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-open-manufacturing-openManufacturingNum--authorize/patch) |
| `PATCH` | `/production/open-manufacturing/{openManufacturingNum}/reject` | Reject an open manufacturing order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-open-manufacturing-openManufacturingNum--reject/patch) |
| `POST` | `/production/open-manufacturing/{openManufacturingNum}/validate` | Validate an open manufacturing order before save | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-open-manufacturing-openManufacturingNum--validate/post) |

## Operational

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/operational/operational-dashboard/cogs` | Get COGS dashboard | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/operational-operational-dashboard-cogs/get) |
| `GET` | `/operational/operational-dashboard/highlight` | Get operational highlight dashboard | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/operational-operational-dashboard-highlight/get) |
| `GET` | `/operational/operational-dashboard/monitoring` | Get monitoring dashboard | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/operational-operational-dashboard-monitoring/get) |
| `GET` | `/operational/operational-dashboard/pending-goods-delivery` | Get pending goods delivery dashboard | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/operational-operational-dashboard-pending-goods-delivery/get) |
| `GET` | `/operational/operational-dashboard/pending-goods-receipt` | Get pending goods receipt dashboard | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/operational-operational-dashboard-pending-goods-receipt/get) |

## Payable Settlement

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/payable-settlement` | Create payable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/payable-settlement/post) |
| `GET` | `/payable-settlement/browse-invoice-reference` | Browse invoice references | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/payable-settlement-browse-invoice-reference/get) |
| `DELETE` | `/payable-settlement/{payableSettlementNum}` | Delete payable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/payable-settlement-payableSettlementNum/delete) |
| `GET` | `/payable-settlement/{payableSettlementNum}` | Get payable settlement details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/payable-settlement-payableSettlementNum/get) |
| `PUT` | `/payable-settlement/{payableSettlementNum}` | Update payable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/payable-settlement-payableSettlementNum/put) |
| `PATCH` | `/payable-settlement/{payableSettlementNum}/authorize` | Authorize payable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/payable-settlement-payableSettlementNum--authorize/patch) |
| `PATCH` | `/payable-settlement/{payableSettlementNum}/reject` | Reject payable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/payable-settlement-payableSettlementNum--reject/patch) |
| `POST` | `/payable-settlement/{payableSettlementNum}/validate` | Validate payable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/payable-settlement-payableSettlementNum--validate/post) |

## Petty Cash

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/petty-cash-fulfillment` | List petty cash fulfillments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment/get) |
| `POST` | `/petty-cash-fulfillment` | Create petty cash fulfillment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment/post) |
| `GET` | `/petty-cash-fulfillment/check-account-balance` | Check account balance | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment-check-account-balance/get) |
| `DELETE` | `/petty-cash-fulfillment/{pettyFulfillmentNum}` | Delete petty cash fulfillment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment-pettyFulfillmentNum/delete) |
| `GET` | `/petty-cash-fulfillment/{pettyFulfillmentNum}` | Get petty cash fulfillment details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment-pettyFulfillmentNum/get) |
| `PUT` | `/petty-cash-fulfillment/{pettyFulfillmentNum}` | Update petty cash fulfillment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment-pettyFulfillmentNum/put) |
| `POST` | `/petty-cash-fulfillment/{pettyFulfillmentNum}/attachment` | Upload attachment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment-pettyFulfillmentNum--attachment/post) |
| `PATCH` | `/petty-cash-fulfillment/{pettyFulfillmentNum}/authorize` | Authorize petty cash fulfillment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment-pettyFulfillmentNum--authorize/patch) |
| `PATCH` | `/petty-cash-fulfillment/{pettyFulfillmentNum}/reject` | Reject petty cash fulfillment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment-pettyFulfillmentNum--reject/patch) |
| `POST` | `/petty-cash-fulfillment/{pettyFulfillmentNum}/validate` | Validate petty cash fulfillment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-fulfillment-pettyFulfillmentNum--validate/post) |
| `GET` | `/petty-cash-request` | List petty cash requests | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request/get) |
| `POST` | `/petty-cash-request` | Create a petty cash request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request/post) |
| `GET` | `/petty-cash-request/browse` | Browse petty cash requests | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-browse/get) |
| `GET` | `/petty-cash-request/current-balance` | Get current petty cash balance | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-current-balance/get) |
| `GET` | `/petty-cash-request/fill-details` | Find petty cash details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-fill-details/get) |
| `DELETE` | `/petty-cash-request/{pettyCashRequestNum}` | Delete a petty cash request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-pettyCashRequestNum/delete) |
| `GET` | `/petty-cash-request/{pettyCashRequestNum}` | Get a petty cash request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-pettyCashRequestNum/get) |
| `PUT` | `/petty-cash-request/{pettyCashRequestNum}` | Update a petty cash request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-pettyCashRequestNum/put) |
| `POST` | `/petty-cash-request/{pettyCashRequestNum}/attachment` | Upload attachment for petty cash request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-pettyCashRequestNum--attachment/post) |
| `PATCH` | `/petty-cash-request/{pettyCashRequestNum}/authorize` | Authorize a petty cash request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-pettyCashRequestNum--authorize/patch) |
| `PATCH` | `/petty-cash-request/{pettyCashRequestNum}/reject` | Reject a petty cash request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-pettyCashRequestNum--reject/patch) |
| `POST` | `/petty-cash-request/{pettyCashRequestNum}/validate` | Validate petty cash request before save | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/petty-cash-request-pettyCashRequestNum--validate/post) |

## Product Sales Invoice

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/sales/product-sales-invoice` | List product sales invoices | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice/get) |
| `POST` | `/sales/product-sales-invoice` | Create a product sales invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice/post) |
| `GET` | `/sales/product-sales-invoice/browse-reference` | Browse product sales invoice references | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-browse-reference/get) |
| `GET` | `/sales/product-sales-invoice/browse-return` | Browse product sales invoice returns | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-browse-return/get) |
| `GET` | `/sales/product-sales-invoice/export-csv` | Export selected product sales invoices to CSV | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-export-csv/get) |
| `GET` | `/sales/product-sales-invoice/export-csv-all` | Export all product sales invoices to CSV | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-export-csv-all/get) |
| `GET` | `/sales/product-sales-invoice/multi-customer/{productSalesInvoiceNum}` | Get multi-customer details for a product sales invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-multi-customer-productSalesInvoiceNum/get) |
| `GET` | `/sales/product-sales-invoice/return-details` | Get return details for product sales invoices | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-return-details/get) |
| `DELETE` | `/sales/product-sales-invoice/{productSalesInvoiceNum}` | Delete a product sales invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-productSalesInvoiceNum/delete) |
| `GET` | `/sales/product-sales-invoice/{productSalesInvoiceNum}` | Get a product sales invoice by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-productSalesInvoiceNum/get) |
| `PUT` | `/sales/product-sales-invoice/{productSalesInvoiceNum}` | Update a product sales invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-productSalesInvoiceNum/put) |
| `PATCH` | `/sales/product-sales-invoice/{productSalesInvoiceNum}/authorize` | Authorize a product sales invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-productSalesInvoiceNum--authorize/patch) |
| `PATCH` | `/sales/product-sales-invoice/{productSalesInvoiceNum}/reject` | Reject a product sales invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-productSalesInvoiceNum--reject/patch) |
| `POST` | `/sales/product-sales-invoice/{refNum}/asset-images` | Upload asset images for a product sales invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-invoice-refNum--asset-images/post) |

## Product Sales Payment

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/sales/product-sales-payment` | List product sales payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment/get) |
| `POST` | `/sales/product-sales-payment` | Create product sales payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment/post) |
| `GET` | `/sales/product-sales-payment/fill-details-invoice` | Get initial payment details from invoices | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment-fill-details-invoice/get) |
| `GET` | `/sales/product-sales-payment/get-data-details` | Get payment details by reference numbers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment-get-data-details/get) |
| `POST` | `/sales/product-sales-payment/import` | Import product sales payments from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment-import/post) |
| `DELETE` | `/sales/product-sales-payment/{productSalesPaymentNum}` | Delete product sales payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment-productSalesPaymentNum/delete) |
| `GET` | `/sales/product-sales-payment/{productSalesPaymentNum}` | Get product sales payment detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment-productSalesPaymentNum/get) |
| `PUT` | `/sales/product-sales-payment/{productSalesPaymentNum}` | Update product sales payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment-productSalesPaymentNum/put) |
| `PATCH` | `/sales/product-sales-payment/{productSalesPaymentNum}/authorize` | Authorize product sales payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment-productSalesPaymentNum--authorize/patch) |
| `PATCH` | `/sales/product-sales-payment/{productSalesPaymentNum}/reject` | Reject product sales payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment-productSalesPaymentNum--reject/patch) |
| `POST` | `/sales/product-sales-payment/{refNum}/asset-images` | Upload product sales payment asset images | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-payment-refNum--asset-images/post) |

## Product Sales Return

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/sales/product-sales-return` | List product sales returns (paginated) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return/get) |
| `POST` | `/sales/product-sales-return` | Create a product sales return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return/post) |
| `GET` | `/sales/product-sales-return/export` | Export all product sales returns | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return-export/get) |
| `POST` | `/sales/product-sales-return/export` | Export product sales returns by IDs | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return-export/post) |
| `GET` | `/sales/product-sales-return/old/{productSalesReturnNum}` | Get product sales return head by number (legacy) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return-old-productSalesReturnNum/get) |
| `DELETE` | `/sales/product-sales-return/{productSalesReturnNum}` | Delete a product sales return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return-productSalesReturnNum/delete) |
| `GET` | `/sales/product-sales-return/{productSalesReturnNum}` | Get a product sales return by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return-productSalesReturnNum/get) |
| `PUT` | `/sales/product-sales-return/{productSalesReturnNum}` | Update a product sales return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return-productSalesReturnNum/put) |
| `PATCH` | `/sales/product-sales-return/{productSalesReturnNum}/authorize` | Authorize a product sales return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return-productSalesReturnNum--authorize/patch) |
| `PATCH` | `/sales/product-sales-return/{productSalesReturnNum}/reject` | Reject a product sales return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-return-productSalesReturnNum--reject/patch) |

## Production Order

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/production/production-order` | List production orders | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order/get) |
| `POST` | `/production/production-order` | Create a production order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order/post) |
| `GET` | `/production/production-order/browse-detail` | Browse production order details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-browse-detail/get) |
| `GET` | `/production/production-order/browse-multi-production-result` | Browse multi production result orders | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-browse-multi-production-result/get) |
| `GET` | `/production/production-order/browse-return` | Browse production order returns | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-browse-return/get) |
| `GET` | `/production/production-order/browse-return-standard` | Browse standard production order returns | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-browse-return-standard/get) |
| `GET` | `/production/production-order/browse-unfinished` | Browse unfinished production orders | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-browse-unfinished/get) |
| `GET` | `/production/production-order/details` | List production order details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-details/get) |
| `GET` | `/production/production-order/group-production-order-material` | List grouped production order materials | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-group-production-order-material/get) |
| `GET` | `/production/production-order/print-group` | List production order print groups | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-print-group/get) |
| `POST` | `/production/production-order/upload` | Upload production orders from Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-upload/post) |
| `DELETE` | `/production/production-order/{productionOrderNum}` | Delete a production order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-productionOrderNum/delete) |
| `GET` | `/production/production-order/{productionOrderNum}` | Get a production order by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-productionOrderNum/get) |
| `PUT` | `/production/production-order/{productionOrderNum}` | Update a production order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-productionOrderNum/put) |
| `PATCH` | `/production/production-order/{productionOrderNum}/authorize` | Authorize a production order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-productionOrderNum--authorize/patch) |
| `GET` | `/production/production-order/{productionOrderNum}/is-finished` | Check if a production order is finished | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-productionOrderNum--is-finished/get) |
| `PATCH` | `/production/production-order/{productionOrderNum}/reject` | Reject a production order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-productionOrderNum--reject/patch) |
| `GET` | `/production/production-order/{productionOrderNum}/unfinished-detail` | List unfinished production order details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-order-productionOrderNum--unfinished-detail/get) |

## Production Result

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/production/production-result` | List production results | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result/get) |
| `POST` | `/production/production-result` | Create a production result | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result/post) |
| `POST` | `/production/production-result/multiple` | Create multiple production results | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result-multiple/post) |
| `DELETE` | `/production/production-result/{productionResultNum}` | Delete a production result | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result-productionResultNum/delete) |
| `GET` | `/production/production-result/{productionResultNum}` | Get a production result | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result-productionResultNum/get) |
| `PUT` | `/production/production-result/{productionResultNum}` | Update a production result | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result-productionResultNum/put) |
| `GET` | `/production/production-result/{productionResultNum}/action-grid` | Validate production result grid actions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result-productionResultNum--action-grid/get) |
| `PATCH` | `/production/production-result/{productionResultNum}/authorize` | Authorize a production result | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result-productionResultNum--authorize/patch) |
| `PATCH` | `/production/production-result/{productionResultNum}/reject` | Reject a production result | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result-productionResultNum--reject/patch) |
| `POST` | `/production/production-result/{productionResultNum}/validate-update` | Validate a production result before update | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-result-productionResultNum--validate-update/post) |

## Production Return

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/production/production-return` | List production returns | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-return/get) |
| `POST` | `/production/production-return` | Create a production return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-return/post) |
| `GET` | `/production/production-return/sum-po-to-pr` | Sum production order material to production return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-return-sum-po-to-pr/get) |
| `DELETE` | `/production/production-return/{productionReturnNum}` | Delete a production return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-return-productionReturnNum/delete) |
| `GET` | `/production/production-return/{productionReturnNum}` | Get a production return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-return-productionReturnNum/get) |
| `PUT` | `/production/production-return/{productionReturnNum}` | Update a production return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-return-productionReturnNum/put) |
| `GET` | `/production/production-return/{productionReturnNum}/action-grid` | Run action grid for a production return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-return-productionReturnNum--action-grid/get) |
| `PATCH` | `/production/production-return/{productionReturnNum}/authorize` | Authorize a production return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-return-productionReturnNum--authorize/patch) |
| `PATCH` | `/production/production-return/{productionReturnNum}/reject` | Reject a production return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-production-return-productionReturnNum--reject/patch) |

## Project

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/project` | List all projects | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/project/get) |
| `POST` | `/project` | Create a new project | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/project/post) |
| `GET` | `/project/index` | List projects with pagination | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/project-index/get) |
| `DELETE` | `/project/{ID}` | Delete a project | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/project-ID/delete) |
| `GET` | `/project/{ID}` | Get a project by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/project-ID/get) |
| `PUT` | `/project/{ID}` | Update a project | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/project-ID/put) |
| `PATCH` | `/project/{ID}/restore` | Restore a deleted project | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/project-ID--restore/patch) |

## Purchase Dashboard

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchase/purchasing-dashboard/comparison` | Get purchasing price comparison | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchasing-dashboard-comparison/get) |
| `GET` | `/purchase/purchasing-dashboard/composition` | Get purchasing composition by category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchasing-dashboard-composition/get) |
| `GET` | `/purchase/purchasing-dashboard/highlight` | Get purchasing dashboard highlights | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchasing-dashboard-highlight/get) |
| `GET` | `/purchase/purchasing-dashboard/per-month` | Get purchasing totals per month | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchasing-dashboard-per-month/get) |
| `GET` | `/purchase/purchasing-dashboard/top-product` | Get top purchasing products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchasing-dashboard-top-product/get) |

## Purchase Head

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchase/purchase-head/{purchaseNum}` | Get purchase head by purchase number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-head-purchaseNum/get) |

## Purchase Invoice

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchase/purchase-invoices` | List purchase invoices | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices/get) |
| `POST` | `/purchase/purchase-invoices` | Create a purchase invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices/post) |
| `GET` | `/purchase/purchase-invoices/browse-data-details` | Browse purchase invoice reference details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-browse-data-details/get) |
| `GET` | `/purchase/purchase-invoices/browse-data-heads` | Browse purchase invoice heads | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-browse-data-heads/get) |
| `GET` | `/purchase/purchase-invoices/browse-references` | Browse purchase invoice references | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-browse-references/get) |
| `GET` | `/purchase/purchase-invoices/browse-return` | Browse purchase invoices for return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-browse-return/get) |
| `POST` | `/purchase/purchase-invoices/export-csv` | Export purchase invoices to CSV | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-export-csv/post) |
| `GET` | `/purchase/purchase-invoices/product-price` | Get latest product price | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-product-price/get) |
| `GET` | `/purchase/purchase-invoices/product-price-history` | Browse product price history | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-product-price-history/get) |
| `POST` | `/purchase/purchase-invoices/return-details` | Get return details for purchase invoices | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-return-details/post) |
| `GET` | `/purchase/purchase-invoices/supplier-invoice/availability` | Check supplier invoice number availability | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-supplier-invoice-availability/get) |
| `GET` | `/purchase/purchase-invoices/validate-references-budget` | Validate references against budget | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-validate-references-budget/get) |
| `DELETE` | `/purchase/purchase-invoices/{purchaseInvoiceNum}` | Delete a purchase invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum/delete) |
| `GET` | `/purchase/purchase-invoices/{purchaseInvoiceNum}` | Get a purchase invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum/get) |
| `PUT` | `/purchase/purchase-invoices/{purchaseInvoiceNum}` | Update a purchase invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum/put) |
| `DELETE` | `/purchase/purchase-invoices/{purchaseInvoiceNum}/attachment` | Delete purchase invoice attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--attachment/delete) |
| `PATCH` | `/purchase/purchase-invoices/{purchaseInvoiceNum}/attachment` | Upload purchase invoice attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--attachment/patch) |
| `PATCH` | `/purchase/purchase-invoices/{purchaseInvoiceNum}/authorize` | Authorize a purchase invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--authorize/patch) |
| `GET` | `/purchase/purchase-invoices/{purchaseInvoiceNum}/payable-settlements` | Fetch payable settlements for a purchase invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--payable-settlements/get) |
| `GET` | `/purchase/purchase-invoices/{purchaseInvoiceNum}/purchase-payments` | Fetch purchase payments for a purchase invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--purchase-payments/get) |
| `PATCH` | `/purchase/purchase-invoices/{purchaseInvoiceNum}/reject` | Reject a purchase invoice | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--reject/patch) |
| `GET` | `/purchase/purchase-invoices/{purchaseInvoiceNum}/validate-adjustment` | Validate purchase invoice adjustment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--validate-adjustment/get) |
| `GET` | `/purchase/purchase-invoices/{purchaseInvoiceNum}/validate-payment` | Validate purchase invoice for payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--validate-payment/get) |
| `GET` | `/purchase/purchase-invoices/{purchaseInvoiceNum}/validate-return` | Validate purchase invoice for return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--validate-return/get) |

## Purchase Order

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchase/purchase-order` | List purchase orders | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order/get) |
| `POST` | `/purchase/purchase-order` | Create a purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order/post) |
| `POST` | `/purchase/purchase-order/auto-close` | Auto-close eligible purchase orders | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-auto-close/post) |
| `POST` | `/purchase/purchase-order/draft` | Create a purchase order draft | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-draft/post) |
| `POST` | `/purchase/purchase-order/form-import` | Import purchase order form (product lines) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-form-import/post) |
| `POST` | `/purchase/purchase-order/send-email-authorize` | Send authorization email for purchase orders | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-send-email-authorize/post) |
| `POST` | `/purchase/purchase-order/upload` | Upload purchase orders (bulk import) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-upload/post) |
| `POST` | `/purchase/purchase-order/with-asset-image` | Create a purchase order with asset images | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-with-asset-image/post) |
| `DELETE` | `/purchase/purchase-order/{purchaseNum}` | Delete a purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum/delete) |
| `GET` | `/purchase/purchase-order/{purchaseNum}` | Get a purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum/get) |
| `PUT` | `/purchase/purchase-order/{purchaseNum}` | Update a purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum/put) |
| `POST` | `/purchase/purchase-order/{purchaseNum}/asset-images` | Upload asset images for a purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--asset-images/post) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/authorize` | Authorize a purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--authorize/patch) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/close` | Close a purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--close/patch) |
| `PUT` | `/purchase/purchase-order/{purchaseNum}/draft` | Update a purchase order draft | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--draft/put) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/print` | Mark a purchase order as printed | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--print/patch) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/reject` | Reject a purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--reject/patch) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/unclose` | Reopen a closed purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--unclose/patch) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/unfinish` | Revert a finished purchase order | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--unfinish/patch) |

## Purchase Payment

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchase/purchase-payments` | List purchase payments (paginated) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments/get) |
| `POST` | `/purchase/purchase-payments` | Create a purchase payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments/post) |
| `GET` | `/purchase/purchase-payments/browse-purchase-invoices` | Browse purchase invoices for payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-browse-purchase-invoices/get) |
| `GET` | `/purchase/purchase-payments/data-details` | Get purchase payment data details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-data-details/get) |
| `POST` | `/purchase/purchase-payments/import` | Import purchase payments from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-import/post) |
| `GET` | `/purchase/purchase-payments/validate-references` | Validate purchase payment references | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-validate-references/get) |
| `DELETE` | `/purchase/purchase-payments/{purchasePaymentNum}` | Delete a purchase payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-purchasePaymentNum/delete) |
| `GET` | `/purchase/purchase-payments/{purchasePaymentNum}` | Get a purchase payment by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-purchasePaymentNum/get) |
| `PUT` | `/purchase/purchase-payments/{purchasePaymentNum}` | Update a purchase payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-purchasePaymentNum/put) |
| `DELETE` | `/purchase/purchase-payments/{purchasePaymentNum}/attachment` | Delete purchase payment attachment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-purchasePaymentNum--attachment/delete) |
| `PATCH` | `/purchase/purchase-payments/{purchasePaymentNum}/attachment` | Upload purchase payment attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-purchasePaymentNum--attachment/patch) |
| `PATCH` | `/purchase/purchase-payments/{purchasePaymentNum}/authorize` | Authorize a purchase payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-purchasePaymentNum--authorize/patch) |
| `PATCH` | `/purchase/purchase-payments/{purchasePaymentNum}/reject` | Reject a purchase payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-payments-purchasePaymentNum--reject/patch) |

## Purchase Request

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchase-request/browse` | Browse purchase request references | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-request-browse/get) |
| `GET` | `/purchase-request/browse-purchase` | Browse purchase requests | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-request-browse-purchase/get) |
| `GET` | `/purchase-request/product/list` | Get product request list | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-request-product-list/get) |
| `GET` | `/purchase/purchase-request` | List purchase requests | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request/get) |
| `POST` | `/purchase/purchase-request` | Create a purchase request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request/post) |
| `GET` | `/purchase/purchase-request/browse-multi` | Browse purchase requests for multi-select | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-browse-multi/get) |
| `PATCH` | `/purchase/purchase-request/close` | Close purchase requests | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-close/patch) |
| `POST` | `/purchase/purchase-request/import` | Import purchase requests from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-import/post) |
| `POST` | `/purchase/purchase-request/qty-suggestion` | Get quantity suggestions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-qty-suggestion/post) |
| `GET` | `/purchase/purchase-request/summary` | Get purchase request summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-summary/get) |
| `GET` | `/purchase/purchase-request/template-details` | Get request template details for a branch | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-template-details/get) |
| `GET` | `/purchase/purchase-request/transfer/pending-qty` | Get pending transfer quantity | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-transfer-pending-qty/get) |
| `DELETE` | `/purchase/purchase-request/{purchaseRequestNum}` | Delete a purchase request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-purchaseRequestNum/delete) |
| `GET` | `/purchase/purchase-request/{purchaseRequestNum}` | Get a purchase request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-purchaseRequestNum/get) |
| `PUT` | `/purchase/purchase-request/{purchaseRequestNum}` | Update a purchase request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-purchaseRequestNum/put) |
| `DELETE` | `/purchase/purchase-request/{purchaseRequestNum}/attachment` | Delete purchase request attachment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-purchaseRequestNum--attachment/delete) |
| `PATCH` | `/purchase/purchase-request/{purchaseRequestNum}/attachment` | Upload purchase request attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-purchaseRequestNum--attachment/patch) |
| `PATCH` | `/purchase/purchase-request/{purchaseRequestNum}/authorize` | Authorize a purchase request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-purchaseRequestNum--authorize/patch) |
| `PATCH` | `/purchase/purchase-request/{purchaseRequestNum}/reject` | Reject a purchase request | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-request-purchaseRequestNum--reject/patch) |

## Purchase Return

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchases/purchase-return` | List purchase returns | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchases-purchase-return/get) |
| `POST` | `/purchases/purchase-return` | Create a purchase return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchases-purchase-return/post) |
| `DELETE` | `/purchases/purchase-return/{purchaseReturnNum}` | Delete a purchase return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchases-purchase-return-purchaseReturnNum/delete) |
| `GET` | `/purchases/purchase-return/{purchaseReturnNum}` | Get a purchase return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchases-purchase-return-purchaseReturnNum/get) |
| `PUT` | `/purchases/purchase-return/{purchaseReturnNum}` | Update a purchase return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchases-purchase-return-purchaseReturnNum/put) |
| `PATCH` | `/purchases/purchase-return/{purchaseReturnNum}/authorize` | Authorize a purchase return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchases-purchase-return-purchaseReturnNum--authorize/patch) |
| `PATCH` | `/purchases/purchase-return/{purchaseReturnNum}/reject` | Reject a purchase return | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchases-purchase-return-purchaseReturnNum--reject/patch) |

## Purpose

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purpose` | List purposes | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose/get) |
| `POST` | `/purpose` | Create a purpose | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose/post) |
| `GET` | `/purpose/export` | Export purpose list to Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose-export/get) |
| `PUT` | `/purpose/pull` | Pull purpose master from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose-pull/put) |
| `PUT` | `/purpose/push` | Push purpose master to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose-push/put) |
| `GET` | `/purpose/transaction` | Find purpose transactions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose-transaction/get) |
| `GET` | `/purpose/transaction-type` | Get transaction types | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose-transaction-type/get) |
| `DELETE` | `/purpose/{purposeID}` | Delete a purpose | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose-purposeID/delete) |
| `GET` | `/purpose/{purposeID}` | Get a purpose | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose-purposeID/get) |
| `PATCH` | `/purpose/{purposeID}` | Update a purpose | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose-purposeID/patch) |
| `PATCH` | `/purpose/{purposeID}/restore` | Restore a deleted purpose | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purpose-purposeID--restore/patch) |

## Receipt

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/receipt` | List receipts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receipt/get) |
| `POST` | `/receipt` | Create a receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receipt/post) |
| `POST` | `/receipt/import` | Import receipts from Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receipt-import/post) |
| `DELETE` | `/receipt/{receiptNum}` | Delete a receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receipt-receiptNum/delete) |
| `GET` | `/receipt/{receiptNum}` | Get a receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receipt-receiptNum/get) |
| `PUT` | `/receipt/{receiptNum}` | Update a receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receipt-receiptNum/put) |
| `POST` | `/receipt/{receiptNum}/attachment` | Upload receipt attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receipt-receiptNum--attachment/post) |
| `PATCH` | `/receipt/{receiptNum}/authorize` | Authorize a receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receipt-receiptNum--authorize/patch) |
| `PATCH` | `/receipt/{receiptNum}/reject` | Reject a receipt | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receipt-receiptNum--reject/patch) |

## Receivable Settlement

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/receivable-settlement` | Create a receivable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receivable-settlement/post) |
| `DELETE` | `/receivable-settlement/{receivableSettlementNum}` | Delete a receivable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receivable-settlement-receivableSettlementNum/delete) |
| `GET` | `/receivable-settlement/{receivableSettlementNum}` | Get a receivable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receivable-settlement-receivableSettlementNum/get) |
| `PUT` | `/receivable-settlement/{receivableSettlementNum}` | Update a receivable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receivable-settlement-receivableSettlementNum/put) |
| `PATCH` | `/receivable-settlement/{receivableSettlementNum}/authorize` | Authorize a receivable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receivable-settlement-receivableSettlementNum--authorize/patch) |
| `PATCH` | `/receivable-settlement/{receivableSettlementNum}/reject` | Reject a receivable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receivable-settlement-receivableSettlementNum--reject/patch) |
| `POST` | `/receivable-settlement/{receivableSettlementNum}/validate` | Validate a receivable settlement | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/receivable-settlement-receivableSettlementNum--validate/post) |

## Recurring Journal

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/accounting/recurring-journal` | List recurring journals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal/get) |
| `POST` | `/accounting/recurring-journal` | Create a recurring journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal/post) |
| `POST` | `/accounting/recurring-journal/import` | Import recurring journals (bulk) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal-import/post) |
| `POST` | `/accounting/recurring-journal/import/form` | Validate a recurring journal upload form | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal-import-form/post) |
| `PUT` | `/accounting/recurring-journal/pull` | Pull recurring journals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal-pull/put) |
| `PUT` | `/accounting/recurring-journal/push` | Push recurring journals | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal-push/put) |
| `DELETE` | `/accounting/recurring-journal/{recurringJournalID}` | Delete a recurring journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal-recurringJournalID/delete) |
| `GET` | `/accounting/recurring-journal/{recurringJournalID}` | Get a recurring journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal-recurringJournalID/get) |
| `PUT` | `/accounting/recurring-journal/{recurringJournalID}` | Update a recurring journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal-recurringJournalID/put) |
| `PATCH` | `/accounting/recurring-journal/{recurringJournalID}/restore` | Restore a recurring journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/accounting-recurring-journal-recurringJournalID--restore/patch) |

## Release Payment

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/release-payment` | List release payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment/get) |
| `DELETE` | `/release-payment-batch` | Delete release payment batches | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-batch/delete) |
| `GET` | `/release-payment-batch/browse` | Browse release payment batches | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-batch-browse/get) |
| `GET` | `/release-payment-batch/fill-details` | Fill batch details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-batch-fill-details/get) |
| `DELETE` | `/release-payment/bulk` | Delete multiple release payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-bulk/delete) |
| `GET` | `/release-payment/pending/export-all` | Export all pending payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-pending-export-all/get) |
| `GET` | `/release-payment/pending/export-batch` | Export batch as Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-pending-export-batch/get) |
| `GET` | `/release-payment/pending/export-csv` | Export batch as CSV | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-pending-export-csv/get) |
| `POST` | `/release-payment/pending/save-batch` | Save batch of payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-pending-save-batch/post) |
| `GET` | `/release-payment/reference` | Get release payment by reference | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-reference/get) |
| `POST` | `/release-payment/release` | Release payments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-release/post) |
| `DELETE` | `/release-payment/{releasePaymentNum}` | Delete release payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/release-payment-releasePaymentNum/delete) |

## Report

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/report/accounting/balance-sheet/daily-in-month` | Get daily-in-month balance sheet report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-accounting-balance-sheet-daily-in-month/post) |
| `POST` | `/report/accounting/balance-sheet/daily-in-month/export` | Queue daily-in-month balance sheet export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-accounting-balance-sheet-daily-in-month-export/post) |
| `POST` | `/report/accounting/balance-sheet/month-to-month` | Get month-to-month balance sheet report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-accounting-balance-sheet-month-to-month/post) |
| `POST` | `/report/accounting/balance-sheet/month-to-month/export` | Queue month-to-month balance sheet export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-accounting-balance-sheet-month-to-month-export/post) |
| `POST` | `/report/accounting/balance-sheet/position-only` | Get position-only balance sheet report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-accounting-balance-sheet-position-only/post) |
| `POST` | `/report/accounting/balance-sheet/position-only/export` | Queue position-only balance sheet export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-accounting-balance-sheet-position-only-export/post) |
| `POST` | `/report/accounting/general-ledger/export` | Queue general ledger report export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-accounting-general-ledger-export/post) |
| `GET` | `/report/goods-receipt-recapitulation` | List goods receipt recapitulation report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-goods-receipt-recapitulation/get) |
| `GET` | `/report/goods-receipt-recapitulation/export` | Queue goods receipt recapitulation export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-goods-receipt-recapitulation-export/get) |
| `GET` | `/report/goods-receipt-recapitulation/summary` | Get goods receipt recapitulation summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-goods-receipt-recapitulation-summary/get) |
| `GET` | `/report/intercash` | Get intercash report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-intercash/get) |
| `GET` | `/report/intercash/export` | Queue intercash report export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-intercash-export/get) |
| `GET` | `/report/inventory-valuation/detail` | Get inventory valuation report by detail mode | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-detail/get) |
| `GET` | `/report/inventory-valuation/detail/export` | Export inventory valuation report by detail mode to Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-detail-export/get) |
| `GET` | `/report/inventory-valuation/detail/summarize` | Get summary of inventory valuation report by detail mode | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-detail-summarize/get) |
| `GET` | `/report/inventory-valuation/location` | Get inventory valuation report by location mode | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-location/get) |
| `GET` | `/report/inventory-valuation/location/export` | Export inventory valuation report by location mode to Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-location-export/get) |
| `GET` | `/report/inventory-valuation/location/summarize` | Get summary of inventory valuation report by location mode | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-location-summarize/get) |
| `GET` | `/report/inventory-valuation/product` | Get inventory valuation report by product mode | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-product/get) |
| `GET` | `/report/inventory-valuation/product/export` | Export inventory valuation report by product mode to Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-product-export/get) |
| `GET` | `/report/inventory-valuation/product/summarize` | Get summary of inventory valuation report by product mode | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-product-summarize/get) |
| `GET` | `/report/inventory-valuation/summary` | Get inventory valuation report by summary mode | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-summary/get) |
| `GET` | `/report/inventory-valuation/summary/export` | Export inventory valuation report by summary mode to Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-summary-export/get) |
| `GET` | `/report/inventory-valuation/summary/summarize` | Get summary of inventory valuation report by summary mode | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-valuation-summary-summarize/get) |
| `GET` | `/report/inventory/goods-receipt-value-recap` | List goods receipt value recap report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-goods-receipt-value-recap/get) |
| `GET` | `/report/inventory/goods-receipt-value-recap/export` | Queue goods receipt value recap export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-goods-receipt-value-recap-export/get) |
| `GET` | `/report/inventory/goods-receipt-value-recap/summary` | Get goods receipt value recap summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-goods-receipt-value-recap-summary/get) |
| `POST` | `/report/inventory/hpp-history-fifo/export` | Queue HPP history FIFO report export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-hpp-history-fifo-export/post) |
| `POST` | `/report/inventory/product-expired` | Get product expired report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-product-expired/post) |
| `POST` | `/report/inventory/product-expired/export` | Queue product expired report export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-inventory-product-expired-export/post) |
| `GET` | `/report/pending-transaction` | Get pending transaction report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-pending-transaction/get) |
| `GET` | `/report/pending-transaction/export` | Queue pending transaction report export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-pending-transaction-export/get) |
| `GET` | `/report/pos-settlement-report` | List POS settlement reports | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-pos-settlement-report/get) |
| `GET` | `/report/pos-settlement-report/export` | Export POS settlement report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-pos-settlement-report-export/get) |
| `GET` | `/report/pos-settlement-report/summarize` | Get POS settlement report summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-pos-settlement-report-summarize/get) |
| `GET` | `/report/profit-loss/daily-in-month` | Get daily-in-month profit loss report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-daily-in-month/get) |
| `GET` | `/report/profit-loss/daily-in-month/export` | Queue daily-in-month profit loss export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-daily-in-month-export/get) |
| `GET` | `/report/profit-loss/month-to-month` | Get month-to-month profit loss report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-month-to-month/get) |
| `GET` | `/report/profit-loss/month-to-month/export` | Queue month-to-month profit loss export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-month-to-month-export/get) |
| `GET` | `/report/profit-loss/position-only` | Get position-only profit loss report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-position-only/get) |
| `GET` | `/report/profit-loss/position-only/export` | Queue position-only profit loss export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-position-only-export/get) |
| `GET` | `/report/profit-loss/previous-month-comparison` | Get previous-month comparison report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-previous-month-comparison/get) |
| `GET` | `/report/profit-loss/previous-month-comparison/export` | Queue previous-month comparison export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-previous-month-comparison-export/get) |
| `GET` | `/report/profit-loss/previous-year-comparison` | Get previous-year comparison report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-previous-year-comparison/get) |
| `GET` | `/report/profit-loss/previous-year-comparison/export` | Queue previous-year comparison export | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-profit-loss-previous-year-comparison-export/get) |
| `GET` | `/report/purchase/purchase-recapitulation/all` | List purchase recapitulation (all) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-all/get) |
| `GET` | `/report/purchase/purchase-recapitulation/all/export` | Queue purchase recapitulation export (all) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-all-export/get) |
| `GET` | `/report/purchase/purchase-recapitulation/all/summarize` | Summarize purchase recapitulation (all) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-all-summarize/get) |
| `GET` | `/report/purchase/purchase-recapitulation/cash` | List purchase recapitulation (cash) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-cash/get) |
| `GET` | `/report/purchase/purchase-recapitulation/cash-only-cost` | List purchase recapitulation (cash only cost) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-cash-only-cost/get) |
| `GET` | `/report/purchase/purchase-recapitulation/cash-only-cost/export` | Queue purchase recapitulation export (cash only cost) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-cash-only-cost-export/get) |
| `GET` | `/report/purchase/purchase-recapitulation/cash-only-cost/summarize` | Summarize purchase recapitulation (cash only cost) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-cash-only-cost-summarize/get) |
| `GET` | `/report/purchase/purchase-recapitulation/cash/export` | Queue purchase recapitulation export (cash) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-cash-export/get) |
| `GET` | `/report/purchase/purchase-recapitulation/cash/summarize` | Summarize purchase recapitulation (cash) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-cash-summarize/get) |
| `GET` | `/report/purchase/purchase-recapitulation/purchase` | List purchase recapitulation (purchase) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-purchase/get) |
| `GET` | `/report/purchase/purchase-recapitulation/purchase/export` | Queue purchase recapitulation export (purchase) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-purchase-export/get) |
| `GET` | `/report/purchase/purchase-recapitulation/purchase/summarize` | Summarize purchase recapitulation (purchase) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-purchase-purchase-recapitulation-purchase-summarize/get) |
| `GET` | `/report/sales-menu-cogs` | Get sales menu COGS report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-sales-menu-cogs/get) |
| `GET` | `/report/sales-menu-cogs-detail` | Get sales menu COGS detail report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-sales-menu-cogs-detail/get) |
| `GET` | `/report/sales-payment-summary` | Get sales payment summary report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-sales-payment-summary/get) |
| `GET` | `/report/stock-movement` | Get stock movement report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/report-stock-movement/get) |

## Request Item List

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/purchase/request-monitoring` | List request monitoring items | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-request-monitoring/post) |
| `POST` | `/purchase/request-monitoring/export` | Export request monitoring items | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-request-monitoring-export/post) |
| `POST` | `/purchase/request-monitoring/process-purchase` | Process request items into purchase | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-request-monitoring-process-purchase/post) |
| `POST` | `/purchase/request-monitoring/process-transfer` | Process request items into transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-request-monitoring-process-transfer/post) |
| `POST` | `/purchase/request-monitoring/request-last-price` | Get last price for request items | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-request-monitoring-request-last-price/post) |
| `POST` | `/purchase/request-monitoring/request-last-price-with-branch` | Get last purchase price per branch | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-request-monitoring-request-last-price-with-branch/post) |

## Sales Order

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/sales/product-sales` | List product sales documents (paginated) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales/get) |
| `POST` | `/sales/product-sales` | Create a product sales document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales/post) |
| `PATCH` | `/sales/product-sales/auto-finish` | Run auto-finish for eligible product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-auto-finish/patch) |
| `GET` | `/sales/product-sales/product-price-history` | Browse product price history | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-product-price-history/get) |
| `POST` | `/sales/product-sales/upload` | Upload product sales documents from a file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-upload/post) |
| `POST` | `/sales/product-sales/upload-product` | Upload product sales detail lines from a file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-upload-product/post) |
| `DELETE` | `/sales/product-sales/{productSalesNum}` | Delete a product sales document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-productSalesNum/delete) |
| `GET` | `/sales/product-sales/{productSalesNum}` | Get a product sales document by number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-productSalesNum/get) |
| `PUT` | `/sales/product-sales/{productSalesNum}` | Update a product sales document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-productSalesNum/put) |
| `PATCH` | `/sales/product-sales/{productSalesNum}/authorize` | Authorize a product sales document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-productSalesNum--authorize/patch) |
| `GET` | `/sales/product-sales/{productSalesNum}/export` | Export a product sales document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-productSalesNum--export/get) |
| `PATCH` | `/sales/product-sales/{productSalesNum}/finish` | Finish a product sales document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-productSalesNum--finish/patch) |
| `PATCH` | `/sales/product-sales/{productSalesNum}/reject` | Reject a product sales document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-productSalesNum--reject/patch) |
| `PATCH` | `/sales/product-sales/{productSalesNum}/unfinished` | Revert a finished product sales document | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-product-sales-productSalesNum--unfinished/patch) |

## Sales Representative

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/sales-representative` | Get paginated sales representatives | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-representative/get) |
| `POST` | `/sales-representative` | Create a new sales representative | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-representative/post) |
| `GET` | `/sales-representative/list` | Get all sales representatives | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-representative-list/get) |
| `PUT` | `/sales-representative/pull` | Pull sales representatives from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-representative-pull/put) |
| `PUT` | `/sales-representative/push` | Push sales representatives to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-representative-push/put) |
| `DELETE` | `/sales-representative/{salesRepID}` | Delete a sales representative | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-representative-salesRepID/delete) |
| `GET` | `/sales-representative/{salesRepID}` | Get sales representative by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-representative-salesRepID/get) |
| `PUT` | `/sales-representative/{salesRepID}` | Update a sales representative | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-representative-salesRepID/put) |
| `PATCH` | `/sales-representative/{salesRepID}/restore` | Restore a deleted sales representative | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-representative-salesRepID--restore/patch) |

## Signature Setting

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/signature-setting/print` | Get signature settings by transaction type for printing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/signature-setting-print/get) |

## Simple Manufacturing

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/production/simple-manufacturing` | List simple manufacturing entries | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing/get) |
| `POST` | `/production/simple-manufacturing/assembly` | Create standard assembly manufacturing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-assembly/post) |
| `POST` | `/production/simple-manufacturing/assembly-actual` | Create actual assembly manufacturing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-assembly-actual/post) |
| `PUT` | `/production/simple-manufacturing/assembly-actual/{simpleManufacturingNum}` | Update actual assembly manufacturing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-assembly-actual-simpleManufacturingNum/put) |
| `PUT` | `/production/simple-manufacturing/assembly/{simpleManufacturingNum}` | Update standard assembly manufacturing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-assembly-simpleManufacturingNum/put) |
| `POST` | `/production/simple-manufacturing/disassembly` | Create standard disassembly manufacturing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-disassembly/post) |
| `POST` | `/production/simple-manufacturing/disassembly-actual` | Create actual disassembly manufacturing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-disassembly-actual/post) |
| `PUT` | `/production/simple-manufacturing/disassembly-actual/{simpleManufacturingNum}` | Update actual disassembly manufacturing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-disassembly-actual-simpleManufacturingNum/put) |
| `PUT` | `/production/simple-manufacturing/disassembly/{simpleManufacturingNum}` | Update standard disassembly manufacturing | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-disassembly-simpleManufacturingNum/put) |
| `GET` | `/production/simple-manufacturing/stock-check/{simpleManufacturingNum}` | Check stock for a simple manufacturing entry | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-stock-check-simpleManufacturingNum/get) |
| `POST` | `/production/simple-manufacturing/upload` | Upload simple manufacturing from Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-upload/post) |
| `DELETE` | `/production/simple-manufacturing/{simpleManufacturingNum}` | Delete a simple manufacturing entry | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-simpleManufacturingNum/delete) |
| `GET` | `/production/simple-manufacturing/{simpleManufacturingNum}` | Get simple manufacturing detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-simpleManufacturingNum/get) |
| `PATCH` | `/production/simple-manufacturing/{simpleManufacturingNum}/authorize` | Authorize a simple manufacturing entry | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-simpleManufacturingNum--authorize/patch) |
| `PATCH` | `/production/simple-manufacturing/{simpleManufacturingNum}/reject` | Reject a simple manufacturing entry | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/production-simple-manufacturing-simpleManufacturingNum--reject/patch) |

## Simple Purchase

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purchase/simple-purchase` | List simple purchases | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase/get) |
| `POST` | `/purchase/simple-purchase` | Create a simple purchase | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase/post) |
| `POST` | `/purchase/simple-purchase/upload` | Upload simple purchases (bulk) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-upload/post) |
| `POST` | `/purchase/simple-purchase/upload-form` | Preview simple purchase items from an Excel upload | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-upload-form/post) |
| `DELETE` | `/purchase/simple-purchase/{cashPurchaseNum}` | Delete a simple purchase | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum/delete) |
| `GET` | `/purchase/simple-purchase/{cashPurchaseNum}` | Get a simple purchase | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum/get) |
| `PUT` | `/purchase/simple-purchase/{cashPurchaseNum}` | Update a simple purchase | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum/put) |
| `POST` | `/purchase/simple-purchase/{cashPurchaseNum}/asset-images` | Upload asset images for a simple purchase | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum--asset-images/post) |
| `PATCH` | `/purchase/simple-purchase/{cashPurchaseNum}/authorize` | Authorize a simple purchase | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum--authorize/patch) |
| `PATCH` | `/purchase/simple-purchase/{cashPurchaseNum}/reject` | Reject a simple purchase | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum--reject/patch) |
| `POST` | `/purchase/simple-purchase/{cashPurchaseNum}/validate` | Validate a simple purchase before action | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum--validate/post) |

## Simple Sales

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/sales/simple-product-sales` | List simple product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales/get) |
| `POST` | `/sales/simple-product-sales` | Create a simple product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales/post) |
| `POST` | `/sales/simple-product-sales/export-csv` | Export selected simple product sales to CSV | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-export-csv/post) |
| `GET` | `/sales/simple-product-sales/export-csv-all` | Export all filtered simple product sales to CSV | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-export-csv-all/get) |
| `POST` | `/sales/simple-product-sales/export-xlsx` | Export selected simple product sales to XLSX | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-export-xlsx/post) |
| `GET` | `/sales/simple-product-sales/export-xlsx-all` | Export all filtered simple product sales to XLSX | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-export-xlsx-all/get) |
| `POST` | `/sales/simple-product-sales/export-xml` | Export selected simple product sales to XML | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-export-xml/post) |
| `GET` | `/sales/simple-product-sales/export-xml-all` | Export all filtered simple product sales to XML | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-export-xml-all/get) |
| `POST` | `/sales/simple-product-sales/upload` | Upload simple product sales from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-upload/post) |
| `DELETE` | `/sales/simple-product-sales/{simpleProductSalesNum}` | Delete a simple product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-simpleProductSalesNum/delete) |
| `GET` | `/sales/simple-product-sales/{simpleProductSalesNum}` | Get a simple product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-simpleProductSalesNum/get) |
| `PUT` | `/sales/simple-product-sales/{simpleProductSalesNum}` | Update a simple product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-simpleProductSalesNum/put) |
| `POST` | `/sales/simple-product-sales/{simpleProductSalesNum}/asset-images` | Upload asset images for a simple product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-simpleProductSalesNum--asset-images/post) |
| `PATCH` | `/sales/simple-product-sales/{simpleProductSalesNum}/authorize` | Authorize a simple product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-simpleProductSalesNum--authorize/patch) |
| `PATCH` | `/sales/simple-product-sales/{simpleProductSalesNum}/reject` | Reject a simple product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-simpleProductSalesNum--reject/patch) |
| `POST` | `/sales/simple-product-sales/{simpleProductSalesNum}/validate` | Validate a simple product sales | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/sales-simple-product-sales-simpleProductSalesNum--validate/post) |

## Simple Transfer

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/simple-transfer` | List simple transfers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer/get) |
| `POST` | `/simple-transfer` | Create simple transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer/post) |
| `POST` | `/simple-transfer/import` | Import simple transfers from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer-import/post) |
| `GET` | `/simple-transfer/stock-check/{simpleTransferNum}` | Check stock for simple transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer-stock-check-simpleTransferNum/get) |
| `DELETE` | `/simple-transfer/{simpleTransferNum}` | Delete simple transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer-simpleTransferNum/delete) |
| `GET` | `/simple-transfer/{simpleTransferNum}` | Get simple transfer by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer-simpleTransferNum/get) |
| `PUT` | `/simple-transfer/{simpleTransferNum}` | Update simple transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer-simpleTransferNum/put) |
| `PATCH` | `/simple-transfer/{simpleTransferNum}/attachment` | Upload attachment for simple transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer-simpleTransferNum--attachment/patch) |
| `PATCH` | `/simple-transfer/{simpleTransferNum}/authorize` | Authorize simple transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer-simpleTransferNum--authorize/patch) |
| `PATCH` | `/simple-transfer/{simpleTransferNum}/reject` | Reject simple transfer | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/simple-transfer-simpleTransferNum--reject/patch) |

## Stock

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/stock/browse` | Browse stocks | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/stock-browse/get) |
| `POST` | `/stock/check-product-stock-location` | Check product stock at a location | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/stock-check-product-stock-location/post) |
| `GET` | `/stock/product-cogs-history` | Browse product COGS history | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/stock-product-cogs-history/get) |
| `POST` | `/stock/product-detail/last-hpp/batch` | Get last HPP for multiple product details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/stock-product-detail-last-hpp-batch/post) |
| `GET` | `/stock/product-detail/{productDetailID}/last-hpp` | Get product detail last HPP | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/stock-product-detail-productDetailID--last-hpp/get) |
| `GET` | `/stock/product/{productID}/last-hpp` | Get product last HPP | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/stock-product-productID--last-hpp/get) |
| `POST` | `/stock/transaction-stock-list` | Get transaction stock list | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/stock-transaction-stock-list/post) |

## Stock List

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/stock-list` | List stock per location | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-list/get) |
| `GET` | `/inventory/stock-list/details` | List stock card detail movements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-list-details/get) |
| `GET` | `/inventory/stock-list/export` | Queue stock list export report | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-list-export/get) |
| `GET` | `/inventory/stock-list/tracking-booked-qty` | List booked qty tracking | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-list-tracking-booked-qty/get) |
| `GET` | `/inventory/stock-list/tracking-booked-qty-summary` | Get booked qty tracking summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-list-tracking-booked-qty-summary/get) |

## Stock Opname

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/stock-opname` | List stock opnames | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname/get) |
| `POST` | `/inventory/stock-opname` | Create stock opname | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname/post) |
| `POST` | `/inventory/stock-opname/import` | Import stock opname from file | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-import/post) |
| `GET` | `/inventory/stock-opname/location/{locationID}/last-opname-date` | Get location last opname date | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-location-locationID--last-opname-date/get) |
| `GET` | `/inventory/stock-opname/outstanding-transaction` | List outstanding transactions | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-outstanding-transaction/get) |
| `GET` | `/inventory/stock-opname/products/export` | Export stock opname products | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-products-export/get) |
| `POST` | `/inventory/stock-opname/stock-period` | Insert stock period | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-stock-period/post) |
| `POST` | `/inventory/stock-opname/upload` | Upload stock opname file to queue | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-upload/post) |
| `DELETE` | `/inventory/stock-opname/{stockOpnameNum}` | Delete stock opname | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-stockOpnameNum/delete) |
| `GET` | `/inventory/stock-opname/{stockOpnameNum}` | Get stock opname detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-stockOpnameNum/get) |
| `PUT` | `/inventory/stock-opname/{stockOpnameNum}` | Update stock opname | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-stockOpnameNum/put) |
| `DELETE` | `/inventory/stock-opname/{stockOpnameNum}/attachment` | Delete stock opname attachment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-stockOpnameNum--attachment/delete) |
| `PATCH` | `/inventory/stock-opname/{stockOpnameNum}/attachment` | Upload stock opname attachment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-stockOpnameNum--attachment/patch) |
| `PATCH` | `/inventory/stock-opname/{stockOpnameNum}/authorize` | Authorize stock opname | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-stockOpnameNum--authorize/patch) |
| `GET` | `/inventory/stock-opname/{stockOpnameNum}/export` | Export stock opname | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-stockOpnameNum--export/get) |
| `PUT` | `/inventory/stock-opname/{stockOpnameNum}/status` | Update stock opname status | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-opname-stockOpnameNum--status/put) |

## Stock Period

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/stock-period/browse` | Browse stock period data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/inventory-stock-period-browse/get) |

## Supplier Category

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/supplier/category` | List supplier categories | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-category/get) |
| `POST` | `/supplier/category/` | Create a supplier category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-category/post) |
| `GET` | `/supplier/category/list` | List supplier categories (paginated) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-category-list/get) |
| `DELETE` | `/supplier/category/{supplierCategoryID}` | Delete a supplier category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID/delete) |
| `GET` | `/supplier/category/{supplierCategoryID}` | Get a supplier category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID/get) |
| `PUT` | `/supplier/category/{supplierCategoryID}` | Update a supplier category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID/put) |
| `GET` | `/supplier/category/{supplierCategoryID}/check-delete` | Check if a supplier category can be deleted | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID--check-delete/get) |
| `PATCH` | `/supplier/category/{supplierCategoryID}/restore` | Restore a supplier category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID--restore/patch) |

## Supplier Payable

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/supplier-payable` | List supplier payables | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable/get) |
| `GET` | `/supplier-payable/details` | List supplier payable details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-details/get) |
| `GET` | `/supplier-payable/details/export` | Export supplier payable details | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-details-export/get) |
| `GET` | `/supplier-payable/details/group` | List supplier payable detail groups | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-details-group/get) |
| `GET` | `/supplier-payable/export` | Export supplier payables | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-export/get) |
| `GET` | `/supplier-payable/invoice-aging` | List invoice aging | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-invoice-aging/get) |
| `GET` | `/supplier-payable/invoice-aging/export` | Export invoice aging | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-invoice-aging-export/get) |
| `GET` | `/supplier-payable/invoice-aging/summary` | Get invoice aging summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-invoice-aging-summary/get) |
| `GET` | `/supplier-payable/pagination-with-summary` | Get supplier payables with summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-pagination-with-summary/get) |
| `GET` | `/supplier-payable/payable-date` | Get payable date | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-payable-date/get) |
| `GET` | `/supplier-payable/summary` | Get supplier payable summary | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-payable-summary/get) |

## Supplier Settlement

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/supplier-settlement` | Get paginated supplier settlements | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/supplier-settlement/get) |

## Tax

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/tax` | List taxes with pagination | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/tax/get) |
| `POST` | `/tax` | Create a new tax | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/tax/post) |
| `GET` | `/tax/list` | List all taxes | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/tax-list/get) |
| `PUT` | `/tax/pull` | Pull taxes from parent company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/tax-pull/put) |
| `PUT` | `/tax/push` | Push taxes to child companies | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/tax-push/put) |
| `DELETE` | `/tax/{taxID}` | Delete (inactivate) a tax by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/tax-taxID/delete) |
| `GET` | `/tax/{taxID}` | Get a tax by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/tax-taxID/get) |
| `PATCH` | `/tax/{taxID}` | Update a tax by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/tax-taxID/patch) |
| `PATCH` | `/tax/{taxID}/restore` | Restore (reactivate) a tax by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/tax-taxID--restore/patch) |

## Usage Management

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/usage-management` | List usage management records | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management/get) |
| `POST` | `/usage-management` | Create a usage management record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management/post) |
| `GET` | `/usage-management/fill-details` | Fill product details from request template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management-fill-details/get) |
| `POST` | `/usage-management/upload` | Import usage management from Excel | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management-upload/post) |
| `DELETE` | `/usage-management/{usageManagementNum}` | Delete a usage management record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management-usageManagementNum/delete) |
| `GET` | `/usage-management/{usageManagementNum}` | Get a usage management record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management-usageManagementNum/get) |
| `PUT` | `/usage-management/{usageManagementNum}` | Update a usage management record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management-usageManagementNum/put) |
| `POST` | `/usage-management/{usageManagementNum}/attachment` | Upload usage management attachments | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management-usageManagementNum--attachment/post) |
| `PATCH` | `/usage-management/{usageManagementNum}/authorize` | Approve a usage management record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management-usageManagementNum--authorize/patch) |
| `GET` | `/usage-management/{usageManagementNum}/print` | Get usage management print view | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management-usageManagementNum--print/get) |
| `PATCH` | `/usage-management/{usageManagementNum}/reject` | Reject a usage management record | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/usage-management-usageManagementNum--reject/patch) |

## User

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/pos-user-role/active` | List active POS user roles | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/pos-user-role-active/get) |
| `GET` | `/user/access` | Get access list for the current user | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-access/get) |
| `GET` | `/user/access-control` | List all user access controls | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-access-control/get) |
| `GET` | `/user/access/{accessID}` | Get access details by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-access-accessID/get) |
| `GET` | `/user/branch` | List branches for the current user | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-branch/get) |
| `POST` | `/user/browse-access-setting` | Browse and manage user access settings | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-browse-access-setting/post) |
| `PATCH` | `/user/change-password` | Change current user password | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-change-password/patch) |
| `GET` | `/user/check-custom-access` | Check if current user has custom access | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-check-custom-access/get) |
| `GET` | `/user/check-internal-user` | Check if current user is internal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-check-internal-user/get) |
| `GET` | `/user/export` | Export all users | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-export/get) |
| `GET` | `/user/identity/active` | Get the current active user identity | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-identity-active/get) |
| `DELETE` | `/user/link` | Unlink user from company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-link/delete) |
| `GET` | `/user/link` | List linked companies of current user | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-link/get) |
| `POST` | `/user/link` | Link user to company | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-link/post) |
| `GET` | `/user/user-filter-access/all` | Get all filter access controls | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-filter-access-all/get) |
| `GET` | `/user/user-role` | List user roles (paginated) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role/get) |
| `POST` | `/user/user-role` | Create user role | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role/post) |
| `GET` | `/user/user-role/all` | Get all user roles | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-all/get) |
| `GET` | `/user/user-role/browse` | Browse user roles | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-browse/get) |
| `GET` | `/user/user-role/pull` | Pull user role data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-pull/get) |
| `GET` | `/user/user-role/push` | Push user role data | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-push/get) |
| `GET` | `/user/user-role/reference` | Get user role reference list | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-reference/get) |
| `DELETE` | `/user/user-role/{userRoleID}` | Delete user role | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-userRoleID/delete) |
| `GET` | `/user/user-role/{userRoleID}` | Get user role by ID | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-userRoleID/get) |
| `PATCH` | `/user/user-role/{userRoleID}` | Update user role | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-userRoleID/patch) |
| `GET` | `/user/user-role/{userRoleID}/access` | Get access permissions for user role | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-userRoleID--access/get) |
| `POST` | `/user/user-role/{userRoleID}/restore` | Restore user role | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-role-userRoleID--restore/post) |
| `GET` | `/user/user-type/all` | List all user types | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-user-type-all/get) |
| `PATCH` | `/user/{username}/restore` | Restore a deleted user | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/user-username--restore/patch) |

## VAT Invoice Number

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/vat-invoice-number` | List VAT invoice numbers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number/get) |
| `POST` | `/vat-invoice-number` | Create VAT invoice number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number/post) |
| `PUT` | `/vat-invoice-number` | Update VAT invoice number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number/put) |
| `GET` | `/vat-invoice-number/additional-info` | Get additional info list | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-additional-info/get) |
| `GET` | `/vat-invoice-number/available` | Get available VAT numbers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-available/get) |
| `GET` | `/vat-invoice-number/browse` | Browse VAT invoice numbers | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-browse/get) |
| `GET` | `/vat-invoice-number/facility-stamp` | Get facility stamp list | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-facility-stamp/get) |
| `PATCH` | `/vat-invoice-number/reject-vat` | Reject VAT invoice number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-reject-vat/patch) |
| `GET` | `/vat-invoice-number/validate-availability` | Validate VAT number availability | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-validate-availability/get) |
| `DELETE` | `/vat-invoice-number/{vatInvoiceNumberID}` | Delete VAT invoice number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-vatInvoiceNumberID/delete) |
| `GET` | `/vat-invoice-number/{vatInvoiceNumberID}` | Get VAT invoice number detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-vatInvoiceNumberID/get) |
| `GET` | `/vat-invoice-number/{vatInvoiceNumberID}/check-used` | Check if VAT invoice number is used | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-vatInvoiceNumberID--check-used/get) |
| `GET` | `/vat-invoice-number/{vatInvoiceNumberID}/history` | Get VAT invoice number history | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-vatInvoiceNumberID--history/get) |
| `GET` | `/vat-invoice-number/{vatInvoiceNumberID}/history/export` | Export VAT invoice number history | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/vat-invoice-number-vatInvoiceNumberID--history-export/get) |

## View Pending Receipt

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/view-pending-receipt/pending-receipt/count` | Get count of pending receipts | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/view-pending-receipt-pending-receipt-count/get) |
| `GET` | `/view-pending-receipt/trans-num/{transNum}` | Get pending receipt by transaction number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#/paths/view-pending-receipt-trans-num-transNum/get) |

