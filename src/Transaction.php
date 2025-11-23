<?php

declare(strict_types=1);

/*
 * This file is part of the QIF Library package.
 *
 * (c) Mário Čechovič <mimographix@gmail.com>
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace MimoGraphix\QIF;

use function array_key_exists;

use DateTimeImmutable;
use Exception;

use function is_scalar;

use MimoGraphix\QIF\Enums\DetailItems;
use MimoGraphix\QIF\Enums\Status;
use MimoGraphix\QIF\Enums\Types;

use function sprintf;

use Stringable;

/**
 * Class Transaction
 *
 * @author MimoGraphix <mimographix@gmail.com>
 */
class Transaction implements Stringable
{
    private ?DateTimeImmutable $date = null;

    private ?string $description = null;

    private ?float $amount = null;

    private ?string $category = null;

    /**
     * @var array<string, array{amount: float, memo: ?string}>
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

    public function setAmount(float|int|string $float): self
    {
        $this->amount = (float) $float;

        return $this;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function addSplit(string $splitName, float|int|string $amount, ?string $memo = null): self
    {
        if (array_key_exists($splitName, $this->splits)) {
            throw new Exception(sprintf('Split "%s" already exists in this transaction.', $splitName));
        }

        $this->splits[$splitName] = [
            'amount' => (float) $amount,
            'memo' => $memo,
        ];

        return $this;
    }

    public function removeSplit(string $splitName): self
    {
        unset($this->splits[$splitName]);

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
        $output = [
            '!Type:' . $this->type?->value,
            $this->renderDateLineIfNotNull(),
            $this->renderIfNotNull(DetailItems::T->value, $this->amount),
            $this->renderIfNotNull(DetailItems::L->value, $this->category),
            $this->renderSplits(),
            $this->renderIfNotNull(DetailItems::C->value, $this->status->value),
            $this->renderIfNotNull(DetailItems::P->value, $this->description),
            '^',
        ];

        return implode(\PHP_EOL, array_filter($output));
    }

    private function renderDateLineIfNotNull(): string|false
    {
        if ($this->date instanceof DateTimeImmutable) {
            return $this->renderIfNotNull(DetailItems::D->value, $this->date->format('d/m/Y'));
        }

        return false;
    }

    private function renderIfNotNull(string $characterKey, mixed $value = null): string|false
    {
        if (null !== $value) {
            if (!is_scalar($value) && !($value instanceof Stringable)) {
                return false;
            }

            return $characterKey . (string) $value;
        }

        return false;
    }

    private function renderSplits(): string
    {
        $output = [];
        foreach ($this->splits as $name => $split) {
            $output[] = $this->renderIfNotNull(DetailItems::S->value, $name);
            $output[] = $this->renderIfNotNull(DetailItems::AMNT->value, $split['amount']);
            $output[] = $this->renderIfNotNull(DetailItems::E->value, $split['memo'] ?? null);
        }

        return implode(\PHP_EOL, array_filter($output));
    }

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
     * @return array<string, array{amount: float, memo: ?string}>
     */
    public function getSplits(): array
    {
        return $this->splits;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }
}
