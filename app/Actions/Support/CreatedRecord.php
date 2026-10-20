<?php

namespace App\Actions\Support;

/**
 * What the ERP said about a record it just created: its id, if it returned
 * one, and whether the record is waiting for approval there before it takes
 * effect (a product create can answer isTemp).
 */
final readonly class CreatedRecord
{
    public function __construct(
        public int|string|null $id,
        public bool $pendingApproval = false,
    ) {}
}
