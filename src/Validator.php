<?php

declare(strict_types=1);

namespace TypeID;

use TypeID\Exception\ValidationException;

/**
 * Stateless validation helpers for TypeID components.
 * All methods are static — this class is not meant to be instantiated.
 *
 * @internal Use TypeID for the stable public API.
 */
final class Validator
{
    /**
     * Prefix rules: lowercase a-z only; may contain underscores but not at
     * the start or end; max 63 chars. Empty string is valid (no prefix).
     */
    private const string PREFIX_PATTERN = '/\A(?:[a-z](?:[a-z_]{0,61}[a-z])?)?\z/';

    /** Exactly 128 bits encoded with the strict TypeID base32 alphabet. */
    private const string SUFFIX_PATTERN = '/\A[0-7][0123456789abcdefghjkmnpqrstvwxyz]{25}\z/';

    /** Canonical UUID format, or the same 32 hexadecimal digits without dashes. */
    private const string UUID_PATTERN = '/\A(?:[0-9a-f]{32}|[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12})\z/i';

    private function __construct() {}

    public static function isValidPrefix(string $prefix): bool
    {
        return preg_match(self::PREFIX_PATTERN, $prefix) === 1;
    }

    /**
     * A valid suffix is exactly 26 Crockford chars whose value fits in 128 bits.
     * 26 × 5 = 130 bits, but the max encodable value is '7zzz…' (first char ≤ '7'),
     * capping the range to exactly 2^128 - 1.
     */
    public static function isValidSuffix(string $suffix): bool
    {
        return preg_match(self::SUFFIX_PATTERN, $suffix) === 1;
    }

    /**
     * Split a TypeID string into [prefix, suffix].
     * The last underscore is always the delimiter; everything before it is the prefix.
     *
     * @return array{0: string, 1: string}
     *
     * @throws ValidationException
     */
    public static function parseTypeID(string $value): array
    {
        if ($value === '') {
            throw new ValidationException('TypeID string cannot be empty');
        }

        if (str_starts_with($value, '_')) {
            throw new ValidationException('TypeID string cannot start with an underscore');
        }

        $lastUnderscore = strrpos($value, '_');

        if ($lastUnderscore === false) {
            return ['', $value];
        }

        return [
            substr($value, 0, $lastUnderscore),
            substr($value, $lastUnderscore + 1),
        ];
    }

    /** Accepts UUID with or without dashes, case-insensitive. */
    public static function isValidUuid(string $uuid): bool
    {
        return preg_match(self::UUID_PATTERN, $uuid) === 1;
    }

    public static function formatForMessage(string $value): string
    {
        $escaped = addcslashes($value, "\0..\37");

        return strlen($escaped) > 64
            ? substr($escaped, 0, 64).'...'
            : $escaped;
    }
}
