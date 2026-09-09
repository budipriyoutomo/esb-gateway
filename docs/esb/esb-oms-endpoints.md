# ESB OMS API — Endpoint Catalog (v2.0.0)

> Generated from the official apiDoc source (`https://developers.esb.co.id/esb-oms/api_data.json`) on 2026-09-09 by `docs/esb/generate-catalog.py`. This is a mechanical transcription of the vendor documentation, not a design document. Do not hand-edit — regenerate it, and record contract changes in `docs/05-api-integration.md`.

Official documentation: https://developers.esb.co.id/esb-oms/

**Total operations:** 38 across 10 groups.

Paths below are relative to the base URL declared for their group. Base URLs are not uniform across groups — see `docs/05-api-integration.md`.

## Groups

- [Authorization](#authorization) — 2 operations
- [Master Member](#master-member) — 1 operations
- [Master Menu](#master-menu) — 3 operations
- [Master Menu Category](#master-menu-category) — 3 operations
- [Master Menu Template](#master-menu-template) — 3 operations
- [Master POS](#master-pos) — 5 operations
- [Master Promotion](#master-promotion) — 6 operations
- [Other](#other) — 3 operations
- [Push Sales Data](#push-sales-data) — 5 operations
- [Report](#report) — 7 operations

## Authorization

Production base URL: `https://services.esb.co.id/core`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `POST` | `/auth/login` | Login | [docs](https://developers.esb.co.id/esb-oms/#api-Authorization-login) |
| `GET` | `/auth/refresh` | Refresh Token | [docs](https://developers.esb.co.id/esb-oms/#api-Authorization-refresh-token) |

## Master Member

Production base URL: `https://core-api.esb.co.id`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `GET` | `/extv1/member` | Get Member | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Member-get-member) |

## Master Menu

Production base URL: `https://core-api.esb.co.id`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `POST` | `/corev1/master/create-menu` | Create Menu | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Menu-create-menu) |
| `GET` | `/corev1/master/get-menu` | Get Menu | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Menu-get-menu) |
| `POST` | `/web/corev1/master/update-menu` | Update Menu | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Menu-update-menu) |

## Master Menu Category

Production base URL: `https://core-api.esb.co.id`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `POST` | `/corev1/master/create-menu-category` | Create Menu Category | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Menu_Category-create-menu-category) |
| `GET` | `/corev1/master/get-menu-category` | Get Menu Category | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Menu_Category-get-menu-category) |
| `POST` | `/corev1/master/update-menu-category` | Update Menu Category | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Menu_Category-update-menu-category) |

## Master Menu Template

Production base URL: `https://core-api.esb.co.id`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `POST` | `/corev1/master/create-menu-template` | Create Menu Template | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Menu_Template-create-menu-template) |
| `GET` | `/corev1/master/get-menu-template?page=1` | Get Menu Template | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Menu_Template-get-menu-template) |
| `POST` | `/corev1/master/update-menu-template` | Update Menu Template | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Menu_Template-update-menu-template) |

## Master POS

Production base URL: `https://esbcore.co.id`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `POST` | `/external/general/get-branch` | Branch | [docs](https://developers.esb.co.id/esb-oms/#api-Master_POS-get-branch) |
| `POST` | `/external/general/get-menu` | Menu | [docs](https://developers.esb.co.id/esb-oms/#api-Master_POS-get-menu) |
| `POST` | `/external/general/get-payment-method` | Payment Method | [docs](https://developers.esb.co.id/esb-oms/#api-Master_POS-get-payment-method) |
| `POST` | `/external/general/get-visit-purpose` | Visit Purpose | [docs](https://developers.esb.co.id/esb-oms/#api-Master_POS-get-visit-purpose) |
| `POST` | `/external/general/stock-branch` | Stock Branch | [docs](https://developers.esb.co.id/esb-oms/#api-Master_POS-stock-branch) |

## Master Promotion

Production base URL: `https://core-api.esb.co.id`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `POST` | `/corev1/promotion/` | Discount (RP) ESO | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Promotion-discount-amount-eso) |
| `POST` | `/corev1/promotion/` | Discount Limit (%) | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Promotion-discount-limit-percentage) |
| `POST` | `/corev1/promotion/` | Discount (%) | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Promotion-discount-percentage) |
| `POST` | `/corev1/promotion/` | Discount (%) ESO | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Promotion-discount-percentage-eso) |
| `POST` | `/corev1/promotion/` | Free Item | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Promotion-free-item) |
| `GET` | `/extv1/promotion` | Promotion List | [docs](https://developers.esb.co.id/esb-oms/#api-Master_Promotion-promotion-list) |

## Other

Production base URL: `https://esbcore.co.id`, `https://core-api.esb.co.id`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `GET` | `/corev1/sales/get-daily-sales-material-usage` | Daily Sales Material Usage | [docs](https://developers.esb.co.id/esb-oms/#api-Other-Daily-Sales-Material-Usage) |
| `POST` | `/external/general/get-sales` | Get Sales | [docs](https://developers.esb.co.id/esb-oms/#api-Other-get-sales) |
| `POST` | `/external/general/sales-branch-summary` | Branch Sales Summary | [docs](https://developers.esb.co.id/esb-oms/#api-Other-sales-branch-summary) |

## Push Sales Data

Production base URL: `https://core-api.esb.co.id`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `POST` | `https://int-erp.esb.co.id/external/push/sales-data` | Push Sales Data | [docs](https://developers.esb.co.id/esb-oms/#api-Push_Sales_Data-push-sales-data) |
| `POST` | `https://int-erp.esb.co.id/external/push/shift-data` | Shift Data | [docs](https://developers.esb.co.id/esb-oms/#api-Push_Sales_Data-shift-data) |
| `POST` | `/corev1/shift-data/push` | Shift Data V2 | [docs](https://developers.esb.co.id/esb-oms/#api-Push_Sales_Data-shift-data-v2) |
| `POST` | `/extv1/push/sales-data` | Push Sales Data V2 | [docs](https://developers.esb.co.id/esb-oms/#api-Push_Sales_Data-push-sales-data-v2) |
| `—` | `|` | Flow Push Sales And Shift Data | [docs](https://developers.esb.co.id/esb-oms/#api-Push_Sales_Data-flow-push-sales-shift-data) |

## Report

Production base URL: `https://esbcore.co.id`, `https://core-api.esb.co.id`, `https://services.esb.co.id/core`

| Method | Path | Operation | Ref |
| --- | --- | --- | --- |
| `POST` | `https://int-erp.esb.co.id/external/sales/get-sales-information?page={i}` | Sales Information | [docs](https://developers.esb.co.id/esb-oms/#api-Report-get-sales-information) |
| `GET` | `/corev1/sales/sales-information` | Sales Information | [docs](https://developers.esb.co.id/esb-oms/#api-Report-get-sales-information) |
| `POST` | `/external/general/sales-head?page=x` | Sales Head | [docs](https://developers.esb.co.id/esb-oms/#api-Report-sales-head) |
| `POST` | `/external/general/sales-menu` | Sales Menu | [docs](https://developers.esb.co.id/esb-oms/#api-Report-sales-menu) |
| `POST` | `/external/general/sales-menu-completion` | Sales Menu Completion | [docs](https://developers.esb.co.id/esb-oms/#api-Report-sales-menu-completion) |
| `GET` | `/extv1/sales/sales-menu-summary/` | Sales Menu Summary | [docs](https://developers.esb.co.id/esb-oms/#api-Report-sales-menu-summary) |
| `GET` | `/report/sales-payment-summary` | Sales Payment Summary | [docs](https://developers.esb.co.id/esb-oms/#api-Report-sales-payment-summary) |

