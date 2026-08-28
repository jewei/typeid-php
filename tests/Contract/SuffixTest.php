<?php

declare(strict_types=1);

use TypeID\Exception\ValidationException;
use TypeID\TypeID;

/**
 * Tests suffix rules through TypeID.
 *
 * Spec 0.3.0 requires 26 characters from the strict alphabet. The first
 * character cannot exceed '7' because the suffix encodes 128 bits.
 */
test('accepts a canonical suffix', function (string $suffix): void {
    expect((new TypeID('user', $suffix))->suffix)->toBe($suffix);
})->with([
    'typical' => '01jsnsf2g7e2saxdjvz3j6tc3x',
    'nil' => TypeID::ZERO_SUFFIX,
    'maximum 128-bit value' => '7zzzzzzzzzzzzzzzzzzzzzzzzz',
    'just below maximum' => '7zzzzzzzzzzzzzzzzzzzzzzzzy',
]);

test('rejects a non-canonical suffix', function (string $suffix): void {
    expect(fn () => new TypeID('user', $suffix))->toThrow(ValidationException::class);
})->with([
    'single character' => '0',
    'one short' => str_repeat('0', 25),
    'one long' => str_repeat('0', 27),
    'ambiguous letters o, i, l' => '01jsnsf2g7e2saxdjvOILz3j6tc',
    'trailing uppercase' => '01jsnsf2g7e2saxdjvz3j6tc3X',
    'all uppercase' => '01JSNSF2G7E2SAXDJVZ3J6TC3X',
    'mixed case' => '01JsNsF2g7E2sAxDjVz3J6tC3x',
    'ambiguous i and l decoded leniently' => '0Ijsnsf2g7e2saxdjvz3jltc3x',
    'trailing newline' => str_repeat('0', 25)."\n",
]);

test('rejects suffixes that overflow 128 bits', function (string $suffix): void {
    expect(fn () => new TypeID('user', $suffix))->toThrow(ValidationException::class);
})->with([
    'leading 8' => '8zzzzzzzzzzzzzzzzzzzzzzzzz',
    'leading 9' => '9zzzzzzzzzzzzzzzzzzzzzzzzz',
    'leading z' => 'zzzzzzzzzzzzzzzzzzzzzzzzzz',
]);

test('every suffix is exactly 26 characters', function (): void {
    expect(TypeID::generate('user')->suffix)->toHaveLength(26)
        ->and(TypeID::zero()->suffix)->toHaveLength(26)
        ->and(TypeID::fromUuid('f47ac10b-58cc-4372-a567-0e02b2c3d479')->suffix)->toHaveLength(26);
});
