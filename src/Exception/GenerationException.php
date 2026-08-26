<?php

declare(strict_types=1);

namespace TypeID\Exception;

use RuntimeException;

/** Thrown when TypeID generation fails operationally. */
final class GenerationException extends RuntimeException implements TypeIDException {}
