<?php

namespace App\Http\Controllers\Promotions;

use App\Actions\Promotions\PromotionWrites;
use App\Domain\Canonical\PromotionType;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\CreatePromotionRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create POS promotions ([ESB-050], S3). Authorization (esb.invoke +
 * promotions.manage) is on the routes and the form request. There is no
 * promotion page to land on, so a create lands on the list.
 */
class PromotionFormController extends Controller
{
    use HandlesRecordForms;

    /**
     * Vendor field => form field, for the ERP's errors in `data`
     * (ADR-021).
     */
    private const FIELD_MAP = [
        'promotionMasterCode' => 'masterCode',
        'branchCode' => 'branchCodes',
        'promotionType' => 'type',
        'notes' => 'notes',
        'promotionDaysID' => 'days',
        'startDate' => 'startAt',
        'endDate' => 'endAt',
        'allCategories' => 'allCategories',
        'applyDiscountTo' => 'applyDiscountTo',
        'menuCategoryID' => 'menuCategoryIds',
        'menuCategoryDetailID' => 'menuCategoryDetailIds',
        'menuID' => 'menuIds',
        'usedForLoyalty' => 'usedForLoyalty',
        'promotionCode' => 'promotionCode',
        'promotionDesc' => 'description',
        'paymentMethodName' => 'paymentMethodName',
        'discount' => 'discount',
        'authorizationNeeded' => 'authorizationNeeded',
        'minSalesPrice' => 'minSalesPrice',
        'maxSalesPrice' => 'maxSalesPrice',
        'maxDiscount' => 'maxDiscount',
        'applyTo' => 'applyTo',
        'employeeGroupName' => 'employeeGroups',
        'includePackageContent' => 'includePackageContent',
        'includeMenuExtra' => 'includeMenuExtra',
        'promotionTime' => 'times',
        'showPromotionEzo' => 'showOnEso',
        'maxUsage' => 'maxUsage',
        'maxUsageTotal' => 'maxUsageTotal',
        'visitPurposeID' => 'visitPurposeIds',
        'selfOrderPaymentMethodCode' => 'selfOrderPaymentMethodCodes',
        'bankIdentificationNumbers' => 'bankIdentificationNumbers',
        'applyToApplicationID' => 'applications',
        'voucherSourceName' => 'voucherSource',
        'prefixPromotion' => 'prefix',
    ];

    public function create(): Response
    {
        return Inertia::render('promotions/form', [
            'types' => array_map(static fn (PromotionType $type): array => [
                'id' => $type->value,
                'name' => $type->label(),
                'fields' => $type->fields(),
            ], PromotionType::cases()),
            'applyTo' => CreatePromotionRequest::APPLY_TO,
            'voucherSources' => CreatePromotionRequest::VOUCHER_SOURCES,
        ]);
    }

    public function store(CreatePromotionRequest $request, PromotionWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome(
            'Promotion',
            $draft->notes,
            'promotions',
            null,
            function () use ($writes, $draft): void {
                // The id is audited; there is no promotion page to open.
                $writes->create($draft);
            },
            self::FIELD_MAP,
        );
    }
}
