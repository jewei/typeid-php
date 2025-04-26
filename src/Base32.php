<?php

declare(strict_types=1);

namespace TypeID;

use InvalidArgumentException;

class Base32
{
    // Crockford's base32 alphabet (lowercase for output)
    private const ALPHABET = '0123456789abcdefghjkmnpqrstvwxyz';

    // Map ambiguous characters to canonical ones
    private const ALPHABET_MAP = ['O' => '0', 'o' => '0', 'I' => '1', 'i' => '1', 'L' => '1', 'l' => '1'];

    /**
     * Encode a UUID (with or without dashes) to TypeID base32 (Crockford, 26 chars, lowercase)
     */
    public static function encode(string $uuid): string
    {
        // Validate UUIDv7 structure when encoding
        if (! Validator::isValidUuidv7($uuid)) {
            throw new InvalidArgumentException('Invalid UUIDv7 string: '.$uuid);
        }

        // Convert hex to binary
        $binary = hex2bin(strtolower(str_replace('-', '', $uuid)));
        if ($binary === false) {
            throw new InvalidArgumentException('Failed to convert hex to binary');
        }

        // Convert binary to GMP integer
        $gmp = gmp_import($binary, 1, GMP_MSW_FIRST | GMP_NATIVE_ENDIAN);
        if ($gmp === false) {
            throw new InvalidArgumentException('Failed to import binary to GMP');
        }

        // Encode to base32 (Crockford, lowercase)
        $base32 = '';
        while (gmp_cmp($gmp, 0) > 0) {
            $remainder = gmp_intval(gmp_mod($gmp, 32));
            $base32 = self::ALPHABET[$remainder].$base32;
            $gmp = gmp_div_q($gmp, 32);
        }

        // Pad to 26 chars (128 bits / 5 = 25.6, so 26 chars)
        return str_pad($base32, 26, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a TypeID base32 string (Crockford, 26 chars) to canonical UUID string
     */
    public static function decode(string $base32): string
    {
        // Map ambiguous characters to canonical ones and convert to lowercase
        $base32 = strtr(strtolower($base32), self::ALPHABET_MAP);

        // Validate the base32 string contains only valid characters (Crockford's alphabet)
        if (! Validator::isValidBase32($base32)) {
            throw new InvalidArgumentException('Invalid TypeID base32 string: '.$base32);
        }

        // Convert base32 to integer
        $integer = gmp_init(0);
        for ($i = 0; $i < 26; $i++) {
            $char = $base32[$i];
            $position = strpos(self::ALPHABET, $char);
            if ($position === false) {
                throw new InvalidArgumentException("Invalid base32 character: $char");
            }
            $integer = gmp_add(gmp_mul($integer, 32), $position);
        }

        // Convert integer to binary (16 bytes) – explicit endianness
        $binary = gmp_export($integer, 1, GMP_MSW_FIRST | GMP_NATIVE_ENDIAN);
        if ($binary === false) {
            throw new InvalidArgumentException('Failed to export GMP to binary');
        }

        $length = strlen($binary);

        // Pad to 16 bytes if needed (binary safe)
        if ($length < 16) {
            $binary = str_repeat("\0", 16 - $length).$binary;
        } elseif ($length > 16) {
            // Overflow – decoded value exceeds 128 bits
            throw new InvalidArgumentException('Decoded value is longer than 128 bits');
        }

        // Convert binary to hexadecimal
        $hex = bin2hex($binary);

        // Format as UUID (8-4-4-4-12)
        $uuid = sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );

        // Validate the UUID has UUIDv7 structure
        if (! Validator::isValidUuidv7($uuid)) {
            throw new InvalidArgumentException('Decoded UUID does not have valid UUIDv7 format: '.$uuid);
        }

        return $uuid;
    }
}
