# Comparison with keskinonur-typeid

## Scope

This compares `jewei/typeid-php` with reference commit `dc7243e` from `keskinonur-typeid`. The review covers the reference PHP core, its PHP framework packages, and repo-level CLI and release tooling.

This is not perfectly apples-to-apples. This repository is a focused PHP library. The reference repository also contains the upstream Go CLI and project documentation. Its PHP work is a single branch commit, and its framework packages currently have no tests.

PHP 8.3 support, ranked first below, was implemented after this review. The remaining opportunities are intentionally deferred.

## Bottom line

The current codebase has the stronger core library. It has stricter validation, safer exceptions, better generation failure handling, a clearer compatibility policy, stronger CI, and a larger effective test suite. The full `composer test` command passes.

The reference is better at reach and packaging. It supports older PHP versions, keeps decoded bytes in the value object, sketches integrations for four PHP ecosystems, exposes an interface for adapters, and includes an operational CLI. Those are the ideas worth considering.

## Ranked opportunities

| Rank | Opportunity | Expected value | Effort | Recommendation |
| --- | --- | --- | --- | --- |
| 1 | Support more PHP versions | Very high | Medium | Implemented for PHP 8.3. PHP 8.2 remains deferred. |
| 2 | Cache decoded UUID bytes | High for database and conversion-heavy use | Medium | Prototype and benchmark with realistic parse/read ratios. |
| 3 | Publish tested framework adapters | High | High | Start with Laravel, then Symfony if demand exists. Keep each adapter in a separate package. |
| 4 | Add a small CLI | Medium-high | Medium | Ship `new`, `decode`, and `encode` as a Composer binary. |
| 5 | Add a public interface when adapters need it | Medium | Medium | Introduce it with the first adapter, not as an isolated abstraction. |
| 6 | Improve contributor and release operations | Medium-low | Low to medium | Add project-specific contribution docs, templates, and a tagged release workflow. |

### 1. Support more PHP versions

The reference core declares PHP 8.1 or newer in `php/typeid/composer.json:8` and includes a Docker runner for PHP 8.1 through 8.4 in `php/test-all-versions.sh:7`. At review time, this package required PHP 8.4 and tested 8.4 and 8.5. It now requires PHP 8.3 and tests PHP 8.3 with both lowest and highest dependencies, plus PHP 8.4 and 8.5.

The narrow runtime requirement was probably the largest adoption constraint in this repository. The Ramsey UUID dependency itself supports PHP 8.0 or newer. Typed class constants and `#[Override]` make PHP 8.3 the natural floor for the current production code. The development constraint now permits Pest 4 on PHP 8.3 and Pest 5 on newer runtimes.

The implementation lowers the production target to PHP 8.3, adds lowest and highest dependency jobs on PHP 8.3, and retains PHP 8.4 and 8.5 jobs. PHP 8.2 remains deferred because it would require source and test-tooling changes. PHP 8.1 is not a sensible target solely because the reference supports it.

This adopts the reference's reach without copying its exact support policy.

### 2. Cache decoded UUID bytes

The reference stores `$uuidBytes` beside the prefix and suffix in `php/typeid/src/TypeId.php:17-20`. Its `bytes()` method returns the cached value. This package stores only prefix and suffix, so every `bytes()` and `toUuid()` call decodes base32 again.

A local directional microbenchmark on PHP 8.5, using 500,000 calls per run, produced these median results:

| Operation | Current | Reference |
| --- | ---: | ---: |
| `bytes()` | 1.885 µs | 0.022 µs |
| UUID string conversion | 2.084 µs | 0.269 µs |
| Parse canonical string | 0.517 µs | 3.602 µs |

The reference makes parsing slower because it decodes immediately, but repeated byte and UUID reads become much cheaper. The current implementation wins when callers parse and only render the TypeID string. Caching can win after a small number of UUID or byte reads. That matters because the README explicitly supports `binary(16)` database storage.

A prototype should add a private immutable byte cache, initialize it in every factory and `__unserialize()`, and keep the serialized form limited to prefix and suffix. Benchmark object creation, memory use, one-read requests, ORM hydration, and repeated conversions before committing. This is a better option than adding a second unchecked Base32 decode method because it does not widen the codec API.

### 3. Publish tested framework adapters

The reference separates integrations into their own Composer packages:

- Laravel cast, validation rule, model trait, route binding, and query scopes under `php/laravel/`
- Symfony Doctrine type, serializer normalizer, and validator under `php/symfony/`
- FlightPHP helpers under `php/flightphp/`
- WordPress and WooCommerce helpers under `php/wordpress/`

