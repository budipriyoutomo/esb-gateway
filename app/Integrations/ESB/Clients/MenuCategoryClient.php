<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\MenuCategoryDraft;
use App\Domain\Canonical\MenuCategoryDraftDetail;
use App\Domain\Canonical\MenuCategoryPage;
use App\Integrations\Contracts\MenuCategorySource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\MenuCategoryMapper;

/**
 * Adapter for ESB OMS Master Menu Category ([ESB-047], [ESB-048]), on the
 * FnB backend hosts. The create and update are POSTs that write: one
 * attempt each.
 */
class MenuCategoryClient implements MenuCategorySource
{
    public const PROVIDER = 'esb';

    /**
     * How many pages a category is looked for on: 200 categories at the
     * vendor's page size of 10.
     */
    public const MAX_PAGES = 20;

    private const FAMILY = EsbFamily::FnbBackend;

    private const PATH = '/corev1/master/';

    public function __construct(
        private readonly EsbClient $client,
        private readonly MenuCategoryMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-oms/#api-Master_Menu_Category-get-menu-category
     */
    public function listCategories(int $page = 1): MenuCategoryPage
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'get-menu-category', ['page' => $page], operation: 'menu-categories.list');

        return new MenuCategoryPage(
            categories: $this->mapper->fromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * The page the person came from first, then the others in order, up to
     * MAX_PAGES reads in all.
     *
     * @throws EsbException
     */
    public function findCategory(int $id, int $page = 1): ?array
    {
        $first = $this->listCategories($page);
        $found = $first->find($id);

        if ($found !== null) {
            return [$found, $page];
        }

        $lastPage = $first->pagination?->lastPage() ?? 1;
        $reads = 1;

        for ($other = 1; $other <= $lastPage && $reads < self::MAX_PAGES; $other++) {
            if ($other === $page) {
                continue;
            }

            $reads++;
            $found = $this->listCategories($other)->find($id);

            if ($found !== null) {
                return [$found, $other];
            }
        }

        return null;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-oms/#api-Master_Menu_Category-create-menu-category
     */
    public function createCategory(MenuCategoryDraft $draft): ?int
    {
        $envelope = $this->client->post(self::FAMILY, self::PATH.'create-menu-category', [$this->body($draft)], operation: 'menu-categories.create');

        $saved = $this->mapper->fromRows($envelope->resultArray());

        return $saved === [] ? null : $saved[0]->id;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-oms/#api-Master_Menu_Category-update-menu-category
     */
    public function updateCategory(int $id, MenuCategoryDraft $draft): void
    {
        $this->client->post(
            self::FAMILY,
            self::PATH.'update-menu-category',
            [['menuCategoryID' => $id, ...$this->body($draft, update: true)]],
            operation: 'menu-categories.update',
        );
    }

    /**
     * One category of the vendor's array body. Texts go as "" when empty
     * (the vendor's examples); an update names each detail it keeps, and ""
     * for a new one. A detail's max order is sent only when given.
     *
     * @return array<string, mixed>
     */
    private function body(MenuCategoryDraft $draft, bool $update = false): array
    {
        return [
            'menuCategoryName' => $draft->name,
            'menuCategoryCode' => $draft->code,
            'salesAccount' => $draft->salesAccount,
            'cogsAccount' => $draft->cogsAccount,
            'discountAccount' => $draft->discountAccount,
            'description' => $draft->description,
            'themeCategoryOnPos' => $draft->buttonColor,
            'menuCategoryDetails' => array_map(static fn (MenuCategoryDraftDetail $detail): array => [
                ...($update ? ['menuCategoryDetailID' => $detail->id ?? ''] : []),
                'menuCategoryDetailName' => $detail->name,
                'menuCategoryDetailCode' => $detail->code,
                'description' => $detail->description,
                ...($detail->maxOrderQty === null ? [] : ['maxOrderQty' => $detail->maxOrderQty]),
                'menuCategoryDetailTheme' => $detail->buttonColor,
            ], $draft->details),
        ];
    }
}
