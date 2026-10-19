<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\DocumentTemplate;
use App\Domain\Canonical\DocumentTemplateDraft;
use App\Domain\Canonical\DocumentTemplatePage;
use App\Domain\Canonical\DocumentTemplateQuery;
use App\Domain\Canonical\ProductUnits;
use App\Integrations\Contracts\DocumentTemplateSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\DocumentTemplateMapper;

/**
 * Adapter for ESB Core Master Document Template: list, detail, and create
 * ([ESB-016]), plus the product-detail lookup the create screen needs.
 *
 * The reads have no Service around them. The create is wrapped by
 * App\Actions\DocumentTemplates\CreateDocumentTemplate, which adds the
 * in-flight lock and the audit trail.
 */
class DocumentTemplateClient implements DocumentTemplateSource
{
    public const PROVIDER = 'esb';

    /**
     * The Master Document Template group is documented under the Core base
     * URLs.
     */
    private const FAMILY = EsbFamily::Core;

    private const PATH = '/document-template';

    /**
     * Products per picker search; each costs a GET /product/{productID}.
     */
    private const PRODUCT_PICKER_LIMIT = 10;

    /**
     * Products per code lookup: the code itself plus any partial-match
     * neighbours (FC00 matches every FC00xx).
     */
    private const PRODUCT_CODE_LIMIT = 50;

    public function __construct(
        private readonly EsbClient $client,
        private readonly DocumentTemplateMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/document-template/get
     */
    public function listDocumentTemplates(DocumentTemplateQuery $query): DocumentTemplatePage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            array_filter([
                'page' => $query->page,
                'limit' => $query->limit,
                'requestTemplateName' => $query->name,
                'requestTemplateTypeID' => $query->type?->value,
                'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'document-template.list',
        );

        return new DocumentTemplatePage(
            templates: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/document-template-requestTemplateID/get
     */
    public function findDocumentTemplate(int $id): ?DocumentTemplate
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/'.$id,
            operation: 'document-template.view',
        );

        return $this->mapper->fromDetail($envelope->resultArray());
    }

    /**
     * EsbClient makes a single attempt for any write.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/document-template/post
     */
    public function createDocumentTemplate(DocumentTemplateDraft $draft): ?int
    {
        $envelope = $this->client->post(
            self::FAMILY,
            self::PATH,
            $this->mapper->toRequestBody($draft),
            operation: 'document-template.create',
        );

        return $this->mapper->createdIdFrom($envelope->resultArray());
    }

    /**
     * Active products whose name matches, each in its active units. One page
     * is enough for a picker; the user narrows the name rather than paging.
     *
     * Costs one read per product found, so the page is small.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/product-list/get
     */
    public function browseProductDetails(string $name): array
    {
        $envelope = $this->client->get(
            self::FAMILY,
            '/product/list',
            ['page' => 1, 'limit' => self::PRODUCT_PICKER_LIMIT, 'productName' => $name, 'flagActive' => 1],
            operation: 'product.list',
        );

        $options = [];

        foreach ($this->mapper->productIdsFromRows($envelope->rows()) as $productId) {
            $options = [...$options, ...$this->productUnits($productId)->units];
        }

        return $options;
    }

    /**
     * The ESB matches productCode partially, so the list is narrowed to the
     * exact code before any product is read.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/product-list/get
     */
    public function findProductsByCode(string $code): array
    {
        $envelope = $this->client->get(
            self::FAMILY,
            '/product/list',
            ['page' => 1, 'limit' => self::PRODUCT_CODE_LIMIT, 'productCode' => $code],
            operation: 'product.list',
        );

        return array_map(
            $this->productUnits(...),
            $this->mapper->productIdsFromRows($envelope->rows(), $code),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/product-productID/get
     */
    private function productUnits(int $productId): ProductUnits
    {
        $product = $this->client->get(
            self::FAMILY,
            '/product/'.$productId,
            operation: 'product.view',
        )->resultArray();

        $units = $this->mapper->productDetailsFromProduct($product);

        return new ProductUnits(
            productId: $productId,
            productCode: is_string($product['productCode'] ?? null) ? $product['productCode'] : null,
            productName: is_string($product['productName'] ?? null) ? $product['productName'] : '',
            active: $this->mapper->productIsActive($product),
            units: $units,
        );
    }
}
