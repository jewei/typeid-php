# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] — 4.0.0

### Removed

- **BREAKING** — `TypeID\Validator` is deleted, along with `isValidPrefix()`,
  `isValidSuffix()`, `isValidUuid()`, the four `assertValid*()` guards, and
  `parseTypeID()`. It was marked `@internal` and documented as unsupported, but
  removal is observable: code that ignored the marker will fatal. Catch
  `ValidationException` from the relevant `TypeID` factory instead of asking a
  predicate first. See [ADR-0001](docs/adr/0001-validation-and-codec-ownership.md).
- **BREAKING** — `Base32::encode()` and `Base32::decode()`, which took and
  returned UUID *strings*. The codec is now byte-only. `Base32` was already
  `@internal`; use `TypeID::fromUuid()` and `TypeID::toUuid()`.

### Changed

- Each validation rule now lives with the concept it defines: the prefix grammar
  and UUID text handling on `TypeID`, suffix canonicality on `Base32`, message
  rendering on `ValidationException`. `Validator` had eight public methods over
  three regexes, expressed one suffix rule under three names, and had exactly
  one production caller per rule — so it concentrated nothing.
- `TypeID::fromString()` owns the whole parse rather than splitting the string
  in one module and validating the parts in another.
- `TypeID::__unserialize()` calls the validation guards directly instead of
  constructing and discarding a throwaway instance. Validate-before-assign is
  preserved.

### Added

- `CONTEXT.md` — the domain glossary, anchored to TypeID specification 0.3.0.
  Defines *canonical suffix* (so the 128-bit overflow rule has a name) and
  separates *nil UUID* from *zero TypeID*.
- `docs/adr/` — [ADR-0001](docs/adr/0001-validation-and-codec-ownership.md) on
  validation and codec ownership, and
  [ADR-0002](docs/adr/0002-read-paths-keep-revalidating.md) recording a measured
  decision *not* to remove redundant read-side validation.
- `composer test:architecture` — enforces the supported-surface dependency
  allow-list in CI. PHP has no package-private visibility for top-level
  classes, so the boundary is policy plus a check rather than a language
  guarantee.
- Named constructors on `ValidationException` (`invalidPrefix()`,
  `invalidSuffix()`, `invalidUuid()`, `invalidCodecInput()`,
  `invalidByteCount()`, `malformedString()`, `malformedPayload()`,
  `unreadableBytes()`). They are `@internal`; the exception class stays
  supported. Escaping of rejected input is now private to the exception and
  cannot be skipped at a call site.

### Documentation

- The README documents the supported surface as a table, and records two
  previously-implicit decisions: the public constructor **is** supported, and
  `serialize()` is supported for runtime round-tripping only — the serialized
  form is not a durable cross-version storage format. Persist
  `(string) $typeId` instead.
- Exception *message wording* is explicitly outside the compatibility contract.

### Internal

- Tests are reorganised into three layers: `tests/Contract/` (through `TypeID`
  only), `tests/Spec/` (vendored conformance vectors), and `tests/Codec/` (the
  single deliberate exception, for bit-boundary diagnostics). 34 test call
  sites previously reached past the seam into `Base32` and `Validator`, which
  made the effective test surface the implementation layout.

## [3.0.0]

### Removed

- **BREAKING** — `TypeID::hasPrefix()`. It only wrapped a comparison against a
  public readonly property. Replace `$id->hasPrefix('user')` with
  `$id->prefix === 'user'`.

### Changed

- **BREAKING** — `TypeID\Exception\ConstructorException` is renamed to
  `TypeID\Exception\GenerationException`. The old name said "constructor", but
  the constructor never threw it: only `TypeID::generate()` does, when UUIDv7
  generation fails. Update any `catch (ConstructorException $e)` block. Code
  that catches `TypeIDException` or `RuntimeException` is unaffected.

### Fixed

- The official TypeID conformance vectors now actually run. `phpunit.xml`
  declared the `tests` directory with no `suffix`, so PHPUnit applied its
  default `Test.php` and silently skipped `tests/Valid.php` and
  `tests/Invalid.php`. Both CI jobs, including the `--min=90` coverage gate,
  missed all 30 vectors. The files are renamed to `ValidTest.php` and
  `InvalidTest.php`; the suite goes from 127 to 157 tests.

### Added

- `Validator::assertValidPrefix()`, `assertValidSuffix()`, `assertValidBase32()`,
  and `assertValidUuid()` — throwing guards that replace four copies of the same
  validate-then-throw block in `TypeID` and `Base32`.

### Documentation

- The README now states the full prefix grammar (start and end with `[a-z]`, no
  digits, no uppercase, consecutive underscores allowed), the suffix rules
  including the first-character `≤ 7` overflow bound, and the 26–90 character
  length range. It previously described the prefix only as a "lowercase entity
  type label (0–63 chars)", which implied `user1` and `_user` were valid.
- Added an API table covering members the README had never documented:
  `jsonSerialize()`, the `__serialize()`/`__unserialize()` round trip, and
  `TypeID::ZERO_SUFFIX`.
- Documented that `toUuid()` always returns the lowercase hyphenated form, so a
  bare 32-character or uppercase input to `fromUuid()` does not return
  byte-identical.
- Clarified that `zero()` is a sentinel constructor, not a generator: the nil
  UUID carries no version or variant bits and is not K-sortable. The spec lists
  the nil suffix among its valid vectors, so this is conformant.
- Recorded in `Base32` why the encoder and decoder stay unrolled rather than
  looping over the repeating 8-char / 5-byte pattern.

### Internal

- `Validator::formatForMessage()` is now private. It renders messages rather
  than validating, and no longer needs to be part of the public surface.
- Shared spec-fixture loading moved to `tests/Pest.php`, replacing the
  duplicated read / decode / `dataset()` block in the two fixture files.
- Renamed the single-letter locals in `Base32` (`$a`, `$b`, `$m`, `$v`) to
  `$alphabet`, `$octets`, `$map`, and `$values`.

## [2.0.0]

### Removed

- **BREAKING** — `TypeID::hasSuffix()`, deprecated in 1.1.6. Every TypeID has a
  suffix, so the name was misleading. Use `isNonZero()`.

### Changed

- Unified validation boundaries and API contracts across `TypeID`, `Base32`, and
  `Validator`.

## [1.1.6]

### Deprecated

- `TypeID::hasSuffix()`. Use `isNonZero()` instead.

### Fixed

- Hardened TypeID validation and API contracts.

## [1.1.5]

### Added

- `JsonSerializable` support, `fromBytes()`, and `bytes()`.

---

Releases before 1.1.5 predate this file. See the
[commit history](https://github.com/jewei/typeid-php/commits/main) for details.
