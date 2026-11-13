<?php

namespace App\Actions\ClosingRecaps;

use RuntimeException;

/**
 * A closing recap cannot be prepared or sent as it stands. The message is
 * written for the operator.
 */
class ClosingRecapNotReady extends RuntimeException {}
