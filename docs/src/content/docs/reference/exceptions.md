---
title: Exceptions
description: Every exception the library throws, with its message.
sidebar:
    order: 3
---

The library throws PHP's own exceptions: `RuntimeException` for files, `Exception` for splits.

| Method                    | Exception          | Message                                              | When                                       |
| ------------------------- | ------------------ | ---------------------------------------------------- | ------------------------------------------ |
| `Parser::parseFile()`     | `RuntimeException` | `File not found or not readable: {path}`             | The file is missing or not readable        |
| `Parser::parseFile()`     | `RuntimeException` | `Failed to read file: {path}`                        | Reading the file fails                     |
| `Parser::parse()`         | `RuntimeException` | `Failed to clean file content`                       | Removing the UTF-8 BOM fails (PCRE error)  |
| `Writer::saveToFile()`    | `RuntimeException` | `Directory not writable: {directory}`                | The directory of the file is not writable  |
| `Writer::saveToFile()`    | `RuntimeException` | `Unable to open file: {path}`                        | The file cannot be opened for writing      |
| `Transaction::addSplit()` | `Exception`        | `Split "{name}" already exists in this transaction.` | The transaction has a split with that name |

`Parser::parseFile()` also throws what `parse()` throws. Malformed QIF content never throws: see
[What does not throw](/guides/error-handling/#what-does-not-throw).
