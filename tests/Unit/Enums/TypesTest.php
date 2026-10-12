<?php

declare(strict_types=1);

/*
 * This file is part of the QIF Library package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silarhi\Qif\Tests\Unit\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\Qif\Enums\Types;

#[CoversClass(Types::class)]
final class TypesTest extends TestCase
{
    public function testCashValue(): void
    {
        self::assertSame('Cash', Types::Cash->value);
    }

    public function testBankValue(): void
    {
        self::assertSame('Bank', Types::Bank->value);
    }

    public function testCCardValue(): void
    {
        self::assertSame('CCard', Types::CreditCard->value);
    }

    public function testInvstValue(): void
    {
        self::assertSame('Invst', Types::Investment->value);
    }

    public function testOthAValue(): void
    {
        self::assertSame('Oth A', Types::OtherAsset->value);
    }

    public function testOthLValue(): void
    {
        self::assertSame('Oth L', Types::OtherLiability->value);
    }

    public function testInvoiceValue(): void
    {
        self::assertSame('Invoice', Types::Invoice->value);
    }

    public function testAllCasesExist(): void
    {
        $cases = Types::cases();

        self::assertCount(7, $cases);
        self::assertContains(Types::Cash, $cases);
        self::assertContains(Types::Bank, $cases);
        self::assertContains(Types::CreditCard, $cases);
        self::assertContains(Types::Investment, $cases);
        self::assertContains(Types::OtherAsset, $cases);
        self::assertContains(Types::OtherLiability, $cases);
        self::assertContains(Types::Invoice, $cases);
    }

    public function testTryFromValidValues(): void
    {
        self::assertSame(Types::Cash, Types::tryFrom('Cash'));
        self::assertSame(Types::Bank, Types::tryFrom('Bank'));
        self::assertSame(Types::CreditCard, Types::tryFrom('CCard'));
        self::assertSame(Types::Investment, Types::tryFrom('Invst'));
        self::assertSame(Types::OtherAsset, Types::tryFrom('Oth A'));
        self::assertSame(Types::OtherLiability, Types::tryFrom('Oth L'));
        self::assertSame(Types::Invoice, Types::tryFrom('Invoice'));
    }

    #[DataProvider('provideInvalidValues')]
    public function testTryFromInvalidValue(string $value): void
    {
        self::assertNull(Types::tryFrom($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'unknown code' => ['InvalidType'];
    }
}
