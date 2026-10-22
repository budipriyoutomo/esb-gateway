<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\PromotionDraft;
use App\Domain\Canonical\PromotionPage;
use App\Domain\Canonical\PromotionQuery;
use App\Integrations\Contracts\PromotionSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\PromotionMapper;

/**
 * Adapter for ESB OMS Master Promotion ([ESB-049], [ESB-050]), on the FnB
 * backend hosts. The list answers a bare JSON array (ADR-027); the create
 * is a write: one attempt.
 */
class PromotionClient implements PromotionSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::FnbBackend;

    /**
     * Each type's own fields: the vendor's name, and how the value is sent.
     *
     * @var array<string, array{string, string}>
     */
    private const FIELDS = [
        'discount' => ['discount', 'scalar'],
        'authorizationNeeded' => ['authorizationNeeded', 'flag'],
        'minSalesPrice' => ['minSalesPrice', 'scalar'],
        'maxSalesPrice' => ['maxSalesPrice', 'scalar'],
        'maxDiscount' => ['maxDiscount', 'scalar'],
        'applyTo' => ['applyTo', 'scalar'],
        'employeeGroups' => ['employeeGroupName', 'list'],
        'includePackageContent' => ['includePackageContent', 'flag'],
        'includeMenuExtra' => ['includeMenuExtra', 'flag'],
        'times' => ['promotionTime', 'times'],
        'showOnEso' => ['showPromotionEzo', 'flag'],
        'maxUsage' => ['maxUsage', 'scalar'],
        'maxUsageTotal' => ['maxUsageTotal', 'scalar'],
        'visitPurposeIds' => ['visitPurposeID', 'list'],
        'selfOrderPaymentMethodCodes' => ['selfOrderPaymentMethodCode', 'list'],
        'bankIdentificationNumbers' => ['bankIdentificationNumbers', 'list'],
        'applications' => ['applyToApplicationID', 'list'],
        'voucherSource' => ['voucherSourceName', 'scalar'],
        'prefix' => ['prefixPromotion', 'scalar'],
    ];

    public function __construct(
        private readonly EsbClient $client,
        private readonly PromotionMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-oms/#api-Master_Promotion-promotion-list
     */
    public function listPromotions(PromotionQuery $query): PromotionPage
    {
        $rows = $this->client->getBareList(
            self::FAMILY,
            '/extv1/promotion',
            array_filter([
                'branchCode' => $query->branchCode,
                'promotionCode' => $query->promotionCode,
            ], static fn (?string $value): bool => $value !== null),
            operation: 'promotions.list',
        );

        return PromotionPage::fromAll($this->mapper->fromRows($rows), $query->page);
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-oms/#api-Master_Promotion-discount-percentage
     */
    public function createPromotion(PromotionDraft $draft): ?int
    {
        $envelope = $this->client->post(self::FAMILY, '/corev1/promotion/', $this->body($draft), operation: 'promotions.create');

        // The vendor answers {code: 200, data: {promotionID, notes}}, not `result`.
        $data = $envelope->body['data'] ?? null;
        $id = is_array($data) ? ($data['promotionID'] ?? null) : null;

        return is_int($id) && $id > 0 ? $id : null;
    }

    /**
     * The common fields, then the type's own. Booleans go as "Yes" / "No"
     * and times as {startTime, endTime}, as in the vendor's examples; an
     * optional value that is not given is left out, a list is sent even
     * when empty.
     *
     * @return array<string, mixed>
     */
    private function body(PromotionDraft $draft): array
    {
        $scope = static fn (int $kind): array => ! $draft->allCategories && $draft->applyDiscountTo === $kind ? $draft->scopeIds : [];

        $body = [
            ...($draft->masterCode === null ? [] : ['promotionMasterCode' => $draft->masterCode]),
            'branchCode' => $draft->branchCodes,
            'promotionType' => $draft->type->value,
            'notes' => $draft->notes,
            'promotionDaysID' => $draft->days,
            'startDate' => $draft->startAt,
            'endDate' => $draft->endAt,
            'allCategories' => self::yesNo($draft->allCategories),
            ...($draft->allCategories ? [] : ['applyDiscountTo' => $draft->applyDiscountTo]),
            'menuCategoryID' => $scope(PromotionDraft::APPLY_TO_MENU_CATEGORY),
            'menuCategoryDetailID' => $scope(PromotionDraft::APPLY_TO_MENU_CATEGORY_DETAIL),
            'menuID' => $scope(PromotionDraft::APPLY_TO_MENU),
            ...($draft->usedForLoyalty === null ? [] : ['usedForLoyalty' => self::yesNo($draft->usedForLoyalty)]),
            'promotionCode' => $draft->promotionCode,
            ...($draft->description === null ? [] : ['promotionDesc' => $draft->description]),
            ...($draft->paymentMethodName === null ? [] : ['paymentMethodName' => $draft->paymentMethodName]),
        ];

        foreach ($draft->type->fields() as $field) {
            if (! $draft->type->sends($field, $draft->values)) {
                continue;
            }

            [$vendor, $kind] = self::FIELDS[$field];
            $value = $draft->values[$field] ?? null;

            $sent = match ($kind) {
                'flag' => $value === null ? null : self::yesNo((bool) $value),
                'list' => is_array($value) ? array_values($value) : [],
                'times' => array_map(
                    static fn (array $time): array => ['startTime' => $time['start'], 'endTime' => $time['end']],
                    is_array($value) ? array_values($value) : [],
                ),
                default => $value,
            };

            if ($sent !== null) {
                $body[$vendor] = $sent;
            }
        }

        return $body;
    }

    private static function yesNo(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }
}
