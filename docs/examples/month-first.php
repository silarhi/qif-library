<?php

use Silarhi\Qif\Enums\DateFormat;
use Silarhi\Qif\Parser;
use Silarhi\Qif\Writer;

// A file from a US version of Quicken
$content = "!Type:Bank\nD01/31/2026\nT-42.50\nPDemo Grocery\n^\n";

$writer = new Writer(DateFormat::MonthFirst);
foreach ((new Parser($content, dateFormat: DateFormat::MonthFirst))->parse() as $transaction) {
    $writer->addTransaction($transaction);
}

echo $writer;
