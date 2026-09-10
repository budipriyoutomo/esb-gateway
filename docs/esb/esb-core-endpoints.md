# ESB Core API — Endpoint Catalog (v2.0.0)

> Generated from the official apiDoc source (`https://developers.esb.co.id/esb-core/api_data.json`) on 2026-09-09 by `docs/esb/generate-catalog.py`. This is a mechanical transcription of the vendor documentation, not a design document. Do not hand-edit — regenerate it, and record contract changes in `docs/05-api-integration.md`.

Official documentation: https://developers.esb.co.id/esb-core/

**Total operations:** 363 across 41 groups.

Paths below are relative to the base URL declared for their group. Base URLs are not uniform across groups — see `docs/05-api-integration.md`.

## Groups

- [Advance Payment](#advance-payment) — 15 operations
- [Authorization](#authorization) — 2 operations
- [Budget Adjustment](#budget-adjustment) — 7 operations
- [Budget Allocate](#budget-allocate) — 7 operations
- [Budget Detail](#budget-detail) — 13 operations
- [Budget Plan](#budget-plan) — 12 operations
- [Employee Advance Payment](#employee-advance-payment) — 9 operations
- [General Ledger](#general-ledger) — 2 operations
- [Goods Delivery](#goods-delivery) — 10 operations
- [Goods Receipt](#goods-receipt) — 12 operations
- [Goods Transfer Request](#goods-transfer-request) — 10 operations
- [Item Journal](#item-journal) — 9 operations
- [Master Accounting](#master-accounting) — 2 operations
- [Master Bill of Material](#master-bill-of-material) — 14 operations
- [Master Category](#master-category) — 2 operations
- [Master Company](#master-company) — 5 operations
- [Master Cost Center](#master-cost-center) — 2 operations
- [Master Customer](#master-customer) — 10 operations
- [Master Customer Pricelist](#master-customer-pricelist) — 7 operations
- [Master Document Template](#master-document-template) — 2 operations
- [Master Pricelist](#master-pricelist) — 14 operations
- [Master Product](#master-product) — 25 operations
- [Master Sub Category](#master-sub-category) — 2 operations
- [Master Supplier](#master-supplier) — 10 operations
- [Master Unit](#master-unit) — 2 operations
- [Material Delivery](#material-delivery) — 7 operations
- [Memorial Journal](#memorial-journal) — 12 operations
- [Online Voucher](#online-voucher) — 3 operations
- [Production Order](#production-order) — 7 operations
- [Production Result](#production-result) — 7 operations
- [Purchase Invoice](#purchase-invoice) — 24 operations
- [Purchase Order](#purchase-order) — 16 operations
- [Purchase Request](#purchase-request) — 7 operations
- [Purchase Return](#purchase-return) — 7 operations
- [Receipt](#receipt) — 9 operations
- [Report](#report) — 1 operations
- [Sales Order](#sales-order) — 11 operations
- [Simple Manufacturing](#simple-manufacturing) — 14 operations
- [Simple Purchase](#simple-purchase) — 8 operations
- [Simple Sales](#simple-sales) — 16 operations
- [Simple Transfer](#simple-transfer) — 9 operations

## Advance Payment

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/purchase/advance-payment` | Create Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-create) |
| `GET` | `/purchase/advance-payment` | Index Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-index) |
| `DELETE` | `/purchase/advance-payment/:advancePaymentNum` | Delete Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-delete) |
| `PUT` | `/purchase/advance-payment/:advancePaymentNum` | Update Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-update) |
| `GET` | `/purchase/advance-payment/:advancePaymentNum` | View Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-view) |
| `DELETE` | `/purchase/advance-payment/:advancePaymentNum/attachment` | Delete Attachment Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-delete-attachment) |
| `PATCH` | `/purchase/advance-payment/:advancePaymentNum/attachment` | Upload Attachment Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-upload-attachment) |
| `PATCH` | `/purchase/advance-payment/:advancePaymentNum/authorize` | Authorize Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-authorize) |
| `GET` | `/purchase/advance-payment/:advancePaymentNum/references` | Get References Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-references) |
| `PATCH` | `/purchase/advance-payment/:advancePaymentNum/reject` | Reject Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-reject) |
| `GET` | `/purchase/advance-payment/available-amount` | Get Available Advance Amount Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-available-amount) |
| `GET` | `/purchase/advance-payment/browse-purchase` | Browse Purchase Reference Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-browse-purchase) |
| `GET` | `/purchase/advance-payment/browse-reference` | Browse Reference Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-browse-reference) |
| `GET` | `/purchase/advance-payment/current-settlement-available-amount` | Get Available Advance Amount By Settlement Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-available-amount-by-settlement) |
| `GET` | `/purchase/advance-payment/purchase-advance-amount` | Get Purchase Advance Amount Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Advance_Payment-advance-payment-purchase-advance-amount) |

## Authorization

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/auth/login` | Login | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Authorization-login) |
| `GET` | `/auth/refresh` | Refresh Token | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Authorization-refresh-token) |

## Budget Adjustment

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/budget/budget-adjustment` | Create Budget Adjustment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Adjustment-budget-adjustment-create) |
| `GET` | `/budget/budget-adjustment` | Get All Data Budget Adjustment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Adjustment-budget-adjustment-get-all) |
| `DELETE` | `/budget/budget-adjustment/:budgetAdjustmentNum` | Delete Budget Adjustment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Adjustment-budget-adjustment-delete) |
| `GET` | `/budget/budget-adjustment/:budgetAdjustmentNum` | Get Budget Adjustment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Adjustment-budget-adjustment-get-by-id) |
| `PUT` | `/budget/budget-adjustment/:budgetAdjustmentNum` | Update Budget Adjustment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Adjustment-budget-adjustment-update) |
| `PATCH` | `/budget/budget-adjustment/:budgetAdjustmentNum/authorize` | Authorize Budget Adjustment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Adjustment-budget-adjustment-authorize) |
| `PATCH` | `/budget/budget-adjustment/:budgetAdjustmentNum/reject` | Reject Budget Adjustment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Adjustment-budget-adjustment-reject) |

## Budget Allocate

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/budget/budget-allocate` | Create Budget Allocate | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Allocate-budget-allocate-create) |
| `GET` | `/budget/budget-allocate` | Get All Data Budget Allocate | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Allocate-budget-allocate-get-all) |
| `DELETE` | `/budget/budget-allocate/:budgetAdjustmentNum` | Delete Budget Allocate | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Allocate-budget-allocate-delete) |
| `GET` | `/budget/budget-allocate/:budgetAdjustmentNum` | Get Budget Allocate | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Allocate-budget-allocate-get-by-id) |
| `PUT` | `/budget/budget-allocate/:budgetAdjustmentNum` | Update Budget Allocate | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Allocate-budget-allocate-update) |
| `PATCH` | `/budget/budget-allocate/:budgetAdjustmentNum/authorize` | Authorize Budget Allocate | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Allocate-budget-allocate-authorize) |
| `PATCH` | `/budget/budget-allocate/:budgetAdjustmentNum/reject` | Reject Budget Allocate | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Allocate-budget-allocate-reject) |

## Budget Detail

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/budgets` | Create Budget Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-create) |
| `GET` | `/budgets` | Get All Data Budget Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-get-all) |
| `DELETE` | `/budgets/:budgetNum` | Delete Budget Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-delete) |
| `GET` | `/budgets/:budgetNum` | Get Budget Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-get) |
| `PUT` | `/budgets/:budgetNum` | Update Budget Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-update) |
| `PATCH` | `/budgets/:budgetNum/authorize` | Authorize Budget Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-authorize) |
| `GET` | `/budgets/:budgetNum/dropdown` | Get Budget Dropdown | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-dropdown) |
| `PATCH` | `/budgets/:budgetNum/reject` | Reject Budget Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-reject) |
| `POST` | `/budgets/amount` | Get Budget Detail Amount | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-amount) |
| `GET` | `/budgets/browse` | Browse Budget Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-browse) |
| `GET` | `/budgets/browse/detail` | Browse Budget Detail Data | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-browse-detail) |
| `POST` | `/budgets/export-form` | Export Budget Detail Form | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-export-form) |
| `POST` | `/budgets/import-form` | Import Budget Detail Form | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Detail-budget-detail-import-form) |

## Budget Plan

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/budget-plan` | Create Budget Plan | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-create) |
| `GET` | `/budget-plan` | Get All Data Budget Plan | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-index) |
| `DELETE` | `/budget-plan/:budgetPlanNum` | Delete Budget Plan | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-delete) |
| `GET` | `/budget-plan/:budgetPlanNum` | Get Budget Plan | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-get) |
| `PUT` | `/budget-plan/:budgetPlanNum` | Update Budget Plan | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-update) |
| `PATCH` | `/budget-plan/:budgetPlanNum/authorize` | Authorize Budget Plan | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-authorize) |
| `GET` | `/budget-plan/:budgetPlanNum/branches` | Get Budget Plan Branches | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-branches) |
| `GET` | `/budget-plan/:budgetPlanNum/branches/details` | Get Budget Plan Detail by Branch | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-detail-by-branch) |
| `GET` | `/budget-plan/:budgetPlanNum/chart-of-accounts` | Get Budget Plan Chart of Accounts | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-chart-of-accounts) |
| `GET` | `/budget-plan/:budgetPlanNum/chart-of-accounts/details` | Get Budget Plan Detail by COA | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-detail-by-coa) |
| `PATCH` | `/budget-plan/:budgetPlanNum/reject` | Reject Budget Plan | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-reject) |
| `GET` | `/budget-plan/browse` | Browse Budget Plan | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Budget_Plan-budget-plan-browse) |

## Employee Advance Payment

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/employee/employee-advance-payment` | Create Employee Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Employee_Advance_Payment-employee-advance-payment-create) |
| `GET` | `/employee/employee-advance-payment` | Index Employee Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Employee_Advance_Payment-employee-advance-payment-index) |
| `DELETE` | `/employee/employee-advance-payment/:employeeAdvanceNum` | Delete Employee Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Employee_Advance_Payment-employee-advance-payment-delete) |
| `PUT` | `/employee/employee-advance-payment/:employeeAdvanceNum` | Update Employee Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Employee_Advance_Payment-employee-advance-payment-update) |
| `GET` | `/employee/employee-advance-payment/:employeeAdvanceNum` | View Employee Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Employee_Advance_Payment-employee-advance-payment-view) |
| `PATCH` | `/employee/employee-advance-payment/:employeeAdvanceNum/authorize` | Authorize Employee Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Employee_Advance_Payment-employee-advance-payment-authorize) |
| `PATCH` | `/employee/employee-advance-payment/:employeeAdvanceNum/reject` | Reject Employee Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Employee_Advance_Payment-employee-advance-payment-reject) |
| `POST` | `/employee/employee-advance-payment/:refNum/asset-images` | Upload Asset Images Employee Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Employee_Advance_Payment-employee-advance-payment-upload-asset-images) |
| `POST` | `/employee/employee-advance-payment/upload` | Upload Employee Advance Payment | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Employee_Advance_Payment-employee-advance-payment-upload) |

## General Ledger

Production base URL: `https://core-api.esb.co.id`, `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/accounting/general-ledger/summary` | Get Summary General Ledger | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-General_Ledger-general-ledger-summary) |
| `GET` | `/corev1/general-ledger` | General Ledger | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-General_Ledger-general-ledger) |

## Goods Delivery

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/goods-delivery` | Goods Delivery Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-goods-delivery-index) |
| `GET` | `/inventory/goods-delivery/create/{refNum}` | Initialize Goods Delivery Data | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-initialize-goods-delivery) |
| `DELETE` | `/inventory/goods-delivery/{goodsDeliveryNum}` | Delete Goods Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-delete-goods-delivery) |
| `PUT` | `/inventory/goods-delivery/{goodsDeliveryNum}` | Update Goods Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-update-goods-delivery) |
| `GET` | `/inventory/goods-delivery/{goodsDeliveryNum}` | View Goods Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-view-goods-delivery) |
| `PATCH` | `/inventory/goods-delivery/{goodsDeliveryNum}/authorize` | Authorize Goods Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-authorize-goods-delivery) |
| `PATCH` | `/inventory/goods-delivery/{goodsDeliveryNum}/reject` | Reject Goods Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-reject-goods-delivery) |
| `POST` | `/inventory/goods-delivery/{goodsDeliveryNum}/upload/attachment` | Upload Goods Delivery Attachment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-upload-goods-delivery-attachment) |
| `POST` | `/inventory/goods-delivery/{refNum}` | Create Goods Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-create-goods-delivery) |
| `—` | `—` | Flow Goods Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Delivery-flow-goods-delivery) |

## Goods Receipt

Production base URL: `https://services.esb.co.id/core`, `https://core-api.esb.co.id`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/inventory/goods-receipt` | Goods Receipt Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-goods-receipt-index) |
| `GET` | `/inventory/goods-receipt/browse-return` | Browse Return Goods Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-browse-return-goods-receipt) |
| `GET` | `/inventory/goods-receipt/initialize` | Initialize Goods Receipt Data | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-initialize-goods-receipt) |
| `DELETE` | `/inventory/goods-receipt/{goodsReceiptNum}` | Delete Goods Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-delete-goods-receipt) |
| `PUT` | `/inventory/goods-receipt/{goodsReceiptNum}` | Update Goods Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-update-goods-receipt) |
| `GET` | `/inventory/goods-receipt/{goodsReceiptNum}` | View Goods Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-view-goods-receipt) |
| `POST` | `/inventory/goods-receipt/{goodsReceiptNum}/asset-imagesn` | Save Goods Receipt Attachments | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-save-goods-receipt-attachments) |
| `PATCH` | `/inventory/goods-receipt/{goodsReceiptNum}/authorize` | Authorize Goods Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-authorize-goods-receipt) |
| `PATCH` | `/inventory/goods-receipt/{goodsReceiptNum}/reject` | Reject Goods Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-reject-goods-receipt) |
| `POST` | `/inventory/goods-receipt/{refNum}` | Create Goods Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-create-goods-receipt) |
| `GET` | `—` | Get Goods Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-get-goods-receipt) |
| `—` | `—` | Flow Goods Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Receipt-flow-goods-receipt) |

## Goods Transfer Request

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/inventory/goods-transfer-request` | Create Goods Transfer Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-create-goods-transfer-request) |
| `GET` | `/inventory/goods-transfer-request` | Goods Transfer Request Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-goods-transfer-request-index) |
| `DELETE` | `/inventory/goods-transfer-request/{transferNum}` | Delete Goods Transfer Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-delete-goods-transfer-request) |
| `PUT` | `/inventory/goods-transfer-request/{transferNum}` | Update Goods Transfer Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-update-goods-transfer-request) |
| `GET` | `/inventory/goods-transfer-request/{transferNum}` | View Goods Transfer Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-view-goods-transfer-request) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}/authorize` | Authorize Goods Transfer Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-authorize-goods-transfer-request) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}/finish` | Finish Goods Transfer Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-finish-goods-transfer-request) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}/reject` | Reject Goods Transfer Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-reject-goods-transfer-request) |
| `PATCH` | `/inventory/goods-transfer-request/{transferNum}/unfinished` | Unfinish Goods Transfer Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-unfinish-goods-transfer-request) |
| `—` | `—` | Flow Goods Transfer Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Goods_Transfer_Request-flow-goods-transfer-request) |

## Item Journal

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/inventory/item-journal` | Create Item Journal | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Item_Journal-create-item-journal) |
| `GET` | `/inventory/item-journal` | Item Journal Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Item_Journal-item-journal-index) |
| `DELETE` | `/inventory/item-journal/:itemJournalNum` | Delete Item Journal | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Item_Journal-delete-item-journal) |
| `PUT` | `/inventory/item-journal/:itemJournalNum` | Update Item Journal | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Item_Journal-update-item-journal) |
| `DELETE` | `/inventory/item-journal/:itemJournalNum/attachment` | Delete Item Journal Attachment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Item_Journal-delete-item-journal-attachment) |
| `PATCH` | `/inventory/item-journal/:itemJournalNum/attachment` | Upload Item Journal Attachment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Item_Journal-upload-item-journal-attachment) |
| `PATCH` | `/inventory/item-journal/:itemJournalNum/authorize` | Authorize Item Journal | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Item_Journal-authorize-item-journal) |
| `PATCH` | `/inventory/item-journal/:itemJournalNum/reject` | Reject Item Journal | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Item_Journal-reject-item-journal) |
| `GET` | `/inventory/item-journal/{itemJournalNum}` | View Item Journal | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Item_Journal-view-item-journal) |

## Master Accounting

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/purpose` | Purpose List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Accounting-purpose-list) |
| `GET` | `/purpose/[purposeID]` | Purpose Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Accounting-purpose-detail) |

## Master Bill of Material

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/product/bom` | Bill of Material List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-bom-list) |
| `POST` | `/product/bom` | Create Bill of Material Assembly | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-create-bill-of-material-assembly) |
| `POST` | `/product/bom` | Create Bill of Material Disassembly | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-create-bill-of-material-disassembly) |
| `POST` | `/product/bom` | Create Bill of Material Menu | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-create-bill-of-material-menu) |
| `GET` | `/product/bom/browse` | Bill of Material Browse | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-bom-browse) |
| `GET` | `/product/bom/export` | Bill of Material Export | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-bom-export) |
| `GET` | `/product/bom/export` | Bill of Material Export Template | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-bom-export-template) |
| `POST` | `/product/bom/upload` | Upload Bill of Material | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-upload-bill-of-material) |
| `POST` | `/product/bom/upload/template` | Upload Update Bill of Material | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-upload-update-bill-of-material) |
| `GET` | `/product/bom/{bomID}` | Bill of Material Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-bom-detail) |
| `DELETE` | `/product/bom/{bomID}` | Delete Bill of Material | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-delete-bill-of-material) |
| `PUT` | `/product/bom/{bomID}` | Update Bill of Material | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-update-bill-of-material) |
| `PATCH` | `/product/bom/{bomID}/restore` | Restore Bill of Material | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-restore-bill-of-material) |
| `GET` | `/product/stock-location` | Browse Stock Location | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Bill_of_Material-browse-product-location) |

## Master Category

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/product/category` | Category List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Category-category-list) |
| `GET` | `/product/category/{categoryID}` | Category Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Category-category-detail) |

