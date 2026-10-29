<?php

declare(strict_types=1);

/*
 * This file is part of the QIF Library package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silarhi\Qif;

use DateTimeImmutable;
use InvalidArgumentException;

use function is_string;

use Silarhi\Qif\Enums\DateFormat;
use Silarhi\Qif\Enums\DetailItems;
use Silarhi\Qif\Enums\Status;
use Silarhi\Qif\Enums\Types;

use function sprintf;

use Stringable;

use function strlen;

class Transaction implements Stringable
{
    private ?DateTimeImmutable $date = null;

    private ?string $description = null;

    private ?float $amount = null;

    private ?string $category = null;

    /**
     * @var list<array{category: string, amount: float, memo: ?string}>
     */
    private array $splits = [];

    private Status $status;

    private ?string $address = null;

    private ?string $memo = null;

    /**
     * Used in parser to capture original values
     */
    public ?string $_raw = null;

    public function __construct(private readonly ?Types $type)
    {
        $this->status = Status::NotCleared;
    }

    public function setDate(DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @throws InvalidArgumentException when the payee contains a line break
     */
    public function setDescription(?string $description): self
    {
        self::assertSingleLine($description, 'payee');
        $this->description = $description;

        return $this;
    }

    /**
     * @param float|int|string $float a string can group thousands, in either convention ("1,234.50" or "1.234,50")
     *
     * @throws InvalidArgumentException when the string is not an amount or is ambiguous ("1,234"), or the amount is not
     *                                  finite (INF, NAN)
     */
    public function setAmount(float|int|string $float): self
    {
        $this->amount = self::toAmount($float);

        return $this;
    }

    /**
     * @throws InvalidArgumentException when the category contains a line break
     */
    public function setCategory(?string $category): self
    {
        self::assertSingleLine($category, 'category');
        $this->category = $category;

        return $this;
    }

    /**
     * Adds a split line. Several splits can share a category.
     *
     * @param float|int|string $amount a string can group thousands, in either convention ("1,234.50" or "1.234,50")
     *
     * @throws InvalidArgumentException when the string is not an amount or is ambiguous ("1,234"), the amount is not
     *                                  finite (INF, NAN), or the category or memo contains a line break
     */
    public function addSplit(string $category, float|int|string $amount, ?string $memo = null): self
    {
        self::assertSingleLine($category, 'split category');
        self::assertSingleLine($memo, 'split memo');
        $this->splits[] = [
            'category' => $category,
            'amount' => self::toAmount($amount),
            'memo' => $memo,
        ];

        return $this;
    }

    /**
     * Removes every split of this category.
     */
    public function removeSplit(string $category): self
    {
        $this->splits = array_values(array_filter(
            $this->splits,
            static fn (array $split): bool => $split['category'] !== $category,
        ));

        return $this;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function markAsReconciled(): self
    {
        $this->status = Status::Reconciled;

        return $this;
    }

    public function markAsCleared(): self
    {
        $this->status = Status::Cleared;

        return $this;
    }

    public function markAsNotCleared(): self
    {
        $this->status = Status::NotCleared;

        return $this;
    }

    /**
     * The record in QIF, dates day first (DateFormat::DayFirst).
     */
    public function __toString(): string
    {
        return $this->toQif();
    }

    /**
     * The record in QIF.
     *
     * @param DateFormat $dateFormat order of the day and month in the date
     */
    public function toQif(DateFormat $dateFormat = DateFormat::DayFirst): string
    {
        $output = '!Type:' . $this->type?->value;

        if (null !== $this->date) {
            $output .= \PHP_EOL . DetailItems::Date->value . $this->date->format($dateFormat->writeFormat());
        }
        if (null !== $this->amount) {
            $output .= \PHP_EOL . DetailItems::Amount->value . self::formatAmount($this->amount);
        }
        if (null !== $this->category) {
            $output .= \PHP_EOL . DetailItems::Category->value . $this->category;
        }
        foreach ($this->splits as $split) {
            $output .= \PHP_EOL . DetailItems::SplitCategory->value . $split['category'] . \PHP_EOL . DetailItems::SplitAmount->value . self::formatAmount($split['amount']);
            if (null !== $split['memo']) {
                $output .= \PHP_EOL . DetailItems::SplitMemo->value . $split['memo'];
            }
        }
        $output .= \PHP_EOL . DetailItems::ClearedStatus->value . $this->status->value;
        if (null !== $this->description) {
            $output .= \PHP_EOL . DetailItems::Payee->value . $this->description;
        }
        if (null !== $this->address) {
            // QIF has one A line per address line
            foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $this->address)) as $addressLine) {
                $output .= \PHP_EOL . DetailItems::Address->value . $addressLine;
            }
        }
        if (null !== $this->memo) {
            $output .= \PHP_EOL . DetailItems::Memo->value . $this->memo;
        }

        return $output . \PHP_EOL . '^';
    }

    /**
     * @param string|null $address the lines of a multi-line address are separated by "\n"
     */
    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @throws InvalidArgumentException when the memo contains a line break
     */
    public function setMemo(?string $memo): self
    {
        self::assertSingleLine($memo, 'memo');
        $this->memo = $memo;

        return $this;
    }

    public function getType(): ?Types
    {
        return $this->type;
    }

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @return list<array{category: string, amount: float, memo: ?string}>
     */
    public function getSplits(): array
    {
        return $this->splits;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    /**
     * @return string|null the lines of a multi-line address are separated by "\n"
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * @throws InvalidArgumentException when the string is not an amount or is ambiguous, or the amount is not finite
     */
    private static function toAmount(float|int|string $amount): float
    {
        if (is_string($amount)) {
            return AmountParser::parse($amount);
        }

        // INF and NAN would be written "TINF" and "TNAN"
        $amount = (float) $amount;
        if (!is_finite($amount)) {
            throw new InvalidArgumentException(sprintf('%s is not an amount.', var_export($amount, true)));
        }

        return $amount;
    }

    /**
     * A QIF field is a single line: after a line break, the value would be read as other fields, or other records.
     *
     * @param string $field the field, for the message
     *
     * @throws InvalidArgumentException when the value contains a line break
     */
    private static function assertSingleLine(?string $value, string $field): void
    {
        if (null !== $value && false !== strpbrk($value, "\r\n")) {
            throw new InvalidArgumentException(sprintf('The %s "%s" contains a line break: QIF fields are single lines.', $field, addcslashes($value, "\r\n")));
        }
    }

    /**
     * Two decimals, more when the amount has them ("1.234" for currencies with 3 decimals), up to 15 significant digits.
     * Never in scientific notation or rounded to 14 digits, as PHP's float to string conversion does ("1.2345678901235E+14").
     */
    private static function formatAmount(float $amount): string
    {
        $decimals = max(2, 15 - strlen(number_format(abs($amount), 0, '.', '')));
        $formatted = number_format($amount, $decimals, '.', '');

        // Without the trailing zeros after the second decimal
        return substr($formatted, 0, max(strlen($formatted) - $decimals + 2, strlen(rtrim($formatted, '0'))));
    }
}
