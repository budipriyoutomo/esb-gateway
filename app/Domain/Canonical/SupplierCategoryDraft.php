<?php

namespace App\Domain\Canonical;

/**
 * A supplier category to create, or to rename an existing one to
 * ([ESB-030]).
 */
final readonly class SupplierCategoryDraft
{
    public function __construct(public string $name) {}
}