## Master Company

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/branch` | Branch List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Company-branch-list) |
| `GET` | `/branch/user` | User Access List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Company-user-access-branch) |
| `GET` | `/location` | Location List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Company-location-list) |
| `GET` | `/location/user` | User Access Location | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Company-user-access-location) |
| `GET` | `/location/{locationID}` | Location Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Company-location-detail) |

## Master Cost Center

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `//cost-center/user` | User Cost Center | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Cost_Center-user-cost-center) |
| `GET` | `/cost-center` | Cost Center Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Cost_Center-cost-center-index) |

## Master Customer

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/customer` | Create Customer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-create-customer) |
| `GET` | `/customer` | Customer Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-customer-index) |
| `GET` | `/customer/export` | Export Customer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-export-customer) |
| `GET` | `/customer/list` | Customer Find All | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-customer-find-all) |
| `POST` | `/customer/upload` | Upload Customer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-upload-customer) |
| `GET` | `/customer/{customerID}` | View Customer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-customer-detail) |
| `DELETE` | `/customer/{customerID}` | Delete Customer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-delete-customer) |
| `PUT` | `/customer/{customerID}` | Update Customer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-update-customer) |
| `PATCH` | `/customer/{customerID}/restore` | Restore Customer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-restore-customer) |
| `POST` | `/customer/{customerID}/validation-before-delete` | Customer Validation Before Delete | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer-validation-before-delete-customer) |

## Master Customer Pricelist

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/customer-pricelist` | Create Customer Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer_Pricelist-create-customer-pricelist) |
| `GET` | `/customer-pricelist` | Customer Pricelist Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer_Pricelist-customer-pricelist-index) |
| `DELETE` | `/customer-pricelist/:customerPricelistID` | Delete Customer Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer_Pricelist-delete-customer-pricelist) |
| `PATCH` | `/customer-pricelist/:customerPricelistID` | Update Customer Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer_Pricelist-update-customer-pricelist) |
| `GET` | `/customer-pricelist/:customerPricelistID` | View Customer Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer_Pricelist-view-customer-pricelist) |
| `GET` | `/customer-pricelist/export` | Export Customer Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer_Pricelist-export-customer-pricelist) |
| `POST` | `/customer-pricelist/import` | Upload Customer Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Customer_Pricelist-upload-customer-pricelist) |

