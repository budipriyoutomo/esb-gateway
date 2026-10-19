<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\DocumentTemplate;
use App\Domain\Canonical\DocumentTemplateDraft;
use App\Domain\Canonical\DocumentTemplatePage;
use App\Domain\Canonical\DocumentTemplateQuery;
use App\Domain\Canonical\ProductDetailOption;
use App\Domain\Canonical\ProductUnits;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for document templates, and asked to create
 * one. Update, delete, and push/pull are not built ([ESB-016]).
 *
 * App\Integrations\ESB\Clients\DocumentTemplateClient is the reference
 * implementation; callers depend on this contract (ADR-016, ADR-019).
 */
interface DocumentTemplateSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listDocumentTemplates(DocumentTemplateQuery $query): DocumentTemplatePage;

    /**
     * The template with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findDocumentTemplate(int $id): ?DocumentTemplate;

    /**
     * Create a template. Not idempotent: one attempt, and a failure where
     * EsbException::mayHaveBeenApplied() is true leaves the outcome unknown.
     *
     * @return int|null The new template's id, when the provider returned one.
     *
     * @throws EsbException
     */
    public function createDocumentTemplate(DocumentTemplateDraft $draft): ?int;

    /**
     * Products in a unit whose name matches, for picking template lines.
     *
     * @return list<ProductDetailOption>
     *
     * @throws EsbException
     */
    public function browseProductDetails(string $name): array;

    /**
     * The products whose code is exactly this one (ignoring case), each with
     * its active units, for resolving an imported product code + unit.
     * Inactive products are returned too, so a caller can say why.
     *
     * @return list<ProductUnits>
     *
     * @throws EsbException
     */
    public function findProductsByCode(string $code): array;
}
