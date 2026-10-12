<?php

use Silarhi\Qif\Enums\DateFormat;
use Silarhi\Qif\Exception\ParseException;
use Silarhi\Qif\Parser;

$dates = ['2026-01-31', '31/01/2026', '01/31/2026', '26-01-31', '31/01/26', "31/01'26", '1/ 1/ 0', '31/02/2026', 'Jan 31', ''];

printf("%-12s %-18s %s\n", 'D line', 'DayFirst', 'MonthFirst');
foreach ($dates as $date) {
    $results = [];
    foreach ([DateFormat::DayFirst, DateFormat::MonthFirst] as $format) {
        try {
            [$transaction] = (new Parser("!Type:Bank\nD{$date}\n^\n", dateFormat: $format))->parse();
            $results[] = $transaction->getDate()?->format('Y-m-d H:i') ?? 'null';
        } catch (ParseException) {
            $results[] = 'ParseException';
        }
    }

    printf("%-12s %-18s %s\n", "D{$date}", ...$results);
}
