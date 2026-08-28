<?php

declare(strict_types=1);

use TypeID\Exception\GenerationException;
use TypeID\Exception\TypeIDException;
use TypeID\Exception\ValidationException;
use TypeID\TypeID;

/**
 * Returns each rejecting public call, keyed by its failure case.
 *
 * @return array<string, callable(): mixed>
 */
function rejectingOperations(): array
{
    $long = "INVALID\n".str_repeat('x', 100);

    return [
        'invalid prefix' => fn () => new TypeID('INVALID', TypeID::ZERO_SUFFIX),
        'invalid suffix' => fn () => new TypeID('user', 'tooshort'),
        'invalid uuid' => fn () => TypeID::fromUuid('not-a-uuid'),
        'malformed string' => fn () => TypeID::fromString('invalid'),
        'wrong byte count' => fn () => TypeID::fromBytes('short'),
        'long prefix' => fn () => new TypeID($long, TypeID::ZERO_SUFFIX),
        'long suffix' => fn () => new TypeID('user', $long),
        'long uuid' => fn () => TypeID::fromUuid($long),
        'long string' => fn () => TypeID::fromString($long),
    ];
}

/** Pest treats an interface passed to toThrow() as message text. */
test('every rejecting operation is catchable as TypeIDException', function (): void {
    foreach (rejectingOperations() as $name => $operation) {
        try {
            $operation();
        } catch (TypeIDException) {
            continue;
        }

        throw new \RuntimeException("Expected a TypeIDException from: {$name}");
    }

    expect(true)->toBeTrue();
});

test('caller-invalid input is catchable as InvalidArgumentException', function (): void {
    expect(fn () => TypeID::fromString('invalid'))->toThrow(\InvalidArgumentException::class)
        ->and(fn () => new TypeID('INVALID', TypeID::ZERO_SUFFIX))->toThrow(\InvalidArgumentException::class);
});

test('ValidationException separates caller error from operational failure', function (): void {
    try {
        TypeID::fromString('invalid');
    } catch (ValidationException $validation) {
        $generation = new GenerationException('generation failed');

        expect($validation)->toBeInstanceOf(TypeIDException::class)
            ->and($validation)->toBeInstanceOf(\InvalidArgumentException::class)
            ->and($generation)->toBeInstanceOf(TypeIDException::class)
            ->and($generation)->toBeInstanceOf(\RuntimeException::class)
            ->and($generation)->not->toBeInstanceOf(\InvalidArgumentException::class);

        return;
    }

    throw new \RuntimeException('Expected a ValidationException');
});

test('ValidationException can only be created through a named constructor', function (): void {
    $constructor = (new \ReflectionClass(ValidationException::class))->getConstructor();

    expect($constructor)->not->toBeNull()
        ->and($constructor->isPrivate())->toBeTrue();
});

/** Validation messages contain bounded ASCII metadata, not rejected input. */
test('validation messages are bounded printable ASCII', function (): void {
    foreach (rejectingOperations() as $name => $operation) {
        try {
            $operation();
        } catch (ValidationException $exception) {
            expect($exception->getMessage())
                ->toMatch('/\A[\x20-\x7e]+\z/D', "unsafe message via: {$name}")
                ->and(strlen($exception->getMessage()))->toBeLessThan(120);

            continue;
        }

        throw new \RuntimeException("Expected a ValidationException from: {$name}");
    }
});

test('rejected byte sequences are not copied into validation messages', function (): void {
    foreach (range(0, 255) as $byte) {
        $value = 'INVALID'.chr($byte);

        try {
            new TypeID($value, TypeID::ZERO_SUFFIX);
        } catch (ValidationException $exception) {
            expect($exception->getMessage())->toMatch('/\A[\x20-\x7e]+\z/D')
                ->and($exception->getMessage())->not->toContain($value);

            continue;
        }

        throw new \RuntimeException("Expected byte {$byte} to make the prefix invalid");
    }
});

test('a large invalid string is rejected without copying it into the message', function (): void {
    $value = str_repeat("\0", 1024 * 1024);

    try {
        TypeID::fromString($value);
    } catch (ValidationException $exception) {
        expect(strlen($exception->getMessage()))->toBeLessThan(120)
            ->and($exception->getMessage())->not->toContain($value);

        return;
    }

    throw new \RuntimeException('Expected a ValidationException');
});
