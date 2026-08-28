<?php

declare(strict_types=1);

namespace TypeID\Exception;

use InvalidArgumentException;

/**
 * Thrown when caller input fails TypeID validation.
 *
 * Construct through the named constructors below. Messages contain bounded
 * metadata rather than rejected values, which may be large, sensitive, or
 * unsafe to write to a log.
 *
 * The exception class is part of the supported surface. Its message wording is
 * not, and the named constructors are internal.
 */
final class ValidationException extends InvalidArgumentException implements TypeIDException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /** @internal */
    public static function invalidPrefix(int $actualLength): self
    {
        return new self("Invalid TypeID prefix ({$actualLength} bytes)");
    }

    /** @internal */
    public static function invalidSuffix(int $actualLength): self
    {
        return new self("Invalid TypeID suffix ({$actualLength} bytes)");
    }

    /** @internal */
    public static function invalidUuid(int $actualLength): self
    {
        return new self("Invalid UUID string ({$actualLength} bytes)");
    }

    /**
     * Input rejected by the codec itself: wrong length, a symbol outside the
     * strict alphabet, or a value that would overflow 128 bits.
     *
     * @internal
     */
    public static function invalidCodecInput(int $actualLength): self
    {
        return new self("Invalid TypeID base32 string ({$actualLength} bytes)");
    }

    /** @internal */
    public static function invalidByteCount(int $actual): self
    {
        return new self("UUID bytes must be exactly 16 bytes, got {$actual}");
    }

    /** @internal */
    public static function emptyString(): self
    {
        return new self('TypeID string cannot be empty');
    }

    /** @internal */
    public static function leadingSeparator(): self
    {
        return new self('TypeID string cannot start with an underscore');
    }

    /** @internal */
    public static function invalidStringLength(int $actualLength): self
    {
        return new self("Invalid TypeID string length ({$actualLength} bytes)");
    }

    /** @internal */
    public static function missingSeparator(): self
    {
        return new self('TypeID string must place the separator before its 26-byte suffix');
    }

    /** @internal */
    public static function malformedPayload(): self
    {
        return new self('Invalid serialized TypeID data');
    }

    /** @internal */
    public static function unreadableBytes(): self
    {
        return new self('Failed to unpack UUID bytes');
    }
}
