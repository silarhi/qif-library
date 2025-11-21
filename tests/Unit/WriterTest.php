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

namespace MimoGraphix\QIF\Tests\Unit;

use DateTimeImmutable;
use MimoGraphix\QIF\Enums\Types;
use MimoGraphix\QIF\Transaction;
use MimoGraphix\QIF\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stringable;

#[CoversClass(Writer::class)]
final class WriterTest extends TestCase
{
    public function testAddTransaction(): void
    {
        $writer = new Writer();
        $transaction = new Transaction(Types::BANK);

        $writer->addTransaction($transaction);

        $this->assertCount(1, $writer->getTransactions());
        $this->assertSame($transaction, $writer->getTransactions()[0]);
    }

    public function testAddMultipleTransactions(): void
    {
        $writer = new Writer();
        $transaction1 = new Transaction(Types::BANK);
        $transaction2 = new Transaction(Types::CASH);

        $writer->addTransaction($transaction1);
        $writer->addTransaction($transaction2);

        $transactions = $writer->getTransactions();
        $this->assertCount(2, $transactions);
        $this->assertSame($transaction1, $transactions[0]);
        $this->assertSame($transaction2, $transactions[1]);
    }

    public function testGetTransactionsReturnsEmptyArrayInitially(): void
    {
        $writer = new Writer();

        $this->assertSame([], $writer->getTransactions());
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

        $this->assertStringContainsString('!Type:Bank', $output);
        $this->assertStringContainsString('D25/12/2023', $output);
        $this->assertStringContainsString('T-1234.5', $output);
        $this->assertStringContainsString('PStandard Oil', $output);
        $this->assertStringContainsString('^', $output);
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

        $this->assertStringContainsString('!Type:Bank', $output);
        $this->assertStringContainsString('PFirst', $output);
        $this->assertStringContainsString('!Type:Cash', $output);
        $this->assertStringContainsString('PSecond', $output);

        // Both transactions should have their own end markers
        $this->assertSame(2, substr_count($output, '^'));
    }

    public function testToStringWithEmptyWriter(): void
    {
        $writer = new Writer();

        $output = (string) $writer;

        $this->assertSame('', $output);
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

            $this->assertFileExists($tempFile);

            $content = file_get_contents($tempFile);
            $this->assertIsString($content);
            $this->assertStringContainsString('!Type:Bank', $content);
            $this->assertStringContainsString('T100', $content);
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
        $this->assertInstanceOf(Stringable::class, $writer);

        // Test implicit string conversion
        $output = (string) $writer;
        $this->assertStringContainsString('T100', $output);
    }
}
