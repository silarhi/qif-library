<?php

use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Transaction;
use Silarhi\Qif\Writer;

$writer = new Writer();

$writer->addTransaction(
    (new Transaction(Types::Bank))
        ->setDate(new DateTimeImmutable('2026-01-15'))
        ->setDescription('Example Hardware Store')
        ->setAddress("1 Market Square\nSpringfield")
        ->setMemo('Drill and bits')
        ->setAmount(-118.99)
        ->setCategory('Home')
        ->addSplit('Home:Tools', -100)
        ->addSplit('Tax', -18.99, 'VAT')
        ->markAsCleared()
);

$writer->addTransaction(
    (new Transaction(Types::Bank))
        ->setDate(new DateTimeImmutable('2026-01-31'))
        ->setDescription('Acme Corp')
        ->setAmount('2,400.00')
        ->setCategory('Income:Salary')
);

echo $writer;
