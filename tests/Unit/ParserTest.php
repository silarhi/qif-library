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

use function count;

use MimoGraphix\QIF\Enums\Status;
use MimoGraphix\QIF\Enums\Types;
use MimoGraphix\QIF\Parser;
use MimoGraphix\QIF\Transaction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Parser::class)]
final class ParserTest extends TestCase
{
    public function testParseSimpleTransaction(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D25/12/2023
T-1234.50
CX
PStandard Oil
MFuel for car
LFuel:Car
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertCount(1, $transactions);
        $this->assertInstanceOf(Transaction::class, $transactions[0]);
    }

    public function testParseTransactionFields(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D25/12/2023
T-1234.50
CX
PStandard Oil
AMain Street 123
MFuel for car
LFuel:Car
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $transaction = $transactions[0];

        $this->assertSame(Types::BANK, $transaction->getType());
        $this->assertSame('2023-12-25', $transaction->getDate()?->format('Y-m-d'));
        $this->assertSame(-1234.50, $transaction->getAmount());
        $this->assertSame(Status::RECONCILED, $transaction->getStatus());
        $this->assertSame('Standard Oil', $transaction->getDescription());
        $this->assertSame('Main Street 123', $transaction->getAddress());
        $this->assertSame('Fuel for car', $transaction->getMemo());
        $this->assertSame('Fuel:Car', $transaction->getCategory());
    }

    public function testParseMultipleTransactions(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D25/12/2023
T-100.00
PFirst Transaction
^
D26/12/2023
T-200.00
PSecond Transaction
^
D27/12/2023
T-300.00
PThird Transaction
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertCount(3, $transactions);
        $this->assertSame(-100.0, $transactions[0]->getAmount());
        $this->assertSame(-200.0, $transactions[1]->getAmount());
        $this->assertSame(-300.0, $transactions[2]->getAmount());
    }

    public function testParseClearedStatusVariations(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D01/01/2023
T-100.00
CX
^
D02/01/2023
T-100.00
CR
^
D03/01/2023
T-100.00
Cc
^
D04/01/2023
T-100.00
C*
^
D05/01/2023
T-100.00
C
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertSame(Status::RECONCILED, $transactions[0]->getStatus());
        $this->assertSame(Status::RECONCILED, $transactions[1]->getStatus());
        $this->assertSame(Status::CLEARED, $transactions[2]->getStatus());
        $this->assertSame(Status::CLEARED, $transactions[3]->getStatus());
        $this->assertSame(Status::NOT_CLEARED, $transactions[4]->getStatus());
    }

    public function testParseAmountWithCommas(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D01/01/2023
T1,234,567.89
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertSame(1234567.89, $transactions[0]->getAmount());
    }

    public function testParseUAmountField(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D01/01/2023
U-500.00
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertSame(-500.0, $transactions[0]->getAmount());
    }

    public function testParseDateWithApostrophe(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D25'12'2023
T-100.00
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertSame('2023-12-25', $transactions[0]->getDate()?->format('Y-m-d'));
    }

    public function testParseDateWithTwoDigitYear(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D25/12/23
T-100.00
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertSame('2023-12-25', $transactions[0]->getDate()?->format('Y-m-d'));
    }

    public function testParseDateWithFourDigitYear(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D25/12/2023
T-100.00
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertSame('2023-12-25', $transactions[0]->getDate()?->format('Y-m-d'));
    }

    public function testParseFileStatic(): void
    {
        $filePath = __DIR__ . '/../fixtures/sample.qif';

        $parser = Parser::parseFile($filePath);
        $transactions = $parser->getTransactions();

        $this->assertGreaterThan(0, count($transactions));
        $this->assertInstanceOf(Transaction::class, $transactions[0]);
    }

    public function testParseFileThrowsExceptionForInvalidPath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found or not readable:');

        Parser::parseFile('/non/existent/file.qif');
    }

    public function testParseWithBOMRemoval(): void
    {
        $qifContent = "\xEF\xBB\xBF" . <<<'QIF'
!Type:Bank
D01/01/2023
T-100.00
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertCount(1, $transactions);
        $this->assertSame(-100.0, $transactions[0]->getAmount());
    }

    public function testParseWithCustomSeparator(): void
    {
        $qifContent = "!Type:Bank\nD01/01/2023\nT-100.00\n^\n";

        $parser = new Parser($qifContent, "\n");
        $transactions = $parser->parse();

        $this->assertCount(1, $transactions);
        $this->assertSame(-100.0, $transactions[0]->getAmount());
    }

    public function testParseUnknownTypeReturnsNull(): void
    {
        $qifContent = <<<'QIF'
!Type:UnknownType
D01/01/2023
T-100.00
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertCount(1, $transactions);
        $this->assertNull($transactions[0]->getType());
    }

    public function testParseIgnoresOField(): void
    {
        $qifContent = <<<'QIF'
!Type:Invst
D01/01/2023
T-100.00
O14.95
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertCount(1, $transactions);
        // O field (commission) is currently ignored in parsing
        $this->assertSame(-100.0, $transactions[0]->getAmount());
    }

    public function testGetTransactionsBeforeParseReturnsEmpty(): void
    {
        $parser = new Parser("!Type:Bank\nD01/01/2023\nT-100.00\n^");

        $transactions = $parser->getTransactions();

        $this->assertCount(0, $transactions);
    }

    public function testParseStoresRawContent(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D01/01/2023
T-100.00
^
QIF;

        $parser = new Parser($qifContent);
        $transactions = $parser->parse();

        $this->assertNotNull($transactions[0]->_raw);
        $this->assertStringContainsString('!Type:Bank', $transactions[0]->_raw);
    }
}
