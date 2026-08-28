<?php

declare(strict_types=1);

use TypeID\Exception\ValidationException;
use TypeID\TypeID;

/**
 * The type prefix grammar, exercised through the supported seam.
 *
 * Spec 0.3.0: at most 63 characters, lowercase [a-z_] only, must start and end
 * with a letter, may be empty, consecutive underscores allowed.
 */
test('accepts every valid type prefix', function (string $prefix): void {
    expect(TypeID::zero($prefix)->prefix)->toBe($prefix);
})->with([
    'empty' => '',
    'single letter' => 'a',
    'common' => 'user',
    'two segments' => 'a_b',
    'three segments' => 'a_b_c',
    'descriptive' => 'prefix_with_underscore',
    'consecutive underscores' => 'multiple__underscores',
    'maximum 63 chars' => str_repeat('a', 63),
]);

test('rejects every invalid type prefix', function (string $prefix): void {
    expect(fn () => TypeID::zero($prefix))->toThrow(ValidationException::class);
})->with([
    'uppercase' => 'UPPERCASE',
    'mixed case' => 'Invalid',
    'non-ascii' => 'préfix',
    'leading space' => ' prefix',
    'trailing space' => 'prefix ',
    'hyphen' => 'invalid-prefix',
    'period' => 'invalid.prefix',
    'leading digits' => '123prefix',
    'all digits' => '123456',
    'leading underscore' => '_prefix',
    'trailing underscore' => 'prefix_',
    'double leading underscore' => '__prefix',
    'trailing newline' => "user\n",
    'over maximum 64 chars' => str_repeat('a', 64),
]);

test('the last underscore separates prefix from suffix', function (string $value, string $prefix): void {
    expect(TypeID::fromString($value)->prefix)->toBe($prefix);
})->with([
    'no prefix' => ['01jsnsf2g7e2saxdjvz3j6tc3x', ''],
    'single letter' => ['a_01jsnsf2g7e2saxdjvz3j6tc3x', 'a'],
    'one segment' => ['user_01jsnsf2g7e2saxdjvz3j6tc3x', 'user'],
    'two segments' => ['user_profile_01jsnsf2g7e2saxdjvz3j6tc3x', 'user_profile'],
    'many segments' => ['very_long_prefix_01jsnsf2g7e2saxdjvz3j6tc3x', 'very_long_prefix'],
]);

test('rejects strings whose split yields an invalid prefix', function (string $value): void {
    expect(fn () => TypeID::fromString($value))->toThrow(ValidationException::class);
})->with([
    'no separator, not a suffix' => 'invalid-typeid',
    'suffix is not canonical' => 'prefix_invalid_suffix',
    'uppercase prefix' => 'Invalid_01jsnsf2g7e2saxdjvz3j6tc3x',
    'prefix ends with underscore' => 'user__01jsnsf2g7e2saxdjvz3j6tc3x',
    'leading underscore' => '__01jsnsf2g7e2saxdjvz3j6tc3x',
    'empty string' => '',
]);

test('a maximum-length prefix survives a full string round trip', function (): void {
    $prefix = str_repeat('a', 63);
    $uuid = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';

    $parsed = TypeID::fromString(TypeID::fromUuid($uuid, $prefix)->toString());

    expect($parsed->prefix)->toBe($prefix)
        ->and($parsed->toUuid())->toBe($uuid);
});
