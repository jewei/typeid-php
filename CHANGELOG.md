# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] — 3.0.0

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
