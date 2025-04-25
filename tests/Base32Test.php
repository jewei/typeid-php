<?php

declare(strict_types=1);

use TypeID\Base32;
use TypeID\TypeID;
use TypeID\Validator;

test('validate valid UUIDv7', function (): void {
    // Valid UUIDv7 with version bits 0111 (7) and variant bits 10xx (8-b)
    $validUuid = '01890a5d-ac91-7c80-9afe-a6b695cefa5d';

    expect(Validator::isValidUUIDv7($validUuid))->toBeTrue();
});

test('invalid UUIDv7 with wrong version', function (): void {
    // Invalid - version bits are 0001 (1) instead of 0111 (7)
    $invalidVersionUuid = '01890a5d-ac91-1c80-9afe-a6b695cefa5d';

    expect(Validator::isValidUUIDv7($invalidVersionUuid))->toBeFalse();
});

test('invalid UUIDv7 with wrong variant', function (): void {
    // Invalid - variant bits are 00xx (0-3) instead of 10xx (8-b)
    $invalidVariantUuid = '01890a5d-ac91-7c80-1afe-a6b695cefa5d';

    expect(Validator::isValidUUIDv7($invalidVariantUuid))->toBeFalse();
});

test('encoding with valid UUIDv7', function (): void {
    $validUuid = '01890a5d-ac91-7c80-9afe-a6b695cefa5d';

    // Should not throw exception
    $encoded = Base32::encode($validUuid);
    expect($encoded)->toBeString();
    expect(strlen($encoded))->toEqual(26);
});

test('encoding with invalid UUIDv7 version', function (): void {
    $invalidUuid = '01890a5d-ac91-1c80-9afe-a6b695cefa5d'; // Version 1 instead of 7

    $this->expectException(\InvalidArgumentException::class);
    Base32::encode($invalidUuid);
});

test('encoding with invalid UUIDv7 variant', function (): void {
    $invalidUuid = '01890a5d-ac91-7c80-1afe-a6b695cefa5d'; // Invalid variant

    $this->expectException(\InvalidArgumentException::class);
    Base32::encode($invalidUuid);
});

test('decode and encode maintain UUIDv7 format', function (): void {
    $originalUuid = '01890a5d-ac91-7c80-9afe-a6b695cefa5d';

    $encoded = Base32::encode($originalUuid);
    $decoded = Base32::decode($encoded);

    expect($decoded)->toEqual($originalUuid);
    expect(Validator::isValidUUIDv7($decoded))->toBeTrue();
});

test('decode TypeID', function (string $typeId, string $uuid): void {

    expect(TypeID::fromString($typeId)->toUuid())->toEqual($uuid);

})->with([
    ['user_01jsns7byze78t2e8kcgkabxcq', '01966b93-afdf-71d1-a139-136426a5f597'],
    ['user_01jsnsf2g7e2saxdjvz3j6tc3x', '01966b97-8a07-70b2-aeb6-5bf8e46d307d'],
    ['user_01jsnsfk97e6fs9587z73nax2r', '01966b97-cd27-719f-9495-07f9c7557458'],
    ['01jsnsq5hnef5scmjw9x8h7sg6', '01966b9b-9635-73cb-9652-5c4f5113e606'],
    ['01jsnsqhhre86rd028q5hbv9vr', '01966b9b-c638-720d-8680-48b962bda778'],
    ['01jsnsr3fbe54rkjzfkta25nct', '01966b9c-0deb-7149-89cb-ef9e9422d59a'],
]);

test('encode TypeID', function (string $typeId, string $uuid, ?string $prefix = null): void {

    expect(TypeID::fromUuid($uuid, $prefix)->toString())->toEqual($typeId);

})->with([
    ['user_01jsns7byze78t2e8kcgkabxcq', '01966b93-afdf-71d1-a139-136426a5f597', 'user'],
    ['user_01jsnsf2g7e2saxdjvz3j6tc3x', '01966b97-8a07-70b2-aeb6-5bf8e46d307d', 'user'],
    ['user_01jsnsfk97e6fs9587z73nax2r', '01966b97-cd27-719f-9495-07f9c7557458', 'user'],
    ['01jsnsq5hnef5scmjw9x8h7sg6', '01966b9b-9635-73cb-9652-5c4f5113e606'],
    ['01jsnsqhhre86rd028q5hbv9vr', '01966b9b-c638-720d-8680-48b962bda778'],
    ['01jsnsr3fbe54rkjzfkta25nct', '01966b9c-0deb-7149-89cb-ef9e9422d59a'],
]);
