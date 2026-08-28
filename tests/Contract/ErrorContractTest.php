<?php

declare(strict_types=1);

use TypeID\Exception\GenerationException;
use TypeID\Exception\TypeIDException;
use TypeID\Exception\ValidationException;
use TypeID\TypeID;

/**
 * Every rejecting public operation, keyed by the failure it provokes.
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

/**
 * TypeIDException is an interface, so it needs a catch rather than toThrow(),
 * which reads a non-class string as an expected message.
 */
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
    $validation = new ValidationException('invalid');
    $generation = new GenerationException('generation failed');

    expect($validation)->toBeInstanceOf(TypeIDException::class)
        ->and($validation)->toBeInstanceOf(\InvalidArgumentException::class)
        ->and($generation)->toBeInstanceOf(TypeIDException::class)
        ->and($generation)->toBeInstanceOf(\RuntimeException::class)
        ->and($generation)->not->toBeInstanceOf(\InvalidArgumentException::class);
});

/**
 * Rejected input is echoed back in the message, so it must be escaped and
 * capped. Exact wording is deliberately not asserted: it is not contractual.
 */
test('rejected input is escaped and truncated in every message', function (): void {
    foreach (rejectingOperations() as $name => $operation) {
        try {
            $operation();
        } catch (ValidationException $exception) {
            $message = $exception->getMessage();

            expect($message)->not->toContain("\n", "newline leaked via: {$name}")
                ->and($message)->not->toContain("\0")
                ->and(strlen($message))->toBeLessThan(120);

            continue;
        }

        throw new \RuntimeException("Expected a ValidationException from: {$name}");
    }
});

test('control characters never reach the message unescaped', function (): void {
    try {
        new TypeID("user\0\x1b[31m", TypeID::ZERO_SUFFIX);
    } catch (ValidationException $exception) {
        expect($exception->getMessage())->not->toContain("\0")
            ->and($exception->getMessage())->not->toContain("\x1b");

        return;
    }

    throw new \RuntimeException('Expected a ValidationException');
});
