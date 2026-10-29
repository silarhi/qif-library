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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\Qif\Enums\DateFormat;
use Silarhi\Qif\Enums\Status;
use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Transaction;

use function sprintf;

#[CoversClass(Transaction::class)]
final class TransactionTest extends TestCase
{
    public function testConstructorSetsType(): void
    {
        $transaction = new Transaction(Types::Bank);

        self::assertSame(Types::Bank, $transaction->getType());
    }

    public function testConstructorSetsDefaultStatus(): void
    {
        $transaction = new Transaction(Types::Bank);

        self::assertSame(Status::NotCleared, $transaction->getStatus());
    }

    public function testSetAndGetDate(): void
    {
        $transaction = new Transaction(Types::Bank);
        $date = new DateTimeImmutable('2023-12-25');

        $result = $transaction->setDate($date);

        self::assertSame($transaction, $result, 'Should return self for fluent interface');
        self::assertSame($date, $transaction->getDate());
    }

    public function testSetAndGetDescription(): void
    {
        $transaction = new Transaction(Types::Bank);

        $result = $transaction->setDescription('Test payment');

        self::assertSame($transaction, $result);
        self::assertSame('Test payment', $transaction->getDescription());
    }

    public function testSetAndGetAmount(): void
    {
        $transaction = new Transaction(Types::Bank);

        $result = $transaction->setAmount(123.45);

        self::assertSame($transaction, $result);
        self::assertSame(123.45, $transaction->getAmount());
    }

    public function testSetAmountAcceptsIntAndString(): void
    {
        $transaction = new Transaction(Types::Bank);

        $transaction->setAmount(100);
        self::assertSame(100.0, $transaction->getAmount());

        $transaction->setAmount('250.75');
        self::assertSame(250.75, $transaction->getAmount());
    }

    public function testSetAmountReadsThousandsSeparators(): void
    {
        $transaction = new Transaction(Types::Bank);

        $transaction->setAmount('1,234.50');
        self::assertSame(1234.5, $transaction->getAmount());

        $transaction->setAmount('-1.234,50');
        self::assertSame(-1234.5, $transaction->getAmount());
    }

    public function testSetAmountRejectsAmbiguousString(): void
    {
        $transaction = new Transaction(Types::Bank);

        $this->expectException(InvalidArgumentException::class);

        $transaction->setAmount('1,234');
    }

    public function testSetAmountRejectsNonNumericString(): void
    {
        $transaction = new Transaction(Types::Bank);

        $this->expectException(InvalidArgumentException::class);

        $transaction->setAmount('twelve');
    }

    public function testSetAmountRejectsNonFiniteFloat(): void
    {
        $transaction = new Transaction(Types::Bank);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('INF is not an amount.');

        $transaction->setAmount(\INF);
    }

    public function testAddSplitRejectsNonFiniteFloat(): void
    {
        $transaction = new Transaction(Types::Bank);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('NAN is not an amount.');

        $transaction->addSplit('Tax', \NAN);
    }

    /**
     * @return iterable<string, array{callable(Transaction, string): Transaction, string}>
     */
    public static function provideSingleLineFields(): iterable
    {
        yield 'payee' => [static fn (Transaction $transaction, string $value): Transaction => $transaction->setDescription($value), 'payee'];
        yield 'category' => [static fn (Transaction $transaction, string $value): Transaction => $transaction->setCategory($value), 'category'];
        yield 'memo' => [static fn (Transaction $transaction, string $value): Transaction => $transaction->setMemo($value), 'memo'];
        yield 'split category' => [static fn (Transaction $transaction, string $value): Transaction => $transaction->addSplit($value, 10), 'split category'];
        yield 'split memo' => [static fn (Transaction $transaction, string $value): Transaction => $transaction->addSplit('Food', 10, $value), 'split memo'];
    }

