<?php

namespace App\Integrations\ESB\Exceptions;

use RuntimeException;

/**
 * The ESB integration is misconfigured -- an unknown environment, a family with
 * no documented host, or a missing credential.
 *
 * This is a deployment problem, not an upstream failure, so it is deliberately
 * not part of the EsbException hierarchy that callers catch and normalize.
 */
class EsbConfigurationException extends RuntimeException {}
