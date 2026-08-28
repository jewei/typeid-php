<?php

declare(strict_types=1);

test('loads every vendored spec vector', function (): void {
    expect(validSpecVectors())->toHaveCount(9)
        ->and(invalidSpecVectors())->toHaveCount(21);
});

test('spec vector names are present and unique', function (): void {
    $names = array_column([...validSpecVectors(), ...invalidSpecVectors()], 'name');

    expect($names)->each->toBeString()->not->toBeEmpty()
        ->and(array_unique($names))->toHaveCount(30);
});

test('valid spec vectors have the required fields', function (): void {
    foreach (validSpecVectors() as $vector) {
        expect(array_keys($vector))->toBe(['name', 'typeid', 'prefix', 'uuid'])
            ->and($vector['name'])->toBeString()->not->toBeEmpty()
            ->and($vector['typeid'])->toBeString()->not->toBeEmpty()
            ->and($vector['prefix'])->toBeString()
            ->and($vector['uuid'])->toBeString()->not->toBeEmpty();
    }
});

test('invalid spec vectors have the required fields', function (): void {
    foreach (invalidSpecVectors() as $vector) {
        expect(array_keys($vector))->toBe(['name', 'typeid', 'description'])
            ->and($vector['name'])->toBeString()->not->toBeEmpty()
            ->and($vector['typeid'])->toBeString()
            ->and($vector['description'])->toBeString()->not->toBeEmpty();
    }
});

test('vendored spec files match their recorded provenance', function (): void {
    $provenance = specProvenance();

    expect($provenance['repository'])->toBe('https://github.com/jetify-com/typeid')
        ->and($provenance['commit'])->toBe('cb20c6eeb4bc6e5a115fceffcbb22f331d4033f8')
        ->and(array_keys($provenance['sha256']))->toBe([
            'spec/README.md',
            'spec/valid.yml',
            'spec/invalid.yml',
            'spec/valid.json',
            'spec/invalid.json',
        ]);

    foreach ($provenance['sha256'] as $file => $hash) {
        expect(hash_file('sha256', __DIR__.'/../../'.$file))->toBe($hash);
    }
});

test('dataset mapping rejects duplicate vector names instead of discarding them', function (): void {
    $vectors = [
        ['name' => 'duplicate', 'typeid' => 'first'],
        ['name' => 'duplicate', 'typeid' => 'second'],
    ];

    expect(fn (): array => specVectorDataset(
        $vectors,
        fn (array $vector): array => [$vector['typeid']],
    ))->toThrow(\RuntimeException::class, 'Duplicate spec vector name: duplicate');
});
