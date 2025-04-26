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
    public const ZERO_SUFFIX = '0000000000e008000000000000';

    // The type prefix for this TypeID
    private string $prefix;

    // The suffix part in base32 encoding
    private string $suffix;

    /**
     * TypeID constructor.
     *
     * @param  string  $prefix  The type prefix
     * @param  string  $suffix  The base32 encoded suffix
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
        $this->suffix = $suffix === '' ? self::ZERO_SUFFIX : $suffix;
    }

    /**
     * Same as toString() but can be used implicitly.
     *
     * @return string The string representation of this TypeID
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Create a new TypeID from a UUID.
     *
     * @param  string  $uuid  The UUID string
     * @param  string|null  $prefix  The type prefix (default: empty string)
     * @return self A new TypeID instance
     *
     * @throws ValidationException If the UUID or prefix is invalid
     * @throws ConstructorException If conversion from UUID to TypeID fails
     */
    public static function fromUuid(string $uuid, ?string $prefix = null): self
    {
        try {
            return new self($prefix ?? '', Base32::encode($uuid));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new ConstructorException('Failed to create TypeID from UUID: '.$exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Parse a TypeID from a string.
     *
     * @param  string  $value  The TypeID string to parse
     * @return self A new TypeID instance
     *
     * @throws ValidationException If the string is empty
     * @throws ConstructorException If TypeID construction fails
     */
    public static function fromString(string $value): self
    {
        try {
            $parts = Validator::parseTypeID($value);

            return new self($parts[0], $parts[1]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ConstructorException('Failed to create TypeID from string: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate a new random TypeID with the given prefix.
     *
     * @param  string|null  $prefix  The type prefix (default: empty string)
     * @return self A new random TypeID instance
     *
     * @throws ValidationException If the prefix is invalid
     * @throws ConstructorException If TypeID generation fails
     */
    public static function generate(?string $prefix = null): self
    {
        try {
            return self::fromUuid(Uuid::uuid7()->toString(), $prefix ?? '');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ConstructorException('Failed to generate TypeID: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a zero TypeID with the given prefix.
     *
     * @param  string|null  $prefix  The type prefix (default: empty string)
     * @return self A new zero TypeID instance
     *
     * @throws ValidationException If the prefix is invalid
     */
    public static function zero(?string $prefix = null): self
    {
        try {
            return new self($prefix ?? '', self::ZERO_SUFFIX);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ConstructorException('Failed to create zero TypeID: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the prefix of this TypeID.
     *
     * @return string The prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get the suffix of this TypeID in base32 representation.
     *
     * @return string The base32 suffix
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * Convert the TypeID to its canonical string representation.
     *
     * @return string The string representation of this TypeID
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
     *
     * @return string The UUID string
     *
     * @throws ValidationException If the suffix cannot be decoded to a valid UUID
     */
    public function toUuid(): string
    {
        try {
            $uuid = Base32::decode($this->getSuffix());

            if (! Validator::isValidUuidv7($uuid)) {
                throw new ValidationException('Decoded value is not a valid UUIDv7: '.$uuid);
            }

            return $uuid;
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Check if this TypeID is a zero ID.
     *
     * @return bool True if this is a zero ID
     */
    public function isZero(): bool
    {
        return $this->suffix === self::ZERO_SUFFIX;
    }

    /**
     * Check if this TypeID has a specific prefix.
     *
     * @param  string  $prefix  The prefix to check
     * @return bool True if this TypeID has the specified prefix
     */
    public function hasPrefix(string $prefix): bool
    {
        return $this->prefix === $prefix;
    }

    /**
     * Check if this TypeID equals another TypeID.
     *
     * @param  TypeID  $other  The other TypeID to compare with
     * @return bool True if the TypeIDs are equal
     */
    public function equals(self $other): bool
    {
        return $this->prefix === $other->prefix && $this->suffix === $other->suffix;
    }
}
