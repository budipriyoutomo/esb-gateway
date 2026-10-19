/**
 * Mirrors App\Domain\Canonical\SimplePurchase, SimplePurchaseSummary, and
 * their nested DTOs.
 *
 * Dates are the strings the ERP sent (format not documented). formatDate()
 * in @/lib/format formats the shapes it recognises and shows anything else
 * as-is. Amounts are display-only: the
 * ERP is the authority on every total.
 */

export type SimplePurchaseSummary = {
    provider: string;
    number: string;
    supplierInvoiceNumber: string | null;
    date: string | null;
    branchId: number | null;
    branchName: string | null;
    supplierId: number | null;
    supplierName: string | null;
    currencySign: string | null;
    total: number | null;
    statusId: number | null;
    statusName: string | null;
    createdByName: string | null;
    notes: string | null;
};

export type SimplePurchaseLine = {
    productDetailId: number | null;
    productCode: string;
    productName: string;
    qty: number | null;
    uomName: string | null;
    price: number | null;
    discount: number | null;
    discountPercent: number | null;
    taxName: string | null;
    total: number | null;
    notes: string | null;
};

export type SimplePurchaseCost = {
    accountNumber: string;
    description: string | null;
    amount: number | null;
    notes: string | null;
};

export type SimplePurchasePayment = {
    number: string;
    date: string | null;
    amount: number | null;
    accountName: string | null;
    statusName: string | null;
};

export type SimplePurchase = {
    provider: string;
    number: string;
    supplierInvoiceNumber: string | null;
    date: string | null;
    dueDate: string | null;
    purchaseTypeName: string | null;
    branchId: number | null;
    branchName: string | null;
    locationName: string | null;
    supplierId: number | null;
    supplierName: string | null;
    supplierPhone: string | null;
    contactName: string | null;
    paymentMethodName: string | null;
    currencyId: number | null;
    currencyName: string | null;
    currencySign: string | null;
    rate: number | null;
    costCenterName: string | null;
    projectName: string | null;
    costTotal: number | null;
    total: number | null;
    statusId: number | null;
    statusName: string | null;
    notes: string | null;
    createdByName: string | null;
    createdAt: string | null;
    editedByName: string | null;
    editedAt: string | null;
    authorizedByName: string | null;
    authorizedAt: string | null;
    lines: SimplePurchaseLine[];
    costs: SimplePurchaseCost[];
    payments: SimplePurchasePayment[];
};
