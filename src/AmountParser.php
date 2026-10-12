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

namespace Silarhi\Qif;

use function count;

use InvalidArgumentException;
use Silarhi\Qif\Enums\DecimalSeparator;

use function sprintf;

/**
 * Reads an amount written with thousands separators, in either convention ("1,234.50" or "1.234,50").
 *
 * @internal
 */
final class AmountParser
{
    /**
     * @param DecimalSeparator|null $decimalSeparator null to detect it, rejecting amounts that could be read both ways
     *
     * @throws InvalidArgumentException when the amount is not a number or is ambiguous
     */
    public static function parse(string $amount, ?DecimalSeparator $decimalSeparator = null): float
    {
        // A plain number ("-1234.50", "1.234") uses a decimal point, as in the QIF specification
        if (DecimalSeparator::Comma !== $decimalSeparator && is_numeric($amount)) {
            return (float) $amount;
        }

        // Spaces, including the (narrow) no-break spaces of French amounts, only group thousands. The messages show
        // the original amount
        $compact = str_replace([' ', "\u{00A0}", "\u{202F}"], '', $amount);

        if (1 !== preg_match('/^([+-]?)([\d.,]*\d[\d.,]*)$/', $compact, $matches)) {
            throw new InvalidArgumentException(sprintf('"%s" is not an amount.', $amount));
        }
        [, $sign, $number] = $matches;

        $decimalSeparator ??= self::detectDecimalSeparator($number, $amount);
        $thousandsSeparator = $decimalSeparator->thousandsSeparator();

        $parts = explode($decimalSeparator->value, $number);
        if (count($parts) > 2 || str_contains($parts[1] ?? '', $thousandsSeparator)) {
            throw new InvalidArgumentException(sprintf('"%s" is not an amount with "%s" as decimal separator.', $amount, $decimalSeparator->value));
        }

        $integer = $parts[0];
        if (str_contains($integer, $thousandsSeparator)) {
            if (1 !== preg_match('/^\d{1,3}(?:' . preg_quote($thousandsSeparator, '/') . '\d{3})+$/', $integer)) {
                throw new InvalidArgumentException(sprintf('"%s" does not group its thousands by "%s".', $amount, $thousandsSeparator));
            }
            $integer = str_replace($thousandsSeparator, '', $integer);
        }

        return (float) ($sign . ('' === $integer ? '0' : $integer) . '.' . ($parts[1] ?? ''));
    }

    /**
     * @param string $amount the amount as written, for the message
     */
    private static function detectDecimalSeparator(string $number, string $amount): DecimalSeparator
    {
        $lastComma = strrpos($number, ',');
        $lastPoint = strrpos($number, '.');

        // Both are used: the last one separates the decimals ("1,234.50", "1.234,50")
        if (false !== $lastComma && false !== $lastPoint) {
            return $lastComma > $lastPoint ? DecimalSeparator::Comma : DecimalSeparator::Point;
        }

        $separator = false !== $lastComma ? DecimalSeparator::Comma : DecimalSeparator::Point;

        // Repeated, it groups thousands ("1,234,567", "1.234.567")
        if (substr_count($number, $separator->value) > 1) {
            return DecimalSeparator::Comma === $separator ? DecimalSeparator::Point : DecimalSeparator::Comma;
        }

        // Once, after 1 to 3 digits and before exactly 3 ("1,234"), it can be either
        if (1 === preg_match('/^[1-9]\d{0,2}[.,]\d{3}$/', $number)) {
            throw new InvalidArgumentException(sprintf('"%s" is ambiguous: "%s" can be a decimal or a thousands separator. Pass the decimal separator explicitly.', $amount, $separator->value));
        }

        return $separator;
    }
}
