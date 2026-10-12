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
use Silarhi\Qif\Enums\DetailItems;

#[CoversClass(DetailItems::class)]
final class DetailItemsTest extends TestCase
{
    public function testCommonFieldValues(): void
    {
        self::assertSame('D', DetailItems::D->value);
        self::assertSame('T', DetailItems::T->value);
        self::assertSame('U', DetailItems::U->value);
        self::assertSame('M', DetailItems::M->value);
        self::assertSame('C', DetailItems::C->value);
    }

    public function testBankingFieldValues(): void
    {
        self::assertSame('N', DetailItems::N->value);
        self::assertSame('A', DetailItems::A->value);
        self::assertSame('L', DetailItems::L->value);
        self::assertSame('P', DetailItems::P->value);
        self::assertSame('F', DetailItems::F->value);
    }

    public function testSplitFieldValues(): void
    {
        self::assertSame('$', DetailItems::AMNT->value);
        self::assertSame('S', DetailItems::S->value);
        self::assertSame('E', DetailItems::E->value);
        self::assertSame('%', DetailItems::PERC->value);
    }

    public function testInvestmentFieldValues(): void
    {
        self::assertSame('Y', DetailItems::Y->value);
        self::assertSame('I', DetailItems::I->value);
        self::assertSame('Q', DetailItems::Q->value);
        self::assertSame('O', DetailItems::O->value);
    }

    public function testCategoryFieldValue(): void
    {
        self::assertSame('B', DetailItems::B->value);
    }

    public function testInvoiceFieldValues(): void
    {
        self::assertSame('X', DetailItems::X->value);
        self::assertSame('XA', DetailItems::XA->value);
        self::assertSame('XI', DetailItems::XI->value);
        self::assertSame('XE', DetailItems::XE->value);
        self::assertSame('XC', DetailItems::XC->value);
        self::assertSame('XR', DetailItems::XR->value);
        self::assertSame('XT', DetailItems::XT->value);
        self::assertSame('XS', DetailItems::XS->value);
        self::assertSame('XN', DetailItems::XN->value);
        self::assertSame('X#', DetailItems::X_HASH->value);
        self::assertSame('X$', DetailItems::X_AMNT->value);
        self::assertSame('XF', DetailItems::XF->value);
    }

    public function testAllCasesExist(): void
    {
        $cases = DetailItems::cases();

        // We have 31 detail item cases
        self::assertCount(31, $cases);
    }

    public function testTryFromValidValues(): void
    {
        self::assertSame(DetailItems::D, DetailItems::tryFrom('D'));
        self::assertSame(DetailItems::AMNT, DetailItems::tryFrom('$'));
        self::assertSame(DetailItems::X_HASH, DetailItems::tryFrom('X#'));
    }

    #[DataProvider('provideInvalidValues')]
    public function testTryFromInvalidValue(string $value): void
    {
        self::assertNull(DetailItems::tryFrom($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'unknown code' => ['Z'];
    }
}
