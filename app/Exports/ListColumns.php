<?php

namespace App\Exports;

use App\Domain\Canonical\CoaAccountSummary;
use App\Domain\Canonical\DisbursementSummary;
use App\Domain\Canonical\GeneralJournalEntry;
use App\Domain\Canonical\LedgerEntry;
use App\Domain\Canonical\LedgerSummaryRow;
use App\Domain\Canonical\MemorialJournalSummary;
use App\Domain\Canonical\PricelistEntry;
use App\Domain\Canonical\Product;
use App\Domain\Canonical\ProductCategorySummary;
use App\Domain\Canonical\ProductSubCategorySummary;
use App\Domain\Canonical\SupplierSummary;
use App\Domain\Canonical\UnitOfMeasureSummary;
use Closure;

/**
 * The columns of each list export (ADR-031): header => cell. The gateway's
 * canonical fields, in the order the list pages show them.
 */
final class ListColumns
{
    /** @return array<string, Closure(UnitOfMeasureSummary): (string|int|float|bool|null)> */
    public static function units(): array
    {
        return [
            'ID' => fn (UnitOfMeasureSummary $u) => $u->id,
            'Name' => fn (UnitOfMeasureSummary $u) => $u->name,
            'Metric' => fn (UnitOfMeasureSummary $u) => $u->metricName,
            'Tax code' => fn (UnitOfMeasureSummary $u) => $u->taxCode,
            'Tax' => fn (UnitOfMeasureSummary $u) => $u->taxName,
            'Notes' => fn (UnitOfMeasureSummary $u) => $u->notes,
            'Active' => fn (UnitOfMeasureSummary $u) => $u->active,
        ];
    }

    /** @return array<string, Closure(ProductCategorySummary): (string|int|float|bool|null)> */
    public static function productCategories(): array
    {
        return [
            'ID' => fn (ProductCategorySummary $c) => $c->id,
            'Name' => fn (ProductCategorySummary $c) => $c->name,
            'Type' => fn (ProductCategorySummary $c) => $c->typeName,
            'Notes' => fn (ProductCategorySummary $c) => $c->notes,
            'Active' => fn (ProductCategorySummary $c) => $c->active,
        ];
    }

    /** @return array<string, Closure(ProductSubCategorySummary): (string|int|float|bool|null)> */
    public static function productSubCategories(): array
    {
        return [
            'ID' => fn (ProductSubCategorySummary $s) => $s->id,
            'Name' => fn (ProductSubCategorySummary $s) => $s->name,
            'Dead stock days' => fn (ProductSubCategorySummary $s) => $s->deadStockDays,
            'Notes' => fn (ProductSubCategorySummary $s) => $s->notes,
            'Active' => fn (ProductSubCategorySummary $s) => $s->active,
        ];
    }

    /** @return array<string, Closure(Product): (string|int|float|bool|null)> */
    public static function products(): array
    {
        return [
            'ID' => fn (Product $p) => $p->id,
            'Code' => fn (Product $p) => $p->code,
            'Name' => fn (Product $p) => $p->name,
            'Category' => fn (Product $p) => $p->categoryName,
            'Sub-category' => fn (Product $p) => $p->subCategoryName,
            'Category type' => fn (Product $p) => $p->categoryTypeName,
            'Bill of material' => fn (Product $p) => $p->bomName,
            'Active' => fn (Product $p) => $p->isActive,
        ];
    }

    /** @return array<string, Closure(PricelistEntry): (string|int|float|bool|null)> */
    public static function pricelists(): array
    {
        return [
            'Pricelist no.' => fn (PricelistEntry $e) => $e->pricelistNumber,
            'Product code' => fn (PricelistEntry $e) => $e->productCode,
            'Product' => fn (PricelistEntry $e) => $e->productName,
            'Unit' => fn (PricelistEntry $e) => $e->unit,
            'Supplier' => fn (PricelistEntry $e) => $e->supplierName,
            'Currency' => fn (PricelistEntry $e) => $e->currencyName,
            'Price' => fn (PricelistEntry $e) => $e->price,
            'Price date' => fn (PricelistEntry $e) => $e->priceDate,
            'Expires' => fn (PricelistEntry $e) => $e->expireDate,
            'Branches' => fn (PricelistEntry $e) => $e->branches->all === true ? 'All' : implode('; ', $e->branches->names),
        ];
    }

    /** @return array<string, Closure(CoaAccountSummary): (string|int|float|bool|null)> */
    public static function coaAccounts(): array
    {
        return [
            'Account no.' => fn (CoaAccountSummary $a) => $a->coaNo,
            'Description' => fn (CoaAccountSummary $a) => $a->description,
            'Level' => fn (CoaAccountSummary $a) => $a->level,
            'Currency' => fn (CoaAccountSummary $a) => $a->currency,
            'Module account' => fn (CoaAccountSummary $a) => $a->moduleAccount,
            'Budget allowed' => fn (CoaAccountSummary $a) => $a->allowBudget,
            'Active' => fn (CoaAccountSummary $a) => $a->active,
        ];
    }

