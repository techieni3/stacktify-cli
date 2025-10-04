<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Exceptions;

use Exception;

/**
 * Indicates that the Git identity is not configured.
 */
class GitIdentityMissing extends Exception {}
