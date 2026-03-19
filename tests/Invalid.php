<?php

declare(strict_types=1);

use TypeID\TypeID;

$invalidCases = json_decode(file_get_contents(__DIR__.'/../spec/invalid.json'), true);

dataset('invalid typeids', array_combine(
    array_column($invalidCases, 'name'),
    array_map(fn ($case) => [$case['typeid']], $invalidCases)
));

test('reject invalid typeids', function (string $typeid): void {
    expect(fn () => TypeID::fromString($typeid))->toThrow(\Exception::class);
})->with('invalid typeids');
