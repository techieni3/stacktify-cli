<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Exceptions;

use Exception;

/**
 * Indicates that Git is not available on the system.
 */
class GitNotAvailable extends Exception {}
