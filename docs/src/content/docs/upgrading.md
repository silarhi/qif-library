---
title: Upgrading
description: Upgrade from mimographix/qif-library, and from version 1 to version 2.
---

The full list of changes is in the [changelog](https://github.com/silarhi/qif-library/blob/main/CHANGELOG.md).

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
   (`DetailItems::D->value` in 1.x, `DetailItems::DATE->value` since 2.0).
5. `Transaction::setStatus()` takes a [`Status`](/reference/enums/#status) case.
6. `nesbot/carbon` is no longer a dependency: `Transaction::setDate()` takes and `getDate()` returns a
   `DateTimeImmutable`.
7. `Parser::parseFile()` throws a `RuntimeException` when the file is missing or unreadable, and
   `Writer::saveToFile()` when the file cannot be written, instead of a PHP warning or `die()`.

Then follow [From 1.x to 2.0](#from-1x-to-20).
