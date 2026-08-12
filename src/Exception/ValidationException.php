<?php

declare(strict_types=1);

namespace TypeID\Exception;

use InvalidArgumentException;

/** Thrown when caller input fails TypeID validation. */
final class ValidationException extends InvalidArgumentException implements TypeIDException {}
