<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/v/silarhi/qif-library?style=for-the-badge&label=stable&color=0d6efd&labelColor=1a1a2e">
        <img src="https://img.shields.io/packagist/v/silarhi/qif-library?style=for-the-badge&label=stable&color=0d6efd"
            alt="Latest Stable Version">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/dt/silarhi/qif-library?style=for-the-badge&color=198754&labelColor=1a1a2e">
        <img src="https://img.shields.io/packagist/dt/silarhi/qif-library?style=for-the-badge&color=198754" alt="Total Downloads">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/l/silarhi/qif-library?style=for-the-badge&color=6f42c1&labelColor=1a1a2e">
        <img src="https://img.shields.io/packagist/l/silarhi/qif-library?style=for-the-badge&color=6f42c1" alt="License">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/packagist/php-v/silarhi/qif-library?style=for-the-badge&color=777bb4&labelColor=1a1a2e">
        <img src="https://img.shields.io/packagist/php-v/silarhi/qif-library?style=for-the-badge&color=777bb4" alt="PHP Version">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/github/actions/workflow/status/silarhi/qif-library/continuous-integration.yml?style=for-the-badge&label=CI&color=20c997&labelColor=1a1a2e">
        <img src="https://img.shields.io/github/actions/workflow/status/silarhi/qif-library/continuous-integration.yml?style=for-the-badge&label=CI&color=20c997"
            alt="CI Status">
    </picture>
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2Fsilarhi%2Fqif-library%2Fbadges%2Fcoverage.json&style=for-the-badge&labelColor=1a1a2e">
        <img src="https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2Fsilarhi%2Fqif-library%2Fbadges%2Fcoverage.json&style=for-the-badge" alt="Coverage">
    </picture>

<h1 align="center">QIF Library</h1>

<p align="center">
    <strong>A PHP library to parse and write QIF files.</strong><br>
    Read Quicken Interchange Format exports into typed PHP objects, or generate QIF files from your own transactions.
</p>

---

## Features

- **Parser** — reads the date, amount, memo, cleared status, payee, address, category and splits of every transaction
- **Writer** — builds QIF output from `Transaction` objects, including split transactions
- **Typed results** — dates as `DateTimeImmutable`, amounts as `float`, account types and cleared status as enums
- **No silent guesses** — impossible dates (`31/02/2023`) and ambiguous amounts (`1,234`) throw a `ParseException`
  instead of being rolled over or misread
- **Both number conventions** — `1,234.50` and `1.234,50`, detected per amount or set explicitly
- **Day- or month-first dates** — `d/m/Y` by default, `m/d/Y` on demand, ISO dates always; the `'` year separator and
  space-padded dates (`1/ 1/ 0`)
- **No dependencies** — plain PHP, works with Windows (`\r\n`) and Unix line endings, strips the UTF-8 BOM

## Requirements

| Dependency | Version |
| ---------- | ------- |
| PHP        | 8.2+    |

## Installation

```bash
composer require silarhi/qif-library
```

## Usage

### Parse a QIF file

```php
use Silarhi\Qif\Parser;

$parser = Parser::parseFile('/path/to/export.qif');

foreach ($parser->getTransactions() as $transaction) {
    $transaction->getType();        // Types enum, e.g. Types::BANK
    $transaction->getDate();        // ?DateTimeImmutable
    $transaction->getAmount();      // ?float, e.g. -1234.5
    $transaction->getDescription(); // ?string, the payee (P line)
    $transaction->getStatus();      // Status enum
}
```

To parse content you already have in memory, use the constructor and call `parse()`, which returns the transactions:

```php
$transactions = (new Parser($content))->parse();
```

Calling `parse()` again parses the content again and replaces the previous transactions.

Account, category, class, security and price lists (`!Account`, `!Type:Cat`...) are skipped: only transactions are
returned. A last transaction without its closing `^` is kept.

### Dates and amounts

Dates are read day first (`25/12/2023`). For files written by US versions of Quicken (`12/25/2023`), pass
`DateFormat::MonthFirst`. ISO dates (`2023-12-25`) are read with either format. Dates are at midnight, in the default
timezone. A date that does not exist in the chosen format (`31/02/2023`, or `01/31/2026` read day first) throws a
`ParseException` instead of rolling over.

Amounts can group thousands with `,`, `.` or spaces. By default the decimal separator is detected for each amount:

