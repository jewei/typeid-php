<?php

declare(strict_types=1);

namespace TypeID\Exception;

use RuntimeException;

/** Thrown when UUIDv7 generation fails. */
final class GenerationException extends RuntimeException implements TypeIDException {}