## Master Document Template

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/document-template` | Document Template List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Document_Template-document-template-list) |
| `GET` | `/document-template/[requestTemplateID]` | Document Template Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Document_Template-document-template-detail) |

## Master Pricelist

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/pricelist` | Create Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-create-pricelist) |
| `GET` | `/pricelist` | Pricelist Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-pricelist-index) |
| `DELETE` | `/pricelist/:pricelistID` | Delete Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-delete-pricelist) |
| `PUT` | `/pricelist/:pricelistID` | Update Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-update-pricelist) |
| `GET` | `/pricelist/:pricelistID` | View Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-view-pricelist) |
| `GET` | `/pricelist/export` | Export Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-export-pricelist) |
| `POST` | `/pricelist/import` | Upload Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-upload-pricelist) |
| `GET` | `/pricelist/temp` | Waiting For Approval Pricelist Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-waiting-for-approval-pricelist-index) |
| `DELETE` | `/pricelist/temp/:pricelistNum` | Delete Waiting For Approval Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-delete-waiting-for-approval-pricelist) |
| `PUT` | `/pricelist/temp/:pricelistNum` | Update Waiting For Approval Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-update-waiting-for-approval-pricelist) |
| `GET` | `/pricelist/temp/:pricelistNum` | View Waiting For Approval Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-view-waiting-for-approval-pricelist) |
| `PATCH` | `/pricelist/temp/:pricelistNum/authorize` | Authorize Waiting For Approval Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-authorize-waiting-for-approval-pricelist) |
| `PATCH` | `/pricelist/temp/:pricelistNum/reject` | Reject Waiting For Approval Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-reject-waiting-for-approval-pricelist) |
| `GET` | `/pricelist/temp/export` | Export Waiting For Approval Pricelist | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Pricelist-export-waiting-for-approval-pricelist) |

