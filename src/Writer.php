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

use function dirname;

use RuntimeException;
use Stringable;

/**
 * Class Writer
 *
 * @author MimoGraphix <mimographix@gmail.com>
 */
class Writer implements Stringable
{
    /**
     * @var Transaction[]
     */
    private array $transactions = [];

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
            $output[] = (string) $transaction;
        }

        return implode(\PHP_EOL, array_filter($output));
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
