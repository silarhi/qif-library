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

namespace Silarhi\Qif\Tests\Unit;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Silarhi\Qif\Enums\Status;
use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Transaction;

#[CoversClass(Transaction::class)]
final class TransactionTest extends TestCase
{
    public function testConstructorSetsType(): void
    {
        $transaction = new Transaction(Types::BANK);

        self::assertSame(Types::BANK, $transaction->getType());
    }

    public function testConstructorSetsDefaultStatus(): void
    {
        $transaction = new Transaction(Types::BANK);

        self::assertSame(Status::NOT_CLEARED, $transaction->getStatus());
    }

    public function testSetAndGetDate(): void
    {
        $transaction = new Transaction(Types::BANK);
        $date = new DateTimeImmutable('2023-12-25');

        $result = $transaction->setDate($date);

        self::assertSame($transaction, $result, 'Should return self for fluent interface');
        self::assertSame($date, $transaction->getDate());
    }

    public function testSetAndGetDescription(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setDescription('Test payment');

        self::assertSame($transaction, $result);
        self::assertSame('Test payment', $transaction->getDescription());
    }

    public function testSetAndGetAmount(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setAmount(123.45);

        self::assertSame($transaction, $result);
        self::assertSame(123.45, $transaction->getAmount());
    }

    public function testSetAmountAcceptsIntAndString(): void
    {
        $transaction = new Transaction(Types::BANK);

        $transaction->setAmount(100);
        self::assertSame(100.0, $transaction->getAmount());

        $transaction->setAmount('250.75');
        self::assertSame(250.75, $transaction->getAmount());
    }

    public function testSetAmountReadsThousandsSeparators(): void
    {
        $transaction = new Transaction(Types::BANK);

        $transaction->setAmount('1,234.50');
        self::assertSame(1234.5, $transaction->getAmount());

        $transaction->setAmount('-1.234,50');
        self::assertSame(-1234.5, $transaction->getAmount());
    }

    public function testSetAmountRejectsAmbiguousString(): void
    {
        $transaction = new Transaction(Types::BANK);

        $this->expectException(InvalidArgumentException::class);

        $transaction->setAmount('1,234');
    }

    public function testSetAmountRejectsNonNumericString(): void
    {
        $transaction = new Transaction(Types::BANK);

        $this->expectException(InvalidArgumentException::class);

        $transaction->setAmount('twelve');
    }

    public function testSetAndGetCategory(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setCategory('Fuel:Car');

        self::assertSame($transaction, $result);
        self::assertSame('Fuel:Car', $transaction->getCategory());
    }

    public function testSetAndGetAddress(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setAddress('123 Main St');

        self::assertSame($transaction, $result);
        self::assertSame('123 Main St', $transaction->getAddress());
    }

    public function testSetAndGetMemo(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setMemo('Test memo');

        self::assertSame($transaction, $result);
        self::assertSame('Test memo', $transaction->getMemo());
    }

    public function testSetStatus(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->setStatus(Status::CLEARED);

        self::assertSame($transaction, $result);
        self::assertSame(Status::CLEARED, $transaction->getStatus());
    }

    public function testMarkAsReconciled(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->markAsReconciled();

        self::assertSame($transaction, $result);
        self::assertSame(Status::RECONCILED, $transaction->getStatus());
    }

    public function testMarkAsCleared(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->markAsCleared();

        self::assertSame($transaction, $result);
        self::assertSame(Status::CLEARED, $transaction->getStatus());
    }

    public function testMarkAsNotCleared(): void
    {
        $transaction = new Transaction(Types::BANK);
        $transaction->markAsReconciled();

        $result = $transaction->markAsNotCleared();

        self::assertSame($transaction, $result);
        self::assertSame(Status::NOT_CLEARED, $transaction->getStatus());
    }

    public function testAddSplit(): void
    {
        $transaction = new Transaction(Types::BANK);

        $result = $transaction->addSplit('Product', 100.50, 'Product sale');

        self::assertSame($transaction, $result);
        self::assertSame([['category' => 'Product', 'amount' => 100.5, 'memo' => 'Product sale']], $transaction->getSplits());
    }

    public function testAddSplitWithNullMemo(): void
    {
        $transaction = new Transaction(Types::BANK);

        $transaction->addSplit('Tax', 20.0);

        self::assertSame([['category' => 'Tax', 'amount' => 20.0, 'memo' => null]], $transaction->getSplits());
    }

    public function testAddSplitKeepsSplitsOfTheSameCategory(): void
    {
        $transaction = new Transaction(Types::BANK);

        $transaction->addSplit('Groceries', 30.0, 'Food')
            ->addSplit('Groceries', 12.5, 'Cleaning');

        self::assertSame([
            ['category' => 'Groceries', 'amount' => 30.0, 'memo' => 'Food'],
            ['category' => 'Groceries', 'amount' => 12.5, 'memo' => 'Cleaning'],
        ], $transaction->getSplits());
    }

    public function testAddSplitReadsAmountWithThousandsSeparator(): void
    {
        $transaction = new Transaction(Types::BANK);

        $transaction->addSplit('Rent', '1,234.50');

        self::assertSame(1234.5, $transaction->getSplits()[0]['amount']);
    }

    public function testRemoveSplit(): void
    {
        $transaction = new Transaction(Types::BANK);
        $transaction->addSplit('Product', 100.0);
        $transaction->addSplit('Tax', 20.0);
        $transaction->addSplit('Product', 50.0);

        $result = $transaction->removeSplit('Product');

        self::assertSame($transaction, $result);
        self::assertSame([['category' => 'Tax', 'amount' => 20.0, 'memo' => null]], $transaction->getSplits());
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

        self::assertStringContainsString('!Type:Bank', $output);
        self::assertStringContainsString('D25/12/2023', $output);
        self::assertStringContainsString('T-1234.5', $output);
        self::assertStringContainsString('LFuel:Car', $output);
        self::assertStringContainsString('CX', $output);
        self::assertStringContainsString('PStandard Oil', $output);
        self::assertStringContainsString('^', $output);
    }

    public function testToStringWithSplits(): void
    {
        $transaction = new Transaction(Types::BANK);
        $transaction->setAmount(120.0)
            ->addSplit('Product', 100.0, 'Item')
            ->addSplit('Tax', 20.0, 'VAT');

        $output = (string) $transaction;

        self::assertStringContainsString('SProduct', $output);
        self::assertStringContainsString('$100', $output);
        self::assertStringContainsString('STax', $output);
        self::assertStringContainsString('$20', $output);
        self::assertStringContainsString('EVAT', $output);
    }

    public function testToStringWritesMemoAndAddress(): void
    {
        $transaction = new Transaction(Types::BANK);
        $transaction->setAmount(-50.0)
            ->setDescription('Standard Oil')
            ->setAddress("Standard Oil\n101 Main St.")
            ->setMemo('Fuel for car');

        self::assertSame(implode(\PHP_EOL, [
            '!Type:Bank',
            'T-50',
            'C',
            'PStandard Oil',
            'AStandard Oil',
            'A101 Main St.',
            'MFuel for car',
            '^',
        ]), (string) $transaction);
    }

    public function testToStringWithNullType(): void
    {
        $transaction = new Transaction(null);
        $transaction->setAmount(100.0);

        $output = (string) $transaction;

        self::assertStringContainsString('!Type:', $output);
        self::assertStringContainsString('T100', $output);
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

        self::assertSame($transaction, $result);
    }
}
