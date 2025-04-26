<p align="center">
  <a href="https://github.com/jewei/typeid-php/actions">
    <img src="https://github.com/jewei/typeid-php/actions/workflows/tests.yml/badge.svg" alt="Build Status">
  </a>
  <a href="https://packagist.org/packages/jewei/typeid-php">
    <img src="https://img.shields.io/packagist/dt/jewei/typeid-php" alt="Total Downloads">
  </a>
  <a href="https://packagist.org/packages/jewei/typeid-php">
    <img src="https://img.shields.io/packagist/v/jewei/typeid-php" alt="Latest Stable Version">
  </a>
  <a href="https://packagist.org/packages/jewei/typeid-php">
    <img src="https://img.shields.io/packagist/l/jewei/typeid-php" alt="License">
  </a>
</p>

# TypeID PHP

A PHP implementation of [TypeIDs](https://github.com/jetify-com/typeid): type-safe, K-sortable, and globally unique identifiers inspired by Stripe IDs.

TypeIDs extend UUIDv7 with type prefixes, offering improved ergonomics for your database IDs, API endpoints, and distributed systems.

## Key Features

- **Type Safety** - Prevent accidentally using a `user` ID where a `product` ID is expected
- **Developer-Friendly** - Type prefixes make debugging more intuitive
- **K-Sortable** - Ensures good database locality compared to random UUIDs
- **Efficient Encoding** - URL-safe base32 encoding (26 characters vs 36 for standard UUIDs)
- **UUID Compatible** - Based on the UUIDv7 standard

## Installation

```bash
composer require jewei/typeid-php
```

## Usage

### Basic Usage

```php
use TypeID\TypeID;

// Create a new TypeID
$typeId = TypeID::generate('user');
echo $typeId; // user_01h455vb4pex5vsknk084sn02q

// Parse a TypeID string
$typeId = TypeID::fromString('user_01h455vb4pex5vsknk084sn02q');
echo $typeId->getPrefix(); // user
echo $typeId->getSuffix(); // 01h455vb4pex5vsknk084sn02q
echo $typeId->toUuid();    // 01890a5d-ac96-774b-bcce-b302099a8057

// Convert UUID to TypeID
$uuid = '0188bac7-4afa-78aa-bc3b-bd1eef28d881';
$typeId = TypeID::fromUuid('post', $uuid);
echo $typeId; // post_01h2xcejqtf2nbrexx3vqjhp41

// Convert TypeId to UUID
$typeId = 'invoice_01jsrkjbqyef0rzzhrpqph5nxk'
$uuid = TypeID::fromString($typeId)->toUuid();
echo $uuid; // 01967139-2efe-73c1-8ffe-38b5ed12d7b3
```

## TypeID Examples Table

| TypeID                                   | Prefix        | Suffix                     |
| ---------------------------------------- | ------------- | -------------------------- |
| 01jsnsf2g7e2saxdjvz3j6tc3x               | (empty)       | 01jsnsf2g7e2saxdjvz3j6tc3x |
| cus_01jsrk9hq5e0tr8cv47xerqkww           | cus           | 01jsrk9hq5e0tr8cv47xerqkww |
| user_01h455vb4pex5vsknk084sn02q          | user          | 01h455vb4pex5vsknk084sn02q |
| post_01h2xcejqtf2nbrexx3vqjhp41          | post          | 01h2xcejqtf2nbrexx3vqjhp41 |
| post_category_01jsnsf2g7e2saxdjvz3j6tc3x | post_category | 01jsnsf2g7e2saxdjvz3j6tc3x |
| product_sku_01jsrjpym8edqsfjhkgp2vmspq   | product_sku   | 01jsrjpym8edqsfjhkgp2vmspq |
