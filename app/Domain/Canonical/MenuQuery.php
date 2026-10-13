<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a MenuSource for. Values are already
 * validated; null means "omit this filter".
 *
 * From the ESB Core GET /menu/browse parameters ([ESB-012]). `sort` is not
 * offered: its accepted values are undocumented beyond the default
 * `menuName`.
 */
readonly class MenuQuery
{
    /**
     * The gateway's own ceiling, the same as for products. The vendor
     * documents none for this operation (TBD).
     */
    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 20;

    public const CODE_MAX_LENGTH = 50;

    public const NAME_MAX_LENGTH = 100;

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $name = null,
        public ?string $code = null,
        public ?bool $activeOnly = null,
    ) {}
}
