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
    /** How many files the ERP holds; the files are not opened from the gateway. */
    attachmentCount: number;
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

export type ProductUnit = {
    productDetailId: number;
    uomId: number | null;
    uomName: string | null;
    /** How many base units one of this unit holds. */
    qty: number | null;
    isBase: boolean | null;
    purchasable: boolean | null;
    saleable: boolean | null;
    stocked: boolean | null;
    transferable: boolean | null;
    sku: string | null;
    /** Display only. */
    basePrice: number | null;
    weight: number | null;
    menuName: string | null;
    menuId: number | null;
    cubication: number | null;
    active: boolean | null;
};

export type ProductDetail = Audit & {
    provider: string;
    id: number;
    name: string;
    code: string | null;
    categoryId: number | null;
    categoryName: string | null;
    subCategoryId: number | null;
    subCategoryName: string | null;
    bomId: number | null;
    bomName: string | null;
    purchasable: boolean | null;
    saleable: boolean | null;
    requestable: boolean | null;
    vat: boolean | null;
    luxury: boolean | null;
    receiptTolerance: number | null;
    coretaxCode: string | null;
    notes: string | null;
    active: boolean | null;
    units: ProductUnit[];
    coretaxCodeId: number | null;
    customFields: Record<string, string | null>;
};

/** Every branch (all = true), the named ones (false), or unknown (null). */
export type BranchScope = {
    all: boolean | null;
    names: string[];
    /** The named branches' ids, when the ERP sends them. */
    ids: number[];
};

export type PricelistEntry = {
    provider: string;
    rowId: number | null;
    pricelistNumber: string | null;
    productDetailId: number | null;
    productCode: string | null;
    productName: string | null;
    unit: string | null;
    supplierId: number | null;
    supplierName: string | null;
    currencyName: string | null;
    /** Display only. */
    price: number | null;
    priceDate: string | null;
    expireDate: string | null;
    branches: BranchScope;
};

export type TempPricelistSummary = {
    provider: string;
    number: string;
    supplierName: string | null;
    currencyName: string | null;
    priceDate: string | null;
    expireDate: string | null;
    statusId: number | null;
    statusName: string | null;
    branches: BranchScope;
};

export type TempPricelistLine = {
    productDetailId: number | null;
    productCode: string | null;
    productName: string | null;
    unitName: string | null;
    price: number | null;
    lastPrice: number | null;
};

export type TempPricelist = TempPricelistSummary & {
    supplierId: number | null;
    currencyId: number | null;
    lines: TempPricelistLine[];
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
    rejectedBy: string | null;
    rejectedAt: string | null;
};
