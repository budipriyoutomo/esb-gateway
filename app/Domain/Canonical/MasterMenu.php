<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A point-of-sale menu item with its prices, package groups, and extras.
 *
 * The field set is the ESB OMS Get Menu row's, read-only ([ESB-013]). Flags
 * are nullable because the vendor sends "Yes"/"No" labels or 1/0 and anything
 * else is not guessed at. `taxLabel` stays a label: the ESB returns "No",
 * "Yes, Tax", or "Yes, VAT", and the gateway applies no tax rule of its own.
 */
readonly class MasterMenu implements JsonSerializable
{
    /**
     * @param  list<MasterMenuTemplatePrice>  $templates
     * @param  list<MasterMenuPackageGroup>  $packageGroups
     * @param  list<MasterMenuExtra>  $extras
     * @param  list<string>  $icons
     * @param  list<string>  $tags
     * @param  list<MasterMenuReference>  $relatedMenus
     */
    public function __construct(
        public string $provider,
        public ?int $id,
        public string $code,
        public string $name,
        public ?string $shortName = null,
        public ?string $alternativeName = null,
        public ?string $categoryName = null,
        public ?string $bomName = null,
        public ?bool $active = null,
        public ?string $taxLabel = null,
        public ?bool $otherTax = null,
        public ?bool $openPrice = null,
        public ?bool $printZeroValue = null,
        public ?string $zeroValueText = null,
        public ?string $salesAccount = null,
        public ?string $cogsAccount = null,
        public ?string $discountAccount = null,
        public ?string $description = null,
        public ?string $notes = null,
        public ?string $buttonColor = null,
        public ?bool $separatePrintPackage = null,
        public ?bool $separateTaxCalculation = null,
        public array $templates = [],
        public array $packageGroups = [],
        public array $extras = [],
        public array $icons = [],
        public array $tags = [],
        public array $relatedMenus = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
