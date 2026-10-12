<?php

use Silarhi\Qif\Parser;

$dates = ['2026-01-31', '31/01/2026', '26-01-31', '31/01/26', "31/01'26", '1/ 1/ 0', '01/31/2026', '31/02/2026', 'Jan 31'];

foreach ($dates as $date) {
    [$transaction] = (new Parser("!Type:Bank\nD{$date}\n^\n"))->parse();

    printf("%-10s => %s\n", $date, $transaction->getDate()?->format('Y-m-d') ?? 'null');
}
