/**
 * Mirrors the canonical master data DTOs ([ESB-020]–[ESB-023]):
 * App\Domain\Canonical\UnitOfMeasure*, ProductCategory*,
 * ProductSubCategory*, SupplierCategory*.
 */

type Audit = {
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
};

export type UnitOfMeasureSummary = {
    provider: string;
    id: number | null;
    name: string;
    metricId: number | null;
    metricName: string | null;
    notes: string | null;
    taxCode: string | null;
    taxName: string | null;
    active: boolean | null;
};

export type UnitOfMeasure = Audit & {
    provider: string;
    id: number;
    name: string;
    metricId: number | null;
    metricName: string | null;
    notes: string | null;
    taxId: number | null;
    active: boolean | null;
};

export type ProductCategorySummary = {
    provider: string;
    id: number | null;
    name: string;
    typeId: number | null;
    typeName: string | null;
    notes: string | null;
    active: boolean | null;
};

export type ProductCategory = Audit & {
    provider: string;
    id: number;
    name: string;
    code: string | null;
    typeId: number | null;
    typeName: string | null;
    notes: string | null;
    active: boolean | null;
    coaNo: string | null;
    cogsCoaNo: string | null;
    accrualBasisCoaNo: string | null;
    opnameCoaNo: string | null;
    productSalesCoaNo: string | null;
    depreciationCoaNo: string | null;
    depreciationExpenseCoaNo: string | null;
    depreciationMonths: number | null;
    fiscalDepreciationMonths: number | null;
    depreciationTypeId: number | null;
    fiscalDepreciationTypeId: number | null;
    assetTax: boolean | null;
};

export type ProductSubCategorySummary = {
    provider: string;
    id: number | null;
    name: string;
    deadStockDays: number | null;
    notes: string | null;
    active: boolean | null;
};

export type ProductSubCategory = Audit &
    ProductSubCategorySummary & {
        id: number;
    };

export type SupplierCategorySummary = {
    provider: string;
    id: number | null;
    name: string;
    active: boolean | null;
};

export type SupplierCategory = Audit & SupplierCategorySummary & { id: number };

export type SupplierSummary = {
    provider: string;
    id: number | null;
    name: string;
    code: string | null;
    categoryId: number | null;
    categoryName: string | null;
    address: string | null;
    contactPerson: string | null;
    cellPhone: string | null;
    creditDays: number | null;
    vatSubject: boolean | null;
    active: boolean | null;
};

export type SupplierContact = {
    id: number | null;
    name: string;
    cellPhone: string | null;
    email: string | null;
    isDefault: boolean | null;
    sendsPurchaseOrderEmail: boolean | null;
};

export type SupplierBankAccount = {
    bankId: number | null;
    bankName: string | null;
    accountName: string | null;
    /** Only the last four digits, e.g. "•••• 6789". */
    maskedAccountNumber: string | null;
};

export type SupplierProduct = {
    productDetailId: number | null;
    productName: string | null;
    supplierProductCode: string | null;
    uomName: string | null;
};

export type Supplier = Audit & {
    provider: string;
    id: number;
    name: string;
    code: string | null;
    categoryId: number | null;
    categoryName: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    zipCode: string | null;
    phone1: string | null;
    phone2: string | null;
    fax: string | null;
    npwp: string | null;
    notes: string | null;
    creditDays: number | null;
    payableCoaNo: string | null;
    advancePurchaseCoaNo: string | null;
    paymentMethodId: number | null;
    vatSubject: boolean | null;
    lockVat: boolean | null;
    invoiceOnReceipt: boolean | null;
    usedInTransactions: boolean | null;
    active: boolean | null;
    contacts: SupplierContact[];
    bankAccounts: SupplierBankAccount[];
    products: SupplierProduct[];
};

export type CoaAccountSummary = {
    provider: string;
    coaNo: string;
    description: string;
    /** 1 (top) to 4 (posting account). */
    level: number | null;
    order: number | null;
    branchId: number | null;
    currency: string | null;
    moduleAccount: boolean | null;
    allowBudget: boolean | null;
    active: boolean | null;
};

export type CoaMapping = {
    id: number | null;
    key: string | null;
    typeId: number | null;
    currencyId: number | null;
};

export type CoaAccount = Audit &
    CoaAccountSummary & {
        additionalInfo: string | null;
        releasePaymentCode: string | null;
        mappings: CoaMapping[];
    };
