---
title: Classes
description: The public methods of Parser, Transaction and Writer.
sidebar:
    order: 1
---

## Parser

`Silarhi\Qif\Parser` reads QIF content. See [Parsing QIF files](/guides/parsing/).

```php
public function __construct(
    string $fileContent,
    string $separator = "\r\n",
    DateFormat $dateFormat = DateFormat::DayFirst,
    ?DecimalSeparator $decimalSeparator = null,
)

public static function parseFile(
    string $filePath,
    DateFormat $dateFormat = DateFormat::DayFirst,
    ?DecimalSeparator $decimalSeparator = null,
): Parser
```

| Method               | Returns         | Description                                                                                               |
| -------------------- | --------------- | --------------------------------------------------------------------------------------------------------- |
| `__construct()`      |                 | The content to parse; each character of `$separator` ends a line. See [Options](/guides/parsing/#options) |
| `static parseFile()` | `Parser`        | Reads and parses a file. Throws a `RuntimeException` or a `ParseException`                                |
| `parse()`            | `Transaction[]` | Parses the content, replacing the transactions of a previous call. Throws a `ParseException`              |
| `getTransactions()`  | `Transaction[]` | The transactions of the last `parse()` call, an empty array before; those read before a `ParseException`  |

`$decimalSeparator` is `null` to detect the decimal separator of each amount. The enums `DateFormat` and
`DecimalSeparator` are in `Silarhi\Qif\Enums`: see [DateFormat](/reference/enums/#dateformat) and
[DecimalSeparator](/reference/enums/#decimalseparator).

## Transaction

`Silarhi\Qif\Transaction` is one QIF record. It implements `Stringable`: `(string) $transaction` returns the record in
QIF, dates day first, and `toQif(DateFormat $dateFormat = DateFormat::DayFirst): string` in the date format you pass.
See [Writing QIF files](/guides/writing/).

`__construct(?Types $type)`: the account type can't be changed afterwards. A new transaction has no date, amount, payee,
category, memo, address or split, and the `Status::NotCleared` status.

| Getter                          | Setters                                                                                                         | QIF line      |
| ------------------------------- | --------------------------------------------------------------------------------------------------------------- | ------------- |
| `getType(): ?Types`             | None: set by the constructor                                                                                    | `!Type:`      |
| `getDate(): ?DateTimeImmutable` | `setDate(DateTimeImmutable $date)`                                                                              | `D`           |
| `getAmount(): ?float`           | `setAmount(float\|int\|string $float)`                                                                          | `T`           |
| `getDescription(): ?string`     | `setDescription(?string $description)`                                                                          | `P`           |
| `getCategory(): ?string`        | `setCategory(?string $category)`                                                                                | `L`           |
| `getMemo(): ?string`            | `setMemo(?string $memo)`                                                                                        | `M`           |
| `getAddress(): ?string`         | `setAddress(?string $address)`, its lines separated by `"\n"`                                                   | `A`           |
| `getStatus(): Status`           | `setStatus(Status $status)`, `markAsReconciled()`, `markAsCleared()`, `markAsNotCleared()`                      | `C`           |
| `getSplits(): array`            | `addSplit(string $category, float\|int\|string $amount, ?string $memo = null)`, `removeSplit(string $category)` | `S`, `$`, `E` |

Setters return the transaction. The parser fills every field and the writer outputs every field: see
[Parsing](/guides/parsing/#what-you-get) and [Writing](/guides/writing/#output-format).

`setAmount()` and `addSplit()` read a string amount with its thousands separators, in either convention (`'1,234.50'`,
`'1.234,50'`), and throw an `InvalidArgumentException` on a string that is not an amount or is ambiguous (`'1,234'`),
or an amount that is not finite: see [String amounts](/guides/writing/#string-amounts). `setDescription()`,
`setCategory()`, `setMemo()` and `addSplit()` throw an `InvalidArgumentException` on a value with a line break: only
the address has several lines.

`getSplits()` returns a `list<array{category: string, amount: float, memo: ?string}>`, in the order the splits were
added or parsed. Several splits can share a category:

```php
[
    ['category' => 'Sales', 'amount' => 18.0, 'memo' => null],
    ['category' => 'Tax', 'amount' => 0.99, 'memo' => 'VAT'],
]
```

`addSplit()` adds a split at the end, `removeSplit()` removes every split of the category (and does nothing when there
is none). The public `?string $_raw` property holds the lines a parsed transaction was read from (see
[Raw record](/guides/parsing/#raw-record)).

## Writer

`Silarhi\Qif\Writer` turns transactions into QIF. It implements `Stringable`: `(string) $writer` returns the content.

```php
public function __construct(DateFormat $dateFormat = DateFormat::DayFirst)
```

`$dateFormat` is the order of the day and month in the dates it writes: see
[Date format](/guides/writing/#date-format).

| Method                                     | Returns         | Description                                                               |
| ------------------------------------------ | --------------- | ------------------------------------------------------------------------- |
| `addTransaction(Transaction $transaction)` | `void`          | Adds a transaction                                                        |
| `getTransactions()`                        | `Transaction[]` | The transactions added so far                                             |
| `saveToFile(string $filePath)`             | `void`          | Writes the content to the file, replacing it. Throws a `RuntimeException` |

## ParseException

`Silarhi\Qif\Exception\ParseException` extends `RuntimeException`. Its message starts with where the value is
(`Line 7 (record index 1): "12 EUR" is not an amount.`), also returned by `getLineNumber(): int`,
`getRecordIndex(): int` and `getReason(): string`. See [Exceptions](/reference/exceptions/#parseexception).
