<?php

declare(strict_types=1);

namespace TypeID\Exception;

use InvalidArgumentException;

class ValidationException extends InvalidArgumentException
{
    // This exception is thrown when a TypeID fails validation
}
