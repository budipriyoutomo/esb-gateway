<?php

namespace App\Domain\Canonical;

/**
 * The reference lists a picker can search ([ESB-019]). The values are the
 * route segment of GET /lookups/{kind}.
 */
enum LookupKind: string
{
    case Branches = 'branches';
    case Suppliers = 'suppliers';
    case Locations = 'locations';
    case CoaAccounts = 'coa-accounts';
    case CashBankAccounts = 'cash-bank-accounts';
    case Statuses = 'statuses';
    case TransferProducts = 'transfer-products';

    /**
     * Whether the list belongs to one branch and cannot be asked for without it.
     */
    public function requiresBranch(): bool
    {
        return $this === self::TransferProducts;
    }

    /**
     * Whether a branch, when given, narrows the list.
     */
    public function acceptsBranch(): bool
    {
        return in_array($this, [self::Locations, self::CashBankAccounts, self::TransferProducts], true);
    }
}
