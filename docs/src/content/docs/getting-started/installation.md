---
title: Installation
description: Install the QIF Library with Composer.
sidebar:
    order: 1
---

## Requirements

- PHP 8.2+
- No other dependency: dates are native `DateTimeImmutable` objects.

## Installation

```bash
composer require silarhi/qif-library
```

The classes live in the `Silarhi\Qif` namespace, the enums in `Silarhi\Qif\Enums`:

| Class                                  | Role                                                             |
| -------------------------------------- | ---------------------------------------------------------------- |
| `Silarhi\Qif\Parser`                   | Reads QIF content into `Transaction` objects                     |
| `Silarhi\Qif\Writer`                   | Turns `Transaction` objects into QIF content                     |
| `Silarhi\Qif\Transaction`              | One QIF record: date, amount, payee, category, splits, status…   |
| `Silarhi\Qif\Enums\Types`              | The account type of the `!Type:` header (`Bank`, `CreditCard`…)  |
| `Silarhi\Qif\Enums\Status`             | The cleared status (`NotCleared`, `Cleared`, `Reconciled`)       |
| `Silarhi\Qif\Enums\DetailItems`        | The QIF field codes (`Date` is `D`, `Amount` is `T`…)            |
| `Silarhi\Qif\Enums\DateFormat`         | Parser option: slash dates day first (`DayFirst`) or month first |
| `Silarhi\Qif\Enums\DecimalSeparator`   | Parser option: amounts with a decimal `Point` or `Comma`         |
| `Silarhi\Qif\Exception\ParseException` | Thrown on a value the parser cannot read without guessing        |

Coming from `mimographix/qif-library`, or from version 1 or 2? See [Upgrading](/upgrading/).
