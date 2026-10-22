<?php

namespace App\Http\Requests\Promotions;

use App\Authorization\Permission;
use App\Domain\Canonical\PromotionDraft;
use App\Domain\Canonical\PromotionType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * A POS promotion to create ([ESB-050]), checked against the vendor's
 * documented fields before anything reaches the ESB. The common rules
 * apply to every type; the type's own rules only to the fields
 * PromotionType::fields() names -- anything else in the request is not
 * validated and never sent.
 *
 * Sizes are the vendor's (codes String(20), notes and description
 * String(100), payment method name String(50), prefix String(5)). Whether
 * a branch, menu, payment method or employee group exists is the ERP's to
 * say.
 */
class CreatePromotionRequest extends BaseFormRequest
{
    public const APPLY_TO = ['All Transaction', 'Member & Staff', 'Staff Only', 'Member Only'];

    public const APPLICATIONS = ['pos', 'eso'];

    public const VOUCHER_SOURCES = ['ESB', 'Giftee'];

    /** Gateway bounds on the lists; the vendor documents none. */
    private const MAX_LIST = 200;

    private const AMOUNT_MAX = 9999999999999999;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManagePromotions);
    }

    public function type(): ?PromotionType
    {
        $type = $this->input('type');

        return is_numeric($type) ? PromotionType::tryFrom((int) $type) : null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $type = $this->type();
        $scoped = fn (int $kind): bool => ! $this->boolean('allCategories') && (int) $this->input('applyDiscountTo') === $kind;
        $ids = ['integer', 'min:1', 'distinct'];

        $rules = [
            'type' => ['required', 'integer', Rule::enum(PromotionType::class)],
            'masterCode' => ['nullable', 'string', 'max:50'],
            'branchCodes' => ['required', 'array', 'min:1', 'max:'.self::MAX_LIST],
            'branchCodes.*' => ['required', 'string', 'max:20', 'distinct'],
            'notes' => ['required', 'string', 'max:100'],
            'promotionCode' => [$type?->isEso() ? 'required' : 'nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:100'],
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*' => ['integer', 'between:1,7', 'distinct'],
            'startAt' => ['required', 'date_format:Y-m-d H:i:s'],
            'endAt' => ['required', 'date_format:Y-m-d H:i:s', 'after:startAt'],
            'allCategories' => ['required', 'boolean'],
            'applyDiscountTo' => [Rule::requiredIf(! $this->boolean('allCategories')), 'nullable', 'integer', Rule::in([
                PromotionDraft::APPLY_TO_MENU_CATEGORY,
                PromotionDraft::APPLY_TO_MENU_CATEGORY_DETAIL,
                PromotionDraft::APPLY_TO_MENU,
            ])],
            'menuCategoryIds' => [Rule::requiredIf($scoped(PromotionDraft::APPLY_TO_MENU_CATEGORY)), 'nullable', 'array', 'max:'.self::MAX_LIST],
            'menuCategoryIds.*' => $ids,
            'menuCategoryDetailIds' => [Rule::requiredIf($scoped(PromotionDraft::APPLY_TO_MENU_CATEGORY_DETAIL)), 'nullable', 'array', 'max:'.self::MAX_LIST],
            'menuCategoryDetailIds.*' => $ids,
            'menuIds' => [Rule::requiredIf($scoped(PromotionDraft::APPLY_TO_MENU)), 'nullable', 'array', 'max:'.self::MAX_LIST],
            'menuIds.*' => $ids,
            'usedForLoyalty' => ['nullable', 'boolean'],
            'paymentMethodName' => ['nullable', 'string', 'max:50'],
        ];

        foreach ($type?->fields() ?? [] as $field) {
            foreach ($this->fieldRules($type, $field) as $key => $fieldRules) {
                $rules[$key] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * The rules for one of the type's own fields (and its items).
     *
     * @return array<string, list<mixed>>
     */
    private function fieldRules(PromotionType $type, string $field): array
    {
        $amount = ['required', 'numeric', 'min:0', 'max:'.self::AMOUNT_MAX, 'decimal:0,4'];
        $eso = $type !== PromotionType::FreeItem || PromotionType::appliesToEso((array) $this->all());

        return match ($field) {
            'discount' => ['discount' => $type->isPercentage()
                ? ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,4']
                : $amount],
            'minSalesPrice', 'maxSalesPrice', 'maxDiscount' => [$field => $amount],
            'authorizationNeeded', 'includePackageContent', 'includeMenuExtra', 'showOnEso' => [$field => ['required', 'boolean']],
            'applyTo' => ['applyTo' => ['nullable', 'string', Rule::in(self::APPLY_TO)]],
            'employeeGroups' => [
                'employeeGroups' => ['nullable', 'array', 'max:'.self::MAX_LIST, 'prohibited_unless:applyTo,Member & Staff,Staff Only'],
                'employeeGroups.*' => ['string', 'max:100', 'distinct'],
            ],
            'times' => [
                'times' => ['nullable', 'array', 'max:24'],
                'times.*' => ['array'],
                'times.*.start' => ['required', 'date_format:H:i:s'],
                'times.*.end' => ['required', 'date_format:H:i:s', 'after:times.*.start'],
            ],
            'maxUsage', 'maxUsageTotal' => [$field => [$eso ? 'required' : 'nullable', 'integer', 'min:0', 'max:2147483647']],
            'visitPurposeIds' => [
                'visitPurposeIds' => ['nullable', 'array', 'max:'.self::MAX_LIST],
                'visitPurposeIds.*' => ['integer', 'min:1', 'distinct'],
            ],
            'selfOrderPaymentMethodCodes' => [
                'selfOrderPaymentMethodCodes' => [$eso ? 'required' : 'nullable', 'array', 'max:'.self::MAX_LIST],
                'selfOrderPaymentMethodCodes.*' => ['string', 'max:100', 'distinct'],
            ],
            'bankIdentificationNumbers' => [
                'bankIdentificationNumbers' => ['nullable', 'array', 'max:'.self::MAX_LIST],
                'bankIdentificationNumbers.*' => ['string', 'digits:6', 'distinct'],
            ],
            'applications' => [
                'applications' => ['required', 'array', 'min:1', 'max:2'],
                'applications.*' => ['string', Rule::in(self::APPLICATIONS), 'distinct'],
            ],
            'voucherSource' => ['voucherSource' => ['required', 'string', Rule::in(self::VOUCHER_SOURCES)]],
            'prefix' => ['prefix' => ['required_if:voucherSource,Giftee', 'nullable', 'string', 'max:5']],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'endAt.after' => 'The promotion must end after it starts.',
            'times.*.end.after' => 'A time window must end after it starts.',
            'employeeGroups.prohibited_unless' => 'Employee groups apply only to "Member & Staff" or "Staff Only".',
            'prefix.required_if' => 'A Giftee voucher needs its prefix.',
            'bankIdentificationNumbers.*.digits' => 'A bank identification number has 6 digits.',
        ];
    }

    public function toDraft(): PromotionDraft
    {
        $type = PromotionType::from((int) $this->validatedInt('type'));
        $validated = $this->validated();
        $allCategories = (bool) $validated['allCategories'];
        $applyDiscountTo = $allCategories ? null : $this->validatedInt('applyDiscountTo');

        $values = [];

        foreach ($type->fields() as $field) {
            $values[$field] = self::value($field, $validated[$field] ?? null);
        }

        return new PromotionDraft(
            type: $type,
            branchCodes: array_values(array_map('strval', $validated['branchCodes'])),
            notes: (string) $validated['notes'],
            promotionCode: $this->validatedString('promotionCode') ?? '',
            days: array_values(array_map('intval', $validated['days'])),
            startAt: (string) $validated['startAt'],
            endAt: (string) $validated['endAt'],
            allCategories: $allCategories,
            applyDiscountTo: $applyDiscountTo,
            scopeIds: array_values(array_map('intval', (array) match ($applyDiscountTo) {
                PromotionDraft::APPLY_TO_MENU_CATEGORY => $validated['menuCategoryIds'] ?? [],
                PromotionDraft::APPLY_TO_MENU_CATEGORY_DETAIL => $validated['menuCategoryDetailIds'] ?? [],
                PromotionDraft::APPLY_TO_MENU => $validated['menuIds'] ?? [],
                default => [],
            })),
            masterCode: $this->validatedString('masterCode'),
            description: $this->validatedString('description'),
            usedForLoyalty: isset($validated['usedForLoyalty']) ? (bool) $validated['usedForLoyalty'] : null,
            paymentMethodName: $this->validatedString('paymentMethodName'),
            values: $values,
        );
    }

    /**
     * A validated value in the shape the draft carries: booleans as bool,
     * numbers as numbers, lists as lists.
     */
    private static function value(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'authorizationNeeded', 'includePackageContent', 'includeMenuExtra', 'showOnEso' => (bool) $value,
            'discount', 'minSalesPrice', 'maxSalesPrice', 'maxDiscount' => is_numeric($value) ? $value + 0 : null,
            'maxUsage', 'maxUsageTotal' => (int) $value,
            'visitPurposeIds' => array_values(array_map('intval', (array) $value)),
            'employeeGroups', 'selfOrderPaymentMethodCodes', 'bankIdentificationNumbers', 'applications' => array_values(array_map('strval', (array) $value)),
            'times' => array_values(array_map(
                static fn (array $time): array => ['start' => (string) $time['start'], 'end' => (string) $time['end']],
                (array) $value,
            )),
            default => is_string($value) ? $value : null,
        };
    }
}
