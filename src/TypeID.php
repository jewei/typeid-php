<?php

declare(strict_types=1);

namespace TypeID;

use Exception;
use Ramsey\Uuid\Uuid;
use TypeID\Exception\ConstructorException;
use TypeID\Exception\ValidationException;

class TypeID
{
    // Zero suffix used for default/empty TypeIDs
    public const ZERO_SUFFIX = '00000000000000000000000000';

    // The type prefix for this TypeID
    private string $prefix;

    // The suffix part in base32 encoding
    private string $suffix;

    /**
     * TypeID constructor.
     *
     * @throws ValidationException If the prefix or suffix is invalid
     */
    public function __construct(string $prefix, string $suffix)
    {
        if (! Validator::isValidPrefix($prefix)) {
            throw new ValidationException("Invalid prefix: $prefix");
        }

        if (! Validator::isValidSuffix($suffix)) {
            throw new ValidationException("Invalid suffix: $suffix");
        }

        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }

    /**
     * Same as toString() but can be used implicitly.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Create a new TypeID from a UUID.
     *
     * @throws ValidationException If the prefix is invalid
     * @throws ConstructorException If the UUID is invalid
     */
    public static function fromUuid(string $uuid, ?string $prefix = null): self
    {
        try {
            $suffix = Base32::encode($uuid);
        } catch (Exception $exception) {
            throw new ConstructorException('Failed to create TypeID from UUID: '.$exception->getMessage(), 0, $exception);
        }

        return new self($prefix ?? '', $suffix);
    }

    /**
     * Parse a TypeID from a string.
     *
     * @throws ConstructorException If the string is invalid or TypeID construction fails
     */
    public static function fromString(string $value): self
    {
        try {
            $parts = Validator::parseTypeID($value);

            return new self($parts[0], $parts[1]);
        } catch (Exception $e) {
            throw new ConstructorException('Failed to create TypeID from string: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate a new random TypeID with the given prefix.
     *
     * @throws ValidationException If the prefix is invalid
     * @throws ConstructorException If TypeID generation fails
     */
    public static function generate(?string $prefix = null): self
    {
        try {
            $uuid = Uuid::uuid7()->toString();
        } catch (Exception $e) {
            throw new ConstructorException('Failed to generate TypeID: '.$e->getMessage(), 0, $e);
        }

        return self::fromUuid($uuid, $prefix ?? '');
    }

    /**
     * Create a zero TypeID with the given prefix.
     *
     * @throws ValidationException If the prefix is invalid
     */
    public static function zero(?string $prefix = null): self
    {
        return new self($prefix ?? '', self::ZERO_SUFFIX);
    }

    /**
     * Get the prefix of this TypeID.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get the suffix of this TypeID in base32 representation.
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * Convert the TypeID to its canonical string representation.
     */
    public function toString(): string
    {
        if ($this->prefix === '') {
            return $this->suffix;
        }

        return $this->prefix.'_'.$this->suffix;
    }

    /**
     * Decode the TypeID's suffix as a UUID and return it.
     */
    public function toUuid(): string
    {
        return Base32::decode($this->suffix);
    }

    /**
     * Check if this TypeID is a zero ID.
     */
    public function isZero(): bool
    {
        return $this->suffix === self::ZERO_SUFFIX;
    }

    /**
     * Check if this TypeID has a specific prefix.
     */
    public function hasPrefix(string $prefix): bool
    {
        return $this->prefix === $prefix;
    }

    /**
     * Check if this TypeID equals another TypeID.
     */
    public function equals(self $other): bool
    {
        return $this->prefix === $other->prefix && $this->suffix === $other->suffix;
    }
}
