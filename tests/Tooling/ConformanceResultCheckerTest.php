<?php

declare(strict_types=1);

use TypeID\Development\ConformanceResultChecker;

require_once __DIR__.'/ConformanceResultChecker.php';

/** @return list<string> */
function conformanceValidVectorNames(): array
{
    return [
        'nil',
        'one',
        'ten',
        'sixteen',
        'thirty-two',
        'max-valid',
        'valid-alphabet',
        'valid-uuidv7',
        'prefix-underscore',
    ];
}

/** @return list<string> */
function conformanceInvalidVectorNames(): array
{
    return [
        'prefix-uppercase',
        'prefix-numeric',
        'prefix-period',
        'prefix-non-ascii',
        'prefix-spaces',
        'prefix-64-chars',
        'separator-empty-prefix',
        'separator-empty',
        'suffix-short',
        'suffix-long',
        'suffix-spaces',
        'suffix-uppercase',
        'suffix-hyphens',
        'suffix-wrong-alphabet',
        'suffix-ambiguous-crockford',
        'suffix-hyphens-crockford',
        'suffix-overflow',
        'prefix-underscore-start',
        'prefix-underscore-end',
        'empty',
        'prefix-empty',
    ];
}

/** @param list<string> $datasets */
function junitDatasetTestCases(string $class, string $test, array $datasets, string $status = ''): string
{
    $cases = '';

    foreach ($datasets as $dataset) {
        $name = htmlspecialchars(
            "{$test} with data set \"dataset \"{$dataset}\"\"",
            ENT_QUOTES | ENT_XML1,
        );
        $cases .= "<testcase classname=\"{$class}\" name=\"{$name}\">{$status}</testcase>";
    }

    return $cases;
}

/** @param list<string> $tests */
function junitNamedTestCases(string $class, array $tests): string
{
    $cases = '';

    foreach ($tests as $test) {
        $cases .= "<testcase classname=\"{$class}\" name=\"{$test}\"/>";
    }

    return $cases;
}

/**
 * @param  list<string>|null  $valid
 * @param  list<string>|null  $invalid
 */
function conformanceJunitXml(
    ?array $valid = null,
    ?array $invalid = null,
    string $invalidStatus = '',
    bool $integrity = true,
): string {
    $integrityTests = [
        'loads every vendored spec vector',
        'spec vector names are present and unique',
        'valid spec vectors have the required fields',
        'invalid spec vectors have the required fields',
        'vendored spec files match their recorded provenance',
        'dataset mapping rejects duplicate vector names instead of discarding them',
    ];

    return '<testsuites><testsuite>'
        .junitDatasetTestCases(
            'Tests.Spec.ValidTest',
            'validate valid typeids',
            $valid ?? conformanceValidVectorNames(),
        )
        .junitDatasetTestCases(
            'Tests.Spec.InvalidTest',
            'reject invalid typeids',
            $invalid ?? conformanceInvalidVectorNames(),
            $invalidStatus,
        )
        .($integrity ? junitNamedTestCases('Tests.Spec.FixtureIntegrityTest', $integrityTests) : '')
        .'</testsuite></testsuites>';
}

function assertConformanceExecution(string $junit): void
{
    ConformanceResultChecker::assertExpectedExecution(
        $junit,
        conformanceValidVectorNames(),
        conformanceInvalidVectorNames(),
    );
}

test('accepts a JUnit report containing every executed conformance test', function (): void {
    expect(fn () => assertConformanceExecution(conformanceJunitXml()))
        ->not->toThrow(\Throwable::class);
});

test('rejects a JUnit report with a missing vector', function (): void {
    $valid = conformanceValidVectorNames();
    array_pop($valid);

    expect(fn () => assertConformanceExecution(conformanceJunitXml(valid: $valid)))
        ->toThrow(
            \RuntimeException::class,
            'Expected vector did not execute exactly once: Tests.Spec.ValidTest dataset prefix-underscore',
        );
});

test('rejects duplicate execution that hides a missing vector', function (): void {
    $valid = conformanceValidVectorNames();
    $valid[1] = $valid[0];

    expect(fn () => assertConformanceExecution(conformanceJunitXml(valid: array_values($valid))))
        ->toThrow(
            \RuntimeException::class,
            'Expected vector did not execute exactly once: Tests.Spec.ValidTest dataset nil',
        );
});

test('rejects a test that merely starts with an expected name', function (): void {
    $valid = conformanceValidVectorNames();
    $valid[0] = 'nil placeholder';

    expect(fn () => assertConformanceExecution(conformanceJunitXml(valid: $valid)))
        ->toThrow(\RuntimeException::class, 'Unexpected test name for Tests.Spec.ValidTest');
});

test('rejects a JUnit report when the integrity tests do not execute', function (): void {
    expect(fn () => assertConformanceExecution(conformanceJunitXml(integrity: false)))
        ->toThrow(
            \RuntimeException::class,
            'Expected integrity test did not execute exactly once: loads every vendored spec vector',
        );
});

test('rejects skipped conformance tests', function (): void {
    expect(fn () => assertConformanceExecution(
        conformanceJunitXml(invalidStatus: '<skipped/>'),
    ))->toThrow(\RuntimeException::class, 'Conformance test did not pass: Tests.Spec.InvalidTest');
});
