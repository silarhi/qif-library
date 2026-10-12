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
        self::assertSame('D', DetailItems::DATE->value);
        self::assertSame('T', DetailItems::AMOUNT->value);
        self::assertSame('U', DetailItems::AMOUNT_ALT->value);
        self::assertSame('M', DetailItems::MEMO->value);
        self::assertSame('C', DetailItems::CLEARED_STATUS->value);
    }

    public function testBankingFieldValues(): void
    {
        self::assertSame('N', DetailItems::CHECK_NUMBER->value);
        self::assertSame('A', DetailItems::ADDRESS->value);
        self::assertSame('L', DetailItems::CATEGORY->value);
        self::assertSame('P', DetailItems::PAYEE->value);
        self::assertSame('F', DetailItems::REIMBURSABLE->value);
    }

    public function testSplitFieldValues(): void
    {
        self::assertSame('$', DetailItems::SPLIT_AMOUNT->value);
        self::assertSame('S', DetailItems::SPLIT_CATEGORY->value);
        self::assertSame('E', DetailItems::SPLIT_MEMO->value);
        self::assertSame('%', DetailItems::SPLIT_PERCENT->value);
    }

    public function testInvestmentFieldValues(): void
    {
        self::assertSame('Y', DetailItems::SECURITY->value);
        self::assertSame('I', DetailItems::PRICE->value);
        self::assertSame('Q', DetailItems::QUANTITY->value);
        self::assertSame('O', DetailItems::COMMISSION->value);
    }

    public function testCategoryFieldValue(): void
    {
        self::assertSame('B', DetailItems::BUDGETED_AMOUNT->value);
    }

    public function testInvoiceFieldValues(): void
    {
        self::assertSame('X', DetailItems::EXTENDED->value);
        self::assertSame('XA', DetailItems::INVOICE_SHIP_TO_ADDRESS->value);
        self::assertSame('XI', DetailItems::INVOICE_TYPE->value);
        self::assertSame('XE', DetailItems::INVOICE_DUE_DATE->value);
        self::assertSame('XC', DetailItems::INVOICE_TAX_ACCOUNT->value);
        self::assertSame('XR', DetailItems::INVOICE_TAX_RATE->value);
        self::assertSame('XT', DetailItems::INVOICE_TAX_AMOUNT->value);
        self::assertSame('XS', DetailItems::INVOICE_LINE_DESCRIPTION->value);
        self::assertSame('XN', DetailItems::INVOICE_LINE_CATEGORY->value);
        self::assertSame('X#', DetailItems::INVOICE_LINE_QUANTITY->value);
        self::assertSame('X$', DetailItems::INVOICE_LINE_PRICE->value);
        self::assertSame('XF', DetailItems::INVOICE_LINE_TAXABLE->value);
    }

    public function testAllCasesExist(): void
    {
        $cases = DetailItems::cases();

        // We have 31 detail item cases
        self::assertCount(31, $cases);
    }

    public function testTryFromValidValues(): void
    {
        self::assertSame(DetailItems::DATE, DetailItems::tryFrom('D'));
        self::assertSame(DetailItems::SPLIT_AMOUNT, DetailItems::tryFrom('$'));
        self::assertSame(DetailItems::INVOICE_LINE_QUANTITY, DetailItems::tryFrom('X#'));
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
