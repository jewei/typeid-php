<?php

declare(strict_types=1);

namespace TypeID;

class Validator
{
    // Maximum length of the prefix
    private const MAX_PREFIX_LENGTH = 63;

    // Regex for valid prefix characters (lowercase a-z and underscores)
    private const PREFIX_PATTERN = '/^([a-z]([a-z_]{0,61}[a-z])?)?$/';

    // The suffix must be exactly 26 characters long
    private const SUFFIX_LENGTH = 26;

    // Pattern for valid characters in the base32 suffix – Crockford's alphabet
    // (lower-case, excluding the ambiguous characters i, l, o)
    private const SUFFIX_PATTERN = '/^[0123456789abcdefghjkmnpqrstvwxyz]+$/';

    /**
     * Check if a prefix is valid.
     *
     * @param  string  $prefix  The prefix to check
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

        return true;
    }

    /**
     * Check if a suffix is valid.
     *
     * @param  string  $suffix  The suffix to check
     */
    public static function isValidSuffix(string $suffix): bool
    {
        // Empty suffix is converted to zero suffix
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
     * @return array|null Array with [prefix, suffix] or null if invalid
     */
    public static function parseTypeID(string $value): ?array
    {
        // If the string contains no underscore, treat it as a suffix only
        if (strpos($value, '_') === false) {
            if (! self::isValidSuffix($value)) {
                return null;
            }

            return ['', $value];
        }

        // Split the string at the underscore
        $parts = explode('_', $value, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$prefix, $suffix] = $parts;

        // Validate prefix and suffix
        if (! self::isValidPrefix($prefix) || ! self::isValidSuffix($suffix)) {
            return null;
        }

        return [$prefix, $suffix];
    }

    /**
     * Validate UUIDv7 structure.
     *
     * Ensures that:
     * - Bits 48-51 of the UUID are 0111 (indicating version 7)
     * - Bits 64-65 of the UUID are 10 (indicating the UUID variant)
     *
     * @param  string  $uuid  UUID string to validate
     * @return bool Whether the UUID has valid UUIDv7 structure
     */
    public static function isValidUUIDv7(string $uuid): bool
    {
        // Remove dashes and lowercase
        $hex = strtolower(str_replace('-', '', $uuid));

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
}
