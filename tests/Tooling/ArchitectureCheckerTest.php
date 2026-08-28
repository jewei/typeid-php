<?php

declare(strict_types=1);

use TypeID\Development\ArchitectureChecker;

require_once __DIR__.'/ArchitectureChecker.php';

test('the architecture checker finds internal references in PHP name tokens', function (string $source): void {
    expect(ArchitectureChecker::internalReferences($source, ['Base32']))->toBe(['Base32']);
})->with([
    'parameter type' => <<<'PHP'
        <?php
        namespace TypeID;
        function decode(Base32 $codec): void {}
        PHP,
    'by-reference parameter type' => <<<'PHP'
        <?php
        namespace TypeID;
        function decode(Base32 &$codec): void {}
        PHP,
    'variadic parameter type' => <<<'PHP'
        <?php
        namespace TypeID;
        function decode(Base32 ...$codecs): void {}
        PHP,
    'intersection parameter type' => <<<'PHP'
        <?php
        namespace TypeID;
        function decode(Base32&Codec $codec): void {}
        PHP,
    'fully qualified construction' => <<<'PHP'
        <?php
        new \TypeID\Base32();
        PHP,
    'aliased import' => <<<'PHP'
        <?php
        use TypeID\Base32 as Codec;
        Codec::decodeBytes('');
        PHP,
    'group import' => <<<'PHP'
        <?php
        use TypeID\{Base32 as Codec, TypeID};
        PHP,
    'class after function in mixed group import' => <<<'PHP'
        <?php
        use TypeID\{function helper, Base32 as Codec};
        PHP,
    'class constant' => <<<'PHP'
        <?php
        $name = TypeID\Base32::class;
        PHP,
    'attribute' => <<<'PHP'
        <?php
        #[Base32]
        final class Example {}
        PHP,
    'grouped attribute with arguments' => <<<'PHP'
        <?php
        #[Other, \TypeID\Base32()]
        final class Example {}
        PHP,
]);

test('the architecture checker ignores non-class names, comments, and strings', function (): void {
    $source = <<<'PHP'
        <?php
        namespace Example;
        function Base32(): void {}
        Base32();
        const Base32 = 'value';
        echo Base32;
        $object->Base32();
        Example::Base32();
        // Base32::decodeBytes($value);
        /** @var Base32 $codec */
        use function TypeID\Base32;
        use function TypeID\{Base32, helper};
        use TypeID\{function Base32, const Other};
        use const TypeID\Other\Base32;
        $literal = 'new TypeID\\Base32()';
        PHP;

    expect(ArchitectureChecker::internalReferences($source, ['Base32']))->toBe([]);
});
