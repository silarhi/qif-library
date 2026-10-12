<?php

use Silarhi\Qif\Parser;
use Silarhi\Qif\Writer;

$parser = Parser::parseFile('export.qif');

$writer = new Writer();
foreach ($parser->getTransactions() as $transaction) {
    $writer->addTransaction($transaction);
}

echo $writer;
