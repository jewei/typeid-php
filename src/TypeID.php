<?php

declare(strict_types=1);

namespace TypeID;

use JsonSerializable;
use Override;
use Ramsey\Uuid\Exception\UuidExceptionInterface;
use Ramsey\Uuid\Uuid;
use Stringable;
use TypeID\Exception\ConstructorException;
use TypeID\Exception\ValidationException;

/**
 * A TypeID value. Values created by generate() are type-safe, K-sortable,
 * and globally unique.
 *
 * Format: {prefix}_{suffix}  e.g. user_01jsnsf2g7e2saxdjvz3j6tc3x
 *
 * - prefix  → lowercase entity type label (0–63 chars, e.g. 'user', 'order')
 * - suffix  → 26-char Crockford base32-encoded UUID
 *
 * @see https://github.com/jetify-com/typeid
 */
final class TypeID implements JsonSerializable, Stringable
{
    /** Crockford base32 of the nil UUID — useful as a sentinel/zero value. */
    public const string ZERO_SUFFIX = '00000000000000000000000000';

    /** @throws ValidationException If prefix or suffix fails TypeID spec validation. */
    public function __construct(
        public readonly string $prefix, // Entity-type label (e.g. 'user', 'order'). Empty string means no prefix.
        public readonly string $suffix, // Crockford base32 UUID payload — always exactly 26 lowercase characters.
    ) {
        if (! Validator::isValidPrefix($this->prefix)) {
            throw new ValidationException(
                'Invalid prefix: '.Validator::formatForMessage($this->prefix)
            );
        }

        if (! Validator::isValidSuffix($this->suffix)) {
            throw new ValidationException(
                'Invalid suffix: '.Validator::formatForMessage($this->suffix)
            );
        }
    }

    #[Override]
    public function __toString(): string
    {
        return $this->toString();
    }

    /** @return array{prefix: string, suffix: string} */
    public function __serialize(): array
    {
        return [
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException If the serialized data is malformed or invalid.
     */
    public function __unserialize(array $data): void
    {
        if (! is_string($data['prefix'] ?? null) || ! is_string($data['suffix'] ?? null)) {
            throw new ValidationException('Invalid serialized TypeID data');
        }

        $validated = new self($data['prefix'], $data['suffix']);

        $this->prefix = $validated->prefix;
        $this->suffix = $validated->suffix;
    }

    /**
     * Create a TypeID from any valid UUID string (v4, v7, nil, …).
     * Uppercase hex is accepted and normalized to lowercase.
     *
     * @throws ValidationException If $uuid or $prefix fails validation.
     */
    public static function fromUuid(string $uuid, ?string $prefix = null): self
    {
        return new self($prefix ?? '', Base32::encode($uuid));
    }

    /**
     * Create a TypeID from a prefix and raw 16-byte binary UUID.
     * Useful for round-tripping UUIDs stored as binary(16) in a database.
     *
     * @throws ValidationException If $bytes or $prefix fails validation.
     */
    public static function fromBytes(string $bytes, ?string $prefix = null): self
    {
        return new self($prefix ?? '', Base32::encodeBytes($bytes));
    }

    /**
     * Parse a TypeID from its canonical string form.
     * Accepts prefixed ('user_01jsnsf2g7…') and bare ('01jsnsf2g7…') forms.
     * The last underscore is always the prefix/suffix delimiter.
     *
     * @throws ValidationException If $value is malformed or fails spec validation.
     */
    public static function fromString(string $value): self
    {
        [$prefix, $suffix] = Validator::parseTypeID($value);

        return new self($prefix, $suffix);
    }

    /**
     * Generate a new TypeID backed by a fresh UUIDv7.
     * UUIDv7 encodes a millisecond timestamp in the high bits, making
     * generated TypeIDs with the same prefix sortable by creation time.
     *
     * @throws ConstructorException If UUIDv7 generation fails.
     * @throws ValidationException If $prefix fails spec validation.
     */
    public static function generate(?string $prefix = null): self
    {
        try {
            $uuid = Uuid::uuid7()->toString();
        } catch (UuidExceptionInterface $e) {
            throw new ConstructorException(
                'Failed to generate TypeID: '.$e->getMessage(),
                previous: $e,
            );
        }

        return self::fromUuid($uuid, $prefix);
    }

    /**
     * Create the nil TypeID (all 128 UUID bits are zero).
     * Useful as a sentinel, placeholder, or default FK value.
     *
     * @throws ValidationException If $prefix fails spec validation.
     */
    public static function zero(?string $prefix = null): self
    {
        return new self($prefix ?? '', self::ZERO_SUFFIX);
    }

    /** Returns '{prefix}_{suffix}', or bare '{suffix}' when prefix is empty. */
    public function toString(): string
    {
        return $this->prefix !== '' ? "{$this->prefix}_{$this->suffix}" : $this->suffix;
    }

    /** Decode the suffix back to its canonical hyphenated UUID string (e.g. '01966b97-8a07-…'). */
    public function toUuid(): string
    {
        return Base32::decode($this->suffix);
    }

    /** Decode the suffix to raw 16-byte binary — useful for binary(16) database columns. */
    public function bytes(): string
    {
        return Base32::decodeBytes($this->suffix);
    }

    /** True when this TypeID represents the nil UUID (all 128 bits are zero). */
    public function isZero(): bool
    {
        return $this->suffix === self::ZERO_SUFFIX;
    }

    /** True when this TypeID has a non-zero suffix (i.e. not the nil UUID). */
    public function isNonZero(): bool
    {
        return ! $this->isZero();
    }

    /** True when this TypeID's prefix exactly matches $prefix (case-sensitive). */
    public function hasPrefix(string $prefix): bool
    {
        return $this->prefix === $prefix;
    }

    /** Value equality — two TypeIDs are equal only when prefix and suffix both match. */
    public function equals(self $other): bool
    {
        return $this->prefix === $other->prefix && $this->suffix === $other->suffix;
    }

    /** Enables native json_encode() support — serializes as the canonical string form. */
    #[Override]
    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
