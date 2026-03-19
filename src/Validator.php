<?php

declare(strict_types=1);

namespace TypeID;

use InvalidArgumentException;

class Validator
{
    /**
     * Maximum length of the TypeID prefix (63 characters).
     */
    private const MAX_PREFIX_LENGTH = 63;

    /**
     * Regex pattern for validating prefix characters.
     * Must start and end with [a-z]; may contain underscores in between.
     */
    private const PREFIX_PATTERN = '/^([a-z]([a-z_]{0,61}[a-z])?)?$/';

    /**
     * The TypeID suffix must be exactly 26 characters long.
     */
    private const SUFFIX_LENGTH = 26;

    /**
     * Regex pattern for validating base32 suffix characters (Crockford's alphabet).
     */
    private const SUFFIX_PATTERN = '/^[0123456789abcdefghjkmnpqrstvwxyz]+$/';

    /**
     * Regex pattern for validating UUID format (with or without dashes).
     */
    private const UUID_PATTERN = '/^[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}$/i';

    /**
     * Check if a prefix is valid.
     */
    public static function isValidPrefix(string $prefix): bool
    {
        if ($prefix === '') {
            return true;
        }

        if (strlen($prefix) > self::MAX_PREFIX_LENGTH) {
            return false;
        }

        return (bool) preg_match(self::PREFIX_PATTERN, $prefix);
    }

    /**
     * Check if a TypeID suffix is valid.
     */
    public static function isValidSuffix(string $suffix): bool
    {
        if ($suffix === '') {
            return false;
        }

        if (strlen($suffix) !== self::SUFFIX_LENGTH) {
            return false;
        }

        if (! preg_match(self::SUFFIX_PATTERN, $suffix)) {
            return false;
        }

        // Suffix must encode at most 128 bits (first char ≤ '7')
        if (strcmp($suffix, '7zzzzzzzzzzzzzzzzzzzzzzzzz') > 0) {
            return false;
        }

        return true;
    }

    /**
     * Parse a TypeID string into prefix and suffix parts.
     *
     * @return array{0: string, 1: string} Array with [prefix, suffix]
     *
     * @throws InvalidArgumentException If the string is empty or improperly formatted
     */
    public static function parseTypeID(string $value): array
    {
        if ($value === '') {
            throw new InvalidArgumentException('TypeID string cannot be empty');
        }

        $firstUnderscore = strpos($value, '_');

        if ($firstUnderscore === 0) {
            throw new InvalidArgumentException('TypeID string cannot start with an underscore');
        }

        if ($firstUnderscore === false) {
            if (! self::isValidSuffix($value)) {
                throw new InvalidArgumentException('Invalid TypeID suffix: '.$value);
            }

            return ['', $value];
        }

        $lastUnderscore = strrpos($value, '_');
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

    /**
     * Check if a string is a valid UUID (with or without dashes).
     */
    public static function isValidUuid(string $uuid): bool
    {
        return preg_match(self::UUID_PATTERN, $uuid) === 1;
    }

    /**
     * Validate UUIDv7 structure.
     *
     * Ensures that:
     * - The string has valid UUID format
     * - Bits 48-51 of the UUID are 0111 (indicating version 7)
     * - Bits 64-65 of the UUID are 10 (indicating the UUID variant)
     */
    public static function isValidUuidv7(string $uuid): bool
    {
        if (! self::isValidUuid($uuid)) {
            return false;
        }

        $hex = strtolower(str_replace('-', '', $uuid));

        // Check version (hex char at index 12 must be '7')
        if ($hex[12] !== '7') {
            return false;
        }

        // Check variant (hex char at index 16 must be 8, 9, a, or b)
        return in_array($hex[16], ['8', '9', 'a', 'b'], true);
    }

    /**
     * Check if a string is a valid base32 suffix.
     */
    public static function isValidBase32(string $base32): bool
    {
        return self::isValidSuffix($base32);
    }
}
