<?php

declare(strict_types=1);

namespace TypeID;

use Exception;
use JsonSerializable;
use Override;
use Ramsey\Uuid\Uuid;
use Stringable;
use TypeID\Exception\ConstructorException;
use TypeID\Exception\ValidationException;

/**
 * A TypeID: a type-safe, K-sortable, globally-unique identifier.
 *
 * Format: {prefix}_{suffix}  e.g. user_01jsnsf2g7e2saxdjvz3j6tc3x
 *
 * - prefix  → lowercase entity type label (0–63 chars, e.g. 'user', 'order')
 * - suffix  → 26-char Crockford base32-encoded UUID (K-sortable via UUIDv7)
 *
 * @see https://github.com/jetpack-io/typeid
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
            throw new ValidationException("Invalid prefix: {$this->prefix}");
        }

        if (! Validator::isValidSuffix($this->suffix)) {
            throw new ValidationException("Invalid suffix: {$this->suffix}");
        }
    }

    #[Override]
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Create a TypeID from any valid UUID string (v4, v7, nil, …).
     * Uppercase hex is accepted and normalized to lowercase.
     *
     * @throws ConstructorException If $uuid is not a valid UUID string.
     * @throws ValidationException If $prefix fails spec validation.
     */
    public static function fromUuid(string $uuid, ?string $prefix = null): self
    {
        try {
            $suffix = Base32::encode($uuid);
        } catch (Exception $e) {
            throw new ConstructorException(
                'Failed to create TypeID from UUID: '.$e->getMessage(),
                previous: $e,
            );
        }

        return new self($prefix ?? '', $suffix);
    }

    /**
     * Create a TypeID from a prefix and raw 16-byte binary UUID.
     * Useful for round-tripping UUIDs stored as binary(16) in a database.
     *
     * @throws ConstructorException If $bytes is not exactly 16 bytes.
     * @throws ValidationException If $prefix fails spec validation.
     */
    public static function fromBytes(string $bytes, ?string $prefix = null): self
    {
        if (strlen($bytes) !== 16) {
            throw new ConstructorException(
                'UUID bytes must be exactly 16 bytes, got '.strlen($bytes)
            );
        }

        $hex = bin2hex($bytes);
        $uuid = sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );

        return self::fromUuid($uuid, $prefix);
    }

    /**
     * Parse a TypeID from its canonical string form.
     * Accepts prefixed ('user_01jsnsf2g7…') and bare ('01jsnsf2g7…') forms.
     * The last underscore is always the prefix/suffix delimiter.
     *
     * @throws ConstructorException If $value is empty, malformed, or fails spec validation.
     */
    public static function fromString(string $value): self
    {
        try {
            [$prefix, $suffix] = Validator::parseTypeID($value);

            return new self($prefix, $suffix);
        } catch (Exception $e) {
            throw new ConstructorException(
                'Failed to create TypeID from string: '.$e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * Generate a new TypeID backed by a fresh UUIDv7.
     * UUIDv7 encodes a millisecond timestamp in the high bits, making
     * generated TypeIDs naturally K-sortable by creation time.
     *
     * @throws ConstructorException If UUIDv7 generation fails.
     * @throws ValidationException If $prefix fails spec validation.
     */
    public static function generate(?string $prefix = null): self
    {
        try {
            $uuid = Uuid::uuid7()->toString();
        } catch (Exception $e) {
            throw new ConstructorException(
                'Failed to generate TypeID: '.$e->getMessage(),
                previous: $e,
            );
        }

        return self::fromUuid($uuid, $prefix ?? '');
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
        return hex2bin(str_replace('-', '', $this->toUuid()));
    }

    /** True when this TypeID represents the nil UUID (all 128 bits are zero). */
    public function isZero(): bool
    {
        return $this->suffix === self::ZERO_SUFFIX;
    }

    /** True when this TypeID has a non-zero suffix (i.e. not the nil UUID). */
    public function hasSuffix(): bool
    {
        return $this->suffix !== self::ZERO_SUFFIX;
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
    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
