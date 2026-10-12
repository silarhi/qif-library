<?php

use Silarhi\Qif\Enums\Types;
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

try {
    (new Transaction(Types::BANK))
        ->addSplit('Tax', -18.99)
        ->addSplit('Tax', -1.50);
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
}
