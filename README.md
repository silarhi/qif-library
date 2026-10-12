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

- **Parser** — reads the date, amount, memo, cleared status, payee, address and category of every transaction
- **Writer** — builds QIF output from `Transaction` objects, including split transactions
- **Typed results** — dates as `DateTimeImmutable`, amounts as `float`, account types and cleared status as enums
- **Lenient dates** — accepts `Y-m-d`, `d/m/Y`, `y-m-d` and `d/m/y`, the `'` year separator and space-padded dates
  (`1/ 1/ 0`)
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

### What you get

| Getter             | Type                 | QIF line | Notes                                                |
| ------------------ | -------------------- | -------- | ---------------------------------------------------- |
| `getType()`        | `?Types`             | `!Type:` | `CASH`, `BANK`, `CCARD`, `INVST`, `OTHA`, `OTHL`...  |
| `getDate()`        | `?DateTimeImmutable` | `D`      |                                                      |
| `getAmount()`      | `?float`             | `T`, `U` | Thousands separators (`,`) are removed               |
| `getMemo()`        | `?string`            | `M`      |                                                      |
| `getStatus()`      | `Status`             | `C`      | `RECONCILED` (`X`, `R`), `CLEARED` (`*`, `c`)        |
| `getDescription()` | `?string`            | `P`      | Payee                                                |
| `getAddress()`     | `?string`            | `A`      |                                                      |
| `getCategory()`    | `?string`            | `L`      |                                                      |
| `getSplits()`      | `array`              | `S`/`$`  | `['name' => ['amount' => float, 'memo' => ?string]]` |

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

The writer outputs the type, date (`d/m/Y`), amount, category, splits, cleared status and payee of each transaction.

### Error handling

- `Parser::parseFile()` throws a `RuntimeException` when the file does not exist or cannot be read
- `Writer::saveToFile()` throws a `RuntimeException` when the directory is not writable or the file cannot be opened
- `Transaction::addSplit()` throws an `Exception` when a split with the same name already exists

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
