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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Transaction;
use Silarhi\Qif\Writer;
use Stringable;

#[CoversClass(Writer::class)]
final class WriterTest extends TestCase
{
    public function testAddTransaction(): void
    {
        $writer = new Writer();
        $transaction = new Transaction(Types::BANK);

        $writer->addTransaction($transaction);

        self::assertCount(1, $writer->getTransactions());
        self::assertSame($transaction, $writer->getTransactions()[0]);
    }

    public function testAddMultipleTransactions(): void
    {
        $writer = new Writer();
        $transaction1 = new Transaction(Types::BANK);
        $transaction2 = new Transaction(Types::CASH);

        $writer->addTransaction($transaction1);
        $writer->addTransaction($transaction2);

        $transactions = $writer->getTransactions();
        self::assertCount(2, $transactions);
        self::assertSame($transaction1, $transactions[0]);
        self::assertSame($transaction2, $transactions[1]);
    }

    public function testGetTransactionsReturnsEmptyArrayInitially(): void
    {
        $writer = new Writer();

        self::assertSame([], $writer->getTransactions());
    }

    public function testToStringWithSingleTransaction(): void
    {
        $writer = new Writer();
        $transaction = new Transaction(Types::BANK);
        $date = new DateTimeImmutable('2023-12-25');

        $transaction->setDate($date)
            ->setAmount(-1234.50)
            ->setDescription('Standard Oil');

        $writer->addTransaction($transaction);

        $output = (string) $writer;

        self::assertStringContainsString('!Type:Bank', $output);
        self::assertStringContainsString('D25/12/2023', $output);
        self::assertStringContainsString('T-1234.5', $output);
        self::assertStringContainsString('PStandard Oil', $output);
        self::assertStringContainsString('^', $output);
    }

    public function testToStringWithMultipleTransactions(): void
    {
        $writer = new Writer();

        $transaction1 = new Transaction(Types::BANK);
        $transaction1->setDate(new DateTimeImmutable('2023-12-25'))
            ->setAmount(-100.0)
            ->setDescription('First');

        $transaction2 = new Transaction(Types::CASH);
        $transaction2->setDate(new DateTimeImmutable('2023-12-26'))
            ->setAmount(-200.0)
            ->setDescription('Second');

        $writer->addTransaction($transaction1);
        $writer->addTransaction($transaction2);

        $output = (string) $writer;

        self::assertStringContainsString('!Type:Bank', $output);
        self::assertStringContainsString('PFirst', $output);
        self::assertStringContainsString('!Type:Cash', $output);
        self::assertStringContainsString('PSecond', $output);

        // Both transactions should have their own end markers
        self::assertSame(2, substr_count($output, '^'));
    }

    public function testToStringWithEmptyWriter(): void
    {
        $writer = new Writer();

        $output = (string) $writer;

        self::assertSame('', $output);
    }

    public function testSaveToFile(): void
    {
        $writer = new Writer();
        $transaction = new Transaction(Types::BANK);
        $transaction->setAmount(100.0);

        $writer->addTransaction($transaction);

        $tempFile = sys_get_temp_dir() . '/test_qif_' . uniqid() . '.qif';

        try {
            $writer->saveToFile($tempFile);

            self::assertFileExists($tempFile);

            $content = file_get_contents($tempFile);
            self::assertIsString($content);
            self::assertStringContainsString('!Type:Bank', $content);
            self::assertStringContainsString('T100', $content);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testSaveToFileThrowsExceptionForInvalidPath(): void
    {
        $writer = new Writer();
        $transaction = new Transaction(Types::BANK);
        $writer->addTransaction($transaction);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Directory not writable:');

        $writer->saveToFile('/invalid/path/that/does/not/exist/file.qif');
    }

    public function testStringableInterface(): void
    {
        $writer = new Writer();
        $transaction = new Transaction(Types::BANK);
        $transaction->setAmount(100.0);

        $writer->addTransaction($transaction);

        // Test that Writer implements Stringable
        self::assertInstanceOf(Stringable::class, $writer);

        // Test implicit string conversion
        $output = (string) $writer;
        self::assertStringContainsString('T100', $output);
    }
}
