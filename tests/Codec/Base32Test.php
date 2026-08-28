<?php

declare(strict_types=1);

use TypeID\Base32;
use TypeID\Exception\ValidationException;

/**
 * Bit-boundary diagnostics for the base32 codec.
 *
 * This is the one test layer permitted to reach past the supported seam. It
 * exists because encode/decode is an unrolled 26-expression bit-shuffle: when a
 * single 5-bit group is wrong, a failure through TypeID reports only "this UUID
 * did not round trip", while these tests name the byte and bit at fault.
 *
 * Everything else about encoding is covered through TypeID and the spec vectors.
 *
 * @see spec/README.md — TypeID specification 0.3.0, base32 encoding
 */
test('every single set bit survives a round trip', function (): void {
    foreach (range(0, 127) as $bit) {
        $bytes = array_fill(0, 16, 0);
        $bytes[intdiv($bit, 8)] = 1 << (7 - $bit % 8);
        $raw = pack('C*', ...$bytes);

        $decoded = Base32::decodeBytes(Base32::encodeBytes($raw));

        expect(bin2hex($decoded))->toBe(bin2hex($raw), "bit {$bit} did not survive the round trip");
    }
});

test('every single saturated byte survives a round trip', function (): void {
    foreach (range(0, 15) as $index) {
        $bytes = array_fill(0, 16, 0);
        $bytes[$index] = 0xFF;
        $raw = pack('C*', ...$bytes);

        $decoded = Base32::decodeBytes(Base32::encodeBytes($raw));

        expect(bin2hex($decoded))->toBe(bin2hex($raw), "byte {$index} did not survive the round trip");
    }
});

/**
 * Chars 3, 5, 6, 8 and their repeats straddle byte boundaries, so they are the
 * groups an off-by-one shift corrupts first.
 */
test('values straddling byte boundaries survive a round trip', function (string $hex): void {
    $raw = hex2bin($hex);

    expect(bin2hex(Base32::decodeBytes(Base32::encodeBytes($raw))))->toBe($hex);
})->with([
    'alternating 0x55' => str_repeat('55', 16),
    'alternating 0xAA' => str_repeat('aa', 16),
    'ascending' => '000102030405060708090a0b0c0d0e0f',
    'descending' => 'f0e0d0c0b0a090807060504030201000',
    'high nibbles' => str_repeat('f0', 16),
    'low nibbles' => str_repeat('0f', 16),
]);

test('the encoded suffix is always 26 characters', function (string $hex): void {
    expect(Base32::encodeBytes(hex2bin($hex)))->toHaveLength(26);
})->with([
    'nil' => str_repeat('00', 16),
    'max' => str_repeat('ff', 16),
    'mid' => str_repeat('80', 16),
]);

/**
 * Two zero bits are prepended before the 128 are split into 26 groups of 5, so
 * the leading group can never exceed 7 regardless of input.
 */
test('the leading character never exceeds 7 for any input', function (): void {
    foreach (['00', 'ff', '80', '7f', 'aa', '55'] as $fill) {
        $encoded = Base32::encodeBytes(hex2bin(str_repeat($fill, 16)));

        expect((int) $encoded[0])->toBeLessThanOrEqual(7);
    }
});

test('the boundary values encode to their documented suffixes', function (): void {
    expect(Base32::encodeBytes(hex2bin(str_repeat('00', 16))))->toBe('00000000000000000000000000')
        ->and(Base32::encodeBytes(hex2bin(str_repeat('ff', 16))))->toBe('7zzzzzzzzzzzzzzzzzzzzzzzzz');
});

/**
 * The codec owns its own domain constraints: alphabet, length and canonicality.
 * Byte-only does not mean validation-free, and these guards are not the same as
 * TypeID re-validating a suffix it already established at construction.
 */
test('the codec rejects input outside its own domain', function (string $suffix): void {
    expect(fn () => Base32::decodeBytes($suffix))->toThrow(ValidationException::class);
})->with([
    'too short' => 'tooshort',
    'one short' => str_repeat('0', 25),
    'one long' => str_repeat('0', 27),
    'uppercase' => '01JSNSF2G7E2SAXDJVZ3J6TC3X',
    'ambiguous letters' => '0Ijsnsf2g7e2saxdjvz3jltc3x',
    'overflow' => '8zzzzzzzzzzzzzzzzzzzzzzzzz',
    'hyphens' => 'ill3g4l-ch4r4ct3rs-in-b4s332',
    'empty' => '',
]);

test('the codec rejects byte strings that are not exactly 16 bytes', function (string $bytes): void {
    expect(fn () => Base32::encodeBytes($bytes))->toThrow(ValidationException::class);
})->with([
    'empty' => '',
    'fifteen' => str_repeat("\0", 15),
    'seventeen' => str_repeat("\0", 17),
]);

test('decoding is the exact inverse of encoding for the boundary suffixes', function (string $suffix): void {
    expect(Base32::encodeBytes(Base32::decodeBytes($suffix)))->toBe($suffix);
})->with([
    'nil' => '00000000000000000000000000',
    'max' => '7zzzzzzzzzzzzzzzzzzzzzzzzz',
    'one below max' => '7zzzzzzzzzzzzzzzzzzzzzzzzy',
    'single low bit' => '00000000000000000000000001',
]);
