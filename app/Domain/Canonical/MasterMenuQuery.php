<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a MasterMenuSource for. Values are already
 * validated; null means "omit this filter".
 *
 * The ESB documents only `page` and `menuCode` for Get Menu, with a fixed
 * page size. Its active-flag parameter has no usable name in the vendor
 * documentation, so it is not offered and the ESB's default (active menus
 * only) applies. TBD in docs/05-api-integration.md, [ESB-013].
 */
readonly class MasterMenuQuery
{
    public const CODE_MAX_LENGTH = 50;

    public function __construct(
        public int $page = 1,
        public ?string $code = null,
    ) {}
}
