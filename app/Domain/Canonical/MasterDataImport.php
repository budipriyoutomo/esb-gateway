<?php

namespace App\Domain\Canonical;

/**
 * The spreadsheets the ERP can import: master data ([ESB-032], S4) and,
 * through the same screen, simple transfers ([ESB-036]) and disbursements
 * ([ESB-040]). The value is the route segment of /imports/{kind}.
 */
enum MasterDataImport: string
{
    case Units = 'units';
    case ProductCategories = 'product-categories';
    case ProductSubCategories = 'product-sub-categories';
    case NewProducts = 'products-create';
    case ProductChanges = 'products-update';
    case Pricelists = 'pricelists';
    case NewSuppliers = 'suppliers-create';
    case SupplierChanges = 'suppliers-update';
    case CoaAccounts = 'coa-accounts';
    case SimpleTransfers = 'simple-transfers';
    case Disbursements = 'disbursements';

    /** For headings and messages, e.g. "units". */
    public function label(): string
    {
        return match ($this) {
            self::Units => 'units',
            self::ProductCategories => 'product categories',
            self::ProductSubCategories => 'product sub-categories',
            self::NewProducts => 'new products',
            self::ProductChanges => 'changes to existing products',
            self::Pricelists => 'pricelists',
            self::NewSuppliers => 'new suppliers',
            self::SupplierChanges => 'changes to existing suppliers',
            self::CoaAccounts => 'COA accounts and account mappings',
            self::SimpleTransfers => 'simple transfers',
            self::Disbursements => 'disbursements',
        };
    }

    /**
     * File extensions the vendor documents. Products and disbursements take
     * legacy .xls too; the rest name .xlsx (the COA upload says "Excel",
     * taken as both).
     *
     * @return list<string>
     */
    public function extensions(): array
    {
        return match ($this) {
            self::NewProducts, self::ProductChanges, self::CoaAccounts, self::Disbursements => ['xlsx', 'xls'],
            default => ['xlsx'],
        };
    }

    /**
     * Whether the ERP only queues the file and imports it later. Only the
     * COA upload is documented as saving the rows in the request.
     */
    public function queued(): bool
    {
        return $this !== self::CoaAccounts;
    }
}
