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
        self::assertSame('D', DetailItems::Date->value);
        self::assertSame('T', DetailItems::Amount->value);
        self::assertSame('U', DetailItems::AmountAlt->value);
        self::assertSame('M', DetailItems::Memo->value);
        self::assertSame('C', DetailItems::ClearedStatus->value);
    }

    public function testBankingFieldValues(): void
    {
        self::assertSame('N', DetailItems::CheckNumber->value);
        self::assertSame('A', DetailItems::Address->value);
        self::assertSame('L', DetailItems::Category->value);
        self::assertSame('P', DetailItems::Payee->value);
        self::assertSame('F', DetailItems::Reimbursable->value);
    }

    public function testSplitFieldValues(): void
    {
        self::assertSame('$', DetailItems::SplitAmount->value);
        self::assertSame('S', DetailItems::SplitCategory->value);
        self::assertSame('E', DetailItems::SplitMemo->value);
        self::assertSame('%', DetailItems::SplitPercent->value);
    }

    public function testInvestmentFieldValues(): void
    {
        self::assertSame('Y', DetailItems::Security->value);
        self::assertSame('I', DetailItems::Price->value);
        self::assertSame('Q', DetailItems::Quantity->value);
        self::assertSame('O', DetailItems::Commission->value);
    }

    public function testCategoryFieldValue(): void
    {
        self::assertSame('B', DetailItems::BudgetedAmount->value);
    }

    public function testInvoiceFieldValues(): void
    {
        self::assertSame('X', DetailItems::Extended->value);
        self::assertSame('XA', DetailItems::InvoiceShipToAddress->value);
        self::assertSame('XI', DetailItems::InvoiceType->value);
        self::assertSame('XE', DetailItems::InvoiceDueDate->value);
        self::assertSame('XC', DetailItems::InvoiceTaxAccount->value);
        self::assertSame('XR', DetailItems::InvoiceTaxRate->value);
        self::assertSame('XT', DetailItems::InvoiceTaxAmount->value);
        self::assertSame('XS', DetailItems::InvoiceLineDescription->value);
        self::assertSame('XN', DetailItems::InvoiceLineCategory->value);
        self::assertSame('X#', DetailItems::InvoiceLineQuantity->value);
        self::assertSame('X$', DetailItems::InvoiceLinePrice->value);
        self::assertSame('XF', DetailItems::InvoiceLineTaxable->value);
    }

    public function testAllCasesExist(): void
    {
        $cases = DetailItems::cases();

        // We have 31 detail item cases
        self::assertCount(31, $cases);
    }

    public function testTryFromValidValues(): void
    {
        self::assertSame(DetailItems::Date, DetailItems::tryFrom('D'));
        self::assertSame(DetailItems::SplitAmount, DetailItems::tryFrom('$'));
        self::assertSame(DetailItems::InvoiceLineQuantity, DetailItems::tryFrom('X#'));
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
