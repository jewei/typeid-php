<?php

declare(strict_types=1);

namespace TypeID\Exception;

use InvalidArgumentException;

/** Thrown when a TypeID prefix or suffix fails spec validation. */
final class ValidationException extends InvalidArgumentException {}
