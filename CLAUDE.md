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
- **Types** (`src/Enums/Types.php`): Backed string enum for QIF transaction types (Cash, Bank, CCard, Invst, etc.)
- **Status** (`src/Enums/Status.php`): Backed string enum for the cleared status (`NOT_CLEARED` = '', `CLEARED` = 'c', `RECONCILED` = 'X')

### Key Design Patterns

- Enums are PHP 8.1+ backed enums (not class constants). Access values with `->value` (e.g., `DetailItems::DATE->value`)
- Transaction splits use array structure: `['amount' => float, 'memo' => ?string]`
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

The Parser uses a switch statement matching single-character field codes from the DetailItems enum (`case DetailItems::DATE->value:`). When adding new field types, update both the enum and the switch statement in `Parser::parse()`.

Dates are parsed once per distinct value within a `parse()` call (`$dateCache`), so transactions with the same date string share one `DateTimeImmutable` instance.

### Transaction Rendering

Transaction objects convert to QIF format via `__toString()`, which concatenates the field lines directly (no intermediate arrays): null fields are skipped, empty strings are still rendered (e.g. `P` alone), dates are formatted as 'd/m/Y'.

### Error Handling

- File operations throw `RuntimeException` on failure
- Split operations throw `Exception` for duplicate split names
- All error messages include context (e.g., file paths)

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
6. Run PHPStan, PHPUnit, and PHP-CS-Fixer

### Modifying Transaction Properties

All Transaction properties are private with public getters/setters. The `$type` property is readonly (set in constructor only). Use setter chaining for fluent API:

```php
$transaction->setDate($date)
    ->setAmount(100.00)
    ->setDescription('Payment')
    ->markAsReconciled();
```
