<?php

declare(strict_types=1);

/*
 * This file is part of the CFONB Parser package.
 *
 * (c) Mário Čechovič <mimographix@gmail.com>
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace MimoGraphix\QIF\Tests\Unit\Enums;

use MimoGraphix\QIF\Enums\DetailItems;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DetailItems::class)]
final class DetailItemsTest extends TestCase
{
    public function testCommonFieldValues(): void
    {
        $this->assertSame('D', DetailItems::D->value);
        $this->assertSame('T', DetailItems::T->value);
        $this->assertSame('U', DetailItems::U->value);
        $this->assertSame('M', DetailItems::M->value);
        $this->assertSame('C', DetailItems::C->value);
    }

    public function testBankingFieldValues(): void
    {
        $this->assertSame('N', DetailItems::N->value);
        $this->assertSame('A', DetailItems::A->value);
        $this->assertSame('L', DetailItems::L->value);
        $this->assertSame('P', DetailItems::P->value);
        $this->assertSame('F', DetailItems::F->value);
    }

    public function testSplitFieldValues(): void
    {
        $this->assertSame('$', DetailItems::AMNT->value);
        $this->assertSame('S', DetailItems::S->value);
        $this->assertSame('E', DetailItems::E->value);
        $this->assertSame('%', DetailItems::PERC->value);
    }

    public function testInvestmentFieldValues(): void
    {
        $this->assertSame('Y', DetailItems::Y->value);
        $this->assertSame('I', DetailItems::I->value);
        $this->assertSame('Q', DetailItems::Q->value);
        $this->assertSame('O', DetailItems::O->value);
    }

    public function testCategoryFieldValue(): void
    {
        $this->assertSame('B', DetailItems::B->value);
    }

    public function testInvoiceFieldValues(): void
    {
        $this->assertSame('X', DetailItems::X->value);
        $this->assertSame('XA', DetailItems::XA->value);
        $this->assertSame('XI', DetailItems::XI->value);
        $this->assertSame('XE', DetailItems::XE->value);
        $this->assertSame('XC', DetailItems::XC->value);
        $this->assertSame('XR', DetailItems::XR->value);
        $this->assertSame('XT', DetailItems::XT->value);
        $this->assertSame('XS', DetailItems::XS->value);
        $this->assertSame('XN', DetailItems::XN->value);
        $this->assertSame('X#', DetailItems::X_HASH->value);
        $this->assertSame('X$', DetailItems::X_AMNT->value);
        $this->assertSame('XF', DetailItems::XF->value);
    }

    public function testAllCasesExist(): void
    {
        $cases = DetailItems::cases();

        // We have 31 detail item cases
        $this->assertCount(31, $cases);
    }

    public function testTryFromValidValues(): void
    {
        $this->assertSame(DetailItems::D, DetailItems::tryFrom('D'));
        $this->assertSame(DetailItems::AMNT, DetailItems::tryFrom('$'));
        $this->assertSame(DetailItems::X_HASH, DetailItems::tryFrom('X#'));
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(DetailItems::tryFrom('Z'));
    }
}
