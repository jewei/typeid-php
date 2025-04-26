<?php

declare(strict_types=1);

use TypeID\Base32;
use TypeID\Exception\ConstructorException;
use TypeID\Exception\ValidationException;
use TypeID\TypeID;
use TypeID\Validator;

// ===== TypeID Creation and Parsing Tests =====

test('create TypeID with valid prefix and suffix', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->getPrefix())->toBe('user');
    expect($typeId->getSuffix())->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->toString())->toBe('user_01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('create TypeID with empty prefix', function (): void {
    $typeId = new TypeID('', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->getPrefix())->toBe('');
    expect($typeId->getSuffix())->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->toString())->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('create TypeID with empty suffix defaults to zero suffix', function (): void {
    $typeId = new TypeID('user', '');
    expect($typeId->getPrefix())->toBe('user');
    expect($typeId->getSuffix())->toBe(TypeID::ZERO_SUFFIX);
    expect($typeId->toString())->toBe('user_'.TypeID::ZERO_SUFFIX);
    expect($typeId->isZero())->toBeTrue();
});

test('TypeID with invalid prefix throws exception', function (): void {
    expect(fn () => new TypeID('Invalid-Prefix', '01jsnsf2g7e2saxdjvz3j6tc3x'))
        ->toThrow(ValidationException::class, 'Invalid prefix: Invalid-Prefix');
});

test('TypeID with too long prefix throws exception', function (): void {
    $longPrefix = str_repeat('a', 64);
    expect(fn () => new TypeID($longPrefix, '01jsnsf2g7e2saxdjvz3j6tc3x'))
        ->toThrow(ValidationException::class);
});

test('TypeID with invalid suffix throws exception', function (): void {
    expect(fn () => new TypeID('user', 'invalid_suffix'))
        ->toThrow(ValidationException::class);
});

test('TypeID string representation works with toString and stringification', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->toString())->toBe('user_01jsnsf2g7e2saxdjvz3j6tc3x');
    expect((string) $typeId)->toBe('user_01jsnsf2g7e2saxdjvz3j6tc3x');
});

// ===== TypeID Factory Methods Tests =====

test('generate random TypeID with prefix', function (): void {
    $typeId = TypeID::generate('user');
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toBe('user');
    expect(strlen($typeId->getSuffix()))->toBe(26);
    expect($typeId->toString())->toStartWith('user_');
    expect($typeId->isZero())->toBeFalse();
});

test('generate random TypeID without prefix', function (): void {
    $typeId = TypeID::generate();
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toBe('');
    expect(strlen($typeId->getSuffix()))->toBe(26);
    expect($typeId->toString())->toBe($typeId->getSuffix());
    expect($typeId->isZero())->toBeFalse();
});

test('generate with invalid prefix throws exception', function (): void {
    expect(fn () => TypeID::generate('Invalid-Prefix'))
        ->toThrow(ValidationException::class);
});

test('create zero TypeID with prefix', function (): void {
    $typeId = TypeID::zero('user');
    expect($typeId->getPrefix())->toBe('user');
    expect($typeId->getSuffix())->toBe(TypeID::ZERO_SUFFIX);
    expect($typeId->toString())->toBe('user_'.TypeID::ZERO_SUFFIX);
    expect($typeId->isZero())->toBeTrue();
});

test('create zero TypeID without prefix', function (): void {
    $typeId = TypeID::zero();
    expect($typeId->getPrefix())->toBe('');
    expect($typeId->getSuffix())->toBe(TypeID::ZERO_SUFFIX);
    expect($typeId->toString())->toBe(TypeID::ZERO_SUFFIX);
    expect($typeId->isZero())->toBeTrue();
});

test('zero with invalid prefix throws exception', function (): void {
    expect(fn () => TypeID::zero('Invalid-Prefix'))
        ->toThrow(ValidationException::class);
});

// ===== TypeID Conversion Tests =====

