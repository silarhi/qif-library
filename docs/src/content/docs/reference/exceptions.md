---
title: Exceptions
description: Every exception the library throws, with its message.
sidebar:
    order: 3
---

The library throws `Silarhi\Qif\Exception\ParseException` (a `RuntimeException`) on QIF content it cannot read without
guessing, and PHP's own exceptions otherwise: `RuntimeException` for files, `InvalidArgumentException` for string
amounts. The `ParseException` messages below are its reason: the message starts with its location, see
[ParseException](#parseexception).

| Method                                   | Exception                  | Message                                                                                                                    | When                                                             |
| ---------------------------------------- | -------------------------- | -------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| `Parser::parseFile()`                    | `RuntimeException`         | `File not found or not readable: {path}`                                                                                   | The file is missing or not readable                              |
| `Parser::parseFile()`                    | `RuntimeException`         | `Failed to read file: {path}`                                                                                              | Reading the file fails                                           |
| `Parser::parse()`                        | `RuntimeException`         | `Failed to clean file content`                                                                                             | Removing the UTF-8 BOM fails (PCRE error)                        |
| `Parser::parse()`                        | `ParseException`           | `"{date}" is not a valid date with the day first (DateFormat::DayFirst).`                                                  | A `D` value that is not a date, or does not exist (`31/02`)      |
| `Parser::parse()`                        | `ParseException`           | `"{date}" is not a valid date with the month first (DateFormat::MonthFirst).`                                              | The same, with `DateFormat::MonthFirst`                          |
| `Parser::parse()`                        | `ParseException`           | `"{amount}" is not an amount.`                                                                                             | A `T`, `U` or `$` value that is not a number                     |
| `Parser::parse()`                        | `ParseException`           | `"{amount}" is ambiguous: "{separator}" can be a decimal or a thousands separator. Pass the decimal separator explicitly.` | An amount like `1,234`, without `DecimalSeparator`               |
| `Parser::parse()`                        | `ParseException`           | `"{amount}" is not an amount with "{separator}" as decimal separator.`                                                     | Two decimal separators, or a thousands separator after it        |
| `Parser::parse()`                        | `ParseException`           | `"{amount}" does not group its thousands by "{separator}".`                                                                | Thousands not grouped by 3 (`1,23,456`), or the other convention |
| `Parser::parse()`                        | `ParseException`           | `The split "{category}" has no amount ($ line).`                                                                           | An `S` line without `$` line, or with an empty one               |
| `Writer::saveToFile()`                   | `RuntimeException`         | `Directory not writable: {directory}`                                                                                      | The directory of the file is not writable                        |
| `Writer::saveToFile()`                   | `RuntimeException`         | `Unable to open file: {path}`                                                                                              | The file cannot be opened for writing                            |
| `Transaction::setAmount()`, `addSplit()` | `InvalidArgumentException` | The amount messages above                                                                                                  | A string that is not an amount or is ambiguous (`'1,234'`)       |

`Parser::parseFile()` also throws what `parse()` throws. In the amount messages, `{amount}` is the value as written,
spaces included. The other malformed content does not throw: see
[What does not throw](/guides/error-handling/#what-does-not-throw).

## ParseException

The message of a `ParseException` starts with where the value is: `Line {line} (record index {index}): {reason}`, e.g.
`Line 7 (record index 1): "12 EUR" is not an amount.` The parts are also returned by its methods:

| Method                  | Returns                                                                                                    |
| ----------------------- | ---------------------------------------------------------------------------------------------------------- |
| `getLineNumber(): int`  | The line of the value, from 1, empty lines included. A split without amount is reported on its `S` line    |
| `getRecordIndex(): int` | The index the transaction would have had in `Parser::getTransactions()`, from 0: the number read before it |
| `getReason(): string`   | The message without the location, one of the `ParseException` messages above                               |

`getPrevious()` returns the `InvalidArgumentException` of an invalid date or amount, `null` for a split without
amount. See [Where it happened](/guides/error-handling/#where-it-happened).
