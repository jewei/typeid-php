<?php

declare(strict_types=1);

use TypeID\TypeID;

test('validate valid typeids', function (string $typeid, string $prefix, string $uuid): void {
    $tid = TypeID::fromString($typeid);
    expect($tid)->toBeInstanceOf(TypeID::class);
    expect((string) $tid)->toBe($typeid);
    expect($tid->prefix)->toBe($prefix);

    $tidFromUuid = TypeID::fromUuid($uuid, $prefix);
    expect((string) $tidFromUuid)->toBe($typeid);

    expect($tid->toUuid())->toBe($uuid);

    $bytes = hex2bin(str_replace('-', '', $uuid));

    if ($bytes === false) {
        throw new \RuntimeException("Invalid UUID in valid spec vector: {$uuid}");
    }

    expect((string) TypeID::fromBytes($bytes, $prefix))->toBe($typeid);
})->with('valid typeids');
