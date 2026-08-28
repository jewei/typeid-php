<?php

declare(strict_types=1);

use TypeID\TypeID;

/**
 * Normative requirements of TypeID specification 0.3.0 that the vendored
 * vectors do not cover on their own.
 *
 * The vectors check 9 valid and 21 invalid strings. The rules below are stated
 * in spec/README.md as MUST/SHOULD requirements, or recommended by the vector
 * files themselves, and would otherwise regress unnoticed.
 *
 * @see spec/README.md
 */

/**
 * "It's recommended that implementations generate thousands of random ids
 * during testing, and verify that after decoding and re-encoding the id, the
 * result is the same as the original."
 *   — spec/valid.yml
 */
test('a random id survives every round trip: string, UUID and bytes', function (): void {
    $failure = null;

    foreach (range(1, 5000) as $ignored) {
        $original = TypeID::generate('prefix')->toString();
        $parsed = TypeID::fromString($original);

        foreach ([
            'string' => $parsed->toString(),
            'uuid' => TypeID::fromUuid($parsed->toUuid(), $parsed->prefix)->toString(),
            'bytes' => TypeID::fromBytes($parsed->bytes(), $parsed->prefix)->toString(),
        ] as $route => $result) {
            if ($result !== $original) {
                $failure = "{$original} did not survive the {$route} round trip: got {$result}";

                break 2;
            }
        }
    }

    expect($failure)->toBeNull();
});

/**
 * "When generating a new TypeID, the generated UUID suffix MUST decode to a
 * valid UUIDv7: bits 48-51 MUST be 0111, bits 64-65 MUST be 10."
 */
test('generate produces a UUID with v7 version and variant bits', function (): void {
    $failure = null;

    foreach (range(1, 5000) as $ignored) {
        $uuid = TypeID::generate('event')->toUuid();
        $hex = str_replace('-', '', $uuid);

        if (hexdec($hex[12]) !== 7) {
            $failure = "{$uuid} is not version 7";

            break;
        }

        if ((hexdec($hex[16]) & 0b1100) !== 0b1000) {
            $failure = "{$uuid} has the wrong variant bits";

            break;
        }
    }

    expect($failure)->toBeNull();
});

/**
 * "Implementations SHOULD allow encoding/decoding of other UUID variants when
 * the bits are provided by end users."
 */
test('caller-supplied non-v7 UUIDs round trip unchanged', function (string $uuid): void {
    expect(TypeID::fromUuid($uuid, 'legacy')->toUuid())->toBe($uuid);
})->with([
    'v4' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
    'v1' => 'c232ab00-9414-11ec-b3c8-9f6bdeced846',
    'nil' => '00000000-0000-0000-0000-000000000000',
    'max' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
]);

/**
 * "The maximum possible suffix for a typeid is 7zzzzzzzzzzzzzzzzzzzzzzzzz,
 * which corresponds to the maximum 128-bit value."
 */
test('the maximum suffix is the maximum 128-bit UUID', function (): void {
    expect(TypeID::fromString('7zzzzzzzzzzzzzzzzzzzzzzzzz')->toUuid())
        ->toBe('ffffffff-ffff-ffff-ffff-ffffffffffff')
        ->and(TypeID::fromUuid('ffffffff-ffff-ffff-ffff-ffffffffffff')->suffix)
        ->toBe('7zzzzzzzzzzzzzzzzzzzzzzzzz');
});

/**
 * "Two zeroed bits are prepended... the first base32 character must never
 * exceed decimal 7."
 */
test('the leading character never exceeds 7 for any 128-bit value', function (string $fill): void {
    $suffix = TypeID::fromBytes(hex2bin(str_repeat($fill, 16)))->suffix;

    expect((int) $suffix[0])->toBeLessThanOrEqual(7);
})->with(['00', 'ff', '80', '7f', 'aa', '55', '0f', 'f0']);

/**
 * The alphabet, derived through the public seam rather than asserted as a
 * constant: encoding the values 0..31 into the final 5-bit group must yield
 * the spec's table in order.
 */
test('the base32 alphabet matches the specification table', function (): void {
    $alphabet = '';

    foreach (range(0, 31) as $value) {
        $bytes = array_fill(0, 16, 0);
        $bytes[15] = $value;
        $alphabet .= TypeID::fromBytes(pack('C*', ...$bytes))->suffix[25];
    }

    expect($alphabet)->toBe('0123456789abcdefghjkmnpqrstvwxyz');
});

/**
 * "Minimum length: 26 characters. Maximum length: 90 characters
 * (63 for prefix + 1 for separator + 26 for suffix)."
 */
test('canonical strings observe the specified length bounds', function (): void {
    $shortest = TypeID::zero()->toString();
    $longest = TypeID::zero(str_repeat('a', 63))->toString();

    expect($shortest)->toHaveLength(26)
        ->and($longest)->toHaveLength(90)
        ->and(TypeID::fromString($longest)->prefix)->toBe(str_repeat('a', 63));
});

/**
 * K-sortability is the reason the spec mandates UUIDv7 for generated ids.
 */
test('generated ids sharing a prefix sort into creation order', function (): void {
    $previous = '';
    $failure = null;

    foreach (range(1, 5000) as $ignored) {
        $current = TypeID::generate('event')->toString();

        if (strcmp($current, $previous) < 0) {
            $failure = "{$current} sorts before its predecessor {$previous}";

            break;
        }

        $previous = $current;
    }

    expect($failure)->toBeNull();
});
