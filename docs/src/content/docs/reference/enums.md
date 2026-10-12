---
title: Enums
description: The account types, cleared statuses and QIF field codes, and which fields the library reads and writes.
sidebar:
    order: 2
---

The three enums of `Silarhi\Qif\Enums` are string-backed: `->value` is what appears in the QIF file, and
`Types::tryFrom('CCard')` returns `Types::CREDIT_CARD`.

## Types

The account type of a `!Type:` header, returned by `Transaction::getType()` and taken by `new Transaction()`.

| Case              | Value     | Account                             |
| ----------------- | --------- | ----------------------------------- |
| `CASH`            | `Cash`    | Cash account                        |
| `BANK`            | `Bank`    | Checking and savings account        |
| `CREDIT_CARD`     | `CCard`   | Credit card account                 |
| `INVESTMENT`      | `Invst`   | Investment account                  |
| `OTHER_ASSET`     | `Oth A`   | Asset                               |
| `OTHER_LIABILITY` | `Oth L`   | Liability                           |
| `INVOICE`         | `Invoice` | Invoice (Quicken for Business only) |

Other headers (`!Account`, `!Type:Cat`, `!Type:Class`, `!Type:Memorized`…) have no case: the parser gives their records
a `null` type.

## Status

The cleared status of a transaction, its `C` line.

| Case          | Value | Parsed from               | Written as |
| ------------- | ----- | ------------------------- | ---------- |
| `NOT_CLEARED` | `''`  | An empty or unknown value | `C`        |
| `CLEARED`     | `c`   | `*`, `c`                  | `Cc`       |
| `RECONCILED`  | `X`   | `X`, `R`                  | `CX`       |

## DetailItems

The QIF field codes: the first character(s) of a line. Few of them are read or written by the library; the others are
listed for reference, e.g. `DetailItems::DATE->value` is `D`.

| Case                       | Code | Field                                              | Read | Written |
| -------------------------- | ---- | -------------------------------------------------- | ---- | ------- |
| `DATE`                     | `D`  | Date                                               | ✓    | ✓       |
| `AMOUNT`                   | `T`  | Amount                                             | ✓    | ✓       |
| `AMOUNT_ALT`               | `U`  | Amount, same as `T` (Quicken 2015 writes both)     | ✓    |         |
| `MEMO`                     | `M`  | Memo                                               | ✓    |         |
| `CLEARED_STATUS`           | `C`  | Cleared status                                     | ✓    | ✓       |
| `CHECK_NUMBER`             | `N`  | Check number, or `Deposit`, `Transfer`, `ATM`…     |      |         |
| `ADDRESS`                  | `A`  | Address of the payee, up to 6 lines                | ✓    |         |
| `CATEGORY`                 | `L`  | Category or transfer, and class (`Fuel:Car/Work`)  | ✓    | ✓       |
| `PAYEE`                    | `P`  | Payee, or a description                            | ✓    | ✓       |
| `REIMBURSABLE`             | `F`  | Reimbursable business expense flag                 |      |         |
| `SPLIT_AMOUNT`             | `$`  | Amount of a split                                  |      | ✓       |
| `SPLIT_CATEGORY`           | `S`  | Category of a split                                |      | ✓       |
| `SPLIT_MEMO`               | `E`  | Memo of a split                                    |      | ✓       |
| `SPLIT_PERCENT`            | `%`  | Percentage of a split                              |      |         |
| `SECURITY`                 | `Y`  | Security name (investment)                         |      |         |
| `PRICE`                    | `I`  | Price (investment)                                 |      |         |
| `QUANTITY`                 | `Q`  | Quantity of shares (investment)                    |      |         |
| `COMMISSION`               | `O`  | Commission cost (investment)                       |      |         |
| `BUDGETED_AMOUNT`          | `B`  | Budgeted amount (categories)                       |      |         |
| `EXTENDED`                 | `X`  | Extended data for Quicken Business (invoices)      |      |         |
| `INVOICE_SHIP_TO_ADDRESS`  | `XA` | Ship-to address                                    |      |         |
| `INVOICE_TYPE`             | `XI` | Invoice transaction type: `1` invoice, `3` payment |      |         |
| `INVOICE_DUE_DATE`         | `XE` | Invoice due date                                   |      |         |
| `INVOICE_TAX_ACCOUNT`      | `XC` | Tax account                                        |      |         |
| `INVOICE_TAX_RATE`         | `XR` | Tax rate                                           |      |         |
| `INVOICE_TAX_AMOUNT`       | `XT` | Tax amount                                         |      |         |
| `INVOICE_LINE_DESCRIPTION` | `XS` | Line item description                              |      |         |
| `INVOICE_LINE_CATEGORY`    | `XN` | Line item category                                 |      |         |
| `INVOICE_LINE_QUANTITY`    | `X#` | Line item quantity                                 |      |         |
| `INVOICE_LINE_PRICE`       | `X$` | Line item unit price                               |      |         |
| `INVOICE_LINE_TAXABLE`     | `XF` | Line item taxable flag                             |      |         |

A record also has the `!Type:` header before it and ends with a `^` line, which are not part of the enum.