## Master Product

Production base URL: `https://services.esb.co.id/core`, `https://core-api.esb.co.id`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/corev1/master/product` | Get Product | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-get-product) |
| `POST` | `/product` | Create Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-create-product) |
| `GET` | `/product` | Product Index | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-product-index) |
| `DELETE` | `/product/:productID` | Delete Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-delete-product) |
| `PUT` | `/product/:productID` | Update Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-update-product) |
| `PATCH` | `/product/:productID/restore` | Restore Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-restore-product) |
| `DELETE` | `/product/:productID/temp` | Delete Pending Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-delete-pending-product) |
| `GET` | `/product/:productID/temp` | Product Pending Detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-product-pending-detail) |
| `GET` | `/product/:productID/validate-delete` | Validate Delete Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-validate-delete-product) |
| `PATCH` | `/product/authorize` | Authorize Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-authorize-product) |
| `GET` | `/product/browse-category` | Browse Category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-browse-product-category) |
| `GET` | `/product/browse-sub-category` | Browse Sub Category | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-browse-product-sub-category) |
| `GET` | `/product/detail/validate-delete` | Validate Delete Product Detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-validate-delete-product-detail) |
| `POST` | `/product/export` | Export Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-export-product) |
| `POST` | `/product/export-template` | Export Product Template | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-export-product-template) |
| `POST` | `/product/export/temp` | Export Pending Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-export-pending-product) |
| `POST` | `/product/import` | Upload Create Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-upload-create-product) |
| `PUT` | `/product/import` | Upload Update Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-upload-update-product) |
| `GET` | `/product/list` | Product List | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-product-list) |
| `PUT` | `/product/pull` | Pull Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-pull-product) |
| `PUT` | `/product/push` | Push Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-push-product) |
| `PATCH` | `/product/reject` | Reject Product | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-reject-product) |
| `GET` | `/product/temp` | Product Pending Index | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-product-pending-index) |
| `GET` | `/product/validate-barcode-number` | Validate Barcode Number | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-validate-barcode-number) |
| `GET` | `/product/{productID}` | Product Detail | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Product-product-detail) |

## Master Sub Category

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/product/sub-category` | Sub Category List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Sub_Category-sub-category-list) |
| `GET` | `/product/sub-category/{subCategoryID}` | Sub Category Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Sub_Category-sub-category-detail) |

