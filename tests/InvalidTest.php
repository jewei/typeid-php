<?php

declare(strict_types=1);

use TypeID\Exception\ValidationException;
use TypeID\TypeID;

test('reject invalid typeids', function (string $typeid): void {
    expect(fn () => TypeID::fromString($typeid))->toThrow(ValidationException::class);
})->with('invalid typeids');
