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
        self::assertSame('Cash', Types::CASH->value);
    }

    public function testBankValue(): void
    {
        self::assertSame('Bank', Types::BANK->value);
    }

    public function testCCardValue(): void
    {
        self::assertSame('CCard', Types::CREDIT_CARD->value);
    }

    public function testInvstValue(): void
    {
        self::assertSame('Invst', Types::INVESTMENT->value);
    }

    public function testOthAValue(): void
    {
        self::assertSame('Oth A', Types::OTHER_ASSET->value);
    }

    public function testOthLValue(): void
    {
        self::assertSame('Oth L', Types::OTHER_LIABILITY->value);
    }

    public function testInvoiceValue(): void
    {
        self::assertSame('Invoice', Types::INVOICE->value);
    }

    public function testAllCasesExist(): void
    {
        $cases = Types::cases();

        self::assertCount(7, $cases);
        self::assertContains(Types::CASH, $cases);
        self::assertContains(Types::BANK, $cases);
        self::assertContains(Types::CREDIT_CARD, $cases);
        self::assertContains(Types::INVESTMENT, $cases);
        self::assertContains(Types::OTHER_ASSET, $cases);
        self::assertContains(Types::OTHER_LIABILITY, $cases);
        self::assertContains(Types::INVOICE, $cases);
    }

    public function testTryFromValidValues(): void
    {
        self::assertSame(Types::CASH, Types::tryFrom('Cash'));
        self::assertSame(Types::BANK, Types::tryFrom('Bank'));
        self::assertSame(Types::CREDIT_CARD, Types::tryFrom('CCard'));
        self::assertSame(Types::INVESTMENT, Types::tryFrom('Invst'));
        self::assertSame(Types::OTHER_ASSET, Types::tryFrom('Oth A'));
        self::assertSame(Types::OTHER_LIABILITY, Types::tryFrom('Oth L'));
        self::assertSame(Types::INVOICE, Types::tryFrom('Invoice'));
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