test('fromString with valid TypeID string', function (): void {
    $typeId = TypeID::fromString('user_01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toBe('user');
    expect($typeId->getSuffix())->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('fromString with suffix only', function (): void {
    $typeId = TypeID::fromString('01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toBe('');
    expect($typeId->getSuffix())->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('fromString with empty string throws exception', function (): void {
    expect(fn () => TypeID::fromString(''))
        ->toThrow(ValidationException::class, 'TypeID string cannot be empty');
});

test('fromString with invalid TypeID format throws exception', function (): void {
    expect(fn () => TypeID::fromString('user-01jsnsf2g7e2saxdjvz3j6tc3x'))
        ->toThrow(ValidationException::class);
});

test('fromUuid with valid UUIDv7', function (): void {
    $uuid = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';
    $typeId = TypeID::fromUuid($uuid, 'user');
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toBe('user');
    expect($typeId->toUuid())->toBe($uuid);
});

test('fromUuid without prefix', function (): void {
    $uuid = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';
    $typeId = TypeID::fromUuid($uuid);
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->getPrefix())->toBe('');
    expect($typeId->toUuid())->toBe($uuid);
});

test('fromUuid with invalid UUID throws exception', function (): void {
    expect(fn () => TypeID::fromUuid('not-a-uuid', 'user'))
        ->toThrow(ConstructorException::class);
});

test('fromUuid with non-UUIDv7 throws exception', function (): void {
    // This is a UUIDv4, not UUIDv7
    $uuidv4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    expect(fn () => TypeID::fromUuid($uuidv4, 'user'))
        ->toThrow(ConstructorException::class);
});

test('toUuid converts TypeID to original UUID', function (): void {
    $uuid = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';
    $typeId = TypeID::fromUuid($uuid, 'user');
    expect($typeId->toUuid())->toBe($uuid);
});

// ===== TypeID Comparison Methods Tests =====

test('TypeID equals with identical TypeIDs', function (): void {
    $typeId1 = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    $typeId2 = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId1->equals($typeId2))->toBeTrue();
});

test('TypeID equals with different prefixes', function (): void {
    $typeId1 = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    $typeId2 = new TypeID('post', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId1->equals($typeId2))->toBeFalse();
});

test('TypeID equals with different suffixes', function (): void {
    $typeId1 = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    $typeId2 = new TypeID('user', '01jsnsfk97e6fs9587z73nax2r');
    expect($typeId1->equals($typeId2))->toBeFalse();
});

test('TypeID hasPrefix returns true for matching prefix', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->hasPrefix('user'))->toBeTrue();
});

test('TypeID hasPrefix returns false for non-matching prefix', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->hasPrefix('post'))->toBeFalse();
});

// ===== Base32 Encoding/Decoding Tests =====

test('Base32 encode and decode roundtrip', function (): void {
    $uuid = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';
    $encoded = Base32::encode($uuid);
    expect(Base32::decode($encoded))->toBe($uuid);
});

test('Base32 encode with malformed UUID throws exception', function (): void {
    expect(fn () => Base32::encode('not-a-uuid'))
        ->toThrow(\InvalidArgumentException::class);
});

test('Base32 encode with invalid UUIDv7 version throws exception', function (): void {
    // UUIDv4
    $uuidv4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    expect(fn () => Base32::encode($uuidv4))
        ->toThrow(\InvalidArgumentException::class);
});

test('Base32 decode with zero suffix', function (): void {
    $zeroUuid = '00000000-0000-7000-8000-000000000000';
    expect(Base32::decode(TypeID::ZERO_SUFFIX))->toBe($zeroUuid);
});

test('Base32 decode with invalid characters throws exception', function (): void {
    expect(fn () => Base32::decode('ill3g4l-ch4r4ct3rs-in-b4s332'))
        ->toThrow(\InvalidArgumentException::class);
});

test('Base32 decode with wrong length throws exception', function (): void {
    expect(fn () => Base32::decode('tooshort'))
        ->toThrow(\InvalidArgumentException::class);
});

// ===== Validator Tests =====

test('Validator isValidPrefix with valid prefixes', function (string $prefix): void {
    expect(Validator::isValidPrefix($prefix))->toBeTrue();
})->with([
    '',
    'user',
    'post',
    'comment',
    'a',
    'a_b',
    'a_b_c',
    'prefix_with_underscore',
    'multiple__underscores',
    str_repeat('a', 63), // Max length
]);

test('Validator isValidPrefix with invalid prefixes', function (string $prefix): void {
    expect(Validator::isValidPrefix($prefix))->toBeFalse();
})->with([
    'UPPERCASE',
    'Invalid',
    'préfix',
    ' prefix',
    'prefix ',
    'invalid-prefix',
    'invalid.prefix',
    '123prefix',
    '123456',
    '_prefix',
    'prefix_',
    '__prefix',
    str_repeat('a', 64), // Too long
]);

