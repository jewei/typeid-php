<?php

declare(strict_types=1);

use TypeID\Base32;
use TypeID\Exception\TypeIDException;
use TypeID\Exception\ValidationException;
use TypeID\TypeID;
use TypeID\Validator;

// ===== TypeID Creation and Parsing Tests =====

test('create TypeID with valid prefix and suffix', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->prefix)->toBe('user');
    expect($typeId->suffix)->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->toString())->toBe('user_01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('create TypeID with empty prefix', function (): void {
    $typeId = new TypeID('', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->prefix)->toBe('');
    expect($typeId->suffix)->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->toString())->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('TypeID with invalid prefix throws exception', function (): void {
    expect(fn () => new TypeID('Invalid-Prefix', '01jsnsf2g7e2saxdjvz3j6tc3x'))
        ->toThrow(ValidationException::class, 'Invalid prefix: Invalid-Prefix');
});

test('TypeID with too long prefix throws exception', function (): void {
    $longPrefix = str_repeat('a', 64);
    expect(fn () => new TypeID($longPrefix, '01jsnsf2g7e2saxdjvz3j6tc3x'))
        ->toThrow(ValidationException::class, 'Invalid prefix: aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
});

test('TypeID with invalid suffix throws exception', function (): void {
    expect(fn () => new TypeID('user', 'invalid_suffix'))
        ->toThrow(ValidationException::class, 'Invalid suffix: invalid_suffix');
});

test('TypeID string representation works with toString and stringification', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->toString())->toBe('user_01jsnsf2g7e2saxdjvz3j6tc3x');
    expect((string) $typeId)->toBe('user_01jsnsf2g7e2saxdjvz3j6tc3x');
});

// ===== TypeID Factory Methods Tests =====

test('generate random TypeID with prefix', function (): void {
    $typeId = TypeID::generate('user');
    $uuidHex = str_replace('-', '', $typeId->toUuid());

    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->prefix)->toBe('user');
    expect(strlen($typeId->suffix))->toBe(26);
    expect($typeId->toString())->toStartWith('user_');
    expect($typeId->isZero())->toBeFalse();
    expect($uuidHex[12])->toBe('7');
    expect(hexdec($uuidHex[16]) & 0xC)->toBe(0x8);
});

test('generate random TypeID without prefix', function (): void {
    $typeId = TypeID::generate();
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->prefix)->toBe('');
    expect(strlen($typeId->suffix))->toBe(26);
    expect($typeId->toString())->toBe($typeId->suffix);
    expect($typeId->isZero())->toBeFalse();
});

test('generated TypeIDs with the same prefix are K-sortable', function (): void {
    $previous = '';

    for ($i = 0; $i < 3000; $i++) {
        $current = TypeID::generate('event')->toString();

        expect(strcmp($current, $previous))->toBeGreaterThanOrEqual(0);
        $previous = $current;
    }
});

test('generate with invalid prefix throws exception', function (): void {
    expect(fn () => TypeID::generate('Invalid-Prefix'))
        ->toThrow(ValidationException::class, 'Invalid prefix: Invalid-Prefix');
});

test('create zero TypeID with prefix', function (): void {
    $typeId = TypeID::zero('user');
    expect($typeId->prefix)->toBe('user');
    expect($typeId->suffix)->toBe(TypeID::ZERO_SUFFIX);
    expect($typeId->toString())->toBe('user_'.TypeID::ZERO_SUFFIX);
    expect($typeId->isZero())->toBeTrue();
});

test('create zero TypeID without prefix', function (): void {
    $typeId = TypeID::zero();
    expect($typeId->prefix)->toBe('');
    expect($typeId->suffix)->toBe(TypeID::ZERO_SUFFIX);
    expect($typeId->toString())->toBe(TypeID::ZERO_SUFFIX);
    expect($typeId->isZero())->toBeTrue();
});

test('zero with invalid prefix throws exception', function (): void {
    expect(fn () => TypeID::zero('Invalid-Prefix'))
        ->toThrow(ValidationException::class, 'Invalid prefix: Invalid-Prefix');
});

// ===== TypeID Conversion Tests =====

