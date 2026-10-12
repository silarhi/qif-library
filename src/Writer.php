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

use function dirname;

use RuntimeException;
use Silarhi\Qif\Enums\DateFormat;
use Stringable;

class Writer implements Stringable
{
    /**
     * @var Transaction[]
     */
    private array $transactions = [];

    /**
     * @param DateFormat $dateFormat order of the day and month in the dates: pass the format the file was parsed with
     *                               to write it back the same way
     */
    public function __construct(private readonly DateFormat $dateFormat = DateFormat::DayFirst)
    {
    }

    public function addTransaction(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }

    /**
     * @return Transaction[]
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    public function __toString(): string
    {
        $output = [];

        foreach ($this->transactions as $transaction) {
            $output[] = $transaction->toQif($this->dateFormat);
        }

        return implode(\PHP_EOL, array_filter($output, static fn (string $transaction): bool => '' !== $transaction));
    }

    public function saveToFile(string $filePath): void
    {
        if (!is_writable(dirname($filePath))) {
            throw new RuntimeException('Directory not writable: ' . dirname($filePath));
        }

        $file = fopen($filePath, 'w');
        if (false === $file) {
            throw new RuntimeException("Unable to open file: {$filePath}");
        }
        fwrite($file, (string) $this);
        fclose($file);
    }
}
