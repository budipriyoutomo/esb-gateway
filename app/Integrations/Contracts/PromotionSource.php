<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\PromotionDraft;
use App\Domain\Canonical\PromotionPage;
use App\Domain\Canonical\PromotionQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Reads POS promotions ([ESB-049]) and creates them ([ESB-050]).
 */
interface PromotionSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listPromotions(PromotionQuery $query): PromotionPage;

    /**
     * The new promotion's id, or null when the provider does not say it.
     *
     * @throws EsbException
     */
    public function createPromotion(PromotionDraft $draft): ?int;
}
