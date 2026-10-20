<?php

namespace App\Actions\Support;

use RuntimeException;

/**
 * The provider says the record is still in use, so it was not deleted.
 * Raised by a pre-check before the write; nothing was sent.
 */
class RecordInUse extends RuntimeException {}
