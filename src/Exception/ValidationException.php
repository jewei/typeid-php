<?php

declare(strict_types=1);

namespace TypeID\Exception;

use InvalidArgumentException;

/**
 * Thrown when caller input fails TypeID validation.
 *
 * Named constructors keep rejected values out of log messages. The exception
 * class is public, but its message text and named constructors are internal.
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
     * The codec rejected the length, alphabet, or 128-bit range.
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