## Master Supplier

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/supplier` | Create Supplier | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-create-supplier) |
| `GET` | `/supplier` | Supplier List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-supplier-list) |
| `GET` | `/supplier/category/list` | Supplier Category List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-supplier-category-list) |
| `GET` | `/supplier/export/BANK` | Export Supplier | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-export-supplier) |
| `POST` | `/supplier/upload` | Upload Supplier | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-upload-supplier) |
| `PATCH` | `/supplier/upload` | Upload Update Supplier | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-upload-update-supplier) |
| `DELETE` | `/supplier/{supplierID}` | Delete Supplier | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-delete-supplier) |
| `GET` | `/supplier/{supplierID}` | Supplier Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-supplier-detail) |
| `PATCH` | `/supplier/{supplierID}` | Update Supplier | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-update-supplier) |
| `PATCH` | `/supplier/{supplierID}/restore` | Restore Supplier | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Supplier-restore-supplier) |

## Master Unit

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/units` | Unit List | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Unit-unit-list) |
| `GET` | `/units/{uomID}` | Unit Detail | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Master_Unit-unit-detail) |

## Material Delivery

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/production/material-delivery` | Create Material Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Material_Delivery-create-material-delivery) |
| `GET` | `/production/material-delivery` | Material Delivery Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Material_Delivery-material-delivery-index) |
| `DELETE` | `/production/material-delivery/{materialDeliveryNum}` | Delete Material Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Material_Delivery-delete-material-delivery) |
| `PUT` | `/production/material-delivery/{materialDeliveryNum}` | Update Material Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Material_Delivery-update-material-delivery) |
| `GET` | `/production/material-delivery/{materialDeliveryNum}` | View Material Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Material_Delivery-view-material-delivery) |
| `PATCH` | `/production/material-delivery/{materialDeliveryNum}/authorize` | Authorize Material Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Material_Delivery-authorize-material-delivery) |
| `PATCH` | `/production/material-delivery/{materialDeliveryNum}/reject` | Reject Material Delivery | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Material_Delivery-reject-material-delivery) |

## Memorial Journal

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/accounting/memorial-journal` | Create Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-create) |
| `GET` | `/accounting/memorial-journal` | Index Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-index) |
| `DELETE` | `/accounting/memorial-journal/:memorialJournalNum` | Delete Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-delete) |
| `PUT` | `/accounting/memorial-journal/:memorialJournalNum` | Update Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-update) |
| `GET` | `/accounting/memorial-journal/:memorialJournalNum` | View Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-view) |
| `POST` | `/accounting/memorial-journal/:memorialJournalNum/asset-images` | Upload Asset Images Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-upload-asset-images) |
| `PATCH` | `/accounting/memorial-journal/:memorialJournalNum/authorize` | Authorize Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-authorize) |
| `PATCH` | `/accounting/memorial-journal/:memorialJournalNum/reject` | Reject Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-reject) |
| `GET` | `/accounting/memorial-journal/access` | Check Access Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-has-access) |
| `GET` | `/accounting/memorial-journal/export` | Export Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-export) |
| `POST` | `/accounting/memorial-journal/form-upload` | Form Upload Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-form-upload) |
| `POST` | `/accounting/memorial-journal/upload` | Upload Memorial Journal | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Memorial_Journal-memorial-journal-upload) |

## Online Voucher

Production base URL: `https://services.esb.co.id/voucher`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/v1/online-voucher/` | Create Online Voucher (Multi Company) | `2.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Online_Voucher-create-online-voucher-multi-company) |
| `POST` | `/v1/online-voucher/burn` | Burn Online Voucher | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Online_Voucher-burn-online-voucher) |
| `POST` | `/v1/online-voucher/validate` | Validate Online Voucher | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Online_Voucher-validate-online-voucher) |

