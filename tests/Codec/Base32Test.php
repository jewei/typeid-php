<?php

declare(strict_types=1);

use InvalidArgumentException;
use TypeID\Base32;
use TypeID\Exception\ValidationException;
use TypeID\Tests\Support\ReferenceBase32;

/**
 * Compare each codec direction with a literal implementation of the spec.
 * This prevents matching masks or shifts in the optimized encoder and decoder
 * from hiding each other's defects.
 *
 * @see spec/README.md TypeID specification 0.3.0, base32 encoding
 */
$expectCodecToMatchReference = function (string $bytes, string $case): void {
    $optimized = Base32::encodeBytes($bytes);
    $reference = ReferenceBase32::encodeBytes($bytes);

    expect($optimized)->toBe($reference, "{$case}: optimized encoding differs from the reference")
        ->and(Base32::decodeBytes($reference))->toBe($bytes, "{$case}: optimized decoding differs from the reference")
        ->and(ReferenceBase32::decodeBytes($optimized))->toBe($bytes, "{$case}: reference decoding differs from the input");
};

test('the optimized codec matches the reference for every single set bit', function () use ($expectCodecToMatchReference): void {
    foreach (range(0, 127) as $bit) {
        $bytes = array_fill(0, 16, 0);
        $bytes[intdiv($bit, 8)] = 1 << (7 - $bit % 8);

        $expectCodecToMatchReference(pack('C*', ...$bytes), "single bit {$bit}");
    }
});

test('the optimized codec matches the reference for every saturated byte position', function () use ($expectCodecToMatchReference): void {
    foreach (range(0, 15) as $index) {
        $bytes = array_fill(0, 16, 0);
        $bytes[$index] = 0xFF;

        $expectCodecToMatchReference(pack('C*', ...$bytes), "saturated byte {$index}");
    }
});

test('the optimized codec matches the reference at boundaries', function (string $hex) use ($expectCodecToMatchReference): void {
    $bytes = hex2bin($hex);

    expect($bytes)->not->toBeFalse();
    $expectCodecToMatchReference($bytes, "boundary {$hex}");
})->with([
    'nil' => str_repeat('00', 16),
    'one' => str_repeat('00', 15).'01',
    'lower half maximum' => '7f'.str_repeat('ff', 15),
    'upper half minimum' => '80'.str_repeat('00', 15),
    'one below maximum' => str_repeat('ff', 15).'fe',
    'maximum' => str_repeat('ff', 16),
    'alternating 0x55' => str_repeat('55', 16),
    'alternating 0xAA' => str_repeat('aa', 16),
    'ascending bytes' => '000102030405060708090a0b0c0d0e0f',
    'descending bytes' => 'f0e0d0c0b0a090807060504030201000',
]);

test('the optimized codec matches the reference for a deterministic hash corpus', function () use ($expectCodecToMatchReference): void {
    foreach (range(0, 511) as $integer) {
        $digest = hash('sha256', (string) $integer, true);
        $expectCodecToMatchReference(substr($digest, 0, 16), "sha256({$integer})");
    }
});

test('the optimized codec and reference match the official vectors', function () use ($expectCodecToMatchReference): void {
    foreach (validSpecVectors() as $vector) {
        $name = $vector['name'] ?? null;
        $typeid = $vector['typeid'] ?? null;
        $uuid = $vector['uuid'] ?? null;

        expect($name)->toBeString()
            ->and($typeid)->toBeString()
            ->and($uuid)->toBeString();

        $separator = strrpos($typeid, '_');
        $suffix = $separator === false ? $typeid : substr($typeid, $separator + 1);
        $bytes = hex2bin(str_replace('-', '', $uuid));

        expect($bytes)->not->toBeFalse()
            ->and(ReferenceBase32::encodeBytes($bytes))->toBe($suffix, "official vector {$name}: reference encoding differs");

        $expectCodecToMatchReference($bytes, "official vector {$name}");
        expect(ReferenceBase32::decodeBytes($suffix))->toBe($bytes, "official vector {$name}: reference decoding differs");
    }
});

test('the documented boundary values have fixed encodings', function (): void {
    expect(Base32::encodeBytes(str_repeat("\0", 16)))->toBe('00000000000000000000000000')
        ->and(Base32::encodeBytes(str_repeat("\xFF", 16)))->toBe('7zzzzzzzzzzzzzzzzzzzzzzzzz')
        ->and(Base32::decodeBytes('00000000000000000000000001'))->toBe(str_repeat("\0", 15)."\x01")
        ->and(Base32::decodeBytes('7zzzzzzzzzzzzzzzzzzzzzzzzz'))->toBe(str_repeat("\xFF", 16));
});

$invalidEncodedValues = [
    'empty' => '',
    'one short' => str_repeat('0', 25),
    'one long' => str_repeat('0', 27),
    'ambiguous i' => str_repeat('0', 25).'i',
    'ambiguous l' => str_repeat('0', 25).'l',
    'ambiguous o' => str_repeat('0', 25).'o',
    'excluded u' => str_repeat('0', 25).'u',
    'hyphen' => str_repeat('0', 25).'-',
    'underscore' => str_repeat('0', 25).'_',
    'space' => str_repeat('0', 25).' ',
];

foreach (str_split('abcdefghjkmnpqrstvwxyz') as $letter) {
    $invalidEncodedValues["uppercase {$letter}"] = str_repeat('0', 25).strtoupper($letter);
}

foreach (str_split('89abcdefghjkmnpqrstvwxyz') as $leading) {
    $invalidEncodedValues["overflow {$leading}"] = $leading.str_repeat('0', 25);
}

test('the optimized decoder and reference reject values outside the encoded domain', function (string $encoded): void {
    expect(fn () => Base32::decodeBytes($encoded))->toThrow(ValidationException::class);
    expect(fn () => ReferenceBase32::decodeBytes($encoded))->toThrow(InvalidArgumentException::class);
})->with($invalidEncodedValues);

test('the optimized encoder and reference reject byte strings that are not exactly 16 bytes', function (string $bytes): void {
    expect(fn () => Base32::encodeBytes($bytes))->toThrow(ValidationException::class);
    expect(fn () => ReferenceBase32::encodeBytes($bytes))->toThrow(InvalidArgumentException::class);
})->with([
    'empty' => '',
    'fifteen' => str_repeat("\0", 15),
    'seventeen' => str_repeat("\0", 17),
]);
