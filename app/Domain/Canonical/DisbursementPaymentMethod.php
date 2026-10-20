<?php

namespace App\Domain\Canonical;

/**
 * How a disbursement is paid ([ESB-037]): the vendor's paymentID. Cash goes
 * out of a cash / bank account now; credit is owed to a supplier.
 */
enum DisbursementPaymentMethod: int
{
    case Cash = 1;
    case Credit = 2;
}
