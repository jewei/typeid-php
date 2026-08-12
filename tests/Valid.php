<?php

declare(strict_types=1);

use TypeID\TypeID;

$validJson = file_get_contents(__DIR__.'/../spec/valid.json');

if ($validJson === false) {
    throw new \RuntimeException('Unable to read valid TypeID fixtures');
}

$validCases = json_decode($validJson, true, flags: JSON_THROW_ON_ERROR);

dataset('valid typeids', array_combine(
    array_column($validCases, 'name'),
    array_map(fn ($case) => [$case['typeid'], $case['prefix'], $case['uuid']], $validCases),
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
