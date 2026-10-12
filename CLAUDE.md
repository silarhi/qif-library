# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`silarhi/qif-library` (namespace `Silarhi\Qif`) is a PHP library for parsing and writing QIF (Quicken Interchange Format) files. The library provides two main components: a Parser for reading QIF files and a Writer for generating QIF output. Published on Packagist under MIT license; originally forked from `mimographix/qif-library`.

## Architecture

### Core Classes

- **Parser** (`src/Parser.php`): Parses QIF file content and converts it to Transaction objects. Uses `strtok()` for line-by-line parsing and a switch statement to handle different QIF field types.
- **Transaction** (`src/Transaction.php`): Represents a single QIF transaction with properties like date, amount, description, category, splits, and status. Implements `Stringable` to output QIF format.
- **Writer** (`src/Writer.php`): Collects Transaction objects and outputs them in QIF format.

### Enums

- **DetailItems** (`src/Enums/DetailItems.php`): Backed string enum defining all QIF field codes (D for date, T for amount, M for memo, etc.)
- **DateFormat** (`src/Enums/DateFormat.php`): Parser and Writer option, `DayFirst` (default) or `MonthFirst` slash dates (`formats()` to read, `writeFormat()` to write)
- **DecimalSeparator** (`src/Enums/DecimalSeparator.php`): Parser option, `Point` or `Comma`; null (default) detects it per amount
- **Types** (`src/Enums/Types.php`): Backed string enum for QIF transaction types (Cash, Bank, CCard, Invst, etc.)
- **Status** (`src/Enums/Status.php`): Backed string enum for the cleared status (`NotCleared` = '', `Cleared` = 'c', `Reconciled` = 'X')

### Key Design Patterns

- Enums are PHP 8.1+ backed enums (not class constants). Access values with `->value` (e.g., `DetailItems::Date->value`)
- Enum cases are PascalCase (`Types::CreditCard`, `Status::NotCleared`, `DateFormat::DayFirst`), never UPPER_SNAKE_CASE
- Transaction splits are a list (several splits can share a category): `[['category' => string, 'amount' => float, 'memo' => ?string], ...]`
- All constructor properties are `readonly` where appropriate
- Methods use fluent interface pattern (return `self`) for chaining setters

## Development Commands

### Testing

```bash
# Run all tests
vendor/bin/phpunit

# Run tests with detailed output
vendor/bin/phpunit --testdox

# Run specific test file
vendor/bin/phpunit tests/Unit/ParserTest.php

# Run tests with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html var/coverage
```

### Code Quality Tools

```bash
# Run PHPStan (level max + strict-rules) - includes src/ and tests/
vendor/bin/phpstan analyze

# Fix code style with PHP-CS-Fixer
vendor/bin/php-cs-fixer fix

# Run Rector (PHP sets + PHPUnit composer-based sets)
vendor/bin/rector process

# Dry run to preview Rector changes
vendor/bin/rector process --dry-run
```

### Code Style Configuration

- Follows `@Symfony` and `@Symfony:risky` rule sets
- Enforces `declare(strict_types=1)` on all files
- Uses short array syntax
- Requires the SILARHI header comment block
- Native function invocation optimization enabled
- Tests use `self::assert*()` (strict-rules forbids `$this->assert*()`)

## PHP Version Requirements

- Minimum: PHP 8.2
- Uses modern PHP features:
    - Backed enums (PHP 8.1+)
    - Readonly properties (PHP 8.1+)
    - Union types (`string|false`, `float|int|string`)
    - Mixed type
    - Constructor property promotion

## Dependencies

- **No runtime dependencies** (dates use native `DateTimeImmutable`)
- **Dev dependencies**: PHPUnit, PHPStan (+ strict-rules), PHP-CS-Fixer, Rector, composer-normalize
- Tooling mirrors the other SILARHI libraries (`caf-parser`, `cfonb-parser`): laminas CI matrix, Renovate, husky + lint-staged

## Important Implementation Details

### QIF Field Parsing

The Parser uses a switch statement matching single-character field codes from the DetailItems enum (`case DetailItems::Date->value:`). When adding new field types, update both the enum and the switch statement in `Parser::parse()`.

Dates are parsed once per distinct value within a `parse()` call (`$dateCache`), so transactions with the same date string share one `DateTimeImmutable` instance.

Never store a guessed value: impossible dates (`31/02`, detected through `DateTimeImmutable::getLastErrors()` warnings), ambiguous amounts (`1,234`) and splits without `$` throw a `ParseException`. Amounts go through the `@internal` `AmountParser`, which `Transaction::setAmount()`/`addSplit()` also use for strings; its messages show the amount as given (spaces included).

`parseDate()` and `AmountParser` throw `InvalidArgumentException`, which `parse()` wraps in a `ParseException` with the line number and the record index (`count($this->transactions)`). `strtok()` skips empty lines, so the loop only counts `$lineIndex` (among non-empty lines) and `lineNumber()` re-scans the content to the real line number only when throwing: keep per-line work out of the hot loop (a `strcspn()`/`strspn()` loop tracking line numbers was 60% slower). A split error is reported on its `S` line (`lineIndex` in the split array). After a `ParseException`, `getTransactions()` holds the transactions read before it.

