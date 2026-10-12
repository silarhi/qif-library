<?php

use Silarhi\Qif\Enums\DecimalSeparator;
use Silarhi\Qif\Exception\ParseException;
use Silarhi\Qif\Parser;

$amounts = ['-1234.50', '1,234.50', '1.234,50', '12,50', '1,234,567', '1.234', '1 234,50', '1,234', '12 EUR'];

printf("%-12s %-16s %-16s %s\n", 'T line', 'Detected', 'Point', 'Comma');
foreach ($amounts as $amount) {
    $results = [];
    foreach ([null, DecimalSeparator::Point, DecimalSeparator::Comma] as $separator) {
        try {
            [$transaction] = (new Parser("!Type:Bank\nT{$amount}\n^\n", decimalSeparator: $separator))->parse();
            $results[] = var_export($transaction->getAmount(), true);
        } catch (ParseException) {
            $results[] = 'ParseException';
        }
    }

    printf("%-12s %-16s %-16s %s\n", "T{$amount}", ...$results);
}
