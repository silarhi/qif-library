<?php

use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Transaction;
use Silarhi\Qif\Writer;

$writer = new Writer();

$writer->addTransaction(
    (new Transaction(Types::BANK))
        ->setDate(new DateTimeImmutable('2026-01-15'))
        ->setDescription('Example Hardware Store')
        ->setAmount(-118.99)
        ->setCategory('Home')
        ->addSplit('Home:Tools', -100)
        ->addSplit('Tax', -18.99, 'VAT')
        ->markAsCleared()
);

$writer->addTransaction(
    (new Transaction(Types::BANK))
        ->setDate(new DateTimeImmutable('2026-01-31'))
        ->setDescription('Acme Corp')
        ->setAmount(2400)
        ->setCategory('Income:Salary')
);

echo $writer;
