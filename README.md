# TypeID PHP

A PHP implementation of [TypeIDs](https://github.com/jetify-com/typeid): type-safe, K-sortable, and globally unique identifiers inspired by Stripe IDs.

TypeIDs are a modern, type-safe extension of UUIDv7. They provide a ton of nice properties that make them a great choice as the primary identifiers for your data in databases, APIs, and distributed systems.

## Installation

```bash
composer require jetify/typeid-php
```

## Benefits

* **Type-safe:** you can't accidentally use a `user` ID where a `post` ID is expected. When debugging, you can immediately understand what type of entity a TypeID refers to thanks to the type prefix.
* **Compatible with UUIDs:** TypeIDs are a superset of UUIDs. They are based on the UUIDv7 standard. If you decode the TypeID and remove the type information, you get a valid UUIDv7.
* **K-Sortable**: TypeIDs are K-sortable and can be used as the primary key in a database while ensuring good locality. Compare to entirely random global ids, like UUIDv4, that generally suffer from poor database locality.
* **Thoughtful encoding**: the base32 encoding is URL safe, case-insensitive, avoids ambiguous characters, can be selected for copy-pasting by double-clicking, and is a more compact encoding than the traditional hex encoding used by UUIDs (26 characters vs 36 characters).

## Usage

### Basic Usage

```php
use TypeID\Factory;

// Create a new TypeID with the prefix "user"
$id = Factory::create('user');
echo $id; // Outputs something like: user_01h455vb4pex5vsknk084sn02q

// Parse a TypeID from a string
$id = Factory::fromString('user_01h455vb4pex5vsknk084sn02q');
echo $id->getPrefix(); // Outputs: user
echo $id->toUuid(); // Outputs the UUID in standard format

// Convert a UUID to a TypeID
$uuid = '0188bac7-4afa-78aa-bc3b-bd1eef28d881';
$id = Factory::fromUuid('post', $uuid);
echo $id; // Outputs: post_01h2xcejqtf2nbrexx3vqjhp41
```

### Type-Safe Usage

For type safety, you can create specific classes for each type of ID:

```php
use TypeID\Types\UserID;

// Create a new UserID
$userId = UserID::create();
echo $userId; // Outputs: user_01h455vb4pex5vsknk084sn02q

// Parse a UserID from a string
$userId = UserID::fromString('user_01h455vb4pex5vsknk084sn02q');

// This will throw an exception because the prefix doesn't match
try {
    $userId = UserID::fromString('post_01h2xcejqtf2nbrexx3vqjhp41');
} catch (\Exception $e) {
    echo $e->getMessage(); // Invalid prefix for UserID: post, expected: user
}
```

### Creating Your Own Type

You can easily create your own typed IDs:

```php
namespace YourApp\ID;

use TypeID\TypeID;

class ProductID extends TypeID
{
    private const PREFIX = 'prod';
    
    public function __construct(string $suffix)
    {
        parent::__construct(self::PREFIX, $suffix);
    }
    
    public static function create(): static
    {
        $typeId = TypeID::generate(self::PREFIX);
        return new static($typeId->getSuffix());
    }
    
    // Add other convenience methods as needed
}
```

## License

[Apache-2.0](LICENSE) 