## Production Order

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/production/production-order` | Create Production Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Order-create-production-order) |
| `GET` | `/production/production-order` | Production Order Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Order-production-order-index) |
| `DELETE` | `/production/production-order/{productionOrderNum}` | Delete Production Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Order-delete-production-order) |
| `PUT` | `/production/production-order/{productionOrderNum}` | Update Production Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Order-update-production-order) |
| `GET` | `/production/production-order/{productionOrderNum}` | View Production Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Order-view-production-order) |
| `PATCH` | `/production/production-order/{productionOrderNum}/authorize` | Authorize Production Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Order-authorize-production-order) |
| `PATCH` | `/production/production-order/{productionOrderNum}/reject` | Reject Production Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Order-reject-production-order) |

## Production Result

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/production/production-result` | Create Production Result | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Result-create-production-result) |
| `GET` | `/production/production-result` | Production Result Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Result-production-result-index) |
| `DELETE` | `/production/production-result/{productionResultNum}` | Delete Production Result | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Result-delete-production-result) |
| `PUT` | `/production/production-result/{productionResultNum}` | Update Production Result | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Result-update-production-result) |
| `GET` | `/production/production-result/{productionResultNum}` | View Production Result | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Result-view-production-result) |
| `PATCH` | `/production/production-result/{productionResultNum}/authorize` | Authorize Production Result | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Result-authorize-production-result) |
| `PATCH` | `/production/production-result/{productionResultNum}/reject` | Reject Production Result | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Production_Result-reject-production-result) |

## Purchase Invoice

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/purchase/purchase-invoices` | Create Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-create) |
| `GET` | `/purchase/purchase-invoices` | Get All Data Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-index) |
| `DELETE` | `/purchase/purchase-invoices/:purchaseInvoiceNum` | Delete Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-delete) |
| `GET` | `/purchase/purchase-invoices/:purchaseInvoiceNum` | Get Data Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-get-by-id) |
| `PUT` | `/purchase/purchase-invoices/:purchaseInvoiceNum` | Update Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-update) |
| `DELETE` | `/purchase/purchase-invoices/:purchaseInvoiceNum/attachment` | Delete Attachment Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-delete-attachment) |
| `PATCH` | `/purchase/purchase-invoices/:purchaseInvoiceNum/attachment` | Upload Attachment Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-upload-attachment) |
| `PATCH` | `/purchase/purchase-invoices/:purchaseInvoiceNum/authorize` | Authorize Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-authorize) |
| `GET` | `/purchase/purchase-invoices/:purchaseInvoiceNum/payable-settlements` | Fetch Payable Settlements | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-fetch-payable-settlements) |
| `GET` | `/purchase/purchase-invoices/:purchaseInvoiceNum/purchase-payments` | Fetch Purchase Payments | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-fetch-purchase-payments) |
| `PATCH` | `/purchase/purchase-invoices/:purchaseInvoiceNum/reject` | Reject Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-reject) |
| `GET` | `/purchase/purchase-invoices/:purchaseInvoiceNum/validate-adjustment` | Validate Purchase Invoice Adjustment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-validate-adjustment) |
| `GET` | `/purchase/purchase-invoices/:purchaseInvoiceNum/validate-payment` | Validate Purchase Payment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-validate-payment) |
| `GET` | `/purchase/purchase-invoices/:purchaseInvoiceNum/validate-return` | Validate Purchase Return | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-validate-return) |
| `GET` | `/purchase/purchase-invoices/browse-data-details` | Browse Data Details | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-browse-data-details) |
| `GET` | `/purchase/purchase-invoices/browse-data-heads` | Browse Data Heads | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-browse-data-heads) |
| `GET` | `/purchase/purchase-invoices/browse-references` | Browse References | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-browse-references) |
| `GET` | `/purchase/purchase-invoices/browse-return` | Browse Return | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-browse-return) |
| `POST` | `/purchase/purchase-invoices/export-csv` | Export CSV Purchase Invoice | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-export-csv) |
| `GET` | `/purchase/purchase-invoices/product-price` | Product Price | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-product-price) |
| `GET` | `/purchase/purchase-invoices/product-price-history` | Product Price History | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-product-price-history) |
| `POST` | `/purchase/purchase-invoices/return-details` | Get Purchase Invoice Return Details | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-get-return-details) |
| `GET` | `/purchase/purchase-invoices/supplier-invoice/availability` | Check Supplier Invoice Number Availability | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-supplier-invoice-availability) |
| `GET` | `/purchase/purchase-invoices/validate-references-budget` | Validate References Budget | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Invoice-purchase-invoice-validate-references-budget) |

## Purchase Order

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/purchase/purchase-order` | Create Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-create-purchase-order) |
| `GET` | `/purchase/purchase-order` | Purchase Order Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-purchase-order-index) |
| `POST` | `/purchase/purchase-order/draft` | Create Draft Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-create-draft-purchase-order) |
| `POST` | `/purchase/purchase-order/upload` | Upload Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-upload-purchase-order) |
| `DELETE` | `/purchase/purchase-order/{purchaseNum}` | Delete Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-delete-purchase-order) |
| `GET` | `/purchase/purchase-order/{purchaseNum}` | View Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-view-purchase-order) |
| `POST` | `/purchase/purchase-order/{purchaseNum}/asset-images` | Upload Asset Image Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-upload-asset-image-purchase-order) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/authorize` | Authorize Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-authorize-purchase-order) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/close` | Close Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-close-purchase-order) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/print` | Print Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-print-purchase-order) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/unclose` | Unclose Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-unclose-purchase-order) |
| `PATCH` | `/purchase/purchase-order/{purchaseNum}/unfinish` | Unfinish Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-unfinish-purchase-order) |
| `PUT` | `/purchase/purchase-order/{{purchaseNum}}` | Update Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-update-purchase-order) |
| `PUT` | `/purchase/purchase-order/{{purchaseNum}}/draft` | Update Draft Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-update-draft-purchase-order) |
| `PATCH` | `/purchase/purchase-order/{{purchaseNum}}/reject` | Reject Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-reject-purchase-order) |
| `—` | `—` | Flow Purchase Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Order-flow-purchase-order) |

