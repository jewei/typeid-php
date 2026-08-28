<?php

declare(strict_types=1);

/**
 * Loads a spec file as a Pest dataset keyed by case name.
 *
 * @param  callable(array<string, string>): array<int, string>  $toArguments
 * @return array<string, array<int, string>>
 */
function loadSpecVectors(string $file, callable $toArguments): array
{
    $json = file_get_contents(__DIR__.'/../spec/'.$file);

    if ($json === false) {
        throw new \RuntimeException("Unable to read spec fixtures: {$file}");
    }

    $cases = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    return array_combine(
        array_column($cases, 'name'),
        array_map($toArguments, $cases),
    );
}

dataset('valid typeids', loadSpecVectors(
    'valid.json',
    fn (array $case): array => [$case['typeid'], $case['prefix'], $case['uuid']],
));

dataset('invalid typeids', loadSpecVectors(
    'invalid.json',
    fn (array $case): array => [$case['typeid']],
));
