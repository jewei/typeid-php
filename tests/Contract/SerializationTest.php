<?php

declare(strict_types=1);

use TypeID\Exception\ValidationException;
use TypeID\TypeID;

test('json encodes as the canonical TypeID string', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');

    expect(json_encode($typeId, JSON_THROW_ON_ERROR))->toBe('"user_01jsnsf2g7e2saxdjvz3j6tc3x"');
});

test('json encodes a bare TypeID without a separator', function (): void {
    expect(json_encode(new TypeID('', '01jsnsf2g7e2saxdjvz3j6tc3x'), JSON_THROW_ON_ERROR))
        ->toBe('"01jsnsf2g7e2saxdjvz3j6tc3x"');
});

test('native serialization round trips a TypeID', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    $restored = unserialize(serialize($typeId));

    expect($restored)->toBeInstanceOf(TypeID::class)
        ->and($restored->equals($typeId))->toBeTrue();
});

test('native serialization round trips a bare TypeID', function (): void {
    $typeId = new TypeID('', '01jsnsf2g7e2saxdjvz3j6tc3x');

    expect(unserialize(serialize($typeId))->equals($typeId))->toBeTrue();
});

/** TypeID rejects invalid fields during unserialization. */
test('unserialization rejects a tampered prefix', function (): void {
    $serialized = serialize(new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x'));
    $corrupt = str_replace('s:4:"user";', 's:7:"INVALID";', $serialized);

    expect($corrupt)->not->toBe($serialized)
        ->and(fn () => unserialize($corrupt))->toThrow(ValidationException::class);
});

test('unserialization rejects a tampered suffix', function (): void {
    $serialized = serialize(new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x'));
    $corrupt = str_replace('01jsnsf2g7e2saxdjvz3j6tc3x', '8zzzzzzzzzzzzzzzzzzzzzzzzz', $serialized);

    expect($corrupt)->not->toBe($serialized)
        ->and(fn () => unserialize($corrupt))->toThrow(ValidationException::class);
});

test('unserialization rejects a payload with missing fields', function (): void {
    expect(fn () => unserialize('O:13:"TypeID\TypeID":0:{}'))
        ->toThrow(ValidationException::class);
});

test('unserialization rejects a payload with non-string fields', function (): void {
    $payload = 'O:13:"TypeID\TypeID":2:{s:6:"prefix";i:1;s:6:"suffix";i:2;}';

    expect(fn () => unserialize($payload))->toThrow(ValidationException::class);
});
