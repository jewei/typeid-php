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
     * Regex pattern for validating prefix characters (lowercase a-z and underscores).
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
     *
     * @param  string  $prefix  The prefix to check
     * @return bool True if the prefix is valid, false otherwise
     */
    public static function isValidPrefix(string $prefix): bool
    {
        // Empty prefix is allowed
        if ($prefix === '') {
            return true;
        }

        // Prefix must be at most MAX_PREFIX_LENGTH characters long
        if (strlen($prefix) > self::MAX_PREFIX_LENGTH) {
            return false;
        }

        // Prefix must match the pattern
        if (! preg_match(self::PREFIX_PATTERN, $prefix)) {
            return false;
        }

        // Cannot end with an underscore
        if (substr($prefix, -1) === '_') {
            return false;
        }

        return true;
    }

    /**
     * Check if a TypeID suffix is valid.
     *
     * @param  string  $suffix  The suffix to check
     * @return bool True if the suffix is valid, false otherwise
     */
    public static function isValidSuffix(string $suffix): bool
    {
        // Empty suffix is allowed and is interpreted as the zero TypeID suffix
        if ($suffix === '') {
            return true;
        }

        // Suffix must be exactly SUFFIX_LENGTH characters long
        if (strlen($suffix) !== self::SUFFIX_LENGTH) {
            return false;
        }

        // Suffix must match the pattern
        if (! preg_match(self::SUFFIX_PATTERN, $suffix)) {
            return false;
        }

        return true;
    }

    /**
     * Parse a TypeID string into prefix and suffix parts.
     *
     * @param  string  $value  The TypeID string to parse
     * @return array{0: string, 1: string} Array with [prefix, suffix]
     *
     * @throws InvalidArgumentException If the string is empty or improperly formatted
     */
    public static function parseTypeID(string $value): array
    {
        // Empty string is not a valid TypeID
        if ($value === '') {
            throw new InvalidArgumentException('TypeID string cannot be empty');
        }

        // If the string contains no underscore, treat it as a suffix only
        if (strpos($value, '_') === false) {
            if (! self::isValidSuffix($value)) {
                throw new InvalidArgumentException('Invalid TypeID suffix: '.$value);
            }

            return ['', $value];
        }

        // Split the string at the last underscore
        $suffix = substr($value, strrpos($value, '_') + 1);
        $prefix = substr($value, 0, strrpos($value, '_'));

        // Validate prefix and suffix
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
     *
     * @param  string  $uuid  UUID string to validate
     * @return bool Whether the string has a valid UUID format
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
     *
     * @param  string  $uuid  UUID string to validate
     * @return bool Whether the UUID has valid UUIDv7 structure
     */
    public static function isValidUuidv7(string $uuid): bool
    {
        // Empty string is not a valid UUIDv7
        if ($uuid === '') {
            return false;
        }

        // First check if it's a valid UUID format
        if (! self::isValidUuid($uuid)) {
            return false;
        }

        // Remove dashes and lowercase
        $hex = strtolower(str_replace('-', '', $uuid));

        // Additional check to ensure we have 32 hex characters
        if (strlen($hex) !== 32 || ! ctype_xdigit($hex)) {
            return false;
        }

        // Check version (bits 48-51, hex char at index 12)
        // Character at position 12 should be '7'
        if ($hex[12] !== '7') {
            return false;
        }

        // Check variant (bits 64-65, first hex digit at index 16)
        // First bits of character at position 16 should be binary '10xx'
        // In hex, this means the character should be '8', '9', 'a', or 'b'
        if (! in_array($hex[16], ['8', '9', 'a', 'b'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a string is a valid base32 suffix.
     *
     * @param  string  $base32  The base32 string to validate
     * @return bool Whether the string has a valid base32 suffix
     */
    public static function isValidBase32(string $base32): bool
    {
        return self::isValidSuffix($base32);
    }
}
