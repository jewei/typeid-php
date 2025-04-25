<?php

declare(strict_types=1);

namespace TypeID;

use InvalidArgumentException;

class Base32
{
    // Crockford's base32 alphabet (lowercase for output)
    private const ALPHABET = '0123456789abcdefghjkmnpqrstvwxyz';
    private const ALPHABET_MAP = [
        // Accept both upper and lower case, and map ambiguous chars
        'O' => '0', 'o' => '0', 'I' => '1', 'i' => '1', 'L' => '1', 'l' => '1',
        // Map all uppercase letters to lowercase
        'A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd', 'E' => 'e', 'F' => 'f', 'G' => 'g',
        'H' => 'h', 'J' => 'j', 'K' => 'k', 'M' => 'm', 'N' => 'n', 'P' => 'p', 'Q' => 'q',
        'R' => 'r', 'S' => 's', 'T' => 't', 'V' => 'v', 'W' => 'w', 'X' => 'x', 'Y' => 'y',
        'Z' => 'z',
    ];

    /**
     * Encode a UUID (with or without dashes) to TypeID base32 (Crockford, 26 chars, lowercase)
     */
    public static function encode(string $uuid): string
    {
        // Remove dashes and lowercase
        $hex = strtolower(str_replace('-', '', $uuid));
        if (strlen($hex) !== 32 || ! ctype_xdigit($hex)) {
            throw new InvalidArgumentException('Invalid UUID string');
        }

        // Validate UUIDv7 structure when encoding
        if (! Validator::isValidUuidv7($uuid)) {
            throw new InvalidArgumentException('Invalid UUIDv7 format: version bits (48-51) must be 0111 and variant bits (64-65) must be 10');
        }

        // Convert hex to binary
        $bin = hex2bin($hex);
        if ($bin === false) {
            throw new InvalidArgumentException('Failed to convert hex to binary');
        }

        // Convert binary to GMP integer
        $int = gmp_import($bin, 1, GMP_MSW_FIRST | GMP_NATIVE_ENDIAN);
        if ($int === false) {
            throw new InvalidArgumentException('Failed to import binary to GMP');
        }

        // Encode to base32 (Crockford, lowercase)
        $base32 = '';
        while (gmp_cmp($int, 0) > 0) {
            $rem = gmp_intval(gmp_mod($int, 32));
            $base32 = self::ALPHABET[$rem].$base32;
            $int = gmp_div_q($int, 32);
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

        if (strlen($base32) !== 26) {
            throw new InvalidArgumentException('TypeID base32 string must be 26 chars');
        }

        // Validate the base32 string contains only valid characters (Crockford's alphabet)
        if (! preg_match('/^[0123456789abcdefghjkmnpqrstvwxyz]*$/', $base32)) {
            throw new InvalidArgumentException('TypeID base32 string contains invalid characters');
        }

        // Convert base32 to integer
        $int = gmp_init(0);
        for ($i = 0; $i < 26; $i++) {
            $char = $base32[$i];
            $pos = strpos(self::ALPHABET, $char);
            if ($pos === false) {
                throw new InvalidArgumentException("Invalid base32 character: $char");
            }
            $int = gmp_add(gmp_mul($int, 32), $pos);
        }

        // Convert integer to binary (16 bytes) – explicit endianness
        $bin = gmp_export($int, 1, GMP_MSW_FIRST | GMP_NATIVE_ENDIAN);
        if ($bin === false) {
            throw new InvalidArgumentException('Failed to export GMP to binary');
        }

        $binLen = strlen($bin);

        // Pad to 16 bytes if needed (binary safe)
        if ($binLen < 16) {
            $bin = str_repeat("\0", 16 - $binLen).$bin;
        } elseif ($binLen > 16) {
            // Overflow – decoded value exceeds 128 bits
            throw new InvalidArgumentException('Decoded value is longer than 128 bits');
        }

        // Convert binary to hex
        $hex = bin2hex($bin);

        // Format as UUID (8-4-4-4-12)
        $uuid = sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );

        // Validate the UUID has UUIDv7 structure unless it is the zero UUID
        if ($uuid !== '00000000-0000-0000-0000-000000000000' && ! Validator::isValidUuidv7($uuid)) {
            throw new InvalidArgumentException('Decoded UUID does not have valid UUIDv7 format: version bits (48-51) must be 0111 and variant bits (64-65) must be 10');
        }

        return $uuid;
    }
}
