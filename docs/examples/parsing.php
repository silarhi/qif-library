<?php

use Silarhi\Qif\Parser;

$parser = Parser::parseFile('export.qif');

foreach ($parser->getTransactions() as $transaction) {
    echo json_encode([
        'type' => $transaction->getType()?->name,
        'date' => $transaction->getDate()?->format('Y-m-d'),
        'amount' => $transaction->getAmount(),
        'status' => $transaction->getStatus()->name,
        'payee' => $transaction->getDescription(),
        'address' => $transaction->getAddress(),
        'memo' => $transaction->getMemo(),
        'category' => $transaction->getCategory(),
        'splits' => $transaction->getSplits(),
    ], JSON_PRETTY_PRINT), PHP_EOL;
}
