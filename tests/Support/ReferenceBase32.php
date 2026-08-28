<?php

declare(strict_types=1);

namespace TypeID\Tests\Support;

use InvalidArgumentException;
use RuntimeException;

/**
 * A deliberately slow Base32 oracle for tests.
 *
 * This follows the specification literally. It does not share the optimized
 * codec's masks, shifts, regular expression, or decode table.
 */
final class ReferenceBase32
{
    private const string ALPHABET = '0123456789abcdefghjkmnpqrstvwxyz';

    private function __construct() {}

    public static function encodeBytes(string $bytes): string
    {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgumentException('Expected exactly 16 bytes');
        }

        $bits = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= sprintf('%08b', ord($byte));
        }

        $bits = '00'.$bits;
        $encoded = '';

        foreach (str_split($bits, 5) as $group) {
            $encoded .= self::ALPHABET[self::binaryToInt($group)];
        }

        return $encoded;
    }

    public static function decodeBytes(string $encoded): string
    {
        if (strlen($encoded) !== 26) {
            throw new InvalidArgumentException('Expected exactly 26 characters');
        }

        $bits = '';

        foreach (str_split($encoded) as $character) {
            $value = strpos(self::ALPHABET, $character);

            if ($value === false) {
                throw new InvalidArgumentException('Character is outside the TypeID alphabet');
            }

            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        if (substr($bits, 0, 2) !== '00') {
            throw new InvalidArgumentException('Encoded value exceeds 128 bits');
        }

        $bytes = '';

        foreach (str_split(substr($bits, 2), 8) as $octet) {
            $bytes .= chr(self::binaryToInt($octet));
        }

        return $bytes;
    }

    private static function binaryToInt(string $bits): int
    {
        $value = bindec($bits);

        if (! is_int($value)) {
            throw new RuntimeException('Binary test group exceeded integer range');
        }

        return $value;
    }
}
