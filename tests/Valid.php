<?php

declare(strict_types=1);

use TypeID\TypeID;

$validCases = json_decode(file_get_contents(__DIR__.'/../spec/valid.json'), true);

dataset('valid typeids', array_combine(
    array_column($validCases, 'name'),
    array_map(fn ($case) => [$case['typeid'], $case['prefix'], $case['uuid']], $validCases)
));

test('validate valid typeids', function (string $typeid, string $prefix, string $uuid): void {
    $tid = TypeID::fromString($typeid);
    expect($tid)->toBeInstanceOf(TypeID::class);
    expect((string) $tid)->toBe($typeid);
    expect($tid->prefix)->toBe($prefix);

    $tidFromUuid = TypeID::fromUuid($uuid, $prefix);
    expect((string) $tidFromUuid)->toBe($typeid);

    expect($tid->toUuid())->toBe($uuid);
})->with('valid typeids');