## Purchase Request

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/purchase/purchase-request` | Create Purchase Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Request-create-purchase-request) |
| `GET` | `/purchase/purchase-request` | Purchase Request Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Request-purchase-request-index) |
| `DELETE` | `/purchase/purchase-request/{purchaseRequestNum}` | Delete Purchase Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Request-delete-purchase-request) |
| `GET` | `/purchase/purchase-request/{purchaseRequestNum}` | View Purchase Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Request-view-purchase-request) |
| `PATCH` | `/purchase/purchase-request/{purchaseRequestNum}/authorize` | Authorize Purchase Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Request-authorize-purchase-request) |
| `PATCH` | `/purchase/purchase-request/{purchaseRequestNum}/reject` | Reject Purchase Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Request-reject-purchase-request) |
| `PUT` | `/purchase/purchase-request/{{purchaseRequestNum}}` | Update Purchase Request | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Request-update-purchase-request) |

## Purchase Return

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/purchases/purchase-return` | Create Purchase Return | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Return-purchase-return-create) |
| `GET` | `/purchases/purchase-return` | Get All Data Purchase Return | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Return-purchase-return-index) |
| `DELETE` | `/purchases/purchase-return/:purchaseReturnNum` | Delete Purchase Return | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Return-purchase-return-delete) |
| `GET` | `/purchases/purchase-return/:purchaseReturnNum` | Get Data Purchase Return | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Return-purchase-return-detail) |
| `PUT` | `/purchases/purchase-return/:purchaseReturnNum` | Update Purchase Return | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Return-purchase-return-update) |
| `PATCH` | `/purchases/purchase-return/:purchaseReturnNum/authorize` | Authorize Purchase Return | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Return-purchase-return-authorize) |
| `PATCH` | `/purchases/purchase-return/:purchaseReturnNum/reject` | Reject Purchase Return | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Purchase_Return-purchase-return-reject) |

## Receipt

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `PATCH` | `/inventory/receipt/{receiptNum}/reject` | Reject Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Receipt-reject-receipt) |
| `POST` | `/receipt` | Create Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Receipt-create-receipt) |
| `GET` | `/receipt` | Receipt Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Receipt-receipt-index) |
| `POST` | `/receipt/import` | Import Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Receipt-import-receipt) |
| `DELETE` | `/receipt/{receiptNum}` | Delete Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Receipt-delete-receipt) |
| `PUT` | `/receipt/{receiptNum}` | Update Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Receipt-update-receipt) |
| `GET` | `/receipt/{receiptNum}` | View Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Receipt-view-receipt) |
| `POST` | `/receipt/{receiptNum}/attachment` | Upload Receipt Attachment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Receipt-upload-receipt-attachment) |
| `PATCH` | `/receipt/{receiptNum}/authorize` | Authorize Receipt | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Receipt-authorize-receipt) |

## Report

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/report/stock-movement` | Stock Movement Report | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Report-stock-movement) |

## Sales Order

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/sales/product-sales` | Create Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-create-sales-order) |
| `GET` | `/sales/product-sales` | Sales Order Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-sales-order-index) |
| `POST` | `/sales/product-sales/upload` | Upload Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-upload-sales-order) |
| `DELETE` | `/sales/product-sales/{productSalesNum}` | Delete Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-delete-sales-order) |
| `PUT` | `/sales/product-sales/{productSalesNum}` | Update Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-update-sales-order) |
| `GET` | `/sales/product-sales/{productSalesNum}` | View Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-view-sales-order) |
| `PATCH` | `/sales/product-sales/{productSalesNum}/authorize` | Authorize Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-authorize-sales-order) |
| `GET` | `/sales/product-sales/{productSalesNum}/export` | Export Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-export-sales-order) |
| `PATCH` | `/sales/product-sales/{productSalesNum}/finish` | Close Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-close-sales-order) |
| `PATCH` | `/sales/product-sales/{productSalesNum}/unfinished` | Unclose Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-unclose-sales-order) |
| `PATCH` | `/sales/product-sales/{{productSalesNum}}/reject` | Reject Sales Order | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Sales_Order-reject-sales-order) |

## Simple Manufacturing

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `GET` | `/production/simple-manufacturing` | Simple Manufacturing Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-simple-manufacturing-index) |
| `POST` | `/production/simple-manufacturing/assembly` | Create Simple Manufacturing Assembly Standard Costing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-create-simple-manufacturing-assembly-standard-costing) |
| `POST` | `/production/simple-manufacturing/assembly-actual` | Create Simple Manufacturing Assembly Actual Costing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-create-simple-manufacturing-assembly-actual-costing) |
| `PUT` | `/production/simple-manufacturing/assembly-actual/{simpleManufacturingNum}` | Update Simple Manufacturing Assembly Actual Costing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-update-simple-manufacturing-assembly-actual-costing) |
| `PUT` | `/production/simple-manufacturing/assembly/{simpleManufacturingNum}` | Update Simple Manufacturing Assembly Standard Costing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-update-simple-manufacturing-standard-costing) |
| `POST` | `/production/simple-manufacturing/disassembly` | Create Simple Manufacturing Disassembly Standard Costing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-create-simple-manufacturing-disassembly-standard-costing) |
| `POST` | `/production/simple-manufacturing/disassembly-actual` | Create Simple Manufacturing Disassembly Actual Costing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-create-simple-manufacturing-disassembly-actual-costing) |
| `PUT` | `/production/simple-manufacturing/disassembly-actual/{simpleManufacturingNum}` | Update Simple Manufacturing Disassembly Actual Costing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-update-simple-manufacturing-disassembly-actual-costing) |
| `PUT` | `/production/simple-manufacturing/disassembly/{simpleManufacturingNum}` | Update Simple Manufacturing Disassembly Standard Costing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-update-simple-manufacturing-disassembly-standard-costing) |
| `POST` | `/production/simple-manufacturing/upload` | Upload Simple Manufacturing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-upload-simple-manufacturing) |
| `DELETE` | `/production/simple-manufacturing/{simpleManufacturingNum}` | Delete Simple Manufacturing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-delete-simple-manufacturing) |
| `GET` | `/production/simple-manufacturing/{simpleManufacturingNum}` | View Simple Manufacturing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-view-simple-manufacturing) |
| `PATCH` | `/production/simple-manufacturing/{simpleManufacturingNum}/authorize` | Authorize Simple Manufacturing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-authorize-simple-manufacturing) |
| `PATCH` | `/production/simple-manufacturing/{simpleManufacturingNum}/reject` | Reject Simple Manufacturing | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Manufacturing-reject-simple-manufacturing) |

