---
title: Enums
description: The account types, cleared statuses, QIF field codes and parser options, and which fields the library reads and writes.
sidebar:
    order: 2
---

The enums of `Silarhi\Qif\Enums` have PascalCase cases. `Types`, `Status`, `DetailItems` and `DecimalSeparator` are
string-backed: `->value` is what appears in the QIF file, and `Types::tryFrom('CCard')` returns `Types::CreditCard`.

## Types

The account type of a `!Type:` header, returned by `Transaction::getType()` and taken by `new Transaction()`.

| Case             | Value     | Account                             |
| ---------------- | --------- | ----------------------------------- |
| `Cash`           | `Cash`    | Cash account                        |
| `Bank`           | `Bank`    | Checking and savings account        |
| `CreditCard`     | `CCard`   | Credit card account                 |
| `Investment`     | `Invst`   | Investment account                  |
| `OtherAsset`     | `Oth A`   | Asset                               |
| `OtherLiability` | `Oth L`   | Liability                           |
| `Invoice`        | `Invoice` | Invoice (Quicken for Business only) |

The records of an unknown type (`!Type:Memorized`) get a `null` type. The lists that hold no transactions (`!Account`,
`!Type:Cat`, `!Type:Class`, `!Type:Security`, `!Type:Prices`) are skipped.

## Status

The cleared status of a transaction, its `C` line.

| Case         | Value | Parsed from               | Written as |
| ------------ | ----- | ------------------------- | ---------- |
| `NotCleared` | `''`  | An empty or unknown value | `C`        |
| `Cleared`    | `c`   | `*`, `c`                  | `Cc`       |
| `Reconciled` | `X`   | `X`, `R`                  | `CX`       |

## DateFormat

A parser option: the order of the day and month in the slash-separated dates of the `D` lines. See
[Dates](/guides/parsing/#dates). It is not backed.

| Case         | Reads                                |                            |
| ------------ | ------------------------------------ | -------------------------- |
| `DayFirst`   | `25/12/2023`, `25/12/23`, `25/12'23` | The default                |
| `MonthFirst` | `12/25/2023`, `12/25/23`, `12/25'23` | The US versions of Quicken |

Both read ISO dates (`2023-12-25`, `23-12-25`). `formats()` returns the `DateTimeImmutable` formats they try, in order.

## DecimalSeparator

A parser option: the decimal separator of the amounts. The other character is the thousands separator, returned by
`thousandsSeparator()`. See [Amounts](/guides/parsing/#amounts).

| Case    | Value | Thousands separator | Reads      |
| ------- | ----- | ------------------- | ---------- |
| `Point` | `.`   | `,`                 | `1,234.50` |
| `Comma` | `,`   | `.`                 | `1.234,50` |

Without it (`null`, the default), the parser detects the separator of each amount.

## DetailItems

The QIF field codes: the first character(s) of a line. Some of them are read and written by the library; the others
are listed for reference, e.g. `DetailItems::Date->value` is `D`.

| Case                     | Code | Field                                              | Read | Written |
| ------------------------ | ---- | -------------------------------------------------- | ---- | ------- |
| `Date`                   | `D`  | Date                                               | ✓    | ✓       |
| `Amount`                 | `T`  | Amount                                             | ✓    | ✓       |
| `AmountAlt`              | `U`  | Amount, same as `T` (Quicken 2015 writes both)     | ✓    |         |
| `Memo`                   | `M`  | Memo                                               | ✓    | ✓       |
| `ClearedStatus`          | `C`  | Cleared status                                     | ✓    | ✓       |
| `CheckNumber`            | `N`  | Check number, or `Deposit`, `Transfer`, `ATM`…     |      |         |
| `Address`                | `A`  | Address of the payee, one line per `A` line        | ✓    | ✓       |
| `Category`               | `L`  | Category or transfer, and class (`Fuel:Car/Work`)  | ✓    | ✓       |
| `Payee`                  | `P`  | Payee, or a description                            | ✓    | ✓       |
| `Reimbursable`           | `F`  | Reimbursable business expense flag                 |      |         |
| `SplitAmount`            | `$`  | Amount of a split                                  | ✓    | ✓       |
| `SplitCategory`          | `S`  | Category of a split                                | ✓    | ✓       |
| `SplitMemo`              | `E`  | Memo of a split                                    | ✓    | ✓       |
| `SplitPercent`           | `%`  | Percentage of a split                              |      |         |
| `Security`               | `Y`  | Security name (investment)                         |      |         |
| `Price`                  | `I`  | Price (investment)                                 |      |         |
| `Quantity`               | `Q`  | Quantity of shares (investment)                    |      |         |
| `Commission`             | `O`  | Commission cost (investment)                       |      |         |
| `BudgetedAmount`         | `B`  | Budgeted amount (categories)                       |      |         |
| `Extended`               | `X`  | Extended data for Quicken Business (invoices)      |      |         |
| `InvoiceShipToAddress`   | `XA` | Ship-to address                                    |      |         |
| `InvoiceType`            | `XI` | Invoice transaction type: `1` invoice, `3` payment |      |         |
| `InvoiceDueDate`         | `XE` | Invoice due date                                   |      |         |
| `InvoiceTaxAccount`      | `XC` | Tax account                                        |      |         |
| `InvoiceTaxRate`         | `XR` | Tax rate                                           |      |         |
| `InvoiceTaxAmount`       | `XT` | Tax amount                                         |      |         |
| `InvoiceLineDescription` | `XS` | Line item description                              |      |         |
| `InvoiceLineCategory`    | `XN` | Line item category                                 |      |         |
| `InvoiceLineQuantity`    | `X#` | Line item quantity                                 |      |         |
| `InvoiceLinePrice`       | `X$` | Line item unit price                               |      |         |
| `InvoiceLineTaxable`     | `XF` | Line item taxable flag                             |      |         |

A record also has the `!Type:` header before it and ends with a `^` line, which are not part of the enum.
