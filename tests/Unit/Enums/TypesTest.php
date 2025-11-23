<?php

declare(strict_types=1);

/*
 * This file is part of the QIF Library package.
 *
 * (c) Mário Čechovič <mimographix@gmail.com>
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace MimoGraphix\QIF\Tests\Unit\Enums;

use MimoGraphix\QIF\Enums\Types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Types::class)]
final class TypesTest extends TestCase
{
    public function testCashValue(): void
    {
        $this->assertSame('Cash', Types::CASH->value);
    }

    public function testBankValue(): void
    {
        $this->assertSame('Bank', Types::BANK->value);
    }

    public function testCCardValue(): void
    {
        $this->assertSame('CCard', Types::CCARD->value);
    }

    public function testInvstValue(): void
    {
        $this->assertSame('Invst', Types::INVST->value);
    }

    public function testOthAValue(): void
    {
        $this->assertSame('Oth A', Types::OTHA->value);
    }

    public function testOthLValue(): void
    {
        $this->assertSame('Oth L', Types::OTHL->value);
    }

    public function testInvoiceValue(): void
    {
        $this->assertSame('Invoice', Types::Invoice->value);
    }

    public function testAllCasesExist(): void
    {
        $cases = Types::cases();

        $this->assertCount(7, $cases);
        $this->assertContains(Types::CASH, $cases);
        $this->assertContains(Types::BANK, $cases);
        $this->assertContains(Types::CCARD, $cases);
        $this->assertContains(Types::INVST, $cases);
        $this->assertContains(Types::OTHA, $cases);
        $this->assertContains(Types::OTHL, $cases);
        $this->assertContains(Types::Invoice, $cases);
    }

    public function testTryFromValidValues(): void
    {
        $this->assertSame(Types::CASH, Types::tryFrom('Cash'));
        $this->assertSame(Types::BANK, Types::tryFrom('Bank'));
        $this->assertSame(Types::CCARD, Types::tryFrom('CCard'));
        $this->assertSame(Types::INVST, Types::tryFrom('Invst'));
        $this->assertSame(Types::OTHA, Types::tryFrom('Oth A'));
        $this->assertSame(Types::OTHL, Types::tryFrom('Oth L'));
        $this->assertSame(Types::Invoice, Types::tryFrom('Invoice'));
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(Types::tryFrom('InvalidType'));
    }
}
