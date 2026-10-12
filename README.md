# PHP QIF Library

A simple QIF parsing/writing library.

## Installation

```bash
composer require silarhi/qif-library
```

## Usage

### Parser

```php
// Instatiate the QIF Parser
$qifParser = new Silarhi\Qif\Parser( $filePath );
$qifParser->parse();

foreach( $qifParser->getTransactions() as $transaction )
{
    // your code
}
```

### Writer

```php
// Instatiate the QIF Writer
$qif = new Silarhi\Qif\Writer();

// Create a new transaction
$transaction = new Transaction( Enums\Types::CASH );

$transaction->setDate( new DateTimeImmutable( '2019-12-31' ) )
    ->setDescription( 'INV666: ' )
    ->setAmount( 18.99 )
    ->setCategory( 'Sales' )
    ->addSplit( 'Sales', 18 )
    ->addSplit( 'Tax', 0.99 )
    ->markAsReconciled();

// Add it to the QIF
$qif->addTransaction( $transaction );

echo $qif;
```
