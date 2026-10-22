<?php

namespace App\Domain\Canonical;

/**
 * The five promotion bodies the vendor documents on one path ([ESB-050]),
 * by their promotionType id. Each type takes its own fields beyond the
 * common ones; a field another type takes is never sent.
 */
enum PromotionType: int
{
    case DiscountPercentage = 1;
    case DiscountLimitPercentage = 10;
    case DiscountPercentageEso = 5;
    case DiscountAmountEso = 6;
    case FreeItem = 4;

    public function label(): string
    {
        return match ($this) {
            self::DiscountPercentage => 'Discount (%)',
            self::DiscountLimitPercentage => 'Discount Limit (%)',
            self::DiscountPercentageEso => 'Discount (%) ESO',
            self::DiscountAmountEso => 'Discount (Rp) ESO',
            self::FreeItem => 'Free Item',
        };
    }

    /**
     * The type's own fields, in the order they are sent.
     *
     * @return list<string>
     */
    public function fields(): array
    {
        return match ($this) {
            self::DiscountPercentage, self::DiscountLimitPercentage => [
                'discount', 'authorizationNeeded', 'minSalesPrice', 'maxSalesPrice', 'applyTo',
                'employeeGroups', 'includePackageContent', 'includeMenuExtra', 'times',
            ],
            self::DiscountPercentageEso => [
                'discount', 'minSalesPrice', 'maxDiscount', 'showOnEso', 'maxUsage', 'maxUsageTotal',
                'visitPurposeIds', 'selfOrderPaymentMethodCodes', 'bankIdentificationNumbers',
            ],
            self::DiscountAmountEso => [
                'discount', 'minSalesPrice', 'showOnEso', 'maxUsage', 'maxUsageTotal',
                'visitPurposeIds', 'selfOrderPaymentMethodCodes', 'bankIdentificationNumbers',
            ],
            self::FreeItem => [
                'authorizationNeeded', 'applyTo', 'employeeGroups', 'applications', 'selfOrderPaymentMethodCodes',
                'maxUsage', 'maxUsageTotal', 'visitPurposeIds', 'times', 'voucherSource', 'minSalesPrice', 'prefix',
            ],
        };
    }

    /**
     * The discount is a percentage (at most 100), not an amount.
     */
    public function isPercentage(): bool
    {
        return in_array($this, [self::DiscountPercentage, self::DiscountLimitPercentage, self::DiscountPercentageEso], true);
    }

    /**
     * An ESB Order promotion: its promotion code is required.
     */
    public function isEso(): bool
    {
        return in_array($this, [self::DiscountPercentageEso, self::DiscountAmountEso], true);
    }

    /**
     * Whether a field of this type is sent, given the values entered: a free
     * item takes the ESB Order fields only when it applies to ESB Order, and
     * a prefix only for Giftee vouchers.
     *
     * @param  array<string, mixed>  $values
     */
    public function sends(string $field, array $values): bool
    {
        if ($this !== self::FreeItem) {
            return true;
        }

        return match ($field) {
            'selfOrderPaymentMethodCodes', 'maxUsage', 'maxUsageTotal' => self::appliesToEso($values),
            'prefix' => ($values['voucherSource'] ?? null) === 'Giftee',
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function appliesToEso(array $values): bool
    {
        return is_array($values['applications'] ?? null) && in_array('eso', $values['applications'], true);
    }
}