    /**
     * @param callable(Transaction, string): Transaction $set
     */
    #[DataProvider('provideSingleLineFields')]
    public function testSingleLineFieldsRejectLineBreaks(callable $set, string $field): void
    {
        // A line break would end the field: the rest would be written as a forged record
        foreach (["Coffee\r\n^\r\n!Type:Bank\r\nT999999\r\nPInjected", "Coffee\nLHacked", "Coffee\rLHacked"] as $value) {
            try {
                $set(new Transaction(Types::Bank), $value);
                self::fail(sprintf('The %s should reject a line break.', $field));
            } catch (InvalidArgumentException $e) {
                self::assertStringStartsWith(sprintf('The %s "Coffee\\', $field), $e->getMessage());
                self::assertStringEndsWith('" contains a line break: QIF fields are single lines.', $e->getMessage());
            }
        }
    }

    public function testLineBreakMessageEscapesTheValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The memo "Coffee\r\n^" contains a line break: QIF fields are single lines.');

        (new Transaction(Types::Bank))->setMemo("Coffee\r\n^");
    }

    public function testSingleLineFieldsAcceptNull(): void
    {
        $transaction = (new Transaction(Types::Bank))
            ->setDescription('Payee')->setDescription(null)
            ->setCategory('Food')->setCategory(null)
            ->setMemo('Memo')->setMemo(null);

        self::assertNull($transaction->getDescription());
        self::assertNull($transaction->getCategory());
        self::assertNull($transaction->getMemo());
    }

    public function testSetAndGetCategory(): void
    {
        $transaction = new Transaction(Types::Bank);

        $result = $transaction->setCategory('Fuel:Car');

        self::assertSame($transaction, $result);
        self::assertSame('Fuel:Car', $transaction->getCategory());
    }

    public function testSetAndGetAddress(): void
    {
        $transaction = new Transaction(Types::Bank);

        $result = $transaction->setAddress('123 Main St');

        self::assertSame($transaction, $result);
        self::assertSame('123 Main St', $transaction->getAddress());
    }

    public function testSetAndGetMemo(): void
    {
        $transaction = new Transaction(Types::Bank);

        $result = $transaction->setMemo('Test memo');

        self::assertSame($transaction, $result);
        self::assertSame('Test memo', $transaction->getMemo());
    }

    public function testSetStatus(): void
    {
        $transaction = new Transaction(Types::Bank);

        $result = $transaction->setStatus(Status::Cleared);

        self::assertSame($transaction, $result);
        self::assertSame(Status::Cleared, $transaction->getStatus());
    }

    public function testMarkAsReconciled(): void
    {
        $transaction = new Transaction(Types::Bank);

        $result = $transaction->markAsReconciled();

        self::assertSame($transaction, $result);
        self::assertSame(Status::Reconciled, $transaction->getStatus());
    }

    public function testMarkAsCleared(): void
    {
        $transaction = new Transaction(Types::Bank);

        $result = $transaction->markAsCleared();

        self::assertSame($transaction, $result);
        self::assertSame(Status::Cleared, $transaction->getStatus());
    }

    public function testMarkAsNotCleared(): void
    {
        $transaction = new Transaction(Types::Bank);
        $transaction->markAsReconciled();

        $result = $transaction->markAsNotCleared();

        self::assertSame($transaction, $result);
        self::assertSame(Status::NotCleared, $transaction->getStatus());
    }

    public function testAddSplit(): void
    {
        $transaction = new Transaction(Types::Bank);

        $result = $transaction->addSplit('Product', 100.50, 'Product sale');

        self::assertSame($transaction, $result);
        self::assertSame([['category' => 'Product', 'amount' => 100.5, 'memo' => 'Product sale']], $transaction->getSplits());
    }

    public function testAddSplitWithNullMemo(): void
    {
        $transaction = new Transaction(Types::Bank);

        $transaction->addSplit('Tax', 20.0);

        self::assertSame([['category' => 'Tax', 'amount' => 20.0, 'memo' => null]], $transaction->getSplits());
    }

    public function testAddSplitKeepsSplitsOfTheSameCategory(): void
    {
        $transaction = new Transaction(Types::Bank);

        $transaction->addSplit('Groceries', 30.0, 'Food')
            ->addSplit('Groceries', 12.5, 'Cleaning');

        self::assertSame([
            ['category' => 'Groceries', 'amount' => 30.0, 'memo' => 'Food'],
            ['category' => 'Groceries', 'amount' => 12.5, 'memo' => 'Cleaning'],
        ], $transaction->getSplits());
    }

    public function testAddSplitReadsAmountWithThousandsSeparator(): void
    {
        $transaction = new Transaction(Types::Bank);

        $transaction->addSplit('Rent', '1,234.50');

        self::assertSame(1234.5, $transaction->getSplits()[0]['amount']);
    }

    public function testRemoveSplit(): void
    {
        $transaction = new Transaction(Types::Bank);
        $transaction->addSplit('Product', 100.0);
        $transaction->addSplit('Tax', 20.0);
        $transaction->addSplit('Product', 50.0);

        $result = $transaction->removeSplit('Product');

        self::assertSame($transaction, $result);
        self::assertSame([['category' => 'Tax', 'amount' => 20.0, 'memo' => null]], $transaction->getSplits());
    }

    public function testToStringWithFullTransaction(): void
    {
        $transaction = new Transaction(Types::Bank);
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
        $transaction = new Transaction(Types::Bank);
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
        $transaction = new Transaction(Types::Bank);
        $transaction->setAmount(-50.0)
            ->setDescription('Standard Oil')
            ->setAddress("Standard Oil\n101 Main St.")
            ->setMemo('Fuel for car');

        self::assertSame(implode(\PHP_EOL, [
            '!Type:Bank',
            'T-50.00',
            'C',
            'PStandard Oil',
            'AStandard Oil',
            'A101 Main St.',
            'MFuel for car',
            '^',
        ]), (string) $transaction);
    }

    /**
     * @return iterable<string, array{float, string}>
     */
    public static function provideWrittenAmounts(): iterable
    {
        yield 'integer' => [-10.0, '-10.00'];
        yield 'one decimal' => [-1234.5, '-1234.50'];
        yield 'three decimals' => [1.234, '1.234'];
        yield 'float error' => [0.1 + 0.2, '0.30'];
        yield 'zero' => [0.0, '0.00'];
        yield 'fourteen digits' => [12345678901234.56, '12345678901234.56'];
        yield 'fifteen digits, no exponent' => [123456789012345.67, '123456789012345.67'];
        yield 'twenty digits' => [1e20, '100000000000000000000.00'];
        yield 'small' => [-0.001, '-0.001'];
    }

    #[DataProvider('provideWrittenAmounts')]
    public function testToQifWritesAmountsWithoutRoundingOrExponent(float $amount, string $expected): void
    {
        $transaction = (new Transaction(Types::Bank))->setAmount($amount)->addSplit('Food', $amount);

        self::assertSame("!Type:Bank\nT{$expected}\nSFood\n\${$expected}\nC\n^", str_replace(\PHP_EOL, "\n", $transaction->toQif()));
    }

    public function testToQifWritesTheDateInTheGivenFormat(): void
    {
        $transaction = (new Transaction(Types::Bank))->setDate(new DateTimeImmutable('2026-01-31'));

        self::assertSame("!Type:Bank\nD31/01/2026\nC\n^", str_replace(\PHP_EOL, "\n", $transaction->toQif()));
        self::assertSame("!Type:Bank\nD01/31/2026\nC\n^", str_replace(\PHP_EOL, "\n", $transaction->toQif(DateFormat::MonthFirst)));
        self::assertSame($transaction->toQif(), (string) $transaction);
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
        $transaction = new Transaction(Types::Cash);
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