Empty values: an empty field is skipped like a missing one (the getter stays `null`), except `S`, which always starts a split (its `$`/`E` lines belong to it; skipping it would put them on the previous split). The `Transaction` is only created at the first non-empty field, so a record without any value (a stray `^`) is skipped. `$_raw` uses the first line break of the file (`detectLineBreak()`).

`parse()` resets the transactions, skips non-transaction lists (`!Account`, `!Type:Cat|Class|Security|Prices`), ignores `!Option:`/`!Clear:` lines and keeps a last record without `^`. Each `S` line starts a split; `E`/`$` without an open split (investment transfers) are ignored. Multiple `A` lines are joined with `"\n"`, and the writer splits them back.

### Transaction Rendering

Transaction objects convert to QIF format via `toQif(DateFormat)` (`__toString()` = `toQif()`, day first), which concatenates the field lines directly (no intermediate arrays): null fields are skipped, empty strings are still rendered (e.g. `P` alone, parsed back as `null`), dates are formatted with `DateFormat::writeFormat()`. `Writer` takes a `DateFormat` and passes it to `toQif()`. Every parsed field is written back (date, amount, category, splits, status, payee, address, memo), so Writer output parses back to the same transactions.

### Error Handling

- File operations throw `RuntimeException` on failure
- Values the parser cannot read without guessing (invalid date, invalid or ambiguous amount, split without `$`) throw `Silarhi\Qif\Exception\ParseException` (a `RuntimeException`), whose message is `Line {n} (record index {i}): {reason}` (`getLineNumber()`, `getRecordIndex()`, `getReason()`)
- `Transaction::setAmount()`/`addSplit()` throw `InvalidArgumentException` on a string that is not an amount or is ambiguous
- All error messages include context (e.g., file paths, the value)

## Testing

The project uses PHPUnit 11 with comprehensive test coverage:

- **Unit tests**: Located in `tests/Unit/`
- **Test fixtures**: Sample QIF files in `tests/fixtures/`
- **Coverage**: All core classes (Parser, Writer, Transaction) and enums have full test coverage
- **PHPUnit config**: `phpunit.xml.dist` (Clover coverage in `coverage/`, used by the CI coverage badge)

### Test Structure

- `TransactionTest`: Tests transaction creation, setters/getters, fluent interface, splits, and string output
- `ParserTest`: Tests QIF parsing, multiple formats, date handling, status variations, and error cases
- `WriterTest`: Tests transaction collection, output generation, and file writing
- `Enums/*Test`: Tests enum values and tryFrom() functionality

## API Documentation

**Always update documentation when adding or modifying public API**: `Parser`, `Transaction`, `Writer`, the enums, what the parser reads and the writer outputs, the exceptions.

1. **`docs/`** — the documentation site (qif-library.silarhi.dev), built with Starlight: getting started, guides (parsing, writing, error handling), reference (classes, enums and QIF fields, exceptions), upgrading. Keep `README.md` short: update its features list or quick start only when they change.
2. **Examples with real output** — `docs/examples/*.php` run with the library (from `docs/examples/`, Composer's autoloader prepended), and `php docs/scripts/render-examples.php` writes what they print to `docs/src/data/examples/*.txt`, which the pages show through `docs/src/components/Example.astro`. Re-run it after any change to an example or to the output of the library, and commit the result: the CI runs it with `--check`.
3. **`CLAUDE.md`** — Architecture, Important Implementation Details.
4. **`CHANGELOG.md`** — see below. A BC break also gets a section in `docs/src/content/docs/upgrading.md`.

## Changelog & Releases

- **`CHANGELOG.md`** follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Every user-facing change gets an entry in the top section, titled with the next release, never a bare `[Unreleased]`: the next minor (e.g. `[2.1.0] - Unreleased`), or the next major for a BC break. Prefix breaking changes with **BC break:** and say what users must change. Skip dependency bumps, CI and tooling changes.
- **Releases**: date the section and point its compare link at the tag (`compare/<previous>...<tag>`, no `v` prefix), then tag `X.Y.Z` on `origin/main` once CI is green, with a GitHub release whose notes follow that section. Packagist updates from the GitHub hook.

## Common Workflows

### Adding New QIF Fields

1. Add enum case to `DetailItems` with backed string value
2. Update `Parser::parse()` switch statement to handle the field
3. Add property and getter/setter to `Transaction` if needed
4. Update `Transaction::__toString()` to output the field
5. Write tests in `ParserTest` and `TransactionTest` for the new field
6. Document it in `docs/` (the guides, and the "Read" / "Written" columns of `docs/src/content/docs/reference/enums.md`), then run `php docs/scripts/render-examples.php`
7. Run PHPStan, PHPUnit, and PHP-CS-Fixer

### Modifying Transaction Properties

All Transaction properties are private with public getters/setters. The `$type` property is readonly (set in constructor only). Use setter chaining for fluent API:

```php
$transaction->setDate($date)
    ->setAmount(100.00)
    ->setDescription('Payment')
    ->markAsReconciled();
```
