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
use Silarhi\Qif\Enums\DateFormat;
use Silarhi\Qif\Enums\DecimalSeparator;
use Silarhi\Qif\Enums\Status;
use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Exception\ParseException;
use Silarhi\Qif\Parser;
use Silarhi\Qif\Transaction;

#[CoversClass(Parser::class)]
#[CoversClass(DateFormat::class)]
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

        self::assertSame("!Type:Bank\r\nD01/01/2023\r\nT-100.00\r\n^\r\n", $transactions[0]->_raw);
    }

    public function testParseStoresRawContentOfEachTransaction(): void
    {
        $qifContent = "!Type:Oth A\nD01/01/2023\nT-100.00\n^\nD02/01/2023\nT-200.00\n^\n";

        $transactions = (new Parser($qifContent, "\n"))->parse();

        self::assertSame("!Type:Oth A\nD01/01/2023\nT-100.00\n^\n", $transactions[0]->_raw);
        self::assertSame("!Type:Oth A\nD02/01/2023\nT-200.00\n^\n", $transactions[1]->_raw);
    }

    public function testParseStoresRawContentWithoutHeader(): void
    {
        $transactions = (new Parser("D01/01/2023\nT-100.00\n^", "\n"))->parse();

        self::assertSame("D01/01/2023\nT-100.00\n^\n", $transactions[0]->_raw);
        self::assertNull($transactions[0]->getType());
    }

    public function testParseStoresRawContentWithUnknownType(): void
    {
        $transactions = (new Parser("!Type:Memorized\nT-100.00\n^", "\n"))->parse();

        self::assertSame("!Type:Memorized\nT-100.00\n^\n", $transactions[0]->_raw);
    }

    public function testParseSplits(): void
    {
        $qifContent = <<<'QIF'
!Type:Bank
D26/12/2023
T600.00
SProduct
$400.00
ETaxable items
SProduct
EOther items
$1,000.00
STax
$-800.00
^
QIF;

        $transactions = (new Parser($qifContent))->parse();

        self::assertSame([
            ['category' => 'Product', 'amount' => 400.0, 'memo' => 'Taxable items'],
            ['category' => 'Product', 'amount' => 1000.0, 'memo' => 'Other items'],
            ['category' => 'Tax', 'amount' => -800.0, 'memo' => null],
        ], $transactions[0]->getSplits());
    }

    public function testParseSplitsDoNotLeakToTheNextTransaction(): void
    {
        $transactions = (new Parser("!Type:Bank\nT10\nSA\n$10\n^\nT20\n^", "\n"))->parse();

        self::assertCount(1, $transactions[0]->getSplits());
        self::assertSame([], $transactions[1]->getSplits());
    }

    public function testParseSplitWithoutAmountThrows(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('The split "Product" has no amount ($ line).');

        (new Parser("!Type:Bank\nT10\nSProduct\nEItems\n^", "\n"))->parse();
    }

    public function testParseInvestmentTransferAmountIsNotASplit(): void
    {
        $transactions = (new Parser("!Type:Invst\nD01/01/2023\nNXIn\nT100.00\n$100.00\n^", "\n"))->parse();

        self::assertSame([], $transactions[0]->getSplits());
        self::assertSame(100.0, $transactions[0]->getAmount());
    }

    public function testParseMultiLineAddress(): void
    {
        $transactions = (new Parser("!Type:Bank\nPStandard Oil\nAStandard Oil\nA101 Main St.\nASpringfield\n^", "\n"))->parse();

        self::assertSame("Standard Oil\n101 Main St.\nSpringfield", $transactions[0]->getAddress());
    }

    public function testParseKeepsLastTransactionWithoutClosingCaret(): void
    {
        $transactions = (new Parser("!Type:Bank\nD25/12/2023\nT-100.00\n^\nD26/12/2023\nT-200.00\nSFee\n$-200.00\n", "\n"))->parse();

        self::assertCount(2, $transactions);
        self::assertSame(-200.0, $transactions[1]->getAmount());
        self::assertSame('2023-12-26', $transactions[1]->getDate()?->format('Y-m-d'));
        self::assertCount(1, $transactions[1]->getSplits());
        self::assertSame("!Type:Bank\nD26/12/2023\nT-200.00\nSFee\n$-200.00\n", $transactions[1]->_raw);
    }

    public function testParseKeepsTransactionWithoutClosingCaretBeforeNextList(): void
    {
        $transactions = (new Parser("!Type:Bank\nT-100.00\n!Type:Cash\nT-200.00\n^", "\n"))->parse();

        self::assertCount(2, $transactions);
        self::assertSame(Types::BANK, $transactions[0]->getType());
        self::assertSame(-100.0, $transactions[0]->getAmount());
        self::assertSame(Types::CASH, $transactions[1]->getType());
    }

    public function testParseTwiceReturnsTheSameTransactions(): void
    {
        $parser = new Parser("!Type:Bank\nT-100.00\n^\nT-200.00\n^", "\n");

        $parser->parse();
        $transactions = $parser->parse();

        self::assertCount(2, $transactions);
        self::assertCount(2, $parser->getTransactions());
    }

    public function testParseAfterParseFileReturnsTheSameTransactions(): void
    {
        $parser = Parser::parseFile(__DIR__ . '/../fixtures/sample.qif');

        self::assertCount(3, $parser->parse());
    }

    public function testParseFileReadsSplits(): void
    {
        $transactions = Parser::parseFile(__DIR__ . '/../fixtures/sample.qif')->getTransactions();

        self::assertSame([
            ['category' => 'Product', 'amount' => 400.0, 'memo' => 'Taxable items'],
            ['category' => 'Tax', 'amount' => 100.0, 'memo' => 'Tax collected'],
        ], $transactions[1]->getSplits());
    }

    public function testParseFileWithOptions(): void
    {
        $file = sys_get_temp_dir() . '/test_qif_' . uniqid() . '.qif';
        file_put_contents($file, "!Type:Bank\nD12/25/2023\nT1.234\n^\n");

        try {
            $transaction = Parser::parseFile($file, DateFormat::MonthFirst, DecimalSeparator::Comma)->getTransactions()[0];
        } finally {
            unlink($file);
        }

        self::assertSame('2023-12-25', $transaction->getDate()?->format('Y-m-d'));
        self::assertSame(1234.0, $transaction->getAmount());
    }

    public function testParseAmountWithDecimalComma(): void
    {
        $transactions = (new Parser("!Type:Bank\nT-1.234,50\n^\nT12,50\n^", "\n"))->parse();

        self::assertSame(-1234.5, $transactions[0]->getAmount());
        self::assertSame(12.5, $transactions[1]->getAmount());
    }

    public function testParseAmbiguousAmountThrows(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"1,234" is ambiguous');

        (new Parser("!Type:Bank\nT1,234\n^", "\n"))->parse();
    }

    public function testParseAmountWithExplicitDecimalSeparator(): void
    {
        $qifContent = "!Type:Bank\nT1,234\n^";

        self::assertSame(1234.0, (new Parser($qifContent, "\n", decimalSeparator: DecimalSeparator::Point))->parse()[0]->getAmount());
        self::assertSame(1.234, (new Parser($qifContent, "\n", decimalSeparator: DecimalSeparator::Comma))->parse()[0]->getAmount());
    }

    public function testParseInvalidAmountThrows(): void
    {
        $this->expectException(ParseException::class);

        (new Parser("!Type:Bank\nT12 EUR\n^", "\n"))->parse();
    }

    public function testParseEmptyAmountAndDateAreNull(): void
    {
        $transactions = (new Parser("!Type:Bank\nD\nT\nPPayee\n^", "\n"))->parse();

        self::assertNull($transactions[0]->getDate());
        self::assertNull($transactions[0]->getAmount());
    }

    public function testParseSkipsAccountList(): void
    {
        $qifContent = <<<'QIF'
!Option:AutoSwitch
!Account
NChecking
TBank
^
NSavings
TBank
^
!Clear:AutoSwitch
!Account
NChecking
TBank
^
!Type:Bank
D25/12/2023
T-100.00
^
QIF;

        $transactions = (new Parser($qifContent))->parse();

        self::assertCount(1, $transactions);
        self::assertSame(Types::BANK, $transactions[0]->getType());
        self::assertSame(-100.0, $transactions[0]->getAmount());
        self::assertSame("!Type:Bank\r\nD25/12/2023\r\nT-100.00\r\n^\r\n", $transactions[0]->_raw);
    }

    public function testParseSkipsCategoryAndClassLists(): void
    {
        $qifContent = <<<'QIF'
!Type:Cat
NFuel
DFuel for the car
E
^
!Type:Class
NBusiness
^
!Type:CCard
D25/12/2023
T-100.00
^
QIF;

        $transactions = (new Parser($qifContent))->parse();

        self::assertCount(1, $transactions);
        self::assertSame(Types::CREDIT_CARD, $transactions[0]->getType());
    }

    public function testParseOptionDoesNotChangeTheType(): void
    {
        $transactions = (new Parser("!Type:Bank\nT-100.00\n^\n!Option:MDY\nT-200.00\n^", "\n"))->parse();

        self::assertSame(Types::BANK, $transactions[1]->getType());
    }

    public function testParseDateIsAtMidnight(): void
    {
        $transactions = (new Parser("!Type:Bank\nD25/12/2023\n^\nD2023-12-26\n^", "\n"))->parse();

        self::assertSame('2023-12-25 00:00:00', $transactions[0]->getDate()?->format('Y-m-d H:i:s'));
        self::assertSame('2023-12-26 00:00:00', $transactions[1]->getDate()?->format('Y-m-d H:i:s'));
    }

    public function testParseIsoDate(): void
    {
        $transactions = (new Parser("!Type:Bank\nD2023-12-25\n^", "\n", DateFormat::MonthFirst))->parse();

        self::assertSame('2023-12-25', $transactions[0]->getDate()?->format('Y-m-d'));
    }

    public function testParseSpacePaddedDate(): void
    {
        $transactions = (new Parser("!Type:Bank\nD1/ 2' 0\n^", "\n"))->parse();

        self::assertSame('2000-02-01', $transactions[0]->getDate()?->format('Y-m-d'));
    }

    public function testParseMonthFirstDate(): void
    {
        $transactions = (new Parser("!Type:Bank\nD01/31/2026\n^\nD12/25'23\n^", "\n", DateFormat::MonthFirst))->parse();

        self::assertSame('2026-01-31', $transactions[0]->getDate()?->format('Y-m-d'));
        self::assertSame('2023-12-25', $transactions[1]->getDate()?->format('Y-m-d'));
    }

    public function testParseMonthFirstDateWithDayFirstFormatThrows(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"01/31/2026" is not a valid date with the day first (DateFormat::DayFirst).');

        (new Parser("!Type:Bank\nD01/31/2026\n^", "\n"))->parse();
    }

    public function testParseImpossibleDateThrows(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('"31/02/2023" is not a valid date');

        (new Parser("!Type:Bank\nD31/02/2023\n^", "\n"))->parse();
    }

    public function testParseUnreadableDateThrows(): void
    {
        $this->expectException(ParseException::class);

        (new Parser("!Type:Bank\nDChristmas\n^", "\n"))->parse();
    }
}
