<?php

declare(strict_types=1);

namespace TypeID;

use TypeID\Exception\ValidationException;

/**
 * Stateless validation helpers for TypeID components.
 *
 * Each rule comes in two forms: an isValidX() predicate for callers that want a
 * boolean, and an assertValidX() guard that raises the matching named
 * constructor on ValidationException, which owns message rendering.
 *
 * @internal Use TypeID for the stable public API.
 */
final class Validator
{
    private const string PREFIX_PATTERN = '/\A(?:[a-z](?:[a-z_]{0,61}[a-z])?)?\z/';

    private const string SUFFIX_PATTERN = '/\A[0-7][0123456789abcdefghjkmnpqrstvwxyz]{25}\z/';

    private const string UUID_PATTERN = '/\A(?:[0-9a-f]{32}|[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12})\z/i';

    private function __construct() {}

    public static function isValidPrefix(string $prefix): bool
    {
        return preg_match(self::PREFIX_PATTERN, $prefix) === 1;
    }

    public static function isValidSuffix(string $suffix): bool
    {
        return preg_match(self::SUFFIX_PATTERN, $suffix) === 1;
    }

    public static function isValidUuid(string $uuid): bool
    {
        return preg_match(self::UUID_PATTERN, $uuid) === 1;
    }

    /** @throws ValidationException If $prefix is not a valid TypeID prefix. */
    public static function assertValidPrefix(string $prefix): void
    {
        if (! self::isValidPrefix($prefix)) {
            throw ValidationException::invalidPrefix($prefix);
        }
    }

    /** @throws ValidationException If $suffix is not a valid TypeID suffix. */
    public static function assertValidSuffix(string $suffix): void
    {
        if (! self::isValidSuffix($suffix)) {
            throw ValidationException::invalidSuffix($suffix);
        }
    }

    /** @throws ValidationException If $suffix is not a canonical 26-char base32 string. */
    public static function assertValidBase32(string $suffix): void
    {
        if (! self::isValidSuffix($suffix)) {
            throw ValidationException::invalidCodecInput($suffix);
        }
    }

    /** @throws ValidationException If $uuid is not a valid UUID string. */
    public static function assertValidUuid(string $uuid): void
    {
        if (! self::isValidUuid($uuid)) {
            throw ValidationException::invalidUuid($uuid);
        }
    }

    /**
     * Split a TypeID string into [prefix, suffix].
     * The last underscore is always the delimiter; everything before it is the prefix.
     *
     * @return array{0: string, 1: string}
     *
     * @throws ValidationException
     */
    public static function parseTypeID(string $value): array
    {
        if ($value === '') {
            throw ValidationException::malformedString('cannot be empty');
        }

        if (str_starts_with($value, '_')) {
            throw ValidationException::malformedString('cannot start with an underscore');
        }

        $lastUnderscore = strrpos($value, '_');

        if ($lastUnderscore === false) {
            return ['', $value];
        }

        return [
            substr($value, 0, $lastUnderscore),
            substr($value, $lastUnderscore + 1),
        ];
    }
}
