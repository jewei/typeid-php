<?php

declare(strict_types=1);

namespace TypeID\Exception;

use RuntimeException;

/** Thrown when a TypeID cannot be constructed from the given input. */
final class ConstructorException extends RuntimeException {}