test('Validator isValidSuffix with valid suffixes', function (string $suffix): void {
    expect(Validator::isValidSuffix($suffix))->toBeTrue();
})->with([
    '',
    '01jsnsf2g7e2saxdjvz3j6tc3x',
    TypeID::ZERO_SUFFIX,
    '8zzzzzzzzzzzzzzzzzzzzzzzzz',
]);

test('Validator isValidSuffix with invalid suffixes', function (string $suffix): void {
    expect(Validator::isValidSuffix($suffix))->toBeFalse();
})->with([
    '0',
    str_repeat('0', 25), // Too short
    str_repeat('0', 27), // Too long
    '01jsnsf2g7e2saxdjvOILz3j6tc', // Contains invalid chars O, I, L
    '01jsnsf2g7e2saxdjvz3j6tc3X', // Contains uppercase
    '01JSNSF2G7E2SAXDJVZ3J6TC3X', // Contains uppercase only
]);

test('Validator parseTypeID with valid TypeIDs', function (string $typeId, array $expected): void {
    expect(Validator::parseTypeID($typeId))->toBe($expected);
})->with([
    ['user_01jsnsf2g7e2saxdjvz3j6tc3x', ['user', '01jsnsf2g7e2saxdjvz3j6tc3x']],
    ['01jsnsf2g7e2saxdjvz3j6tc3x', ['', '01jsnsf2g7e2saxdjvz3j6tc3x']],
    ['a_01jsnsf2g7e2saxdjvz3j6tc3x', ['a', '01jsnsf2g7e2saxdjvz3j6tc3x']],
    ['very_long_prefix_01jsnsf2g7e2saxdjvz3j6tc3x', ['very_long_prefix', '01jsnsf2g7e2saxdjvz3j6tc3x']],
]);

test('Validator parseTypeID with invalid TypeIDs returns null', function (string $typeId): void {
    expect(Validator::parseTypeID($typeId))->toBeNull();
})->with([
    '',
    'invalid-typeid',
    'prefix_invalid_suffix',
    'Invalid_01jsnsf2g7e2saxdjvz3j6tc3x',
    'user__01jsnsf2g7e2saxdjvz3j6tc3x',
    '__01jsnsf2g7e2saxdjvz3j6tc3x',
]);

test('Validator isValidUuid with valid UUIDs', function (string $uuid): void {
    expect(Validator::isValidUuid($uuid))->toBeTrue();
})->with([
    '01966b97-8a07-70b2-aeb6-5bf8e46d307d', // With dashes
    '01966b978a0770b2aeb65bf8e46d307d', // Without dashes
    '00000000-0000-0000-0000-000000000000', // Zero UUID
    'f47ac10b-58cc-4372-a567-0e02b2c3d479', // UUIDv4
]);

test('Validator isValidUuid with invalid UUIDs', function (string $uuid): void {
    expect(Validator::isValidUuid($uuid))->toBeFalse();
})->with([
    '',
    'not-a-uuid',
    '01966b97-8a07-70b2-aeb6-5bf8e46d307', // Too short
    '01966b97-8a07-70b2-aeb6-5bf8e46d307d0', // Too long
    '01966b97-8a07-70b2-aebz-5bf8e46d307d', // Invalid char
]);

test('Validator isValidUuidv7 with valid UUIDv7s', function (string $uuid): void {
    expect(Validator::isValidUuidv7($uuid))->toBeTrue();
})->with([
    '01966b97-8a07-70b2-aeb6-5bf8e46d307d', // With dashes
    '01966b978a0770b2aeb65bf8e46d307d', // Without dashes
]);

test('Validator isValidUuidv7 with invalid UUIDv7s', function (string $uuid, ?string $description = null): void {
    expect(Validator::isValidUuidv7($uuid))->toBeFalse();
})->with([
    ['', 'Empty string'],
    ['not-a-uuid', 'Not a UUID'],
    ['f47ac10b-58cc-4372-a567-0e02b2c3d479', 'UUIDv4'],
    ['01966b97-8a07-10b2-aeb6-5bf8e46d307d', 'Wrong version bits (1 instead of 7)'],
    ['01966b97-8a07-70b2-2eb6-5bf8e46d307d', 'Wrong variant bits (2 instead of a/b)'],
]);

