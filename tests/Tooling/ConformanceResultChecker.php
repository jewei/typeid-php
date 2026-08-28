<?php

declare(strict_types=1);

namespace TypeID\Development;

use DOMDocument;
use DOMElement;
use RuntimeException;

final class ConformanceResultChecker
{
    /** @var array<string, string> */
    private const array VECTOR_TESTS = [
        'Tests.Spec.ValidTest' => 'validate valid typeids',
        'Tests.Spec.InvalidTest' => 'reject invalid typeids',
    ];

    /** @var list<string> */
    private const array INTEGRITY_TESTS = [
        'loads every vendored spec vector',
        'spec vector names are present and unique',
        'valid spec vectors have the required fields',
        'invalid spec vectors have the required fields',
        'vendored spec files match their recorded provenance',
        'dataset mapping rejects duplicate vector names instead of discarding them',
    ];

    /**
     * @param  list<string>  $validVectorNames
     * @param  list<string>  $invalidVectorNames
     */
    public static function assertExpectedExecution(
        string $junit,
        array $validVectorNames,
        array $invalidVectorNames,
    ): void {
        $xml = self::parse($junit);
        $expectedVectors = [
            'Tests.Spec.ValidTest' => array_fill_keys($validVectorNames, 0),
            'Tests.Spec.InvalidTest' => array_fill_keys($invalidVectorNames, 0),
        ];
        $executedIntegrityTests = array_fill_keys(self::INTEGRITY_TESTS, 0);

        foreach ($xml->getElementsByTagName('testcase') as $testCase) {
            $class = $testCase->getAttribute('classname');

            if (isset(self::VECTOR_TESTS[$class])) {
                self::assertPassed($testCase, $class);
                $dataset = self::datasetName(
                    $testCase->getAttribute('name'),
                    self::VECTOR_TESTS[$class],
                    array_keys($expectedVectors[$class]),
                );

                if ($dataset === null) {
                    throw new RuntimeException("Unexpected test name for {$class}: {$testCase->getAttribute('name')}");
                }

                $expectedVectors[$class][$dataset]++;

                continue;
            }

            if ($class === 'Tests.Spec.FixtureIntegrityTest') {
                self::assertPassed($testCase, $class);
                $name = $testCase->getAttribute('name');

                if (! isset($executedIntegrityTests[$name])) {
                    throw new RuntimeException("Unexpected test name for {$class}: {$name}");
                }

                $executedIntegrityTests[$name]++;
            }
        }

        foreach ($expectedVectors as $class => $datasets) {
            foreach ($datasets as $dataset => $count) {
                if ($count !== 1) {
                    throw new RuntimeException(
                        "Expected vector did not execute exactly once: {$class} dataset {$dataset}",
                    );
                }
            }
        }

        foreach ($executedIntegrityTests as $test => $count) {
            if ($count !== 1) {
                throw new RuntimeException(
                    "Expected integrity test did not execute exactly once: {$test}",
                );
            }
        }
    }

    private static function parse(string $junit): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = new DOMDocument;
            $parsed = $xml->loadXML($junit, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $parsed) {
            throw new RuntimeException('Unable to parse the JUnit report');
        }

        return $xml;
    }

    /**
     * @param  list<string>  $expectedDatasets
     */
    private static function datasetName(
        string $junitName,
        string $testName,
        array $expectedDatasets,
    ): ?string {
        foreach ($expectedDatasets as $dataset) {
            if (in_array($junitName, [
                "{$testName} with data set \"dataset \"{$dataset}\"\"",
                "{$testName} with data set \"{$dataset}\"",
            ], true)) {
                return $dataset;
            }
        }

        return null;
    }

    private static function assertPassed(DOMElement $testCase, string $class): void
    {
        foreach (['failure', 'error', 'skipped'] as $status) {
            if ($testCase->getElementsByTagName($status)->length > 0) {
                throw new RuntimeException("Conformance test did not pass: {$class}");
            }
        }
    }
}
