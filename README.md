<p align="center">
  <a href="https://github.com/jewei/typeid-php/actions"><img src="https://github.com/jewei/typeid-php/actions/workflows/tests.yml/badge.svg" alt="Build status"></a>
  <a href="https://packagist.org/packages/jewei/typeid-php"><img src="https://img.shields.io/packagist/dt/jewei/typeid-php" alt="Total downloads"></a>
  <a href="https://packagist.org/packages/jewei/typeid-php"><img src="https://img.shields.io/packagist/v/jewei/typeid-php" alt="Latest stable version"></a>
  <a href="https://packagist.org/packages/jewei/typeid-php"><img src="https://img.shields.io/packagist/l/jewei/typeid-php" alt="License"></a>
</p>

# TypeID PHP

TypeID PHP implements [TypeID specification 0.3.0](https://github.com/jetify-com/typeid/tree/main/spec) for PHP `^8.3`.

A TypeID joins a type prefix, such as `user` or `invoice`, to a UUID encoded as a 26-character base32 suffix:

```text
user_01jsnsf2g7e2saxdjvz3j6tc3x
```

Generated TypeIDs use UUIDv7. TypeIDs with the same type prefix sort by their millisecond timestamps. Ramsey's default UUIDv7 generator also orders successive values within one PHP process. Separate processes and hosts do not share that ordering state.

The package has these properties:

- The type prefix remains visible in strings, logs, and URLs.
- A suffix uses 26 characters instead of the 36 characters in a hyphenated UUID.
- Canonical strings contain only lowercase ASCII letters, digits, and underscores.
- The codec uses PHP bit operations and does not require the GMP or BCMath extensions.

A UUIDv7 TypeID exposes its millisecond timestamp. Do not use a TypeID as a secret or access token.

## Install

```bash
composer require jewei/typeid-php
```

## Create and parse TypeIDs

Generate a TypeID with a `user` type prefix:

```php
use TypeID\TypeID;

$id = TypeID::generate('user');

echo $id;           // For example: user_01jsnsf2g7e2saxdjvz3j6tc3x
echo $id->prefix;   // user
echo $id->suffix;   // 01jsnsf2g7e2saxdjvz3j6tc3x
echo $id->toUuid(); // 01966b97-8a07-70b2-aeb6-5bf8e46d307d
```

Parse a canonical TypeID string or a bare suffix:

```php
$id = TypeID::fromString('user_01jsnsf2g7e2saxdjvz3j6tc3x');
$bare = TypeID::fromString('01jsnsf2g7e2saxdjvz3j6tc3x');
```

Convert an existing UUID of any version:

```php
$id = TypeID::fromUuid(
	'01966b97-8a07-70b2-aeb6-5bf8e46d307d',
	'invoice',
);

echo $id; // invoice_01jsnsf2g7e2saxdjvz3j6tc3x
```

## Store TypeIDs

For text columns, store the canonical string and restore it with `TypeID::fromString()`:

```php
$stored = $id->toString();
$restored = TypeID::fromString($stored);
```

For `binary(16)` columns, store the UUID bytes. The bytes do not contain the type prefix, so store the type prefix separately or supply it from the application context.

```php
$stored = $id->bytes();
$restored = TypeID::fromBytes($stored, 'invoice');
```

Do not use native PHP serialization as a persistent storage format. The package supports `serialize()` and `unserialize()` for runtime round trips within one major version.

`json_encode()` writes the canonical string:

```php
echo json_encode(['id' => $id]);
// {"id":"invoice_01jsnsf2g7e2saxdjvz3j6tc3x"}
```

## Use a zero TypeID

If your application requires a sentinel, create one with `TypeID::zero()`. Its suffix encodes the nil UUID.

```php
$zero = TypeID::zero('user');

var_export($zero->isZero());    // true
var_export($zero->isNonZero()); // false

echo $zero; // user_00000000000000000000000000
```

A zero TypeID is not UUIDv7 and is not K-sortable. The same limit applies to TypeIDs created from non-v7 UUIDs.

## Handle errors

Invalid input throws `TypeID\Exception\ValidationException`, which extends `InvalidArgumentException`. UUID generation failures throw `TypeID\Exception\GenerationException`, which extends `RuntimeException`.

Catch `TypeID\Exception\TypeIDException` to handle either package exception:

```php
use TypeID\Exception\TypeIDException;

try {
	$id = TypeID::fromString($input);
} catch (TypeIDException $exception) {
	// Handle an exception from this package.
}
```

Validation messages identify the failed rule and may report the input length. They never include the rejected value.

## API reference

| Member | Result | Behavior |
| --- | --- | --- |
| `TypeID::generate(?string $prefix = null)` | `TypeID` | Creates a TypeID backed by UUIDv7. |
| `TypeID::fromString(string $value)` | `TypeID` | Parses a canonical string or a bare suffix. |
| `TypeID::fromUuid(string $uuid, ?string $prefix = null)` | `TypeID` | Accepts any valid UUID string, including the nil UUID. |
| `TypeID::fromBytes(string $bytes, ?string $prefix = null)` | `TypeID` | Accepts exactly 16 UUID bytes. |
| `TypeID::zero(?string $prefix = null)` | `TypeID` | Creates a TypeID backed by the nil UUID. |
| `new TypeID(string $prefix, string $suffix)` | `TypeID` | Validates both arguments. |
| `->prefix`, `->suffix` | `string` | Expose readonly properties. |
| `->toString()`, `(string)`, `->jsonSerialize()` | `string` | Return the canonical TypeID string. |
| `->toUuid()` | `string` | Returns a lowercase, hyphenated UUID. |
| `->bytes()` | `string` | Returns 16 UUID bytes. |
| `->isZero()`, `->isNonZero()` | `bool` | Check whether the suffix encodes the nil UUID. |
| `->equals(TypeID $other)` | `bool` | Compares both the type prefix and the suffix. |
| `TypeID::ZERO_SUFFIX` | `string` | Contains the 26-character suffix for the nil UUID. |

`fromUuid()` normalizes uppercase or bare input. `toUuid()` always returns lowercase, hyphenated text.

## Format reference

```text
user_01jsnsf2g7e2saxdjvz3j6tc3x
^^^^  ^^^^^^^^^^^^^^^^^^^^^^^^^^
|     +-- 26-character base32 suffix encoding a 128-bit UUID
+-------- type prefix
```

A separator joins a non-empty type prefix to the suffix. A bare TypeID contains only the suffix. The last underscore in a canonical string is the separator, so a type prefix can contain underscores.

### Type prefix

A type prefix matches this expression:

```regex
^([a-z]([a-z_]{0,61}[a-z])?)?$
```

The type prefix follows these rules:

- It contains no more than 63 characters. An empty type prefix is valid.
- It contains only lowercase ASCII letters and underscores.
- A non-empty type prefix starts and ends with a letter.
- It can contain consecutive underscores.
- It cannot contain digits or uppercase letters.

For example, `my__type` is valid. `_user`, `user_`, `user1`, and `User` are invalid.

### Suffix

A canonical suffix matches this expression:

```regex
^[0-7][0123456789abcdefghjkmnpqrstvwxyz]{25}$
```

The suffix follows these rules:

- It contains exactly 26 lowercase base32 characters.
- Its alphabet omits `i`, `l`, `o`, and `u`.
- It contains no hyphens or padding.
- Its first character cannot exceed `7`.

A UUID contains 128 bits, while 26 base32 characters can hold 130 bits. The encoder adds two zero bits at the start. The parser rejects any suffix greater than `7zzzzzzzzzzzzzzzzzzzzzzzzz` because that value exceeds 128 bits.

### Canonical string length

A canonical TypeID string contains between 26 and 90 characters. The maximum contains a 63-character type prefix, one separator, and a 26-character suffix.

## Compatibility policy

`TypeID` is the only supported production entry point. The exception classes are supported catch types. This table defines the compatibility promise within a major version:

| Element | Status |
| --- | --- |
| `TypeID` factories, constructor, and instance methods | Supported |
| `TypeID::$prefix`, `TypeID::$suffix`, and `TypeID::ZERO_SUFFIX` | Supported |
| `Stringable` and `JsonSerializable` behavior | Supported |
| `TypeIDException`, `ValidationException`, and `GenerationException` | Supported catch types |
| Native `serialize()` and `unserialize()` | Supported for runtime round trips |
| Exception message text | Can change |
| `ValidationException` named constructors | Internal |
| `TypeID\Base32` | Internal and can be removed |

PHP cannot hide a top-level class from Composer's autoloader. Composer can therefore load `TypeID\Base32`, but code that calls it has no compatibility guarantee. `composer test:architecture` prevents production code and contract tests in this repository from depending on `Base32`.

[CONTEXT.md](CONTEXT.md) defines the terms used by the package.

## Specification conformance

The repository vendors specification 0.3.0 and its conformance vectors in [`spec/`](spec/). The files come from [`jetify-com/typeid`](https://github.com/jetify-com/typeid/tree/main/spec).

The test suite also checks requirements outside the supplied vectors:

- Generated TypeIDs survive string, UUID, and byte round trips.
- Generated TypeIDs contain the required UUIDv7 version and variant bits.
- Ramsey's default UUIDv7 generator returns increasing values within one process.
- The codec uses the specified alphabet and maps the maximum suffix to the maximum UUID value.
- Canonical strings obey the 26-character and 90-character limits.

## Run the checks

Install the development dependencies, then run the checks from the repository root:

```bash
composer install
composer test
vendor/bin/pint --test
```

The Composer scripts run these checks:

| Command | Check |
| --- | --- |
| `composer test` | Runs the architecture check, PHPStan, and the Pest suite. |
| `composer test:unit` | Runs the Pest suite. |
| `composer test:architecture` | Checks the internal dependency rules. |
| `composer test:types` | Runs PHPStan at maximum strictness against PHP 8.3. |
| `vendor/bin/pint --test` | Checks PHP formatting. |

The test directories have separate responsibilities:

| Directory | Responsibility |
| --- | --- |
| `tests/Contract/` | Tests the public API through `TypeID`. |
| `tests/Spec/` | Tests the vendored specification and requirements absent from its vectors. |
| `tests/Codec/` | Identifies failures in individual base32 bit positions through `Base32`. |
| `tests/Tooling/` | Tests the architecture checker with PHP snippets. |

Only `tests/Codec/` can call `Base32`. Direct codec tests report the failed byte and character. Other tests use `TypeID`.
