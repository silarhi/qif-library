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

use Silarhi\Qif\Enums\DetailItems;
use Silarhi\Qif\Enums\Status;
use Silarhi\Qif\Enums\Types;
use Stringable;

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
        $this->status = Status::NOT_CLEARED;
    }

    public function setDate(DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param float|int|string $float a string can group thousands, in either convention ("1,234.50" or "1.234,50")
     *
     * @throws InvalidArgumentException when the string is not an amount or is ambiguous ("1,234")
     */
    public function setAmount(float|int|string $float): self
    {
        $this->amount = is_string($float) ? AmountParser::parse($float) : (float) $float;

        return $this;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Adds a split line. Several splits can share a category.
     *
     * @param float|int|string $amount a string can group thousands, in either convention ("1,234.50" or "1.234,50")
     *
     * @throws InvalidArgumentException when the string is not an amount or is ambiguous ("1,234")
     */
    public function addSplit(string $category, float|int|string $amount, ?string $memo = null): self
    {
        $this->splits[] = [
            'category' => $category,
            'amount' => is_string($amount) ? AmountParser::parse($amount) : (float) $amount,
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
        $this->status = Status::RECONCILED;

        return $this;
    }

    public function markAsCleared(): self
    {
        $this->status = Status::CLEARED;

        return $this;
    }

    public function markAsNotCleared(): self
    {
        $this->status = Status::NOT_CLEARED;

        return $this;
    }

    public function __toString(): string
    {
        $output = '!Type:' . $this->type?->value;

        if (null !== $this->date) {
            $output .= \PHP_EOL . DetailItems::DATE->value . $this->date->format('d/m/Y');
        }
        if (null !== $this->amount) {
            $output .= \PHP_EOL . DetailItems::AMOUNT->value . $this->amount;
        }
        if (null !== $this->category) {
            $output .= \PHP_EOL . DetailItems::CATEGORY->value . $this->category;
        }
        foreach ($this->splits as $split) {
            $output .= \PHP_EOL . DetailItems::SPLIT_CATEGORY->value . $split['category'] . \PHP_EOL . DetailItems::SPLIT_AMOUNT->value . $split['amount'];
            if (null !== $split['memo']) {
                $output .= \PHP_EOL . DetailItems::SPLIT_MEMO->value . $split['memo'];
            }
        }
        $output .= \PHP_EOL . DetailItems::CLEARED_STATUS->value . $this->status->value;
        if (null !== $this->description) {
            $output .= \PHP_EOL . DetailItems::PAYEE->value . $this->description;
        }
        if (null !== $this->address) {
            // QIF has one A line per address line
            foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $this->address)) as $addressLine) {
                $output .= \PHP_EOL . DetailItems::ADDRESS->value . $addressLine;
            }
        }
        if (null !== $this->memo) {
            $output .= \PHP_EOL . DetailItems::MEMO->value . $this->memo;
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

    public function setMemo(?string $memo): self
    {
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
}
