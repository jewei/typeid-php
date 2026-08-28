<?php

declare(strict_types=1);

use TypeID\Exception\ValidationException;
use TypeID\TypeID;

test('constructs from a prefix and suffix', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');

    expect($typeId->prefix)->toBe('user')
        ->and($typeId->suffix)->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('constructs a bare TypeID from an empty prefix', function (): void {
    $typeId = new TypeID('', '01jsnsf2g7e2saxdjvz3j6tc3x');

    expect($typeId->prefix)->toBe('')
        ->and($typeId->toString())->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('generate produces a distinct UUIDv7-backed value each call', function (): void {
    $first = TypeID::generate('user');
    $second = TypeID::generate('user');

    expect($first->prefix)->toBe('user')
        ->and($first->suffix)->toHaveLength(26)
        ->and($first->equals($second))->toBeFalse()
        ->and($first->isNonZero())->toBeTrue();
});

test('generate without a prefix produces a bare TypeID', function (): void {
    $typeId = TypeID::generate();

    expect($typeId->prefix)->toBe('')
        ->and($typeId->toString())->toBe($typeId->suffix);
});

test('default Ramsey generation sorts chronologically within one process', function (): void {
    $ids = array_map(fn (): string => TypeID::generate('user')->toString(), range(1, 25));
    $sorted = $ids;
    sort($sorted);

    expect($ids)->toBe($sorted);
});

test('zero produces the nil-backed TypeID', function (): void {
    $zero = TypeID::zero('user');

    expect($zero->suffix)->toBe(TypeID::ZERO_SUFFIX)
        ->and($zero->isZero())->toBeTrue()
        ->and($zero->toUuid())->toBe('00000000-0000-0000-0000-000000000000');
});

test('fromUuid accepts any UUID version', function (string $uuid): void {
    expect(TypeID::fromUuid($uuid, 'user')->toUuid())->toBe($uuid);
})->with([
    'v7' => '01966b97-8a07-70b2-aeb6-5bf8e46d307d',
    'v4' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
    'nil' => '00000000-0000-0000-0000-000000000000',
]);

test('fromUuid accepts unhyphenated and uppercase forms, normalising to lowercase', function (): void {
    $canonical = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';

    expect(TypeID::fromUuid('01966B97-8A07-70B2-AEB6-5BF8E46D307D', 'user')->toUuid())->toBe($canonical)
        ->and(TypeID::fromUuid('01966b978a0770b2aeb65bf8e46d307d', 'user')->toUuid())->toBe($canonical);
});

test('fromUuid rejects malformed UUIDs', function (string $uuid): void {
    expect(fn () => TypeID::fromUuid($uuid))->toThrow(ValidationException::class);
})->with([
    'empty' => '',
    'not a uuid' => 'not-a-uuid',
    'one hex short' => '01966b97-8a07-70b2-aeb6-5bf8e46d307',
    'one hex long' => '01966b97-8a07-70b2-aeb6-5bf8e46d307d0',
    'non-hex character' => '01966b97-8a07-70b2-aebz-5bf8e46d307d',
    'misplaced hyphens' => '01966b97-8a0770b2-aeb6-5bf8e46d307d',
    'hyphens shifted' => '01966b978a07-70b2aeb6-5bf8e46d307d',
    'trailing newline' => "01966b97-8a07-70b2-aeb6-5bf8e46d307d\n",
    'unhyphenated one short' => '01966b978a0770b2aeb65bf8e46d307',
    'unhyphenated one long' => '01966b978a0770b2aeb65bf8e46d307d0',
    'unhyphenated empty' => '',
    'unhyphenated non-hex' => '01966b978a0770b2aeb65bf8e46d307z',
]);

test('fromBytes accepts exactly 16 raw bytes', function (): void {
    $bytes = implode('', array_map(chr(...), range(0, 15)));
    $typeId = TypeID::fromBytes($bytes, 'binary');

    expect($typeId->prefix)->toBe('binary')
        ->and($typeId->bytes())->toBe($bytes);
});

test('fromBytes rejects anything other than 16 bytes', function (string $bytes): void {
    expect(fn () => TypeID::fromBytes($bytes))->toThrow(ValidationException::class);
})->with([
    'empty' => '',
    'fifteen' => str_repeat("\0", 15),
    'seventeen' => str_repeat("\0", 17),
]);

test('fromString parses prefixed and bare forms', function (): void {
    expect(TypeID::fromString('user_01jsnsf2g7e2saxdjvz3j6tc3x')->toString())
        ->toBe('user_01jsnsf2g7e2saxdjvz3j6tc3x')
        ->and(TypeID::fromString('01jsnsf2g7e2saxdjvz3j6tc3x')->toString())
        ->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});
