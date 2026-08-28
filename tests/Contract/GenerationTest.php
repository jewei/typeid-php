<?php

declare(strict_types=1);

use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use TypeID\Exception\GenerationException;
use TypeID\Exception\ValidationException;
use TypeID\TypeID;

/**
 * @template T
 *
 * @param  \Closure(): T  $run
 * @return T
 */
function withTemporaryUuidFactory(UuidFactoryInterface $factory, \Closure $run): mixed
{
    $original = Uuid::getFactory();

    try {
        Uuid::setFactory($factory);

        return $run();
    } finally {
        Uuid::setFactory($original);
    }
}

test('generate validates the prefix before calling the UUID factory', function (): void {
    $factory = new class extends UuidFactory
    {
        public bool $called = false;

        public function uuid7(?\DateTimeInterface $dateTime = null): UuidInterface
        {
            $this->called = true;

            throw new \RuntimeException('the factory must not be called');
        }
    };

    withTemporaryUuidFactory($factory, function () use ($factory): void {
        expect(fn () => TypeID::generate('INVALID'))->toThrow(ValidationException::class)
            ->and($factory->called)->toBeFalse();
    });
});

test('generate wraps ordinary UUID factory exceptions without copying their message', function (): void {
    $failure = new \RuntimeException("sensitive factory failure\n");
    $factory = new class($failure) extends UuidFactory
    {
        public function __construct(private readonly \RuntimeException $failure) {}

        public function uuid7(?\DateTimeInterface $dateTime = null): UuidInterface
        {
            throw $this->failure;
        }
    };

    withTemporaryUuidFactory($factory, function () use ($failure): void {
        try {
            TypeID::generate('event');
        } catch (GenerationException $exception) {
            expect($exception->getPrevious())->toBe($failure)
                ->and($exception->getMessage())->not->toContain($failure->getMessage())
                ->and($exception->getMessage())->not->toContain("\n");

            return;
        }

        throw new \RuntimeException('Expected a GenerationException');
    });
});

test('generate wraps invalid UUID factory return types', function (): void {
    $factory = new class extends UuidFactory
    {
        public function uuid7(?\DateTimeInterface $dateTime = null): UuidInterface
        {
            // @phpstan-ignore return.type (deliberately violate the dependency contract)
            return null;
        }
    };

    withTemporaryUuidFactory($factory, function (): void {
        try {
            TypeID::generate('event');
        } catch (GenerationException $exception) {
            expect($exception->getPrevious())->toBeInstanceOf(\TypeError::class);

            return;
        }

        throw new \RuntimeException('Expected a GenerationException');
    });
});

test('generate normalizes valid UUIDv7 values from a GUID codec', function (): void {
    $guidFactory = new UuidFactory(new FeatureSet(true));
    $guid = $guidFactory->fromString('01966b97-8a07-70b2-aeb6-5bf8e46d307d');
    $factory = new class($guid) extends UuidFactory
    {
        public function __construct(private readonly UuidInterface $uuid) {}

        public function uuid7(?\DateTimeInterface $dateTime = null): UuidInterface
        {
            return $this->uuid;
        }
    };

    withTemporaryUuidFactory($factory, function (): void {
        expect(TypeID::generate('event')->toUuid())->toBe('01966b97-8a07-70b2-aeb6-5bf8e46d307d');
    });
});

test('generate rejects a non-v7 UUID returned by a custom factory', function (): void {
    $uuid4 = Uuid::fromString('f47ac10b-58cc-4372-a567-0e02b2c3d479');
    $factory = new class($uuid4) extends UuidFactory
    {
        public function __construct(private readonly UuidInterface $uuid) {}

        public function uuid7(?\DateTimeInterface $dateTime = null): UuidInterface
        {
            return $this->uuid;
        }
    };

    withTemporaryUuidFactory($factory, function (): void {
        expect(fn () => TypeID::generate('event'))->toThrow(GenerationException::class);
    });
});
