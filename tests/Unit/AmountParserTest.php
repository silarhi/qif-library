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

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\Qif\AmountParser;
use Silarhi\Qif\Enums\DecimalSeparator;

#[CoversClass(AmountParser::class)]
#[CoversClass(DecimalSeparator::class)]
final class AmountParserTest extends TestCase
{
    /**
     * @return iterable<string, array{string, float}>
     */
    public static function provideDetectedAmounts(): iterable
    {
        yield 'plain' => ['-1234.50', -1234.5];
        yield 'plus sign' => ['+12.5', 12.5];
        yield 'integer' => ['42', 42.0];
        yield 'single point is decimal' => ['1.234', 1.234];
        yield 'point decimal, comma thousands' => ['1,234.50', 1234.5];
        yield 'comma decimal, point thousands' => ['1.234,50', 1234.5];
        yield 'negative comma decimal' => ['-1.234,50', -1234.5];
        yield 'comma decimal' => ['-12,50', -12.5];
        yield 'comma with one decimal' => ['3,5', 3.5];
        yield 'comma after zero' => ['0,125', 0.125];
        yield 'comma after four digits' => ['1234,567', 1234.567];
        yield 'repeated commas' => ['1,234,567', 1234567.0];
        yield 'repeated points' => ['1.234.567', 1234567.0];
        yield 'repeated commas with decimals' => ['1,234,567.89', 1234567.89];
        yield 'space thousands' => ['1 234,50', 1234.5];
        yield 'no-break space thousands' => ["1\u{202F}234,50", 1234.5];
        yield 'leading decimal comma' => [',5', 0.5];
        yield 'leading decimal point' => ['.5', 0.5];
        yield 'trailing decimal point' => ['12.', 12.0];
        yield 'surrounding spaces' => [' -12.5 ', -12.5];
        yield '300 digits' => [str_repeat('9', 300), (float) str_repeat('9', 300)];
    }

    #[DataProvider('provideDetectedAmounts')]
    public function testParseDetectsTheDecimalSeparator(string $amount, float $expected): void
    {
        self::assertSame($expected, AmountParser::parse($amount));
    }

    /**
     * @return iterable<string, array{string, DecimalSeparator, float}>
     */
    public static function provideExplicitAmounts(): iterable
    {
        yield 'point: comma thousands' => ['1,234', DecimalSeparator::Point, 1234.0];
        yield 'point: plain' => ['-1234.50', DecimalSeparator::Point, -1234.5];
        yield 'comma: point thousands' => ['1.234', DecimalSeparator::Comma, 1234.0];
        yield 'comma: decimals' => ['1,234', DecimalSeparator::Comma, 1.234];
        yield 'comma: both' => ['-1.234.567,89', DecimalSeparator::Comma, -1234567.89];
    }

    #[DataProvider('provideExplicitAmounts')]
    public function testParseWithExplicitDecimalSeparator(string $amount, DecimalSeparator $decimalSeparator, float $expected): void
    {
        self::assertSame($expected, AmountParser::parse($amount, $decimalSeparator));
    }

    /**
     * @return iterable<string, array{string, DecimalSeparator|null}>
     */
    public static function provideInvalidAmounts(): iterable
    {
        yield 'empty' => ['', null];
        yield 'text' => ['twelve', null];
        yield 'currency' => ['$12.50', null];
        yield 'ambiguous comma' => ['1,234', null];
        yield 'ambiguous negative comma' => ['-12,345', null];
        yield 'two decimal separators' => ['1.234,567.89', null];
        yield 'misgrouped thousands' => ['12,34.50', null];
        yield 'comma convention read as point' => ['1.234,50', DecimalSeparator::Point];
        yield 'point convention read as comma' => ['1,234.50', DecimalSeparator::Comma];
        yield 'plain amount read as comma' => ['1234.50', DecimalSeparator::Comma];
        yield 'exponent' => ['1e3', null];
        yield 'exponent overflowing to INF' => ['1e400', null];
        yield 'exponent read as point' => ['1.5E3', DecimalSeparator::Point];
        yield 'INF' => ['INF', null];
        yield 'NAN' => ['NAN', null];
        yield '400 digits' => [str_repeat('9', 400), null];
        yield '400 digits with decimal comma' => [str_repeat('9', 400) . ',00', null];
        yield '400 digits read as comma' => [str_repeat('9', 400), DecimalSeparator::Comma];
    }

    #[DataProvider('provideInvalidAmounts')]
    public function testParseRejectsWhatItCannotReadSafely(string $amount, ?DecimalSeparator $decimalSeparator): void
    {
        $this->expectException(InvalidArgumentException::class);

        AmountParser::parse($amount, $decimalSeparator);
    }

    /**
     * @return iterable<string, array{string, DecimalSeparator|null, string}>
     */
    public static function provideMessages(): iterable
    {
        yield 'not an amount' => ['12 EUR', null, '"12 EUR" is not an amount.'];
        yield 'ambiguous' => ['- 1,234', null, '"- 1,234" is ambiguous'];
        yield 'two decimal separators' => ['1 234,5,6', DecimalSeparator::Comma, '"1 234,5,6" is not an amount with "," as decimal separator.'];
        yield 'misgrouped thousands' => ['1 2.34,50', null, '"1 2.34,50" does not group its thousands by ".".'];
        yield 'exponent' => ['1e400', null, '"1e400" is not an amount.'];
        yield 'too large' => [str_repeat('9', 400), null, '"' . str_repeat('9', 400) . '" is too large to be an amount.'];
    }

    #[DataProvider('provideMessages')]
    public function testMessagesShowTheAmountAsWritten(string $amount, ?DecimalSeparator $decimalSeparator, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        AmountParser::parse($amount, $decimalSeparator);
    }
}
