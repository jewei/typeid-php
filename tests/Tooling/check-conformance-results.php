<?php

declare(strict_types=1);

use TypeID\Development\ConformanceResultChecker;

require_once __DIR__.'/ConformanceResultChecker.php';

/** @return list<string> */
function loadConformanceVectorNames(string $file): array
{
    $json = file_get_contents(__DIR__.'/../../spec/'.$file);

    if ($json === false) {
        throw new \RuntimeException("Unable to read spec vectors: {$file}");
    }

    $vectors = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($vectors) || ! array_is_list($vectors)) {
        throw new \RuntimeException("Spec vectors must be a JSON list: {$file}");
    }

    $names = [];

    foreach ($vectors as $vector) {
        $name = is_array($vector) ? ($vector['name'] ?? null) : null;

        if (! is_string($name) || $name === '' || in_array($name, $names, true)) {
            throw new \RuntimeException("Spec vector names must be nonempty and unique: {$file}");
        }

        $names[] = $name;
    }

    return $names;
}

function checkConformanceResults(mixed $arguments): int
{
    if (! is_array($arguments)
        || ! array_is_list($arguments)
        || count($arguments) !== 2
        || ! is_string($arguments[0])
        || ! is_string($arguments[1])
    ) {
        fwrite(STDERR, "Usage: php tests/Tooling/check-conformance-results.php <junit.xml>\n");

        return 2;
    }

    $junit = file_get_contents($arguments[1]);

    if ($junit === false) {
        fwrite(STDERR, "Unable to read JUnit report: {$arguments[1]}\n");

        return 1;
    }

    try {
        ConformanceResultChecker::assertExpectedExecution(
            $junit,
            loadConformanceVectorNames('valid.json'),
            loadConformanceVectorNames('invalid.json'),
        );
    } catch (\Throwable $exception) {
        fwrite(STDERR, "Conformance result check failed: {$exception->getMessage()}\n");

        return 1;
    }

    echo "Conformance result check passed (30 spec vectors and 6 integrity tests executed).\n";

    return 0;
}

exit(checkConformanceResults($_SERVER['argv'] ?? []));