    /** @return array<string, Closure(SupplierSummary): (string|int|float|bool|null)> */
    public static function suppliers(): array
    {
        return [
            'ID' => fn (SupplierSummary $s) => $s->id,
            'Code' => fn (SupplierSummary $s) => $s->code,
            'Name' => fn (SupplierSummary $s) => $s->name,
            'Category' => fn (SupplierSummary $s) => $s->categoryName,
            'Address' => fn (SupplierSummary $s) => $s->address,
            'Contact' => fn (SupplierSummary $s) => $s->contactPerson,
            'Cell phone' => fn (SupplierSummary $s) => $s->cellPhone,
            'Credit days' => fn (SupplierSummary $s) => $s->creditDays,
            'VAT subject' => fn (SupplierSummary $s) => $s->vatSubject,
            'Active' => fn (SupplierSummary $s) => $s->active,
        ];
    }

    /** @return array<string, Closure(DisbursementSummary): (string|int|float|bool|null)> */
    public static function disbursements(): array
    {
        return [
            'Number' => fn (DisbursementSummary $d) => $d->number,
            'Date' => fn (DisbursementSummary $d) => $d->date,
            'Branch' => fn (DisbursementSummary $d) => $d->branchName,
            'Supplier' => fn (DisbursementSummary $d) => $d->supplierName,
            'Payment' => fn (DisbursementSummary $d) => $d->paymentMethod,
            'Reference' => fn (DisbursementSummary $d) => $d->reference,
            'Grand total' => fn (DisbursementSummary $d) => $d->grandTotal,
            'Due date' => fn (DisbursementSummary $d) => $d->invoiceDueDate,
            'Overdue' => fn (DisbursementSummary $d) => $d->overdue,
            'Status' => fn (DisbursementSummary $d) => $d->statusName,
            'Notes' => fn (DisbursementSummary $d) => $d->notes,
        ];
    }

    /** @return array<string, Closure(GeneralJournalEntry): (string|int|float|bool|null)> */
    public static function generalJournals(): array
    {
        return [
            'Journal ID' => fn (GeneralJournalEntry $e) => $e->journalHeadId,
            'Date' => fn (GeneralJournalEntry $e) => $e->date,
            'Transaction type' => fn (GeneralJournalEntry $e) => $e->transactionType,
            'Reference' => fn (GeneralJournalEntry $e) => $e->reference,
            'Notes' => fn (GeneralJournalEntry $e) => $e->notes,
        ];
    }

    /** @return array<string, Closure(MemorialJournalSummary): (string|int|float|bool|null)> */
    public static function memorialJournals(): array
    {
        return [
            'Number' => fn (MemorialJournalSummary $j) => $j->number,
            'Date' => fn (MemorialJournalSummary $j) => $j->date,
            'Status' => fn (MemorialJournalSummary $j) => $j->statusName,
            'Created by' => fn (MemorialJournalSummary $j) => $j->createdBy,
            'Notes' => fn (MemorialJournalSummary $j) => $j->notes,
        ];
    }

    /** @return array<string, Closure(LedgerEntry): (string|int|float|bool|null)> */
    public static function ledgerEntries(): array
    {
        return [
            'Date' => fn (LedgerEntry $e) => $e->date,
            'Branch' => fn (LedgerEntry $e) => $e->branchName,
            'Account no.' => fn (LedgerEntry $e) => $e->coaNo,
            'Account' => fn (LedgerEntry $e) => $e->coaDescription,
            'Transaction type' => fn (LedgerEntry $e) => $e->transactionType,
            'Reference' => fn (LedgerEntry $e) => $e->reference,
            'Notes' => fn (LedgerEntry $e) => $e->notes,
            'Debit' => fn (LedgerEntry $e) => $e->debit,
            'Credit' => fn (LedgerEntry $e) => $e->credit,
            'Cost center' => fn (LedgerEntry $e) => $e->costCenter,
            'Project' => fn (LedgerEntry $e) => $e->project,
        ];
    }

    /** @return array<string, Closure(LedgerSummaryRow): (string|int|float|bool|null)> */
    public static function ledgerSummary(): array
    {
        return [
            'Date' => fn (LedgerSummaryRow $r) => $r->date,
            'Branch code' => fn (LedgerSummaryRow $r) => $r->branchCode,
            'Branch' => fn (LedgerSummaryRow $r) => $r->branchName,
            'Account no.' => fn (LedgerSummaryRow $r) => $r->accountNo,
            'Account' => fn (LedgerSummaryRow $r) => $r->accountDescription,
            'Debit' => fn (LedgerSummaryRow $r) => $r->debit,
            'Credit' => fn (LedgerSummaryRow $r) => $r->credit,
        ];
    }
}
