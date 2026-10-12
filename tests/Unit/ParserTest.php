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

use function count;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Silarhi\Qif\Enums\Status;
use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Parser;
use Silarhi\Qif\Transaction;

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

        self::assertCount(1, $transactions);
        self::assertInstanceOf(Transaction::class, $transactions[0]);
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

        self::assertSame(Types::BANK, $transaction->getType());
        self::assertSame('2023-12-25', $transaction->getDate()?->format('Y-m-d'));
        self::assertSame(-1234.50, $transaction->getAmount());
        self::assertSame(Status::RECONCILED, $transaction->getStatus());
        self::assertSame('Standard Oil', $transaction->getDescription());
        self::assertSame('Main Street 123', $transaction->getAddress());
        self::assertSame('Fuel for car', $transaction->getMemo());
        self::assertSame('Fuel:Car', $transaction->getCategory());
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

        self::assertCount(3, $transactions);
        self::assertSame(-100.0, $transactions[0]->getAmount());
        self::assertSame(-200.0, $transactions[1]->getAmount());
        self::assertSame(-300.0, $transactions[2]->getAmount());
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

        self::assertSame(Status::RECONCILED, $transactions[0]->getStatus());
        self::assertSame(Status::RECONCILED, $transactions[1]->getStatus());
        self::assertSame(Status::CLEARED, $transactions[2]->getStatus());
        self::assertSame(Status::CLEARED, $transactions[3]->getStatus());
        self::assertSame(Status::NOT_CLEARED, $transactions[4]->getStatus());
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

        self::assertSame(1234567.89, $transactions[0]->getAmount());
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

        self::assertSame(-500.0, $transactions[0]->getAmount());
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

        self::assertSame('2023-12-25', $transactions[0]->getDate()?->format('Y-m-d'));
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

        self::assertSame('2023-12-25', $transactions[0]->getDate()?->format('Y-m-d'));
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

        self::assertSame('2023-12-25', $transactions[0]->getDate()?->format('Y-m-d'));
    }

    public function testParseFileStatic(): void
    {
        $filePath = __DIR__ . '/../fixtures/sample.qif';

        $parser = Parser::parseFile($filePath);
        $transactions = $parser->getTransactions();

        self::assertGreaterThan(0, count($transactions));
        self::assertInstanceOf(Transaction::class, $transactions[0]);
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

        self::assertCount(1, $transactions);
        self::assertSame(-100.0, $transactions[0]->getAmount());
    }

    public function testParseWithCustomSeparator(): void
    {
        $qifContent = "!Type:Bank\nD01/01/2023\nT-100.00\n^\n";

        $parser = new Parser($qifContent, "\n");
        $transactions = $parser->parse();

        self::assertCount(1, $transactions);
        self::assertSame(-100.0, $transactions[0]->getAmount());
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

        self::assertCount(1, $transactions);
        self::assertNull($transactions[0]->getType());
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

        self::assertCount(1, $transactions);
        // O field (commission) is currently ignored in parsing
        self::assertSame(-100.0, $transactions[0]->getAmount());
    }

    public function testGetTransactionsBeforeParseReturnsEmpty(): void
    {
        $parser = new Parser("!Type:Bank\nD01/01/2023\nT-100.00\n^");

        $transactions = $parser->getTransactions();

        self::assertCount(0, $transactions);
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

        self::assertNotNull($transactions[0]->_raw);
        self::assertStringContainsString('!Type:Bank', $transactions[0]->_raw);
    }
}
