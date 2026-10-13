<?php

namespace App\Domain\Canonical;

/**
 * One page of canonical menus, plus the page state that produced it.
 */
readonly class MasterMenuPage
{
    /**
     * @param  list<MasterMenu>  $menus
     */
    public function __construct(
        public array $menus,
        public ?Pagination $pagination = null,
    ) {}
}
