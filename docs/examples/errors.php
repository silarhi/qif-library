<?php

use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Exception\ParseException;
use Silarhi\Qif\Parser;
use Silarhi\Qif\Transaction;
use Silarhi\Qif\Writer;

try {
    Parser::parseFile('missing.qif');
} catch (RuntimeException $e) {
    echo $e->getMessage(), PHP_EOL;
}

try {
    (new Writer())->saveToFile('/missing/directory/export.qif');
} catch (RuntimeException $e) {
    echo $e->getMessage(), PHP_EOL;
}

$records = [
    "!Type:Bank\nD31/02/2026\n^",  // a date that does not exist
    "!Type:Bank\nT1,234\n^",       // 1234 or 1.234?
    "!Type:Bank\nT12 EUR\n^",      // not a number
    "!Type:Bank\nT-20\nSTax\n^",   // a split without its $ line
];
foreach ($records as $content) {
    try {
        (new Parser($content))->parse();
    } catch (ParseException $e) {
        echo $e->getMessage(), PHP_EOL;
    }
}

// Where it happened, and the transactions read before
$parser = new Parser("!Type:Bank\nD25/12/2026\nT-20\n^\n\nD26/12/2026\nT12 EUR\n^\n");
try {
    $parser->parse();
} catch (ParseException $e) {
    printf("Line %d, record index %d: %s\n", $e->getLineNumber(), $e->getRecordIndex(), $e->getReason());
    printf("%d transaction read before it\n", count($parser->getTransactions()));
}

try {
    (new Transaction(Types::Bank))->setAmount('1,234');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(), PHP_EOL;
}
