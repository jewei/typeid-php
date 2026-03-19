<?php

declare(strict_types=1);

namespace TypeID;

use InvalidArgumentException;

/**
 * Base32 encoding/decoding for TypeID (Crockford's alphabet).
 *
 * Uses direct bit manipulation on the 16-byte UUID representation —
 * no GMP or bcmath extension required.
 */
class Base32
{
    // Crockford's base32 alphabet (lowercase for output)
    private const ALPHABET = '0123456789abcdefghjkmnpqrstvwxyz';

    // O/I/L are ambiguous in Crockford's alphabet; map them after lowercasing
    private const NORMALIZE_MAP = ['o' => '0', 'i' => '1', 'l' => '1'];

    // Reverse lookup: character → 5-bit value
    private const DECODE_MAP = [
        '0' => 0,  '1' => 1,  '2' => 2,  '3' => 3,  '4' => 4,
        '5' => 5,  '6' => 6,  '7' => 7,  '8' => 8,  '9' => 9,
        'a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14,
        'f' => 15, 'g' => 16, 'h' => 17, 'j' => 18, 'k' => 19,
        'm' => 20, 'n' => 21, 'p' => 22, 'q' => 23, 'r' => 24,
        's' => 25, 't' => 26, 'v' => 27, 'w' => 28, 'x' => 29,
        'y' => 30, 'z' => 31,
    ];

    /**
     * Encode a UUID (with or without dashes) to TypeID base32 (Crockford, 26 chars, lowercase).
     *
     * @throws InvalidArgumentException If the UUID is invalid
     */
    public static function encode(string $uuid): string
    {
        if (! Validator::isValidUuid($uuid)) {
            throw new InvalidArgumentException('Invalid UUID string: '.$uuid);
        }

        /** @var int[] $b */
        $b = array_values(unpack('C*', hex2bin(str_replace('-', '', strtolower($uuid)))));

        $a = self::ALPHABET;

        return
            $a[$b[0] >> 5].
            $a[$b[0] & 0x1F].
            $a[$b[1] >> 3].
            $a[($b[1] & 0x07) << 2 | $b[2] >> 6].
            $a[($b[2] >> 1) & 0x1F].
            $a[($b[2] & 0x01) << 4 | $b[3] >> 4].
            $a[($b[3] & 0x0F) << 1 | $b[4] >> 7].
            $a[($b[4] >> 2) & 0x1F].
            $a[($b[4] & 0x03) << 3 | $b[5] >> 5].
            $a[$b[5] & 0x1F].
            $a[$b[6] >> 3].
            $a[($b[6] & 0x07) << 2 | $b[7] >> 6].
            $a[($b[7] >> 1) & 0x1F].
            $a[($b[7] & 0x01) << 4 | $b[8] >> 4].
            $a[($b[8] & 0x0F) << 1 | $b[9] >> 7].
            $a[($b[9] >> 2) & 0x1F].
            $a[($b[9] & 0x03) << 3 | $b[10] >> 5].
            $a[$b[10] & 0x1F].
            $a[$b[11] >> 3].
            $a[($b[11] & 0x07) << 2 | $b[12] >> 6].
            $a[($b[12] >> 1) & 0x1F].
            $a[($b[12] & 0x01) << 4 | $b[13] >> 4].
            $a[($b[13] & 0x0F) << 1 | $b[14] >> 7].
            $a[($b[14] >> 2) & 0x1F].
            $a[($b[14] & 0x03) << 3 | $b[15] >> 5].
            $a[$b[15] & 0x1F];
    }

    /**
     * Decode a TypeID base32 string (Crockford, 26 chars) to canonical UUID string.
     *
     * @throws InvalidArgumentException If the base32 string is invalid
     */
    public static function decode(string $base32): string
    {
        $original = $base32;
        $base32 = strtr(strtolower($base32), self::NORMALIZE_MAP);

        if (! Validator::isValidBase32($base32)) {
            throw new InvalidArgumentException('Invalid TypeID base32 string: '.$original);
        }

        $m = self::DECODE_MAP;
        $v = array_map(fn (string $ch): int => $m[$ch], str_split($base32));

        $hex = bin2hex(pack('C*',
            $v[0] << 5 | $v[1],
            $v[2] << 3 | $v[3] >> 2,
            ($v[3] & 0x03) << 6 | $v[4] << 1 | $v[5] >> 4,
            ($v[5] & 0x0F) << 4 | $v[6] >> 1,
            ($v[6] & 0x01) << 7 | $v[7] << 2 | $v[8] >> 3,
            ($v[8] & 0x07) << 5 | $v[9],
            $v[10] << 3 | $v[11] >> 2,
            ($v[11] & 0x03) << 6 | $v[12] << 1 | $v[13] >> 4,
            ($v[13] & 0x0F) << 4 | $v[14] >> 1,
            ($v[14] & 0x01) << 7 | $v[15] << 2 | $v[16] >> 3,
            ($v[16] & 0x07) << 5 | $v[17],
            $v[18] << 3 | $v[19] >> 2,
            ($v[19] & 0x03) << 6 | $v[20] << 1 | $v[21] >> 4,
            ($v[21] & 0x0F) << 4 | $v[22] >> 1,
            ($v[22] & 0x01) << 7 | $v[23] << 2 | $v[24] >> 3,
            ($v[24] & 0x07) << 5 | $v[25],
        ));

        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
