<?php

namespace App\Integrations\ESB;

/**
 * The ESB base URL families.
 *
 * The vendor declares a different host per endpoint group, so every ESB
 * operation must say which family it belongs to (ADR-010). The generated
 * catalogs in docs/esb/ record the production base URL declared for each group;
 * treat that as the source of truth when wiring an adapter.
 */
enum EsbFamily: string
{
    /** Almost all ESB Core groups. */
    case Core = 'core';

    /** ESB Core's Online_Voucher group. */
    case Voucher = 'voucher';

    /** Most ESB OMS groups, plus a few Core operations. */
    case FnbBackend = 'fnb_backend';

    /** ESB OMS Master_POS and parts of Report. */
    case Pos = 'pos';
}
