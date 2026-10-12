---
title: Classes
description: The public methods of Parser, Transaction and Writer.
sidebar:
    order: 1
---

## Parser

`Silarhi\Qif\Parser` reads QIF content. See [Parsing QIF files](/guides/parsing/).

| Method                                                         | Returns         | Description                                                                |
| -------------------------------------------------------------- | --------------- | -------------------------------------------------------------------------- |
| `__construct(string $fileContent, string $separator = "\r\n")` |                 | The content to parse; each character of `$separator` ends a line           |
| `static parseFile(string $filePath)`                           | `Parser`        | Reads and parses a file. Throws a `RuntimeException`                       |
| `parse()`                                                      | `Transaction[]` | Parses the content, adds the transactions to the parser's list, returns it |
| `getTransactions()`                                            | `Transaction[]` | The transactions parsed so far                                             |

## Transaction

`Silarhi\Qif\Transaction` is one QIF record. It implements `Stringable`: `(string) $transaction` returns the record in
QIF. See [Writing QIF files](/guides/writing/).

`__construct(?Types $type)`: the account type can't be changed afterwards. A new transaction has no date, amount, payee,
category, memo, address or split, and the `Status::NOT_CLEARED` status.

| Getter                          | Setters                                                                                                           | QIF line      |
| ------------------------------- | ----------------------------------------------------------------------------------------------------------------- | ------------- |
| `getType(): ?Types`             | None: set by the constructor                                                                                      | `!Type:`      |
| `getDate(): ?DateTimeImmutable` | `setDate(DateTimeImmutable $date)`                                                                                | `D`           |
| `getAmount(): ?float`           | `setAmount(float\|int\|string $float)`, cast to `float`                                                           | `T`           |
| `getDescription(): ?string`     | `setDescription(?string $description)`                                                                            | `P`           |
| `getCategory(): ?string`        | `setCategory(?string $category)`                                                                                  | `L`           |
| `getMemo(): ?string`            | `setMemo(?string $memo)`                                                                                          | `M`           |
| `getAddress(): ?string`         | `setAddress(?string $address)`                                                                                    | `A`           |
| `getStatus(): Status`           | `setStatus(Status $status)`, `markAsReconciled()`, `markAsCleared()`, `markAsNotCleared()`                        | `C`           |
| `getSplits(): array`            | `addSplit(string $splitName, float\|int\|string $amount, ?string $memo = null)`, `removeSplit(string $splitName)` | `S`, `$`, `E` |

Setters return the transaction. The parser fills every field but the splits, and the writer leaves out the memo and
the address: see [Parsing](/guides/parsing/#what-you-get) and [Writing](/guides/writing/#output-format).

`getSplits()` returns `array<string, array{amount: float, memo: ?string}>`, keyed
by the split name:

```php
['Sales' => ['amount' => 18.0, 'memo' => null], 'Tax' => ['amount' => 0.99, 'memo' => 'VAT']]
```

`addSplit()` throws an `Exception` when a split with the same name exists. The public `?string $_raw` property holds the
lines a parsed transaction was read from (see [Raw record](/guides/parsing/#raw-record)).

## Writer

`Silarhi\Qif\Writer` turns transactions into QIF. It implements `Stringable`: `(string) $writer` returns the content.

| Method                                     | Returns         | Description                                                               |
| ------------------------------------------ | --------------- | ------------------------------------------------------------------------- |
| `addTransaction(Transaction $transaction)` | `void`          | Adds a transaction                                                        |
| `getTransactions()`                        | `Transaction[]` | The transactions added so far                                             |
| `saveToFile(string $filePath)`             | `void`          | Writes the content to the file, replacing it. Throws a `RuntimeException` |
