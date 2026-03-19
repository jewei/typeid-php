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
- **K-sortable** — UUIDv7 timestamp in the high bits means IDs sort chronologically
- **Compact** — 26-char Crockford base32 suffix vs 36 chars for a standard UUID string
- **URL-safe** — only `[a-z0-9_]` characters, no encoding needed
- **Zero dependencies** — pure bit manipulation, no GMP or bcmath required

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

// Equality check
$a = TypeID::fromString('user_01jsnsf2g7e2saxdjvz3j6tc3x');
$b = TypeID::fromString('user_01jsnsf2g7e2saxdjvz3j6tc3x');
echo $a->equals($b); // true
```

## Format

```
user_01jsnsf2g7e2saxdjvz3j6tc3x
^^^^  ^^^^^^^^^^^^^^^^^^^^^^^^^^
│     └─ 26-char Crockford base32 (encodes a 128-bit UUIDv7)
└─ prefix: lowercase entity type label (0–63 chars)
```

The prefix is separated from the suffix by `_`. When no prefix is used, the TypeID is just the bare 26-char suffix. Multiple underscores are allowed in the prefix (`post_category_01jsnsf2g7…`); the last underscore is always the delimiter.

## Examples

| TypeID                                     | Prefix        | Suffix                     |
| ------------------------------------------ | ------------- | -------------------------- |
| `01jsnsf2g7e2saxdjvz3j6tc3x`               | _(none)_      | 01jsnsf2g7e2saxdjvz3j6tc3x |
| `user_01jsnsf2g7e2saxdjvz3j6tc3x`          | user          | 01jsnsf2g7e2saxdjvz3j6tc3x |
| `post_category_01jsnsf2g7e2saxdjvz3j6tc3x` | post_category | 01jsnsf2g7e2saxdjvz3j6tc3x |

## Testing

```bash
composer test
```
