<?php

declare(strict_types=1);

use TypeID\Exception\ValidationException;
use TypeID\TypeID;

$invalidJson = file_get_contents(__DIR__.'/../spec/invalid.json');

if ($invalidJson === false) {
    throw new \RuntimeException('Unable to read invalid TypeID fixtures');
}

$invalidCases = json_decode($invalidJson, true, flags: JSON_THROW_ON_ERROR);

dataset('invalid typeids', array_combine(
    array_column($invalidCases, 'name'),
    array_map(fn ($case) => [$case['typeid']], $invalidCases),
));

test('reject invalid typeids', function (string $typeid): void {
    expect(fn () => TypeID::fromString($typeid))->toThrow(ValidationException::class);
})->with('invalid typeids');
