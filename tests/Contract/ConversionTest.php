<?php

declare(strict_types=1);

use TypeID\TypeID;

test('decodes a TypeID string to its UUID', function (string $typeId, string $uuid): void {
    expect(TypeID::fromString($typeId)->toUuid())->toBe($uuid);
})->with([
    ['user_01jsns7byze78t2e8kcgkabxcq', '01966b93-afdf-71d1-a139-136426a5f597'],
    ['user_01jsnsf2g7e2saxdjvz3j6tc3x', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
    ['user_01jsnsfk97e6fs9587z73nax2r', '01966b97-cd27-719f-9495-07f9c7557458'],
    ['01jsnsq5hnef5scmjw9x8h7sg6', '01966b9b-9635-73cb-9652-5c4f5113e606'],
    ['01jsnsqhhre86rd028q5hbv9vr', '01966b9b-c638-720d-8680-48b962bda778'],
    ['01jsnsr3fbe54rkjzfkta25nct', '01966b9c-0deb-7149-89cb-ef9e9422d59a'],
]);

test('encodes a UUID to its TypeID string', function (string $typeId, string $uuid, ?string $prefix = null): void {
    expect(TypeID::fromUuid($uuid, $prefix)->toString())->toBe($typeId);
})->with([
    ['user_01jsns7byze78t2e8kcgkabxcq', '01966b93-afdf-71d1-a139-136426a5f597', 'user'],
    ['user_01jsnsf2g7e2saxdjvz3j6tc3x', '01966b97-8a07-70b2-aeb6-5bf8e46d307d', 'user'],
    ['user_01jsnsfk97e6fs9587z73nax2r', '01966b97-cd27-719f-9495-07f9c7557458', 'user'],
    ['01jsnsq5hnef5scmjw9x8h7sg6', '01966b9b-9635-73cb-9652-5c4f5113e606'],
    ['01jsnsqhhre86rd028q5hbv9vr', '01966b9b-c638-720d-8680-48b962bda778'],
    ['01jsnsr3fbe54rkjzfkta25nct', '01966b9c-0deb-7149-89cb-ef9e9422d59a'],
]);

test('UUID and string forms round trip through every prefix shape', function (string $prefix): void {
    $uuid = '01966b97-8a07-70b2-aeb6-5bf8e46d307d';
    $typeId = TypeID::fromUuid($uuid, $prefix);
    $parsed = TypeID::fromString($typeId->toString());

    expect($parsed->prefix)->toBe($prefix)
        ->and($parsed->toUuid())->toBe($uuid)
        ->and($parsed->equals($typeId))->toBeTrue();
})->with([
    'bare' => '',
    'single letter' => 'a',
    'common' => 'user',
    'many segments' => 'very_long_prefix_with_underscores',
]);

test('raw bytes round trip across every byte value', function (): void {
    $bytes = implode('', array_map(chr(...), range(0, 15)));

    expect(TypeID::fromBytes($bytes, 'binary')->bytes())->toBe($bytes);
});

test('the byte and UUID views agree', function (): void {
    $typeId = TypeID::fromUuid('01966b97-8a07-70b2-aeb6-5bf8e46d307d', 'user');

    expect(bin2hex($typeId->bytes()))->toBe(str_replace('-', '', $typeId->toUuid()));
});

test('the nil UUID decodes to the zero suffix', function (): void {
    expect(TypeID::zero()->toUuid())->toBe('00000000-0000-0000-0000-000000000000')
        ->and(TypeID::fromUuid('00000000-0000-0000-0000-000000000000')->suffix)
        ->toBe(TypeID::ZERO_SUFFIX);
});
