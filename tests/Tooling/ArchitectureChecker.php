<?php

declare(strict_types=1);

namespace TypeID\Development;

/** Finds internal class references in PHP source. */
final class ArchitectureChecker
{
    /**
     * Returns internal class short names used by executable PHP.
     *
     * A matching short name is rejected in any namespace. Imports, class
     * operations, type declarations, attributes, and class constants count as
     * references. Comments, strings, functions, ordinary constants, and member
     * names do not.
     *
     * @param  list<string>  $internalModules
     * @return list<string>
     */
    public static function internalReferences(string $source, array $internalModules): array
    {
        $modulesByName = [];

        foreach ($internalModules as $module) {
            $modulesByName[strtolower($module)] = $module;
        }

        $found = [];
        $tokens = token_get_all($source);
        $attributeBrackets = 0;
        $attributeParentheses = 0;

        foreach ($tokens as $index => $token) {
            if (is_array($token) && $token[0] === T_ATTRIBUTE) {
                $attributeBrackets = 1;
                $attributeParentheses = 0;

                continue;
            }

            $attributeClassPosition = $attributeBrackets > 0 && $attributeParentheses === 0;

            if (is_array($token) && self::isNameToken($token[0])) {
                $name = ltrim($token[1], '\\');
                $separator = strrpos($name, '\\');
                $shortName = $separator === false ? $name : substr($name, $separator + 1);
                $module = $modulesByName[strtolower($shortName)] ?? null;

                if ($module !== null && self::isClassLikeName($tokens, $index, $attributeClassPosition)) {
                    $found[$module] = true;
                }
            }

            if ($attributeBrackets === 0 || ! is_string($token)) {
                continue;
            }

            if ($token === '[') {
                $attributeBrackets++;
            } elseif ($token === ']') {
                $attributeBrackets--;

                if ($attributeBrackets === 0) {
                    $attributeParentheses = 0;
                }
            } elseif ($token === '(') {
                $attributeParentheses++;
            } elseif ($token === ')') {
                $attributeParentheses--;
            }
        }

        $references = array_keys($found);
        sort($references);

        return $references;
    }

    /** @param array<int, array{int, string, int}|string> $tokens */
    private static function isClassLikeName(
        array $tokens,
        int $index,
        bool $attributeClassPosition,
    ): bool {
        if ($attributeClassPosition || self::isClassImport($tokens, $index)) {
            return true;
        }

        $previousIndex = self::significantIndex($tokens, $index, -1);
        $nextIndex = self::significantIndex($tokens, $index, 1);
        $previousType = self::tokenType($previousIndex === null ? null : $tokens[$previousIndex]);
        $next = $nextIndex === null ? null : $tokens[$nextIndex];
        $nextType = self::tokenType($next);

        if ($nextType === T_DOUBLE_COLON) {
            return true;
        }

        if (in_array($previousType, [
            T_NEW,
            T_INSTANCEOF,
            T_EXTENDS,
            T_IMPLEMENTS,
            T_INSTEADOF,
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
            T_ENUM,
        ], true)) {
            return true;
        }

        if ($previousType === T_CONST && is_array($next) && $next[0] === T_STRING) {
            return true;
        }

        return self::isTypeDeclaration($tokens, $index);
    }

    /** @param array<int, array{int, string, int}|string> $tokens */
    private static function isClassImport(array $tokens, int $index): bool
    {
        $memberKind = null;
        $groupKind = null;
        $possibleOuterKind = null;
        $crossedMemberBoundary = false;
        $reachedGroupStart = false;
        $cursor = $index;

        while (($cursor = self::significantIndex($tokens, $cursor, -1)) !== null) {
            $type = self::tokenType($tokens[$cursor]);

            if ($type === ';') {
                return false;
            }

            if ($type === ',' && ! $reachedGroupStart) {
                $crossedMemberBoundary = true;

                continue;
            }

            if ($type === '{') {
                $reachedGroupStart = true;
                $possibleOuterKind = null;

                continue;
            }

            if ($type === T_FUNCTION || $type === T_CONST) {
                if ($reachedGroupStart) {
                    $groupKind = $type;
                } elseif ($crossedMemberBoundary) {
                    $possibleOuterKind = $type;
                } else {
                    $memberKind = $type;
                }
            }

            if ($type === T_USE) {
                return ($memberKind ?? $groupKind ?? $possibleOuterKind) === null;
            }
        }

        return false;
    }

    /** @param array<int, array{int, string, int}|string> $tokens */
    private static function isTypeDeclaration(array $tokens, int $index): bool
    {
        $cursor = $index;

        while (($cursor = self::significantIndex($tokens, $cursor, 1)) !== null) {
            $token = $tokens[$cursor];

            if (self::tokenType($token) === T_VARIABLE) {
                return true;
            }

            if (! self::isTypeSequenceToken($token)) {
                break;
            }
        }

        $cursor = $index;

        while (($cursor = self::significantIndex($tokens, $cursor, -1)) !== null) {
            $token = $tokens[$cursor];

            if (self::tokenType($token) === ':') {
                $beforeColon = self::significantIndex($tokens, $cursor, -1);

                return self::tokenType($tokens[$beforeColon] ?? null) === ')';
            }

            if (! self::isTypeSequenceToken($token)) {
                break;
            }
        }

        return false;
    }

    /** @param array{int, string, int}|string $token */
    private static function isTypeSequenceToken(array|string $token): bool
    {
        if (is_array($token)) {
            return self::isNameToken($token[0]) || in_array($token[0], [
                T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,
                T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,
                T_ELLIPSIS,
            ], true);
        }

        return in_array($token, ['?', '|', '&', '(', ')'], true);
    }

    /**
     * @param  array<int, array{int, string, int}|string>  $tokens
     */
    private static function significantIndex(array $tokens, int $index, int $direction): ?int
    {
        for ($index += $direction; isset($tokens[$index]); $index += $direction) {
            $token = $tokens[$index];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $index;
        }

        return null;
    }

    /** @param array{int, string, int}|string|null $token */
    private static function tokenType(array|string|null $token): int|string|null
    {
        return is_array($token) ? $token[0] : $token;
    }

    private static function isNameToken(int $token): bool
    {
        return in_array($token, [
            T_STRING,
            T_NAME_QUALIFIED,
            T_NAME_FULLY_QUALIFIED,
            T_NAME_RELATIVE,
        ], true);
    }
}
