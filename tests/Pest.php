<?php

declare(strict_types=1);

/**
 * Loads spec vectors from a vendored JSON file.
 *
 * @return list<array<string, mixed>>
 */
function loadSpecVectors(string $file): array
{
    $json = file_get_contents(__DIR__.'/../spec/'.$file);

    if ($json === false) {
        throw new \RuntimeException("Unable to read spec vectors: {$file}");
    }

    $vectors = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($vectors) || ! array_is_list($vectors)) {
        throw new \RuntimeException("Spec vectors must be a JSON list: {$file}");
    }

    return $vectors;
}

/** @return list<array<string, mixed>> */
function validSpecVectors(): array
{
    return loadSpecVectors('valid.json');
}

/** @return list<array<string, mixed>> */
function invalidSpecVectors(): array
{
    return loadSpecVectors('invalid.json');
}

/** @return array{repository: string, commit: string, sha256: array<string, string>} */
function specProvenance(): array
{
    $json = file_get_contents(__DIR__.'/../spec/provenance.json');

    if ($json === false) {
        throw new \RuntimeException('Unable to read spec provenance');
    }

    $provenance = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($provenance)
        || ! is_string($provenance['repository'] ?? null)
        || ! is_string($provenance['commit'] ?? null)
        || ! is_array($provenance['sha256'] ?? null)
    ) {
        throw new \RuntimeException('Spec provenance has an invalid schema');
    }

    return $provenance;
}

/**
 * Maps spec vectors to a Pest dataset keyed by vector name.
 *
 * @param  list<array<string, mixed>>  $vectors
 * @param  callable(array<string, mixed>): array<int, string>  $toArguments
 * @return array<string, array<int, string>>
 */
function specVectorDataset(array $vectors, callable $toArguments): array
{
    $dataset = [];

    foreach ($vectors as $vector) {
        $name = $vector['name'] ?? null;

        if (! is_string($name) || $name === '') {
            throw new \RuntimeException('Every spec vector must have a nonempty name');
        }

        if (array_key_exists($name, $dataset)) {
            throw new \RuntimeException("Duplicate spec vector name: {$name}");
        }

        $dataset[$name] = $toArguments($vector);
    }

    return $dataset;
}

dataset('valid typeids', specVectorDataset(
    validSpecVectors(),
    fn (array $vector): array => [$vector['typeid'], $vector['prefix'], $vector['uuid']],
));

dataset('invalid typeids', specVectorDataset(
    invalidSpecVectors(),
    fn (array $vector): array => [$vector['typeid']],
));
