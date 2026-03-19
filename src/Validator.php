<?php

declare(strict_types=1);

namespace TypeID;

use InvalidArgumentException;

/**
 * Stateless validation helpers for TypeID components.
 * All methods are static — this class is not meant to be instantiated.
 */
final class Validator
{
    private const int    MAX_PREFIX_LENGTH = 63;
    private const int    SUFFIX_LENGTH = 26;

    /**
     * Prefix rules: lowercase a-z only; may contain underscores but not at
     * the start or end; max 63 chars. Empty string is valid (no prefix).
     */
    private const string PREFIX_PATTERN = '/^([a-z]([a-z_]{0,61}[a-z])?)?$/';

    /** Crockford base32 alphabet: 0-9 and a-z minus i, l, o, u. */
    private const string SUFFIX_PATTERN = '/^[0123456789abcdefghjkmnpqrstvwxyz]+$/';

    /** Standard UUID format with or without dashes, case-insensitive. */
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}$/i';

    private function __construct() {}

    public static function isValidPrefix(string $prefix): bool
    {
        return $prefix === '' || (
            strlen($prefix) <= self::MAX_PREFIX_LENGTH &&
            (bool) preg_match(self::PREFIX_PATTERN, $prefix)
        );
    }

    /**
     * A valid suffix is exactly 26 Crockford chars whose value fits in 128 bits.
     * 26 × 5 = 130 bits, but the max encodable value is '7zzz…' (first char ≤ '7'),
     * capping the range to exactly 2^128 - 1.
     */
    public static function isValidSuffix(string $suffix): bool
    {
        return strlen($suffix) === self::SUFFIX_LENGTH
            && (bool) preg_match(self::SUFFIX_PATTERN, $suffix)
            && strcmp($suffix, '7zzzzzzzzzzzzzzzzzzzzzzzzz') <= 0;
    }

    /**
     * Split a TypeID string into [prefix, suffix].
     * The last underscore is always the delimiter; everything before it is the prefix.
     *
     * @return array{0: string, 1: string}
     *
     * @throws InvalidArgumentException
     */
    public static function parseTypeID(string $value): array
    {
        if ($value === '') {
            throw new InvalidArgumentException('TypeID string cannot be empty');
        }

        $lastUnderscore = strrpos($value, '_');

        if ($lastUnderscore === 0) {
            throw new InvalidArgumentException('TypeID string cannot start with an underscore');
        }

        if ($lastUnderscore === false) {
            if (! self::isValidSuffix($value)) {
                throw new InvalidArgumentException('Invalid TypeID suffix: '.$value);
            }

            return ['', $value];
        }

        $prefix = substr($value, 0, $lastUnderscore);
        $suffix = substr($value, $lastUnderscore + 1);

        if (! self::isValidPrefix($prefix)) {
            throw new InvalidArgumentException('Invalid TypeID prefix: '.$prefix);
        }

        if (! self::isValidSuffix($suffix)) {
            throw new InvalidArgumentException('Invalid TypeID suffix: '.$suffix);
        }

        return [$prefix, $suffix];
    }

    /** Accepts UUID with or without dashes, case-insensitive. */
    public static function isValidUuid(string $uuid): bool
    {
        return preg_match(self::UUID_PATTERN, $uuid) === 1;
    }

    /**
     * Validates UUIDv7 structure:
     * - hex[12] must be '7'          → version bits (48-51) = 0111
     * - hex[16] must be 8/9/a/b      → variant bits (64-65) = 10xx  (RFC 4122)
     */
    public static function isValidUuidv7(string $uuid): bool
    {
        if (! self::isValidUuid($uuid)) {
            return false;
        }

        $hex = strtolower(str_replace('-', '', $uuid));

        return $hex[12] === '7' && in_array($hex[16], ['8', '9', 'a', 'b'], strict: true);
    }

    /** Alias for isValidSuffix — used internally by Base32. */
    public static function isValidBase32(string $base32): bool
    {
        return self::isValidSuffix($base32);
    }
}
