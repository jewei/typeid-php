# TypeID PHP

A PHP implementation of [TypeIDs](https://github.com/jetify-com/typeid): type-safe, K-sortable, and globally unique identifiers inspired by Stripe IDs.

TypeIDs extend UUIDv7 with type prefixes, offering improved ergonomics for your database IDs, API endpoints, and distributed systems.

## Key Features

* **Type Safety** - Prevent accidentally using a `user` ID where a `product` ID is expected
* **Developer-Friendly** - Type prefixes make debugging more intuitive
* **K-Sortable** - Ensures good database locality compared to random UUIDs
* **Efficient Encoding** - URL-safe base32 encoding (26 characters vs 36 for standard UUIDs)
* **UUID Compatible** - Based on the UUIDv7 standard

## Installation

```bash
composer require jewei/typeid-php
```

## Usage

### Basic Usage

```php
use TypeID\TypeID;

// Generate a new TypeID
$id = TypeID::create('user');
echo $id; // user_01h455vb4pex5vsknk084sn02q

// Parse a TypeID string
$id = TypeID::fromString('user_01h455vb4pex5vsknk084sn02q');
echo $id->getPrefix(); // user
echo $id->toUuid();    // UUID in standard format

// Convert UUID to TypeID
$uuid = '0188bac7-4afa-78aa-bc3b-bd1eef28d881';
$id = TypeID::fromUuid('post', $uuid);
echo $id; // post_01h2xcejqtf2nbrexx3vqjhp41
```
