<?php

declare(strict_types=1);

namespace TypeID\Exception;

use InvalidArgumentException;

/**
 * Thrown when caller input fails TypeID validation.
 *
 * Construct through the named constructors below rather than directly: each one
 * names the failure at the site that detects it, and each renders the rejected
 * value through the same escaping. Building this exception with a free-form
 * message risks echoing untrusted input back unescaped.
 *
 * The exception class is part of the supported surface. Its message wording is
 * not, and the named constructors are internal.
 */
final class ValidationException extends InvalidArgumentException implements TypeIDException
{
    /** Longest rejected value echoed back before truncation. */
    private const int MAX_RENDERED_LENGTH = 64;

    /** @internal */
    public static function invalidPrefix(string $value): self
    {
        return new self('Invalid prefix: '.self::render($value));
    }

    /** @internal */
    public static function invalidSuffix(string $value): self
    {
        return new self('Invalid suffix: '.self::render($value));
    }

    /** @internal */
    public static function invalidUuid(string $value): self
    {
        return new self('Invalid UUID string: '.self::render($value));
    }

    /**
     * Input rejected by the codec itself: wrong length, a symbol outside the
     * strict alphabet, or a value that would overflow 128 bits.
     *
     * @internal
     */
    public static function invalidCodecInput(string $value): self
    {
        return new self('Invalid TypeID base32 string: '.self::render($value));
    }

    /** @internal */
    public static function invalidByteCount(int $actual): self
    {
        return new self("UUID bytes must be exactly 16 bytes, got {$actual}");
    }

    /** @internal */
    public static function malformedString(string $reason): self
    {
        return new self('TypeID string '.$reason);
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

    /**
     * Escape control characters and cap length, so a rejected value is safe to
     * echo back in a message.
     */
    private static function render(string $value): string
    {
        $escaped = addcslashes($value, "\0..\37");

        return strlen($escaped) > self::MAX_RENDERED_LENGTH
            ? substr($escaped, 0, self::MAX_RENDERED_LENGTH).'...'
            : $escaped;
    }
}