// ===== Edge Cases and Robustness Tests =====

test('TypeID roundtrip with various prefixes and UUIDs', function (string $prefix, string $uuid): void {
    $typeId = TypeID::fromUuid($uuid, $prefix);
    expect($typeId->getPrefix())->toBe($prefix);
    expect($typeId->toUuid())->toBe($uuid);

    // Roundtrip through string
    $typeIdString = $typeId->toString();
    $parsedTypeId = TypeID::fromString($typeIdString);
    expect($parsedTypeId->getPrefix())->toBe($prefix);
    expect($parsedTypeId->toUuid())->toBe($uuid);
})->with([
    ['user', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
    ['', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
    ['very_long_prefix_with_underscores', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
    ['a', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
]);

test('case normalization in Base32 decode', function (): void {
    // Mixed case input should be normalized
    $mixedCase = '01JsNsF2g7E2sAxDjVz3J6tC3x';
    $normalized = strtolower($mixedCase);

    $uuid = Base32::decode($mixedCase);
    expect($uuid)->toBe(Base32::decode($normalized));
});

test('ambiguous character handling in Base32 decode', function (): void {
    // Replace ambiguous characters: O->0, I/L->1
    $withAmbiguous = '0IJsNsF2g7E2sAxDjVz3JLtC3x'; // Contains O, I, L
    $normalized = '01jsnsf2g7e2saxdjvz3j1tc3x';   // Should convert to 0, 1, 1

    $uuid = Base32::decode($withAmbiguous);
    expect($uuid)->toBe(Base32::decode($normalized));
});

test('multiple underscores in TypeID handling', function (): void {
    $typeIdWithMultipleUnderscores = 'user_profile_01jsnsf2g7e2saxdjvz3j6tc3x';

    $typeId = TypeID::fromString($typeIdWithMultipleUnderscores);
    expect($typeId->getPrefix())->toBe('user_profile');
    expect($typeId->getSuffix())->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('zero UUID handling', function (): void {
    $zeroUuid = '00000000-0000-7000-8000-000000000000';
    $typeId = TypeID::fromUuid($zeroUuid, 'user');

    expect($typeId->isZero())->toBeTrue();
    expect($typeId->getSuffix())->toBe(TypeID::ZERO_SUFFIX);
    expect($typeId->toUuid())->toBe($zeroUuid);
});

test('decoding TypeID', function (string $typeId, string $uuid): void {

    expect(TypeID::fromString($typeId)->toUuid())->toEqual($uuid);

})->with([
    ['user_01jsns7byze78t2e8kcgkabxcq', '01966b93-afdf-71d1-a139-136426a5f597'],
    ['user_01jsnsf2g7e2saxdjvz3j6tc3x', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
    ['user_01jsnsfk97e6fs9587z73nax2r', '01966b97-cd27-719f-9495-07f9c7557458'],
    ['01jsnsq5hnef5scmjw9x8h7sg6', '01966b9b-9635-73cb-9652-5c4f5113e606'],
    ['01jsnsqhhre86rd028q5hbv9vr', '01966b9b-c638-720d-8680-48b962bda778'],
    ['01jsnsr3fbe54rkjzfkta25nct', '01966b9c-0deb-7149-89cb-ef9e9422d59a'],
]);

test('encoding TypeID', function (string $typeId, string $uuid, ?string $prefix = null): void {

    expect(TypeID::fromUuid($uuid, $prefix)->toString())->toEqual($typeId);

})->with([
    ['user_01jsns7byze78t2e8kcgkabxcq', '01966b93-afdf-71d1-a139-136426a5f597', 'user'],
    ['user_01jsnsf2g7e2saxdjvz3j6tc3x', '01966b97-8a07-70b2-aeb6-5bf8e46d307d', 'user'],
    ['user_01jsnsfk97e6fs9587z73nax2r', '01966b97-cd27-719f-9495-07f9c7557458', 'user'],
    ['01jsnsq5hnef5scmjw9x8h7sg6', '01966b9b-9635-73cb-9652-5c4f5113e606'],
    ['01jsnsqhhre86rd028q5hbv9vr', '01966b9b-c638-720d-8680-48b962bda778'],
    ['01jsnsr3fbe54rkjzfkta25nct', '01966b9c-0deb-7149-89cb-ef9e9422d59a'],
]);
