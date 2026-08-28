<p align="center">
  <a href="https://github.com/jewei/typeid-php/actions"><img src="https://github.com/jewei/typeid-php/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
  <a href="https://packagist.org/packages/jewei/typeid-php"><img src="https://img.shields.io/packagist/dt/jewei/typeid-php" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/jewei/typeid-php"><img src="https://img.shields.io/packagist/v/jewei/typeid-php" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/jewei/typeid-php"><img src="https://img.shields.io/packagist/l/jewei/typeid-php" alt="License"></a>
</p>

# TypeID PHP

A PHP 8.4 implementation of [TypeIDs](https://github.com/jetify-com/typeid): type-safe, K-sortable, globally-unique identifiers inspired by Stripe IDs.

TypeIDs extend UUIDv7 with a type prefix, giving you better ergonomics for database IDs, API resources, and distributed systems.

## Features

- **Type-safe** — prefix encodes the entity type, preventing ID mix-ups across types
- **K-sortable when generated** — generated IDs with the same prefix sort chronologically via UUIDv7
- **Compact** — 26-char Crockford base32 suffix vs 36 chars for a standard UUID string
- **URL-safe** — only `[a-z0-9_]` characters, no encoding needed
- **No math extensions** — base32 uses pure bit manipulation, with no GMP or bcmath requirement

## Requirements

- PHP 8.4+

## Installation

```bash
composer require jewei/typeid-php
```

## Usage

```php
use TypeID\TypeID;

// Generate a new K-sortable TypeID
$id = TypeID::generate('user');
echo $id;          // user_01jsnsf2g7e2saxdjvz3j6tc3x
echo $id->prefix;  // user
echo $id->suffix;  // 01jsnsf2g7e2saxdjvz3j6tc3x
echo $id->toUuid(); // 01966b97-8a07-70b2-aeb6-5bf8e46d307d

// Parse from a string
$id = TypeID::fromString('user_01jsnsf2g7e2saxdjvz3j6tc3x');
echo $id->prefix;  // user

// Encode an existing UUID
$id = TypeID::fromUuid('01966b97-8a07-70b2-aeb6-5bf8e46d307d', 'invoice');
echo $id; // invoice_01jsnsf2g7e2saxdjvz3j6tc3x

// Zero/nil TypeID — useful as a sentinel value
$zero = TypeID::zero('user');
echo $zero->isZero(); // true
echo $zero->isNonZero(); // false

// Equality check
$a = TypeID::fromString('user_01jsnsf2g7e2saxdjvz3j6tc3x');
$b = TypeID::fromString('user_01jsnsf2g7e2saxdjvz3j6tc3x');
echo $a->equals($b); // true

// Round-trip a UUID stored in a binary(16) database column
$uuidBytes = TypeID::generate('user')->bytes();
$binaryId = TypeID::fromBytes($uuidBytes, 'user');
$uuidBytes = $binaryId->bytes();

// Encodes as the canonical string in JSON
echo json_encode(['id' => $id]); // {"id":"invoice_01jsnsf2g7e2saxdjvz3j6tc3x"}

// Survives native serialize()/unserialize(), and revalidates on the way back
$restored = unserialize(serialize($id));

// The nil suffix constant, if you need to match on it directly
echo TypeID::ZERO_SUFFIX; // 00000000000000000000000000
```

### API

| Member | Returns | Notes |
| ------ | ------- | ----- |
| `TypeID::generate(?string $prefix)` | `TypeID` | Fresh UUIDv7. The only K-sortable constructor. |
| `TypeID::fromString(string $value)` | `TypeID` | Parses `prefix_suffix` or a bare suffix. |
| `TypeID::fromUuid(string $uuid, ?string $prefix)` | `TypeID` | Any valid UUID, not just v7. |
| `TypeID::fromBytes(string $bytes, ?string $prefix)` | `TypeID` | Exactly 16 raw bytes. |
| `TypeID::zero(?string $prefix)` | `TypeID` | Nil UUID sentinel. Not a generator — see below. |
| `->prefix`, `->suffix` | `string` | Readonly. |
| `->toString()`, `(string)`, `->jsonSerialize()` | `string` | All give the canonical form. |
| `->toUuid()` | `string` | Always lowercase and hyphenated. |
| `->bytes()` | `string` | 16 raw bytes. |
| `->isZero()`, `->isNonZero()` | `bool` | Nil-suffix checks. |
| `->equals(TypeID $other)` | `bool` | Prefix and suffix must both match. |
| `TypeID::ZERO_SUFFIX` | `string` | The 26-char nil suffix. |

To compare prefixes, read the property: `$id->prefix === 'user'`.

The package uses `ramsey/uuid` to generate standards-compliant UUIDv7 values. Encoding and decoding are implemented locally without optional math extensions.

`fromUuid()` also accepts valid non-v7 UUIDs for interoperability, in either the hyphenated or the bare 32-character hex form, in upper or lower case. Input is normalized: `toUuid()` always returns the lowercase hyphenated form, so a bare or uppercase input does not come back byte-identical.

`zero()` is a sentinel constructor, not a generator. The nil UUID has no version or variant bits, so a zero TypeID is deliberately not a UUIDv7 and is not K-sortable. The TypeID spec lists the nil suffix among its valid vectors. The same applies to non-v7 values passed to `fromUuid()`: they do not gain chronological ordering merely by being encoded as TypeIDs.

Caller-invalid input throws `TypeID\Exception\ValidationException`, which extends `InvalidArgumentException`. `TypeID\Exception\GenerationException` extends `RuntimeException` and is reserved for UUID generation failures. Both implement `TypeID\Exception\TypeIDException`, so `catch (TypeIDException $e)` catches everything this package throws.

## Supported surface

`TypeID` is the only supported entry point. Everything below carries a compatibility promise within a major version; anything not listed does not.

| Element | Status |
| --- | --- |
| `TypeID` factories and instance methods | Supported |
| `TypeID::$prefix`, `TypeID::$suffix`, `TypeID::ZERO_SUFFIX` | Supported |
| `new TypeID($prefix, $suffix)` | Supported — validates both arguments |
| `Stringable`, `JsonSerializable` behaviour | Supported |
| `TypeIDException`, `ValidationException`, `GenerationException` | Supported as a catch contract |
| Exception *message wording* | **Not** supported — may change at any time |
| Native `serialize()` / `unserialize()` | Supported for runtime round-tripping only |
| `TypeID\Base32`, `TypeID\Validator` | **Internal.** No promise; may change or disappear |

Two notes on the edges of that table:

**Serialization is not a storage format.** `serialize()` round-trips correctly within a process and within a major version, but the serialized representation is not guaranteed stable across versions. Persist `(string) $typeId` and rehydrate with `TypeID::fromString()` instead.

**Internal classes are internal by policy, not by language.** PHP has no package-private visibility for top-level classes, so `Base32` and `Validator` remain autoloadable. `composer test:architecture` enforces the boundary inside this repository; for consumers it is a documented promise, and calling them directly forfeits it.

Domain vocabulary — prefix, suffix, canonical suffix, zero, nil, spec vector — is defined in [CONTEXT.md](CONTEXT.md).

## Format

```
user_01jsnsf2g7e2saxdjvz3j6tc3x
^^^^  ^^^^^^^^^^^^^^^^^^^^^^^^^^
│     └─ 26-char Crockford base32 (encodes a 128-bit UUIDv7)
└─ prefix: lowercase entity type label (0–63 chars)
```

The prefix is separated from the suffix by `_`. When no prefix is used, the TypeID is just the bare 26-char suffix, with no leading separator. The last underscore is always the delimiter.

**Prefix rules** — must match `^([a-z]([a-z_]{0,61}[a-z])?)?$`:

- 0 to 63 characters. The empty prefix is valid.
- Lowercase ASCII letters `[a-z]` and underscore `_` only. **Digits and uppercase letters are rejected.**
- Must start and end with a letter. `_user` and `user_` are both invalid.
- Consecutive underscores are allowed: `my__type` is valid.

**Suffix rules** — must match `^[0-7][0123456789abcdefghjkmnpqrstvwxyz]{25}$`:

- Exactly 26 characters, lowercase Crockford base32.
- The alphabet excludes `i`, `l`, `o`, and `u`. No hyphens, no padding.
- **The first character must be `7` or less.** 26 base32 characters hold 130 bits, but a UUID is 128, so anything above `7zzzzzzzzzzzzzzzzzzzzzzzzz` would overflow and is rejected.

**Length** — a TypeID is between 26 characters (bare suffix) and 90 characters (63-char prefix + `_` + 26-char suffix).

This implementation is verified against the official conformance vectors in [`spec/valid.json`](spec/valid.json) and [`spec/invalid.json`](spec/invalid.json), which run as part of the test suite.

## Examples

| TypeID                                     | Prefix        | Suffix                     |
| ------------------------------------------ | ------------- | -------------------------- |
| `01jsnsf2g7e2saxdjvz3j6tc3x`               | _(none)_      | 01jsnsf2g7e2saxdjvz3j6tc3x |
| `user_01jsnsf2g7e2saxdjvz3j6tc3x`          | user          | 01jsnsf2g7e2saxdjvz3j6tc3x |
| `post_category_01jsnsf2g7e2saxdjvz3j6tc3x` | post_category | 01jsnsf2g7e2saxdjvz3j6tc3x |

## Testing

```bash
composer test              # architecture check, then the full suite
composer test:unit         # the suite alone
composer test:architecture # dependency allow-list only
```

Tests are organised in three layers, which is what keeps the supported surface honest:

| Layer | Exercises |
| --- | --- |
| `tests/Contract/` | The supported surface, through `TypeID` only |
| `tests/Spec/` | Conformance against the vendored [spec vectors](spec/) |
| `tests/Codec/` | Bit-boundary diagnostics, reaching `Base32` directly |

`tests/Codec/` is the single deliberate exception to the boundary. The codec is an unrolled 26-expression bit-shuffle, and a failure there should name the offending byte rather than report that some UUID failed to round-trip. `composer test:architecture` fails if any other test reaches past `TypeID`.
