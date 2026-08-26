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
 * That repeat could fold into a 5-byte loop. It stays unrolled on purpose:
 * encode/decode sit on the hot path of every TypeID created or parsed, and a
 * straight-line expression avoids 26 iterations of loop and index arithmetic.
 * Change it only with a benchmark that shows the loop is no slower.
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
        Validator::assertValidUuid($uuid);

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

        $octets = array_values($unpacked);

        $alphabet = self::ALPHABET;

        return
            $alphabet[$octets[0] >> 5].
            $alphabet[$octets[0] & 0x1F].
            $alphabet[$octets[1] >> 3].
            $alphabet[($octets[1] & 0x07) << 2 | $octets[2] >> 6].
            $alphabet[($octets[2] >> 1) & 0x1F].
            $alphabet[($octets[2] & 0x01) << 4 | $octets[3] >> 4].
            $alphabet[($octets[3] & 0x0F) << 1 | $octets[4] >> 7].
            $alphabet[($octets[4] >> 2) & 0x1F].
            $alphabet[($octets[4] & 0x03) << 3 | $octets[5] >> 5].
            $alphabet[$octets[5] & 0x1F].
            $alphabet[$octets[6] >> 3].
            $alphabet[($octets[6] & 0x07) << 2 | $octets[7] >> 6].
            $alphabet[($octets[7] >> 1) & 0x1F].
            $alphabet[($octets[7] & 0x01) << 4 | $octets[8] >> 4].
            $alphabet[($octets[8] & 0x0F) << 1 | $octets[9] >> 7].
            $alphabet[($octets[9] >> 2) & 0x1F].
            $alphabet[($octets[9] & 0x03) << 3 | $octets[10] >> 5].
            $alphabet[$octets[10] & 0x1F].
            $alphabet[$octets[11] >> 3].
            $alphabet[($octets[11] & 0x07) << 2 | $octets[12] >> 6].
            $alphabet[($octets[12] >> 1) & 0x1F].
            $alphabet[($octets[12] & 0x01) << 4 | $octets[13] >> 4].
            $alphabet[($octets[13] & 0x0F) << 1 | $octets[14] >> 7].
            $alphabet[($octets[14] >> 2) & 0x1F].
            $alphabet[($octets[14] & 0x03) << 3 | $octets[15] >> 5].
            $alphabet[$octets[15] & 0x1F];
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
        Validator::assertValidBase32($base32);

        $map = self::DECODE_MAP;
        $values = array_map(fn (string $ch): int => $map[$ch], str_split($base32));

        return pack('C*',
            $values[0] << 5 | $values[1],
            $values[2] << 3 | $values[3] >> 2,
            ($values[3] & 0x03) << 6 | $values[4] << 1 | $values[5] >> 4,
            ($values[5] & 0x0F) << 4 | $values[6] >> 1,
            ($values[6] & 0x01) << 7 | $values[7] << 2 | $values[8] >> 3,
            ($values[8] & 0x07) << 5 | $values[9],
            $values[10] << 3 | $values[11] >> 2,
            ($values[11] & 0x03) << 6 | $values[12] << 1 | $values[13] >> 4,
            ($values[13] & 0x0F) << 4 | $values[14] >> 1,
            ($values[14] & 0x01) << 7 | $values[15] << 2 | $values[16] >> 3,
            ($values[16] & 0x07) << 5 | $values[17],
            $values[18] << 3 | $values[19] >> 2,
            ($values[19] & 0x03) << 6 | $values[20] << 1 | $values[21] >> 4,
            ($values[21] & 0x0F) << 4 | $values[22] >> 1,
            ($values[22] & 0x01) << 7 | $values[23] << 2 | $values[24] >> 3,
            ($values[24] & 0x07) << 5 | $values[25],
        );
    }
}
