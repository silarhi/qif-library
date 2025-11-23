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

namespace MimoGraphix\QIF\Tests\Unit;

use DateTimeImmutable;
use Exception;
use MimoGraphix\QIF\Enums\Status;
use MimoGraphix\QIF\Enums\Types;
use MimoGraphix\QIF\Transaction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Transaction::class)]
final class TransactionTest extends TestCase
{
    public function testConstructorSetsType(): void
    {
        $transaction = new Transaction(Types::BANK);

        $this->assertSame(Types::BANK, $transaction->getType());
    }

    public function testConstructorSetsDefaultStatus(): void
    {
        $transaction = new Transaction(Types::BANK);

        $this->assertSame(Status::NOT_CLEARED, $transaction->getStatus());
    }

    public function testSetAndGetDate(): void
    {
        $transaction = new Transaction(Types::BANK);
        $date = new DateTimeImmutable('2023-12-25');

        $result = $transaction->setDate($date);

        $this->assertSame($transaction, $result, 'Should return self for fluent interface');
        $this->assertSame($date, $transaction->getDate());
    }

    public function testSetAndGetDescription(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setDescription('Test payment');

        $this->assertSame($transaction, $result);
        $this->assertSame('Test payment', $transaction->getDescription());
    }

    public function testSetAndGetAmount(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setAmount(123.45);

        $this->assertSame($transaction, $result);
        $this->assertSame(123.45, $transaction->getAmount());
    }

    public function testSetAmountAcceptsIntAndString(): void
    {
        $transaction = new Transaction(Types::BANK);

        $transaction->setAmount(100);
        $this->assertSame(100.0, $transaction->getAmount());

        $transaction->setAmount('250.75');
        $this->assertSame(250.75, $transaction->getAmount());
    }

    public function testSetAndGetCategory(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setCategory('Fuel:Car');

        $this->assertSame($transaction, $result);
        $this->assertSame('Fuel:Car', $transaction->getCategory());
    }

    public function testSetAndGetAddress(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setAddress('123 Main St');

        $this->assertSame($transaction, $result);
        $this->assertSame('123 Main St', $transaction->getAddress());
    }

    public function testSetAndGetMemo(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setMemo('Test memo');

        $this->assertSame($transaction, $result);
        $this->assertSame('Test memo', $transaction->getMemo());
    }

    public function testSetStatus(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setStatus(Status::CLEARED);

        $this->assertSame($transaction, $result);
        $this->assertSame(Status::CLEARED, $transaction->getStatus());
    }

    public function testMarkAsReconciled(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->markAsReconciled();

        $this->assertSame($transaction, $result);
        $this->assertSame(Status::RECONCILED, $transaction->getStatus());
    }

    public function testMarkAsCleared(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->markAsCleared();

        $this->assertSame($transaction, $result);
        $this->assertSame(Status::CLEARED, $transaction->getStatus());
    }

    public function testMarkAsNotCleared(): void
    {
        $transaction = new Transaction(Types::BANK);
        $transaction->markAsReconciled();

        $result = $transaction->markAsNotCleared();

        $this->assertSame($transaction, $result);
        $this->assertSame(Status::NOT_CLEARED, $transaction->getStatus());
    }

    public function testAddSplit(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->addSplit('Product', 100.50, 'Product sale');

        $this->assertSame($transaction, $result);

        $splits = $transaction->getSplits();
        $this->assertArrayHasKey('Product', $splits);
        $this->assertSame(100.5, $splits['Product']['amount']);
        $this->assertSame('Product sale', $splits['Product']['memo']);
    }

    public function testAddSplitWithNullMemo(): void
    {
        $transaction = new Transaction(Types::BANK);

        $transaction->addSplit('Tax', 20.0);

        $splits = $transaction->getSplits();
        $this->assertArrayHasKey('Tax', $splits);
        $this->assertSame(20.0, $splits['Tax']['amount']);
        $this->assertNull($splits['Tax']['memo']);
    }

    public function testAddSplitThrowsExceptionForDuplicateName(): void
    {
        $transaction = new Transaction(Types::BANK);
        $transaction->addSplit('Product', 100.0);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Split "Product" already exists in this transaction.');

        $transaction->addSplit('Product', 50.0);
    }

    public function testRemoveSplit(): void
    {
        $transaction = new Transaction(Types::BANK);
        $transaction->addSplit('Product', 100.0);
        $transaction->addSplit('Tax', 20.0);

        $result = $transaction->removeSplit('Product');

        $this->assertSame($transaction, $result);

        $splits = $transaction->getSplits();
        $this->assertArrayNotHasKey('Product', $splits);
        $this->assertArrayHasKey('Tax', $splits);
    }

    public function testToStringWithFullTransaction(): void
    {
        $transaction = new Transaction(Types::BANK);
        $date = new DateTimeImmutable('2023-12-25');

        $transaction->setDate($date)
            ->setAmount(-1234.50)
            ->setDescription('Standard Oil')
            ->setCategory('Fuel:Car')
            ->setMemo('Fuel for car')
            ->markAsReconciled();

        $output = (string) $transaction;

        $this->assertStringContainsString('!Type:Bank', $output);
        $this->assertStringContainsString('D25/12/2023', $output);
        $this->assertStringContainsString('T-1234.5', $output);
        $this->assertStringContainsString('LFuel:Car', $output);
        $this->assertStringContainsString('CX', $output);
        $this->assertStringContainsString('PStandard Oil', $output);
        $this->assertStringContainsString('^', $output);
    }

    public function testToStringWithSplits(): void
    {
        $transaction = new Transaction(Types::BANK);
        $transaction->setAmount(120.0)
            ->addSplit('Product', 100.0, 'Item')
            ->addSplit('Tax', 20.0, 'VAT');

        $output = (string) $transaction;

        $this->assertStringContainsString('SProduct', $output);
        $this->assertStringContainsString('$100', $output);
        $this->assertStringContainsString('STax', $output);
        $this->assertStringContainsString('$20', $output);
    }

    public function testToStringWithNullType(): void
    {
        $transaction = new Transaction(null);
        $transaction->setAmount(100.0);

        $output = (string) $transaction;

        $this->assertStringContainsString('!Type:', $output);
        $this->assertStringContainsString('T100', $output);
    }

    public function testFluentInterface(): void
    {
        $transaction = new Transaction(Types::CASH);
        $date = new DateTimeImmutable('2023-12-25');

        $result = $transaction
            ->setDate($date)
            ->setAmount(100.0)
            ->setDescription('Test')
            ->setCategory('Sales')
            ->setMemo('Note')
            ->setAddress('Address')
            ->markAsCleared()
            ->addSplit('Item', 100.0);

        $this->assertSame($transaction, $result);
    }
}