| QIF line     | Amount           |                                                       |
| ------------ | ---------------- | ----------------------------------------------------- |
| `T-1234.50`  | `-1234.5`        |                                                       |
| `T1,234.50`  | `1234.5`         | the last separator holds the decimals                 |
| `T1.234,50`  | `1234.5`         |                                                       |
| `T12,50`     | `12.5`           |                                                       |
| `T1,234,567` | `1234567.0`      | a repeated separator groups thousands                 |
| `T1.234`     | `1.234`          | a single `.` is a decimal point, as in the QIF format |
| `T1,234`     | `ParseException` | ambiguous: `1234` or `1.234`                          |

Pass the decimal separator to read every amount the same way:

```php
use Silarhi\Qif\Enums\DateFormat;
use Silarhi\Qif\Enums\DecimalSeparator;

$parser = Parser::parseFile('/path/to/export.qif', DateFormat::MonthFirst, DecimalSeparator::Point);
$transactions = (new Parser($content, dateFormat: DateFormat::DayFirst, decimalSeparator: DecimalSeparator::Comma))->parse();
```

With `DecimalSeparator::Comma`, `T1.234` is `1234.0` and `T1,234` is `1.234`. An amount written in the other convention
(`1,234.50`) throws a `ParseException`.

### What you get

| Getter             | Type                 | QIF line      | Notes                                                                           |
| ------------------ | -------------------- | ------------- | ------------------------------------------------------------------------------- |
| `getType()`        | `?Types`             | `!Type:`      | `CASH`, `BANK`, `CREDIT_CARD`, `INVESTMENT`...                                  |
| `getDate()`        | `?DateTimeImmutable` | `D`           | At midnight, see [Dates and amounts](#dates-and-amounts)                        |
| `getAmount()`      | `?float`             | `T`, `U`      | See [Dates and amounts](#dates-and-amounts)                                     |
| `getMemo()`        | `?string`            | `M`           |                                                                                 |
| `getStatus()`      | `Status`             | `C`           | `RECONCILED` (`X`, `R`), `CLEARED` (`*`, `c`)                                   |
| `getDescription()` | `?string`            | `P`           | Payee                                                                           |
| `getAddress()`     | `?string`            | `A`           | One line per `A` line, joined with `"\n"`                                       |
| `getCategory()`    | `?string`            | `L`           |                                                                                 |
| `getSplits()`      | `list<array>`        | `S`, `E`, `$` | `[['category' => string, 'amount' => float, 'memo' => ?string], ...]`, in order |

### Write a QIF file

```php
use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Transaction;
use Silarhi\Qif\Writer;

$transaction = (new Transaction(Types::CASH))
    ->setDate(new DateTimeImmutable('2019-12-31'))
    ->setDescription('INV666')
    ->setAmount(18.99)
    ->setCategory('Sales')
    ->addSplit('Sales', 18)
    ->addSplit('Tax', 0.99, 'VAT')
    ->markAsReconciled();

$writer = new Writer();
$writer->addTransaction($transaction);

echo $writer;                              // QIF content
$writer->saveToFile('/path/to/export.qif'); // or write it to disk
```

The writer outputs the type, date (`d/m/Y`), amount, category, splits, cleared status, payee, address (one `A` line
per line) and memo of each transaction. Several splits can share a category; `removeSplit()` removes all of them.

### Error handling

- `Parser::parseFile()` throws a `RuntimeException` when the file does not exist or cannot be read
- `Parser::parse()` and `Parser::parseFile()` throw a `Silarhi\Qif\Exception\ParseException` (a `RuntimeException`)
  on an invalid date, an invalid or ambiguous amount, or a split without amount
- `Writer::saveToFile()` throws a `RuntimeException` when the directory is not writable or the file cannot be opened
- `Transaction::setAmount()` and `addSplit()` throw an `InvalidArgumentException` when given a string that is not an
  amount or is ambiguous (`'1,234'`); `'1,234.50'` and `'1.234,50'` are read as `1234.5`

## Testing & Quality

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Static analysis (level: max)
vendor/bin/phpstan analyse

# Code style check
vendor/bin/php-cs-fixer fix --dry-run --diff

# Code style fix
vendor/bin/php-cs-fixer fix

# Code modernization check
vendor/bin/rector process --dry-run
```

## Contributing

Contributions are welcome! Please make sure your changes pass all quality checks before submitting a pull request:

```bash
vendor/bin/phpunit && vendor/bin/phpstan analyse && vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Credits

This library started as a fork of [mimographix/qif-library](https://github.com/MimoGraphix/qif-library) by Mário
Čechovič.

## License

MIT License. See [LICENSE](LICENSE) for details.

---

<p align="center">
    Built with care by <a href="https://github.com/silarhi">SILARHI</a>.<br>
    If QIF Library saves you time, consider giving it a star on GitHub.
</p>
