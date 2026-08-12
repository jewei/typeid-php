<?php

declare(strict_types=1);

namespace TypeID;

use TypeID\Exception\ValidationException;

/**
 * Crockford base32 encoder/decoder for TypeID suffixes.
 *
 * Bit layout — 16 UUID bytes (128 bits) → 26 × 5-bit chars:
 *
 *   c[ 0] = b[0]>>5                         bits 127-125  (top 2 always 0 → max char is '7')
 *   c[ 1] = b[0]&0x1F                       bits 124-120
 *   c[ 2] = b[1]>>3                         bits 119-115
 *   c[ 3] = (b[1]&0x07)<<2 | b[2]>>6        bits 114-110
 *   c[ 4] = (b[2]>>1)&0x1F                  bits 109-105
 *   c[ 5] = (b[2]&0x01)<<4 | b[3]>>4        bits 104-100
 *   c[ 6] = (b[3]&0x0F)<<1 | b[4]>>7        bits  99- 95
 *   c[ 7] = (b[4]>>2)&0x1F                  bits  94- 90
 *   c[ 8] = (b[4]&0x03)<<3 | b[5]>>5        bits  89- 85
 *   c[ 9] = b[5]&0x1F                       bits  84- 80
 *   … chars 2-25 repeat an 8-char / 5-byte pattern three times.
 *
 * @internal Use TypeID for the stable public API.
 */
final class Base32
{
    private const string ALPHABET = '0123456789abcdefghjkmnpqrstvwxyz';

    private const array DECODE_MAP = [
        '0' => 0,  '1' => 1,  '2' => 2,  '3' => 3,  '4' => 4,
        '5' => 5,  '6' => 6,  '7' => 7,  '8' => 8,  '9' => 9,
        'a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14,
        'f' => 15, 'g' => 16, 'h' => 17, 'j' => 18, 'k' => 19,
        'm' => 20, 'n' => 21, 'p' => 22, 'q' => 23, 'r' => 24,
        's' => 25, 't' => 26, 'v' => 27, 'w' => 28, 'x' => 29,
        'y' => 30, 'z' => 31,
    ];

    private function __construct() {}

    /**
     * Encode a UUID string to a 26-char Crockford base32 suffix.
     *
     * @throws ValidationException If $uuid is not a valid UUID.
     */
    public static function encode(string $uuid): string
    {
        if (! Validator::isValidUuid($uuid)) {
            throw new ValidationException(
                'Invalid UUID string: '.Validator::formatForMessage($uuid)
            );
        }

        return self::encodeBytes(hex2bin(str_replace('-', '', strtolower($uuid))));
    }

    /**
     * Encode 16 raw UUID bytes to a 26-char Crockford base32 suffix.
     *
     * @throws ValidationException If $bytes is not exactly 16 bytes.
     */
    public static function encodeBytes(string $bytes): string
    {
        if (strlen($bytes) !== 16) {
            throw new ValidationException(
                'UUID bytes must be exactly 16 bytes, got '.strlen($bytes)
            );
        }

        $unpacked = unpack('C*', $bytes);

        if ($unpacked === false) {
            throw new ValidationException('Failed to unpack UUID bytes');
        }

        $b = array_values($unpacked);

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
     * Decode a 26-char Crockford base32 suffix to its canonical UUID string.
     * Input is strict: lowercase only, with no ambiguous Crockford characters.
     *
     * @throws ValidationException If $base32 is not a valid 26-char Crockford string.
     */
    public static function decode(string $base32): string
    {
        $hex = bin2hex(self::decodeBytes($base32));

        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    /**
     * Decode a 26-char Crockford base32 suffix to 16 raw UUID bytes.
     *
     * @throws ValidationException If $base32 is not a valid TypeID suffix.
     */
    public static function decodeBytes(string $base32): string
    {
        if (! Validator::isValidSuffix($base32)) {
            throw new ValidationException(
                'Invalid TypeID base32 string: '.Validator::formatForMessage($base32)
            );
        }

        $m = self::DECODE_MAP;
        $v = array_map(fn (string $ch): int => $m[$ch], str_split($base32));

        return pack('C*',
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
        );
    }
}