The package split is the good part. Consumers install framework dependencies only when they need them, while the core stays small. Laravel is the best first candidate because the cast, route binding, model generation, and validation rule remove recurring application code. Symfony would be the next useful adapter because Doctrine and Serializer integration otherwise gets rebuilt in each application.

Do not copy the reference adapter code as-is. None of the four adapter packages has tests. There are also concrete gaps:

- `TypeIdCast` accepts a prefix in its constructor but never checks it in `get()` or `set()`.
- `TypeIdCast::set()` silently returns `null` for unsupported values.
- Symfony's Doctrine conversion accepts arbitrary strings on the database write path without parsing them.
- WordPress helpers interpolate table and column identifiers into SQL and need a stricter identifier policy.
- The Laravel service provider has empty `register()` and `boot()` methods.

Build one narrow adapter, test it against supported framework versions, and only then add another.

### 4. Add a small CLI

The reference repository exposes three useful commands in `cli/`:

- `typeid new [prefix]`
- `typeid decode <typeid>`
- `typeid encode [prefix] <uuid>`

Its release setup builds cross-platform binaries and publishes checksums through `.goreleaser.yaml` and `.github/workflows/release.yml`. That CLI uses the Go implementation, so it cannot be copied into this package. It still shows the product value. A CLI helps with database migrations, shell scripts, support work, and manual inspection.

For this repository, a Composer binary is enough. It can call the existing public `TypeID` API and avoid a second implementation. Keep stdout machine-friendly, errors on stderr, and non-zero exit codes for invalid input. Add end-to-end command tests before documenting it.

### 5. Add a public interface when adapters need it

The reference defines `TypeIdInterface` and types its adapters against it in `php/typeid/src/TypeIdInterface.php:7`. This lets framework code depend on behavior rather than the final class and gives applications a substitution point for wrappers and test doubles.

The current repository deliberately promises one concrete production module. That simplicity is valuable. An interface only pays for itself when another package consumes it. Add it with the first adapter and include only stable value behavior such as string, UUID, byte, prefix, suffix, equality, and zero checks. Keep `TypeID` final.

This needs a compatibility review. In particular, widening `equals(TypeID $other)` to an interface changes the public contract, and every method placed on the interface becomes a long-term promise.

### 6. Improve contributor and release operations

The reference has `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, a pull request template, tagged release automation, reproducible binary settings, and checksums. This repository has strong engineering documentation but no contributor guide, issue or pull request templates, security policy, or release workflow.

Add repository-specific instructions for running tests, style checks, architecture checks, and updating vendored spec vectors. A release workflow should validate the tag, run the full matrix, and create release notes. Do not copy the reference contribution text because it points contributors to Jetify's monorepo and does not apply here.

## Useful idea, but not a current priority

The reference core has no runtime dependencies. This package requires `ramsey/uuid`. A smaller dependency graph is attractive, but the reference achieves it with a hand-written UUIDv7 generator that does not provide Ramsey's within-process monotonic ordering. Its UUID parser also strips hyphens before validation, so misplaced hyphens can be accepted.

Keeping Ramsey is the safer trade for now. Revisit this only if dependency conflicts or install size show up in real consumer reports. A generation strategy interface or a codec-only companion package would be safer than replacing Ramsey with local UUID code.

## What not to copy

Several reference choices are weaker than the current implementation:

- Its generated UUIDv7 values are random within the same millisecond, while the current default generator is strictly increasing within one process.
- Its `isZero()` requires both an empty prefix and a zero suffix. The current package correctly treats a prefixed zero TypeID as zero while retaining prefix-aware equality.
- Its UUID parser accepts noncanonical hyphen placement after stripping all hyphens.
- Its exceptions include rejected values in some messages. The current bounded-message policy is safer for logs and untrusted input.
- Its spec tests use absolute `/spec/*.json` paths and need the Docker mount. A normal Composer test run outside that container reports data-provider errors.
- The PHP framework packages have no tests or PHP CI workflow.

## Recommended sequence

1. Decide the PHP support floor and add the matrix before the next major release.
2. Prototype byte caching behind benchmarks. Keep it only if application-shaped tests confirm the microbenchmark.
3. Build a tested Laravel adapter as a separate package. Add the interface as part of that work if the adapter needs it.
4. Add the Composer CLI.
5. Add contributor and release files once the package and adapter release process is settled.

That sequence takes the reference's strongest ideas while preserving the current library's better correctness and maintenance discipline.
