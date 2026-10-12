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

The classes live in the `Silarhi\Qif` namespace, and the enums in `Silarhi\Qif\Enums`:

| Class                           | Role                                                             |
| ------------------------------- | ---------------------------------------------------------------- |
| `Silarhi\Qif\Parser`            | Reads QIF content into `Transaction` objects                     |
| `Silarhi\Qif\Writer`            | Turns `Transaction` objects into QIF content                     |
| `Silarhi\Qif\Transaction`       | One QIF record: date, amount, payee, category, splits, status…   |
| `Silarhi\Qif\Enums\Types`       | The account type of the `!Type:` header (`BANK`, `CREDIT_CARD`…) |
| `Silarhi\Qif\Enums\Status`      | The cleared status (`NOT_CLEARED`, `CLEARED`, `RECONCILED`)      |
| `Silarhi\Qif\Enums\DetailItems` | The QIF field codes (`DATE` is `D`, `AMOUNT` is `T`…)            |

Coming from `mimographix/qif-library` or from version 1? See [Upgrading](/upgrading/).