test('fromString with valid TypeID string', function (): void {
    $typeId = TypeID::fromString('user_01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->prefix)->toBe('user');
    expect($typeId->suffix)->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('fromString with suffix only', function (): void {
    $typeId = TypeID::fromString('01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->prefix)->toBe('');
    expect($typeId->suffix)->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('fromString with empty string throws exception', function (): void {
    expect(fn () => TypeID::fromString(''))
        ->toThrow(ValidationException::class, 'TypeID string cannot be empty');
});

test('fromString with invalid TypeID format throws exception', function (): void {
    expect(fn () => TypeID::fromString('user-01jsnsf2g7e2saxdjvz3j6tc3x'))
        ->toThrow(ValidationException::class, 'Invalid suffix: user-01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('fromUuid with valid UUIDv7', function (): void {
    $uuid = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';
    $typeId = TypeID::fromUuid($uuid, 'user');
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->prefix)->toBe('user');
    expect($typeId->toUuid())->toBe($uuid);
});

test('fromUuid without prefix', function (): void {
    $uuid = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';
    $typeId = TypeID::fromUuid($uuid);
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->prefix)->toBe('');
    expect($typeId->toUuid())->toBe($uuid);
});

test('fromUuid with invalid UUID throws exception', function (): void {
    expect(fn () => TypeID::fromUuid('not-a-uuid', 'user'))
        ->toThrow(ValidationException::class, 'Invalid UUID string: not-a-uuid');
});

test('fromUuid with non-UUIDv7 succeeds', function (): void {
    // UUIDv4 should now encode successfully
    $uuidv4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $typeId = TypeID::fromUuid($uuidv4, 'user');
    expect($typeId)->toBeInstanceOf(TypeID::class);
    expect($typeId->toUuid())->toBe($uuidv4);
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

test('Base32 byte encoding and decoding roundtrip', function (): void {
    $bytes = implode('', array_map(chr(...), range(0, 15)));
    $encoded = Base32::encodeBytes($bytes);

    expect($encoded)->toHaveLength(26);
    expect(Base32::decodeBytes($encoded))->toBe($bytes);
});

test('Base32 encodeBytes rejects values that are not exactly 16 bytes', function (string $bytes): void {
    expect(fn () => Base32::encodeBytes($bytes))->toThrow(ValidationException::class);
})->with([
    'too short' => str_repeat("\0", 15),
    'too long' => str_repeat("\0", 17),
]);

test('Base32 encode with malformed UUID throws exception', function (): void {
    expect(fn () => Base32::encode('not-a-uuid'))
        ->toThrow(ValidationException::class, 'Invalid UUID string: not-a-uuid');
});

test('Base32 encode with valid non-UUIDv7 succeeds', function (): void {
    // UUIDv4 should encode successfully
    $uuidv4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $encoded = Base32::encode($uuidv4);
    expect($encoded)->toHaveLength(26);
    expect(Base32::decode($encoded))->toBe($uuidv4);
});

test('Base32 decode with zero suffix', function (): void {
    $zeroUuid = '00000000-0000-0000-0000-000000000000';
    expect(Base32::decode(TypeID::ZERO_SUFFIX))->toBe($zeroUuid);
});

test('Base32 decode with invalid characters throws exception', function (): void {
    expect(fn () => Base32::decode('ill3g4l-ch4r4ct3rs-in-b4s332'))
        ->toThrow(ValidationException::class, 'Invalid TypeID base32 string: ill3g4l-ch4r4ct3rs-in-b4s332');
});

test('Base32 decode with wrong length throws exception', function (): void {
    expect(fn () => Base32::decode('tooshort'))
        ->toThrow(ValidationException::class, 'Invalid TypeID base32 string: tooshort');
});

test('Base32 decode rejects non-canonical Crockford input', function (string $suffix): void {
    expect(fn () => Base32::decode($suffix))->toThrow(ValidationException::class);
})->with([
    'uppercase' => '01JsNsF2g7E2sAxDjVz3J6tC3x',
    'ambiguous characters' => '0Ijsnsf2g7e2saxdjvz3jltc3x',
]);

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
    '01jsnsf2g7e2saxdjvz3j6tc3x',
    TypeID::ZERO_SUFFIX,
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

test('Validator parseTypeID only rejects empty and leading underscore strings', function (string $typeId): void {
    expect(fn () => Validator::parseTypeID($typeId))
        ->toThrow(ValidationException::class);
})->with([
    '',
    '__01jsnsf2g7e2saxdjvz3j6tc3x',
]);

test('Validator parseTypeID leaves component validation to TypeID', function (
    string $typeId,
    array $expected,
): void {
    expect(Validator::parseTypeID($typeId))->toBe($expected);
    expect(fn () => TypeID::fromString($typeId))->toThrow(ValidationException::class);
})->with([
    ['invalid-typeid', ['', 'invalid-typeid']],
    ['prefix_invalid_suffix', ['prefix_invalid', 'suffix']],
    ['Invalid_01jsnsf2g7e2saxdjvz3j6tc3x', ['Invalid', '01jsnsf2g7e2saxdjvz3j6tc3x']],
    ['user__01jsnsf2g7e2saxdjvz3j6tc3x', ['user_', '01jsnsf2g7e2saxdjvz3j6tc3x']],
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
    '01966b97-8a0770b2-aeb6-5bf8e46d307d', // Partially dashed
    '01966b978a07-70b2aeb6-5bf8e46d307d', // Inconsistently dashed
    "01966b97-8a07-70b2-aeb6-5bf8e46d307d\n", // Trailing newline
]);

// ===== Edge Cases and Robustness Tests =====

test('TypeID roundtrip with various prefixes and UUIDs', function (string $prefix, string $uuid): void {
    $typeId = TypeID::fromUuid($uuid, $prefix);
    expect($typeId->prefix)->toBe($prefix);
    expect($typeId->toUuid())->toBe($uuid);

    // Roundtrip through string
    $typeIdString = $typeId->toString();
    $parsedTypeId = TypeID::fromString($typeIdString);
    expect($parsedTypeId->prefix)->toBe($prefix);
    expect($parsedTypeId->toUuid())->toBe($uuid);
})->with([
    ['user', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
    ['', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
    ['very_long_prefix_with_underscores', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
    ['a', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
]);

test('multiple underscores in TypeID handling', function (): void {
    $typeIdWithMultipleUnderscores = 'user_profile_01jsnsf2g7e2saxdjvz3j6tc3x';

    $typeId = TypeID::fromString($typeIdWithMultipleUnderscores);
    expect($typeId->prefix)->toBe('user_profile');
    expect($typeId->suffix)->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('zero UUID handling', function (): void {
    $zeroUuid = '00000000-0000-0000-0000-000000000000';
    $typeId = TypeID::fromUuid($zeroUuid, 'user');

    expect($typeId->isZero())->toBeTrue();
    expect($typeId->suffix)->toBe(TypeID::ZERO_SUFFIX);
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

// ===== New Coverage Tests =====

test('fromUuid with uppercase UUID normalizes correctly', function (): void {
    $upper = '01966B97-8A07-70B2-AEB6-5BF8E46D307D';
    $lower = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';
    $typeId = TypeID::fromUuid($upper, 'user');
    expect($typeId->toUuid())->toBe($lower);
});

test('TypeID equals returns false comparing zero to non-zero with same prefix', function (): void {
    $zero = TypeID::zero('user');
    $nonZero = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($zero->equals($nonZero))->toBeFalse();
});

test('TypeID hasPrefix returns false when checking empty string on prefixed TypeID', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    expect($typeId->hasPrefix(''))->toBeFalse();
});

test('Validator isValidSuffix with boundary suffix values', function (string $suffix, bool $expected): void {
    expect(Validator::isValidSuffix($suffix))->toBe($expected);
})->with([
    ['7zzzzzzzzzzzzzzzzzzzzzzzzz', true],   // max valid
    ['7zzzzzzzzzzzzzzzzzzzzzzzzy', true],   // just below max
    ['8zzzzzzzzzzzzzzzzzzzzzzzzz', false],  // overflow
]);

test('fromString roundtrip with max-length prefix', function (): void {
    $maxPrefix = str_repeat('a', 63);
    $typeId = TypeID::fromUuid('01966b97-8a07-70b2-aeb6-5bf8e46d307d', $maxPrefix);
    $parsed = TypeID::fromString($typeId->toString());
    expect($parsed->prefix)->toBe($maxPrefix);
    expect($parsed->toUuid())->toBe('01966b97-8a07-70b2-aeb6-5bf8e46d307d');
});

test('all package exceptions share a catchable domain contract', function (): void {
    foreach ([
        fn () => TypeID::fromUuid('not-a-uuid'),
        fn () => new TypeID('INVALID', TypeID::ZERO_SUFFIX),
    ] as $operation) {
        try {
            $operation();
        } catch (TypeIDException $exception) {
            expect($exception)->toBeInstanceOf(TypeIDException::class);

            continue;
        }

        throw new \RuntimeException('Expected a TypeID exception');
    }
});

test('caller-invalid input is catchable as InvalidArgumentException', function (): void {
    expect(fn () => TypeID::fromString('invalid'))
        ->toThrow(\InvalidArgumentException::class);
});

test('validation rejects final newlines without leaking native errors', function (): void {
    $shortSuffixWithNewline = str_repeat('0', 25)."\n";

    expect(Validator::isValidPrefix("user\n"))->toBeFalse();
    expect(Validator::isValidSuffix($shortSuffixWithNewline))->toBeFalse();
    expect(fn () => TypeID::fromString($shortSuffixWithNewline))->toThrow(ValidationException::class);
    expect(fn () => TypeID::fromUuid("01966b97-8a07-70b2-aeb6-5bf8e46d307d\n"))
        ->toThrow(ValidationException::class);
    expect(fn () => TypeID::fromUuid('01966b97-8a0770b2-aeb6-5bf8e46d307d'))
        ->toThrow(ValidationException::class);
});

test('rejected values are escaped and truncated in exception messages', function (): void {
    $invalid = "INVALID\n".str_repeat('x', 100);

    foreach ([
        fn () => new TypeID($invalid, TypeID::ZERO_SUFFIX),
        fn () => Base32::encode($invalid),
        fn () => Base32::decode($invalid),
    ] as $operation) {
        try {
            $operation();
        } catch (ValidationException $exception) {
            expect($exception->getMessage())->not->toContain("\n");
            expect(strlen($exception->getMessage()) < 100)->toBeTrue();

            continue;
        }

        throw new \RuntimeException('Expected a validation exception');
    }
});

test('binary conversion roundtrips all byte values', function (): void {
    $bytes = implode('', array_map(chr(...), range(0, 15)));
    $typeId = TypeID::fromBytes($bytes, 'binary');

    expect($typeId->prefix)->toBe('binary');
    expect($typeId->bytes())->toBe($bytes);
});

test('fromBytes rejects values that are not exactly 16 bytes', function (string $bytes): void {
    expect(fn () => TypeID::fromBytes($bytes))->toThrow(ValidationException::class);
})->with([
    'too short' => str_repeat("\0", 15),
    'too long' => str_repeat("\0", 17),
]);

test('JSON serialization uses the canonical TypeID string', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');

    expect(json_encode($typeId, JSON_THROW_ON_ERROR))->toBe('"user_01jsnsf2g7e2saxdjvz3j6tc3x"');
});

test('native serialization roundtrips a TypeID', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    $restored = unserialize(serialize($typeId));

    expect($restored)->toBeInstanceOf(TypeID::class);
    expect($restored->equals($typeId))->toBeTrue();
});

test('native unserialization rejects corrupt TypeID data', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    $serialized = serialize($typeId);
    $corrupt = str_replace('s:4:"user";', 's:7:"INVALID";', $serialized);

    expect($corrupt)->not->toBe($serialized);
    expect(fn () => unserialize($corrupt))->toThrow(ValidationException::class);
});

test('non-zero helpers distinguish nil and populated suffixes', function (): void {
    $zero = TypeID::zero('user');
    $nonZero = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');

    expect($zero->isNonZero())->toBeFalse()
        ->and($nonZero->isNonZero())->toBeTrue();
});
