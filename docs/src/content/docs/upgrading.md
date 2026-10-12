---
title: Upgrading
description: Upgrade from mimographix/qif-library, and from each major version to the next one.
---

The full list of changes is in the [changelog](https://github.com/silarhi/qif-library/blob/main/CHANGELOG.md).

## From 3.x to 4.0

4.0 reads empty fields and empty records the same way everywhere, and tells where a `ParseException` happened.

### Empty fields are `null`

An empty field (a bare `P`, `M`, `L`, `A` or `E` line) is skipped like a missing one, as the empty `D` and `T` lines
already were: the getter returns `null` where 3.0 returned an empty string.

| Input                                    | 3.0                                      | 4.0                                       |
| ---------------------------------------- | ---------------------------------------- | ----------------------------------------- |
| A bare `P`, `M` or `L` line              | `''`                                     | `null`                                    |
| A bare `A` line                          | An empty line in the address             | Left out of the address                   |
| A bare `E` line in a split               | `'memo' => ''`                           | `'memo' => null`                          |
| A bare `$` line in a split               | `ParseException`: `"" is not an amount.` | `ParseException`: the split has no amount |
| A record without any value (a stray `^`) | A transaction with `null` fields         | Skipped                                   |

Code testing `'' === $transaction->getDescription()` (or the memo, category, address) must test `null` instead, and a
file with stray `^` lines gives fewer transactions. An empty `S` line still starts a split, with an empty category.

### ParseException messages start with the location

The message of a `ParseException` starts with the line and the record index of the value:
`Line 7 (record index 1): "12 EUR" is not an amount.` Code matching the whole message must match its end, or use
`getReason()`, which returns the message without the location. Its constructor takes the reason, the line number and
the record index: see [ParseException](/reference/exceptions/#parseexception).

The amount messages show the amount as written in the file: `"12 EUR" is not an amount.` (3.0: `"12EUR"`).

### Unchanged by default

- The writer still writes dates day first: pass `DateFormat::MonthFirst` to `new Writer()` to write them month first.
- `Transaction::$_raw` ends its lines with the line break of the file instead of the parser separator (`"\r\n"` by
  default). Its format is not part of the API.

## From 2.x to 3.0

3.0 renames the enum cases once more, and stops losing data silently: the parser reads the splits and throws on values
it would have guessed, the writer writes the memo and the address. Check each section below against your code.

### Enum cases are PascalCase

The `DetailItems`, `Types` and `Status` enum cases are PascalCase, like the new `DateFormat` and `DecimalSeparator`.
Their values are unchanged, so parsed and written QIF files are identical: only code referring to the case names needs
updating.

| `Types` 2.x       | `Types` 3.0      |
| ----------------- | ---------------- |
| `CASH`            | `Cash`           |
| `BANK`            | `Bank`           |
| `CREDIT_CARD`     | `CreditCard`     |
| `INVESTMENT`      | `Investment`     |
| `OTHER_ASSET`     | `OtherAsset`     |
| `OTHER_LIABILITY` | `OtherLiability` |
| `INVOICE`         | `Invoice`        |

| `Status` 2.x  | `Status` 3.0 |
| ------------- | ------------ |
| `NOT_CLEARED` | `NotCleared` |
| `CLEARED`     | `Cleared`    |
| `RECONCILED`  | `Reconciled` |

| `DetailItems` 2.x | `DetailItems` 3.0 | `DetailItems` 2.x          | `DetailItems` 3.0        |
| ----------------- | ----------------- | -------------------------- | ------------------------ |
| `DATE`            | `Date`            | `PRICE`                    | `Price`                  |
| `AMOUNT`          | `Amount`          | `QUANTITY`                 | `Quantity`               |
| `AMOUNT_ALT`      | `AmountAlt`       | `COMMISSION`               | `Commission`             |
| `MEMO`            | `Memo`            | `BUDGETED_AMOUNT`          | `BudgetedAmount`         |
| `CLEARED_STATUS`  | `ClearedStatus`   | `EXTENDED`                 | `Extended`               |
| `CHECK_NUMBER`    | `CheckNumber`     | `INVOICE_SHIP_TO_ADDRESS`  | `InvoiceShipToAddress`   |
| `ADDRESS`         | `Address`         | `INVOICE_TYPE`             | `InvoiceType`            |
| `CATEGORY`        | `Category`        | `INVOICE_DUE_DATE`         | `InvoiceDueDate`         |
| `PAYEE`           | `Payee`           | `INVOICE_TAX_ACCOUNT`      | `InvoiceTaxAccount`      |
| `REIMBURSABLE`    | `Reimbursable`    | `INVOICE_TAX_RATE`         | `InvoiceTaxRate`         |
| `SPLIT_AMOUNT`    | `SplitAmount`     | `INVOICE_TAX_AMOUNT`       | `InvoiceTaxAmount`       |
| `SPLIT_CATEGORY`  | `SplitCategory`   | `INVOICE_LINE_DESCRIPTION` | `InvoiceLineDescription` |
| `SPLIT_MEMO`      | `SplitMemo`       | `INVOICE_LINE_CATEGORY`    | `InvoiceLineCategory`    |
| `SPLIT_PERCENT`   | `SplitPercent`    | `INVOICE_LINE_QUANTITY`    | `InvoiceLineQuantity`    |
| `SECURITY`        | `Security`        | `INVOICE_LINE_PRICE`       | `InvoiceLinePrice`       |
|                   |                   | `INVOICE_LINE_TAXABLE`     | `InvoiceLineTaxable`     |

```php
// 2.x
$transaction = (new Transaction(Types::CREDIT_CARD))->setStatus(Status::RECONCILED);

// 3.0
$transaction = (new Transaction(Types::CreditCard))->setStatus(Status::Reconciled);
```

Code that stores the case names (`->name`) rather than the values gets the new names too.

### Splits are a list

`Transaction::getSplits()` returns a list instead of an array keyed by category, as a QIF file can hold several splits
with the same category:

```php
// 2.x
['Sales' => ['amount' => 18.0, 'memo' => null], 'Tax' => ['amount' => 0.99, 'memo' => 'VAT']]

// 3.0
[
    ['category' => 'Sales', 'amount' => 18.0, 'memo' => null],
    ['category' => 'Tax', 'amount' => 0.99, 'memo' => 'VAT'],
]
```

- `addSplit()` no longer throws an `Exception` when the category already has a split: it adds another one.
- Its first parameter is renamed `$splitName` → `$category`, and so is the one of `removeSplit()`: update the calls
  with named arguments.
- `removeSplit()` removes every split of the category.

### The parser throws instead of guessing

`Parser::parse()` and `Parser::parseFile()` throw a `Silarhi\Qif\Exception\ParseException`, a `RuntimeException`,
where 2.x stored a wrong or missing value:

| Input                                     | 2.x                      | 3.0                                                         |
| ----------------------------------------- | ------------------------ | ----------------------------------------------------------- |
| A date that does not exist (`31/02/2026`) | Rolled over: 3 March     | `ParseException`                                            |
| A month-first date (`01/31/2026`)         | Rolled over: 1 July 2028 | `ParseException`, or the date with `DateFormat::MonthFirst` |
| A value that is not a date (`Jan 31`)     | `null` date              | `ParseException`                                            |
| An amount that is not a number (`12 EUR`) | `12.0`                   | `ParseException`                                            |
| An ambiguous amount (`1,234`)             | `1234.0`                 | `ParseException`, or the amount with a `DecimalSeparator`   |
| A split without its `$` line              | Ignored, as every split  | `ParseException`                                            |

Files from the US versions of Quicken need `DateFormat::MonthFirst`, and files with amounts like `1,234` need their
`DecimalSeparator`:

```php
use Silarhi\Qif\Enums\DateFormat;
use Silarhi\Qif\Enums\DecimalSeparator;

$parser = Parser::parseFile('/path/to/export.qif', DateFormat::MonthFirst, DecimalSeparator::Point);
```

If you parse files you do not control, catch the `ParseException`: see [Error handling](/guides/error-handling/).

### String amounts

`Transaction::setAmount()` and `addSplit()` read string amounts like the parser: `'1,234.50'` and `'1.234,50'` are
`1234.5` (2.x stored `1.0` and `1.234`). A string that is not an amount (`'12 EUR'`, stored as `12.0`, or `'abc'`,
stored as `0.0`) or is ambiguous (`'1,234'`) throws an `InvalidArgumentException`. Floats and integers are unchanged.

### Fixed data loss

These fixes change what you get from the same file or the same transactions, without any code change:

- **Splits**: the parser reads the `S`, `E` and `$` lines; `getSplits()` of a parsed transaction was always empty. A
  `$` line outside a split (the transfer amount of investment records) is ignored.
- **Memo and address**: the writer outputs the memo (`M`) and the address (`A`), so a parsed file written back keeps
  them.
- **Multi-line addresses**: the parser keeps every `A` line, joined with `"\n"` (2.x kept the last one), and the
  writer outputs one `A` line per line.
- **Last record**: a last record without its closing `^` is kept, and so is a record followed by a new `!Type:`
  header.
- **Parsing twice**: calling `parse()` again, or after `Parser::parseFile()`, replaces the transactions instead of
  adding them a second time.
- **Decimal commas**: `1.234,50` is `1234.5`, not `1.2345`, and `12,50` is `12.5`.
- **Lists that are not transactions**: `!Account`, `!Type:Cat`, `!Type:Class`, `!Type:Security` and `!Type:Prices`
  records are skipped. 2.x returned them as transactions with a `null` type and an amount of `0.0`: files with an
  account list give fewer transactions. `!Option:` and `!Clear:` lines no longer reset the type of the following
  transactions to `null`.
- **Raw record**: `Transaction::$_raw` has the `!Type:` header on a line of its own (2.x glued it to the first field:
  `!Type:BankD25/12/2023`), and keeps the header of unknown types (`!Type:Memorized` was `!Type:`).
- **Midnight**: parsed dates are at midnight instead of the time of parsing: drop the `setTime(0, 0)` calls written
  around it.

Parsing is about 6% slower, for the amount and date validation.

## From 1.x to 2.0

The `DetailItems` and `Types` enum cases have descriptive names instead of single letters and abbreviations. Their
values are unchanged, so parsed and written QIF files are identical: only code referring to the case names needs
updating. `Status` is unchanged.

| `Types` 1.x | `Types` 2.0       |
| ----------- | ----------------- |
| `CASH`      | `CASH`            |
| `BANK`      | `BANK`            |
| `CCARD`     | `CREDIT_CARD`     |
| `INVST`     | `INVESTMENT`      |
| `OTHA`      | `OTHER_ASSET`     |
| `OTHL`      | `OTHER_LIABILITY` |
| `Invoice`   | `INVOICE`         |

| `DetailItems` 1.x | `DetailItems` 2.0 | `DetailItems` 1.x | `DetailItems` 2.0          |
| ----------------- | ----------------- | ----------------- | -------------------------- |
| `D`               | `DATE`            | `I`               | `PRICE`                    |
| `T`               | `AMOUNT`          | `Q`               | `QUANTITY`                 |
| `U`               | `AMOUNT_ALT`      | `O`               | `COMMISSION`               |
| `M`               | `MEMO`            | `B`               | `BUDGETED_AMOUNT`          |
| `C`               | `CLEARED_STATUS`  | `X`               | `EXTENDED`                 |
| `N`               | `CHECK_NUMBER`    | `XA`              | `INVOICE_SHIP_TO_ADDRESS`  |
| `A`               | `ADDRESS`         | `XI`              | `INVOICE_TYPE`             |
| `L`               | `CATEGORY`        | `XE`              | `INVOICE_DUE_DATE`         |
| `P`               | `PAYEE`           | `XC`              | `INVOICE_TAX_ACCOUNT`      |
| `F`               | `REIMBURSABLE`    | `XR`              | `INVOICE_TAX_RATE`         |
| `AMNT`            | `SPLIT_AMOUNT`    | `XT`              | `INVOICE_TAX_AMOUNT`       |
| `S`               | `SPLIT_CATEGORY`  | `XS`              | `INVOICE_LINE_DESCRIPTION` |
| `E`               | `SPLIT_MEMO`      | `XN`              | `INVOICE_LINE_CATEGORY`    |
| `PERC`            | `SPLIT_PERCENT`   | `X_HASH`          | `INVOICE_LINE_QUANTITY`    |
| `Y`               | `SECURITY`        | `X_AMNT`          | `INVOICE_LINE_PRICE`       |
|                   |                   | `XF`              | `INVOICE_LINE_TAXABLE`     |

```php
// 1.x
$transaction = new Transaction(Types::CCARD);

// 2.0
$transaction = new Transaction(Types::CREDIT_CARD);
```

Parsing is faster in 2.0 because each distinct date string is parsed once per `parse()` call: transactions with the
same date string now share one `DateTimeImmutable` instance. This only shows in code comparing dates with `===`.

Then follow [From 2.x to 3.0](#from-2x-to-30).

## From mimographix/qif-library to 1.0

`silarhi/qif-library` 1.0 is a fork of the unmaintained `mimographix/qif-library`.

1. Replace the package:

    ```bash
    composer remove mimographix/qif-library
    composer require silarhi/qif-library
    ```

2. Replace `MimoGraphix\QIF\` with `Silarhi\Qif\` in your `use` statements.
3. Use PHP 8.2 or later.
4. `DetailItems` and `Types` are backed enums instead of classes of constants: read their value with `->value`
   (`DetailItems::D->value` in 1.x, `DetailItems::DATE->value` in 2.x, `DetailItems::Date->value` since 3.0).
5. `Transaction::setStatus()` takes a [`Status`](/reference/enums/#status) case (`Status::RECONCILED` in 1.x and 2.x,
   `Status::Reconciled` since 3.0).
6. `nesbot/carbon` is no longer a dependency: `Transaction::setDate()` takes and `getDate()` returns a
   `DateTimeImmutable`.
7. `Parser::parseFile()` throws a `RuntimeException` when the file is missing or unreadable, and
   `Writer::saveToFile()` when the file cannot be written, instead of a PHP warning or `die()`.

Then follow [From 1.x to 2.0](#from-1x-to-20) and [From 2.x to 3.0](#from-2x-to-30).
