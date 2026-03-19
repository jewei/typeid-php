<?php

declare(strict_types=1);

use Exception;
use TypeID\TypeID;

/**
 * TypeID Specification (Version 0.3.0)
 *
 * This tests the list of strings that are invalid typeids and should fail to parse/decode.
 *
 * @see https://github.com/jetify-com/typeid/blob/main/spec/README.md
 */

// The prefix should be lowercase with no uppercase letters
test('prefix-uppercase', function (): void {
    expect(fn () => TypeID::fromString('PREFIX_00000000000000000000000000'))
        ->toThrow(Exception::class);
});

// The prefix can't have numbers, it needs to be alphabetic
test('prefix-numeric', function (): void {
    expect(fn () => TypeID::fromString('12345_00000000000000000000000000'))
        ->toThrow(Exception::class);
});

// The prefix can't have symbols, it needs to be alphabetic
test('prefix-period', function (): void {
    expect(fn () => TypeID::fromString('pre.fix_00000000000000000000000000'))
        ->toThrow(Exception::class);
});

// The prefix can only have ascii letters
test('prefix-non-ascii', function (): void {
    expect(fn () => TypeID::fromString('préfix_00000000000000000000000000'))
        ->toThrow(Exception::class);
});

// The prefix can't have any spaces
test('prefix-spaces', function (): void {
    expect(fn () => TypeID::fromString('  prefix_00000000000000000000000000'))
        ->toThrow(Exception::class);
});

// The prefix can't be 64 characters, it needs to be 63 characters or less
test('prefix-64-chars', function (): void {
    expect(fn () => TypeID::fromString('abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijkl_00000000000000000000000000'))
        ->toThrow(Exception::class);
});

// If the prefix is empty, the separator should not be there
test('separator-empty-prefix', function (): void {
    expect(fn () => TypeID::fromString('_00000000000000000000000000'))
        ->toThrow(Exception::class);
});

// A separator by itself should not be treated as the empty string
test('separator-empty', function (): void {
    expect(fn () => TypeID::fromString('_'))
        ->toThrow(Exception::class);
});

// The suffix can't be 25 characters, it needs to be exactly 26 characters
test('suffix-short', function (): void {
    expect(fn () => TypeID::fromString('prefix_1234567890123456789012345'))
        ->toThrow(Exception::class);
});

// The suffix can't be 27 characters, it needs to be exactly 26 characters
test('suffix-long', function (): void {
    expect(fn () => TypeID::fromString('prefix_123456789012345678901234567'))
        ->toThrow(Exception::class);
});

// The suffix can't have any spaces
test('suffix-spaces', function (): void {
    expect(fn () => TypeID::fromString('prefix_1234567890123456789012345 '))
        ->toThrow(Exception::class);
});

// The suffix should be lowercase with no uppercase letters
test('suffix-uppercase', function (): void {
    expect(fn () => TypeID::fromString('prefix_0123456789ABCDEFGHJKMNPQRS'))
        ->toThrow(Exception::class);
});

// The suffix can't have any hyphens
test('suffix-hyphens', function (): void {
    expect(fn () => TypeID::fromString('prefix_123456789-123456789-123456'))
        ->toThrow(Exception::class);
});

// The suffix should only have letters from the spec's alphabet
test('suffix-wrong-alphabet', function (): void {
    expect(fn () => TypeID::fromString('prefix_ooooooiiiiiiuuuuuuulllllll'))
        ->toThrow(Exception::class);
});

// The suffix should not have any ambiguous characters from the crockford encoding
test('suffix-ambiguous-crockford', function (): void {
    expect(fn () => TypeID::fromString('prefix_i23456789ol23456789oi23456'))
        ->toThrow(Exception::class);
});

// The suffix can't ignore hyphens as in the crockford encoding
test('suffix-hyphens-crockford', function (): void {
    expect(fn () => TypeID::fromString('prefix_123456789-0123456789-0123456'))
        ->toThrow(Exception::class);
});

// The suffix should encode at most 128-bits
test('suffix-overflow', function (): void {
    expect(fn () => TypeID::fromString('prefix_8zzzzzzzzzzzzzzzzzzzzzzzzz'))
        ->toThrow(Exception::class);
});

// The prefix can't start with an underscore
test('prefix-underscore-start', function (): void {
    expect(fn () => TypeID::fromString('_prefix_00000000000000000000000000'))
        ->toThrow(Exception::class);
});

// The prefix can't end with an underscore
test('prefix-underscore-end', function (): void {
    expect(fn () => TypeID::fromString('prefix__00000000000000000000000000'))
        ->toThrow(Exception::class);
});

// The empty string is not a valid typeid
test('empty', function (): void {
    expect(fn () => TypeID::fromString(''))
        ->toThrow(Exception::class);
});

// The suffix can't be the empty string
test('prefix-empty', function (): void {
    expect(fn () => TypeID::fromString('prefix_'))
        ->toThrow(Exception::class);
});
