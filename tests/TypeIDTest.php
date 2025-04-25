<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;
use TypeID\Exception\ValidationException;
use TypeID\TypeID;
use TypeID\Validator;

test('create with prefix', function (): void {
    $typeId = TypeID::generate('user');

    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toEqual('user');
    expect($typeId->toString())->toStartWith('user_');
});

test('create with empty prefix', function (): void {
    $typeId = TypeID::generate('');

    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toEqual('');
    expect($typeId->toString())->toEqual($typeId->getSuffix());
});

test('from string', function (): void {
    $original = 'user_01h455vb4pex5vsknk084sn02q';
    $typeId = TypeID::fromString($original);

    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toEqual('user');
    expect($typeId->getSuffix())->toEqual('01h455vb4pex5vsknk084sn02q');
    expect($typeId->toString())->toEqual($original);
});

test('from uuid', function (): void {
    // Use a valid UUIDv7 string - with version 7 and variant 10xx
    $uuid = '01890a5d-ac91-7c80-9afe-a6b695cefa5d';
    $typeId = TypeID::fromUuid($uuid, 'post');

    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toEqual('post');
    expect($typeId->toUuid())->toEqual($uuid);
});

test('from uuid object', function (): void {
    $uuid = Uuid::uuid7();
    $typeId = TypeID::fromUuid($uuid->toString(), 'post');

    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toEqual('post');
    expect($typeId->toUuid())->toEqual($uuid->toString());
});

test('invalid string', function (): void {
    $this->expectException(ValidationException::class);
    TypeID::fromString('invalid string');
});

test('invalid prefix', function (): void {
    $this->expectException(ValidationException::class);
    TypeID::fromString('INVALID-PREFIX_01h455vb4pex5vsknk084sn02q');
});

test('invalid suffix', function (): void {
    $this->expectException(ValidationException::class);
    TypeID::fromString('user_invalid!suffix');
});

test('string representation', function (): void {
    $typeId = TypeID::generate('product');
    $string = (string) $typeId;

    expect($string)->toStartWith('product_');
});

test('zero id', function (): void {
    $typeId = new TypeID('test', '');

    expect($typeId->isZero())->toBeTrue();
    expect($typeId->toString())->toEqual('test_00000000000000000000000000');
});

test('generated TypeID has valid UUIDv7 format', function (): void {
    // Generate a new TypeID
    $typeId = TypeID::generate('test');

    // Extract the UUID
    $uuid = $typeId->toUuid();

    // Verify it's a valid UUIDv7
    expect(Validator::isValidUUIDv7($uuid))->toBeTrue();

    // Check specific version and variant bits
    $hexString = str_replace('-', '', $uuid);
    expect($hexString[12])->toBe('7'); // Version bits should be 7
    expect(in_array($hexString[16], ['8', '9', 'a', 'b'], true))->toBeTrue(); // Variant bits should be 10xx
});
