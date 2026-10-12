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
</p>

<h1 align="center">QIF Library</h1>

<p align="center">
    <strong>A PHP library to parse and write QIF files.</strong><br>
    Read Quicken Interchange Format exports into typed PHP objects, or generate QIF files from your own transactions.
</p>

<p align="center">
    📖 <a href="https://qif-library.silarhi.dev"><strong>Documentation</strong></a>
</p>

---

## Features

- **Parser**: reads the date, amount, memo, cleared status, payee, address, category and splits of every transaction
- **Writer**: builds QIF output from `Transaction` objects, splits, memo and address included
- **Typed results**: dates as `DateTimeImmutable`, amounts as `float`, account types and cleared status as enums
- **No silent guesses**: impossible dates (`31/02/2023`) and ambiguous amounts (`1,234`) throw a `ParseException`
  instead of being rolled over or misread, with the line where they are
- **Both number conventions**: `1,234.50` and `1.234,50`, detected per amount or set explicitly
- **Day- or month-first dates**: `d/m/Y` by default, `m/d/Y` on demand, read and written; ISO dates always
- **No dependencies**: plain PHP, Windows and Unix line endings, UTF-8 BOM stripped

## Requirements

- PHP 8.2+

## Installation

```bash
composer require silarhi/qif-library
```

## Quick start

```php
use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Parser;
use Silarhi\Qif\Transaction;
use Silarhi\Qif\Writer;

// Parse
foreach (Parser::parseFile('/path/to/export.qif')->getTransactions() as $transaction) {
    $transaction->getDate();        // ?DateTimeImmutable
    $transaction->getAmount();      // ?float
    $transaction->getDescription(); // ?string, the payee
}

// Write
$writer = new Writer();
$writer->addTransaction(
    (new Transaction(Types::Bank))
        ->setDate(new DateTimeImmutable('2026-01-31'))
        ->setDescription('Acme Corp')
        ->setAmount(2400)
        ->setCategory('Income:Salary')
);
$writer->saveToFile('/path/to/export.qif');
```

## Documentation

Everything else is on **[qif-library.silarhi.dev](https://qif-library.silarhi.dev)**:

- [Installation](https://qif-library.silarhi.dev/getting-started/installation/) and [quick start](https://qif-library.silarhi.dev/getting-started/quick-start/)
- Guides: [parsing QIF files](https://qif-library.silarhi.dev/guides/parsing/) (with [dates](https://qif-library.silarhi.dev/guides/parsing/#dates)
  and [amounts](https://qif-library.silarhi.dev/guides/parsing/#amounts)), [writing QIF files](https://qif-library.silarhi.dev/guides/writing/),
  [error handling](https://qif-library.silarhi.dev/guides/error-handling/)
- Reference: [classes](https://qif-library.silarhi.dev/reference/classes/), [enums and QIF fields](https://qif-library.silarhi.dev/reference/enums/),
  [exceptions](https://qif-library.silarhi.dev/reference/exceptions/)
- [Upgrading](https://qif-library.silarhi.dev/upgrading/) from `mimographix/qif-library`, 1.x or 2.x

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Release notes are in [CHANGELOG.md](CHANGELOG.md).

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
