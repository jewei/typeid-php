<?php

declare(strict_types=1);

namespace TypeID;

use JsonSerializable;
use Override;
use Ramsey\Uuid\Uuid;
use Stringable;
use Throwable;
use TypeID\Exception\GenerationException;
use TypeID\Exception\ValidationException;

/**
 * A TypeID value. Values from generate() are designed for global uniqueness
 * with negligible collision probability when backed by a correct cryptographic
 * random source. Values with the same type prefix are K-sortable by their
 * UUIDv7 millisecond timestamp.
 *
 * Format: {prefix}_{suffix}, for example user_01jsnsf2g7e2saxdjvz3j6tc3x
 *
 * The type prefix contains up to 63 lowercase ASCII letters and internal
 * underscores. The suffix is a 26-character base32 encoding of a UUID.
 *
 * @see https://github.com/jetify-com/typeid
 */
final class TypeID implements JsonSerializable, Stringable
{
    /** Base32 encoding of the nil UUID. */
    public const string ZERO_SUFFIX = '00000000000000000000000000';

    /**
     * A type prefix: lowercase ASCII letters and underscores, at most 63
     * characters, starting and ending with a letter. May be empty.
     */
    private const string PREFIX_PATTERN = '/\A(?:[a-z](?:[a-z_]{0,61}[a-z])?)?\z/';

    /** A UUID in hyphenated or bare hex form, in either case. */
    private const string UUID_PATTERN = '/\A(?:[0-9a-f]{32}|[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12})\z/i';

    /** @throws ValidationException If prefix or suffix fails TypeID spec validation. */
    public function __construct(
        public readonly string $prefix,
        public readonly string $suffix,
    ) {
        self::assertValidPrefix($this->prefix);
        self::assertValidSuffix($this->suffix);
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
            throw ValidationException::malformedPayload();
        }

        // Validate both fields before assigning either one.
        self::assertValidPrefix($data['prefix']);
        self::assertValidSuffix($data['suffix']);

        $this->prefix = $data['prefix'];
        $this->suffix = $data['suffix'];
    }

    /**
     * Accept a UUID as 32 hexadecimal characters or canonical 8-4-4-4-12
     * notation. Hexadecimal letters may be uppercase or lowercase and are
     * normalized internally.
     *
     * @throws ValidationException If $uuid or $prefix fails validation.
     */
    public static function fromUuid(string $uuid, ?string $prefix = null): self
    {
        return new self($prefix ?? '', Base32::encodeBytes(self::parseUuidBytes($uuid)));
    }

    /**
     * Create a TypeID from 16 raw UUID bytes.
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
     * Accepts prefixed and bare forms.
     * The last separator divides the type prefix from the suffix.
     *
     * @throws ValidationException If $value is malformed or fails spec validation.
     */
    public static function fromString(string $value): self
    {
        $length = strlen($value);

        if ($length === 0) {
            throw ValidationException::emptyString();
        }

        if ($length > 90) {
            throw ValidationException::invalidStringLength($length);
        }

        if ($value[0] === '_') {
            throw ValidationException::leadingSeparator();
        }

        if ($length === 26) {
            return new self('', $value);
        }

        if ($length < 28) {
            throw ValidationException::invalidStringLength($length);
        }

        $separator = $length - 27;

        if ($value[$separator] !== '_') {
            throw ValidationException::missingSeparator();
        }

        return new self(substr($value, 0, $separator), substr($value, $separator + 1));
    }

    /**
     * Generate a new TypeID backed by a fresh UUIDv7.
     * UUIDv7 encodes a millisecond timestamp in the high bits. Ramsey's
     * default generator keeps successive values monotonic within one process;
     * separate processes do not share that monotonic state.
     *
     * @throws GenerationException If UUIDv7 generation fails.
     * @throws ValidationException If $prefix fails spec validation.
     */
    public static function generate(?string $prefix = null): self
    {
        $prefix ??= '';
        self::assertValidPrefix($prefix);

        try {
            $uuid = Uuid::uuid7()->toString();
        } catch (Throwable $exception) {
            throw new GenerationException(
                'Failed to generate a UUIDv7 for TypeID',
                previous: $exception,
            );
        }

        try {
            $bytes = self::parseUuidBytes($uuid);
        } catch (ValidationException) {
            throw new GenerationException('The UUID factory did not return a valid UUIDv7');
        }

        if ((ord($bytes[6]) & 0xF0) !== 0x70 || (ord($bytes[8]) & 0xC0) !== 0x80) {
            throw new GenerationException('The UUID factory did not return a valid UUIDv7');
        }

        return new self($prefix, Base32::encodeBytes($bytes));
    }

    /**
     * Create the nil TypeID (all 128 UUID bits are zero).
     * Useful as a sentinel or placeholder. Use it as a foreign-key value only
     * when the data model defines a sentinel record; use a nullable foreign key
     * for an absent relationship.
     *
     * The nil UUID has no version or variant bits. The result is not UUIDv7 or
     * K-sortable. The TypeID spec includes the nil suffix as a valid case.
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

    /** Decode the suffix to a lowercase, hyphenated UUID string. */
    public function toUuid(): string
    {
        $hex = bin2hex($this->bytes());

        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    /** Decode the suffix to 16 raw UUID bytes. */
    public function bytes(): string
    {
        return Base32::decodeBytes($this->suffix);
    }

    /** True when this TypeID represents the nil UUID (all 128 bits are zero). */
    public function isZero(): bool
    {
        return $this->suffix === self::ZERO_SUFFIX;
    }

    /** True when the suffix does not encode the nil UUID. */
    public function isNonZero(): bool
    {
        return ! $this->isZero();
    }

    /** Compare the type prefix and suffix. */
    public function equals(self $other): bool
    {
        return $this->prefix === $other->prefix && $this->suffix === $other->suffix;
    }

    /** Return the canonical string for json_encode(). */
    #[Override]
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /** @throws ValidationException If $uuid is not a valid UUID string. */
    private static function parseUuidBytes(string $uuid): string
    {
        $length = strlen($uuid);

        if (($length !== 32 && $length !== 36) || preg_match(self::UUID_PATTERN, $uuid) !== 1) {
            throw ValidationException::invalidUuid($length);
        }

        return (string) hex2bin(str_replace('-', '', strtolower($uuid)));
    }

    /** @throws ValidationException If $prefix is not a valid type prefix. */
    private static function assertValidPrefix(string $prefix): void
    {
        $length = strlen($prefix);

        if ($length > 63 || preg_match(self::PREFIX_PATTERN, $prefix) !== 1) {
            throw ValidationException::invalidPrefix($length);
        }
    }

    /** @throws ValidationException If $suffix is not a canonical suffix. */
    private static function assertValidSuffix(string $suffix): void
    {
        if (! Base32::isCanonicalSuffix($suffix)) {
            throw ValidationException::invalidSuffix(strlen($suffix));
        }
    }
}
