# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-10-12

### Changed

- **BC break:** the `DetailItems` and `Types` enum cases have descriptive names instead of single letters and abbreviations. Their backed values are unchanged, so parsed and written QIF files are identical: only code referring to the case names needs updating.
    - `DetailItems`: `D` → `DATE`, `T` → `AMOUNT`, `U` → `AMOUNT_ALT`, `M` → `MEMO`, `C` → `CLEARED_STATUS`, `N` → `CHECK_NUMBER`, `A` → `ADDRESS`, `L` → `CATEGORY`, `P` → `PAYEE`, `F` → `REIMBURSABLE`, `AMNT` → `SPLIT_AMOUNT`, `S` → `SPLIT_CATEGORY`, `E` → `SPLIT_MEMO`, `PERC` → `SPLIT_PERCENT`, `Y` → `SECURITY`, `I` → `PRICE`, `Q` → `QUANTITY`, `O` → `COMMISSION`, `B` → `BUDGETED_AMOUNT`, `X` → `EXTENDED`, `XA` → `INVOICE_SHIP_TO_ADDRESS`, `XI` → `INVOICE_TYPE`, `XE` → `INVOICE_DUE_DATE`, `XC` → `INVOICE_TAX_ACCOUNT`, `XR` → `INVOICE_TAX_RATE`, `XT` → `INVOICE_TAX_AMOUNT`, `XS` → `INVOICE_LINE_DESCRIPTION`, `XN` → `INVOICE_LINE_CATEGORY`, `X_HASH` → `INVOICE_LINE_QUANTITY`, `X_AMNT` → `INVOICE_LINE_PRICE`, `XF` → `INVOICE_LINE_TAXABLE`.
    - `Types`: `CCARD` → `CREDIT_CARD`, `INVST` → `INVESTMENT`, `OTHA` → `OTHER_ASSET`, `OTHL` → `OTHER_LIABILITY`, `Invoice` → `INVOICE`. `CASH` and `BANK` are unchanged.
- Parsing is about 38% faster with about 20% less peak memory: each distinct date string is parsed once per `parse()` call, and the date formats stop at the first match. Transactions with the same date string now share one `DateTimeImmutable` instance, which is only visible to code comparing dates with `===`.
- Writing is about 20% faster: `Transaction::__toString()` builds the output directly instead of through intermediate arrays. The output is unchanged.

## [1.0.0] - 2026-10-12

First release as `silarhi/qif-library`, a fork of the unmaintained `mimographix/qif-library`.

### Added

- `Status` enum (`NOT_CLEARED`, `CLEARED`, `RECONCILED`) for the cleared status of a transaction.

### Changed

- **BC break:** the package is `silarhi/qif-library` and the namespace `Silarhi\Qif`: require the new package and replace `MimoGraphix\QIF\` with `Silarhi\Qif\` in `use` statements.
- **BC break:** PHP 8.2 or later is required. `DetailItems` and `Types` are backed enums instead of classes of constants (`DetailItems::D->value` instead of `DetailItems::D`), and `Transaction::setStatus()` takes a `Status`.
- **BC break:** `nesbot/carbon` is no longer a dependency: `Transaction::setDate()` takes and `getDate()` returns a `DateTimeImmutable`.
- `Parser::parseFile()` throws a `RuntimeException` when the file is missing or unreadable, and `Writer::saveToFile()` when the file cannot be written, instead of a PHP warning or `die()`.

[2.0.0]: https://github.com/silarhi/qif-library/compare/1.0.0...2.0.0
[1.0.0]: https://github.com/silarhi/qif-library/releases/tag/1.0.0
