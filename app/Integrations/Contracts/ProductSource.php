<?php

namespace App\Integrations\Contracts;

use App\Actions\Support\CreatedRecord;
use App\Domain\Canonical\ProductDetail;
use App\Domain\Canonical\ProductDraft;
use App\Domain\Canonical\ProductPage;
use App\Domain\Canonical\ProductQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for product master data.
 *
 * Extracted from the working ESB implementation rather than designed in the
 * abstract, per ADR-017: App\Integrations\ESB\Clients\ProductClient is the
 * reference implementation, and callers depend on this contract so that a
 * second provider is a binding change rather than a rewrite.
 *
 * Nothing provider-shaped crosses this boundary in either direction -- the
 * vendor's parameter spelling and response envelope stay behind the adapter.
 */
interface ProductSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     *
     * TBD (ADR-019): the thrown type is still the ESB's. Normalizing it to a
     * provider-neutral exception needs a second provider's error model to
     * design against; EsbExceptionRenderer maps the current hierarchy onto HTTP
     * statuses today.
     */
    public function listProducts(ProductQuery $query): ProductPage;

    /**
     * The product with this id and its units, or null when the provider
     * answers successfully with none ([ESB-026]).
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findProduct(int $id): ?ProductDetail;

    /**
     * Delete a product. One attempt.
     *
     * @throws EsbException
     */
    public function deleteProduct(int $id): void;

    /**
     * Restore a deleted product. One attempt.
     *
     * @throws EsbException
     */
    public function restoreProduct(int $id): void;

    /**
     * Create a product. The ERP may hold it for approval (isTemp). One attempt.
     *
     * @throws EsbException
     */
    public function createProduct(ProductDraft $draft): CreatedRecord;

    /**
     * Replace a product. $current is the product as the ERP has it, for what
     * the draft does not carry. One attempt.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function updateProduct(int $id, ProductDraft $draft, ProductDetail $current): void;
}