## Simple Purchase

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/purchase/simple-purchase` | Create Simple Purchase | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Purchase-create-simple-purchase) |
| `GET` | `/purchase/simple-purchase` | Simple Purchase Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Purchase-simple-purchase-index) |
| `POST` | `/purchase/simple-purchase/upload` | Upload Simple Purchase | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Purchase-upload-simple-purchase) |
| `DELETE` | `/purchase/simple-purchase/{cashPurchaseNum}` | Delete Simple Purchase | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Purchase-delete-simple-purchase) |
| `PUT` | `/purchase/simple-purchase/{cashPurchaseNum}` | Update Simple Purchase | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Purchase-update-simple-purchase) |
| `PATCH` | `/purchase/simple-purchase/{cashPurchaseNum}/authorize` | Authorize Simple Purchase | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Purchase-authorize-simple-purchase) |
| `PATCH` | `/purchase/simple-purchase/{cashPurchaseNum}/reject` | Reject Simple Purchase | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Purchase-reject-simple-purchase) |
| `GET` | `/purchase/simple-purchase/{simplePurchaseNum}` | View Simple Purchase | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Purchase-view-simple-purchase) |

## Simple Sales

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/sales/simple-product-sales` | Create Simple Sales | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-create-simple-sales) |
| `GET` | `/sales/simple-product-sales` | Simple Sales Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-simple-sales-index) |
| `DELETE` | `/sales/simple-product-sales/:simpleProductSalesNum` | Delete Simple Sales | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-delete-simple-sales) |
| `PUT` | `/sales/simple-product-sales/:simpleProductSalesNum` | Update Simple Sales | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-update-simple-sales) |
| `GET` | `/sales/simple-product-sales/:simpleProductSalesNum` | View Simple Sales | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-view-simple-sales) |
| `POST` | `/sales/simple-product-sales/:simpleProductSalesNum/asset-images` | Upload Attachment Simple Sales | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-upload-attachment-simple-sales) |
| `PATCH` | `/sales/simple-product-sales/:simpleProductSalesNum/authorize` | Authorize Simple Sales | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-authorize-simple-sales) |
| `PATCH` | `/sales/simple-product-sales/:simpleProductSalesNum/reject` | Reject Simple Sales | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-reject-simple-sales) |
| `POST` | `/sales/simple-product-sales/:simpleProductSalesNum/validate` | Validate Before Save | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-validate-simple-sales) |
| `POST` | `/sales/simple-product-sales/export-csv` | Export CSV by IDs | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-export-simple-sales-csv) |
| `GET` | `/sales/simple-product-sales/export-csv-all` | Export CSV All | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-export-simple-sales-csv-all) |
| `POST` | `/sales/simple-product-sales/export-xlsx` | Export XLSX by IDs | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-export-simple-sales-xlsx) |
| `GET` | `/sales/simple-product-sales/export-xlsx-all` | Export XLSX All | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-export-simple-sales-xlsx-all) |
| `POST` | `/sales/simple-product-sales/export-xml` | Export XML by IDs | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-export-simple-sales-xml) |
| `GET` | `/sales/simple-product-sales/export-xml-all` | Export XML All | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-export-simple-sales-xml-all) |
| `POST` | `/sales/simple-product-sales/upload` | Upload Asset Image Simple Sales | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Sales-upload-simple-sales) |

## Simple Transfer

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Version | Ref |
| --- | --- | --- | --- | --- |
| `POST` | `/simple-transfer` | Create Simple Transfer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Transfer-create-simple-transfer) |
| `GET` | `/simple-transfer` | Simple Transfer Index | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Transfer-simple-transfer-index) |
| `POST` | `/simple-transfer/import` | Import Simple Transfer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Transfer-import-simple-transfer) |
| `POST` | `/simple-transfer/simpleTransferNum/attachment` | Upload Simple Transfer Attachment | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Transfer-upload-simple-transfer-attachment) |
| `PATCH` | `/simple-transfer/simpleTransferNum/authorize` | Authorize Simple Transfer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Transfer-authorize-simple-transfer) |
| `PATCH` | `/simple-transfer/simpleTransferNum/reject` | Reject Simple Transfer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Transfer-reject-simple-transfer) |
| `DELETE` | `/simple-transfer/{simpleTransferNum}` | Delete Simple Transfer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Transfer-delete-simple-transfer) |
| `PUT` | `/simple-transfer/{simpleTransferNum}` | Update Simple Transfer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Transfer-update-simple-transfer) |
| `GET` | `/simple-transfer/{simpleTransferNum}` | View Simple Transfer | `1.0.0` | [docs](https://developers.esb.co.id/esb-core/#api-Simple_Transfer-view-simple-transfer) |

