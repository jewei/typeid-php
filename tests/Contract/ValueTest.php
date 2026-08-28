<?php

declare(strict_types=1);

use TypeID\TypeID;

test('renders prefixed and bare forms', function (): void {
    $prefixed = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');
    $bare = new TypeID('', '01jsnsf2g7e2saxdjvz3j6tc3x');

    expect($prefixed->toString())->toBe('user_01jsnsf2g7e2saxdjvz3j6tc3x')
        ->and((string) $prefixed)->toBe('user_01jsnsf2g7e2saxdjvz3j6tc3x')
        ->and($bare->toString())->toBe('01jsnsf2g7e2saxdjvz3j6tc3x')
        ->and((string) $bare)->toBe('01jsnsf2g7e2saxdjvz3j6tc3x');
});

test('equality requires both prefix and suffix to match', function (): void {
    $typeId = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');

    expect($typeId->equals(new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x')))->toBeTrue()
        ->and($typeId->equals(new TypeID('post', '01jsnsf2g7e2saxdjvz3j6tc3x')))->toBeFalse()
        ->and($typeId->equals(new TypeID('user', '01jsnsfk97e6fs9587z73nax2r')))->toBeFalse();
});

test('zero TypeIDs with different prefixes are not equal', function (): void {
    expect(TypeID::zero('user')->equals(TypeID::zero('order')))->toBeFalse();
});

test('a zero TypeID never equals a populated one', function (): void {
    expect(TypeID::zero('user')->equals(new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x')))
        ->toBeFalse();
});

test('isZero and isNonZero are complementary', function (): void {
    $zero = TypeID::zero('user');
    $populated = new TypeID('user', '01jsnsf2g7e2saxdjvz3j6tc3x');

    expect($zero->isZero())->toBeTrue()
        ->and($zero->isNonZero())->toBeFalse()
        ->and($populated->isZero())->toBeFalse()
        ->and($populated->isNonZero())->toBeTrue();
});

test('a zero TypeID keeps its prefix', function (): void {
    expect(TypeID::zero('user')->toString())->toBe('user_'.TypeID::ZERO_SUFFIX);
});
