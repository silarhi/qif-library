# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0] - Unreleased

### Added

- `DateFormat` option of `Writer`, to write dates month first (`new Writer(DateFormat::MonthFirst)`): a file read with `DateFormat::MonthFirst` can be written back the same way. `Transaction::toQif()` takes it too, and `DateFormat::writeFormat()` returns the format (`d/m/Y` or `m/d/Y`).
- `ParseException::getLineNumber()`, `getRecordIndex()` (the index the transaction would have had in `getTransactions()`) and `getReason()` (the message without the location).

### Changed

- **BC break:** an empty field (a bare `P`, `M`, `L`, `A` or `E` line) is skipped like a missing one, as the empty `D` and `T` lines already were: `getDescription()`, `getMemo()` and `getCategory()` return `null` instead of `''`, an empty `A` line is left out of the address and an empty split memo is `null`. Test `null` instead of `''`. An empty `S` line still starts a split, with an empty category.
- **BC break:** the message of a `ParseException` starts with its location, `Line 7 (record index 1): "12 EUR" is not an amount.`, and its constructor takes the reason, the line number and the record index. Match the end of the message, or use `getReason()`.
- An empty `$` line in a split throws `The split "…" has no amount ($ line).` like a missing one, instead of `"" is not an amount.`

### Fixed

- A record without any value (a stray `^`, or only empty fields) is skipped: `"^\n^"` returned 2 transactions with only `null` fields.
- The amount messages show the amount as written: `12 EUR` was reported as `"12EUR" is not an amount.`
- After a `ParseException`, `Parser::getTransactions()` returns the transactions read before the error (documented and tested).
- `Transaction::$_raw` ends its lines with the line break of the file instead of the parser separator (`"\r\n"` by default, even for Unix files).

## [3.0.0] - 2026-10-12

### Added

- `DateFormat` option of `Parser` and `Parser::parseFile()`: `DayFirst` (`25/12/2023`, the default) or `MonthFirst` (`12/25/2023`, US versions of Quicken). ISO dates are read with either.
- `DecimalSeparator` option of `Parser` and `Parser::parseFile()`: `Point` (`1,234.50`) or `Comma` (`1.234,50`). By default the separator is detected for each amount.
- `Silarhi\Qif\Exception\ParseException`, a `RuntimeException` thrown on values that cannot be read without guessing.

### Changed

- **BC break:** the `DetailItems`, `Types` and `Status` enum cases are PascalCase, like the new `DateFormat` and `DecimalSeparator`. Their backed values are unchanged, so parsed and written QIF files are identical: only code referring to the case names needs updating.
    - `DetailItems`: `DATE` → `Date`, `AMOUNT` → `Amount`, `AMOUNT_ALT` → `AmountAlt`, `MEMO` → `Memo`, `CLEARED_STATUS` → `ClearedStatus`, `CHECK_NUMBER` → `CheckNumber`, `ADDRESS` → `Address`, `CATEGORY` → `Category`, `PAYEE` → `Payee`, `REIMBURSABLE` → `Reimbursable`, `SPLIT_AMOUNT` → `SplitAmount`, `SPLIT_CATEGORY` → `SplitCategory`, `SPLIT_MEMO` → `SplitMemo`, `SPLIT_PERCENT` → `SplitPercent`, `SECURITY` → `Security`, `PRICE` → `Price`, `QUANTITY` → `Quantity`, `COMMISSION` → `Commission`, `BUDGETED_AMOUNT` → `BudgetedAmount`, `EXTENDED` → `Extended`, `INVOICE_SHIP_TO_ADDRESS` → `InvoiceShipToAddress`, `INVOICE_TYPE` → `InvoiceType`, `INVOICE_DUE_DATE` → `InvoiceDueDate`, `INVOICE_TAX_ACCOUNT` → `InvoiceTaxAccount`, `INVOICE_TAX_RATE` → `InvoiceTaxRate`, `INVOICE_TAX_AMOUNT` → `InvoiceTaxAmount`, `INVOICE_LINE_DESCRIPTION` → `InvoiceLineDescription`, `INVOICE_LINE_CATEGORY` → `InvoiceLineCategory`, `INVOICE_LINE_QUANTITY` → `InvoiceLineQuantity`, `INVOICE_LINE_PRICE` → `InvoiceLinePrice`, `INVOICE_LINE_TAXABLE` → `InvoiceLineTaxable`.
    - `Types`: `CASH` → `Cash`, `BANK` → `Bank`, `CREDIT_CARD` → `CreditCard`, `INVESTMENT` → `Investment`, `OTHER_ASSET` → `OtherAsset`, `OTHER_LIABILITY` → `OtherLiability`, `INVOICE` → `Invoice`.
    - `Status`: `NOT_CLEARED` → `NotCleared`, `CLEARED` → `Cleared`, `RECONCILED` → `Reconciled`.
- **BC break:** `Transaction::getSplits()` returns a list of `['category' => string, 'amount' => float, 'memo' => ?string]` instead of an array keyed by category, as QIF files can contain several splits with the same category. `addSplit()` no longer throws on a duplicate category and its first parameter is renamed `$category`; `removeSplit()` removes every split of the category.
- **BC break:** the parser throws a `ParseException` instead of storing a wrong or missing value:
    - on a date that does not exist (`31/02/2023` was read as 3 March, and the US date `01/31/2026` as 1 July 2028) or cannot be read (the date was null). Pass `DateFormat::MonthFirst` for month-first files;
    - on an amount that is not a number (`12 EUR` was read as `12.0`) or is ambiguous (`1,234`: `1234` or `1.234`). Pass a `DecimalSeparator` to read such files;
    - on a split without amount (`$` line).
- **BC break:** `Transaction::setAmount()` and `addSplit()` read string amounts with thousands separators (`'1,234.50'` was stored as `1.0`) and throw an `InvalidArgumentException` on a string that is not an amount (stored as `0.0`) or is ambiguous (`'1,234'`).
- Parsing is about 6% slower, for the amount and date validation.

### Fixed

- The parser reads splits: split category (`S`), memo (`E`) and amount (`$`). `getSplits()` of a parsed transaction was always empty. A `$` line outside a split (investment transfer amount) is ignored.
- The writer outputs the memo (`M`) and the address (`A`) of transactions.
- A multi-line address keeps every `A` line, joined with `"\n"` (only the last one was kept); the writer outputs one `A` line per line.
- The last transaction of a file is kept when it misses its closing `^`, and so is a transaction followed by a new `!Type:` header.
- Calling `parse()` again, or after `Parser::parseFile()`, replaces the transactions instead of adding them a second time.
- Amounts with a decimal comma are read correctly: `1.234,50` was read as `1.2345`.
- Account, category, class, security and price lists (`!Account`, `!Type:Cat`, `!Type:Class`, `!Type:Security`, `!Type:Prices`) are skipped: an account record was returned as a transaction with a null type and an amount of `0.0`. `!Option:` and `!Clear:` lines no longer reset the type of the following transactions to null.
- `Transaction::$_raw` separates the `!Type:` header from the first field line (`!Type:BankD25/12/2023`) and keeps the header of unknown types (`!Type:Memorized` was `!Type:`).
- Parsed dates are at midnight instead of the time of parsing.

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

[4.0.0]: https://github.com/silarhi/qif-library/compare/3.0.0...HEAD
[3.0.0]: https://github.com/silarhi/qif-library/compare/2.0.0...3.0.0
[2.0.0]: https://github.com/silarhi/qif-library/compare/1.0.0...2.0.0
[1.0.0]: https://github.com/silarhi/qif-library/releases/tag/1.0.0
