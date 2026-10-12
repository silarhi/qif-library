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

use function array_key_exists;

use DateTimeImmutable;

use function in_array;

use InvalidArgumentException;
use RuntimeException;
use Silarhi\Qif\Enums\DateFormat;
use Silarhi\Qif\Enums\DecimalSeparator;
use Silarhi\Qif\Enums\DetailItems;
use Silarhi\Qif\Enums\Status;
use Silarhi\Qif\Enums\Types;
use Silarhi\Qif\Exception\ParseException;

use function sprintf;

class Parser
{
    /**
     * Lists that hold other records than transactions (accounts, categories, classes, securities, prices).
     */
    private const NON_TRANSACTION_LISTS = ['Account', 'Type:Cat', 'Type:Class', 'Type:Security', 'Type:Prices'];

    /**
     * @var Transaction[]
     */
    private array $transactions = [];

    /**
     * @param DecimalSeparator|null $decimalSeparator null to detect it from each amount, rejecting ambiguous ones ("1,234")
     */
    public function __construct(
        private readonly string $fileContent,
        private readonly string $separator = "\r\n",
        private readonly DateFormat $dateFormat = DateFormat::DAY_FIRST,
        private readonly ?DecimalSeparator $decimalSeparator = null,
    ) {
    }

    /**
     * @param DecimalSeparator|null $decimalSeparator null to detect it from each amount, rejecting ambiguous ones ("1,234")
     *
     * @throws ParseException when a value cannot be read without guessing
     */
    public static function parseFile(string $filePath, DateFormat $dateFormat = DateFormat::DAY_FIRST, ?DecimalSeparator $decimalSeparator = null): self
    {
        if (!is_readable($filePath)) {
            throw new RuntimeException("File not found or not readable: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new RuntimeException("Failed to read file: {$filePath}");
        }
        $parser = new self($content, dateFormat: $dateFormat, decimalSeparator: $decimalSeparator);
        $parser->parse();

        return $parser;
    }

    /**
     * @return Transaction[]
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Parses the content, replacing the transactions of a previous call.
     *
     * @return Transaction[]
     *
     * @throws ParseException when a value cannot be read without guessing
     */
    public function parse(): array
    {
        $cleanContent = preg_replace("/\xEF\xBB\xBF/", '', $this->fileContent);
        if (null === $cleanContent) {
            throw new RuntimeException('Failed to clean file content');
        }
        $line = strtok($cleanContent, $this->separator);

        $this->transactions = [];
        $type = null;
        $header = '';
        $inTransactionList = true;
        // Record being read: null until its first line
        $transaction = null;
        $transactionRaw = '';
        /** @var array{category: string, amount: ?float, memo: ?string}|null $split */
        $split = null;
        /** @var array<string, DateTimeImmutable> $dateCache */
        $dateCache = [];
        while (false !== $line) {
            $line = trim($line);
            $first = substr($line, 0, 1);
            $value = substr($line, 1);

            if ('!' === $first) {
                // Options (!Option:AutoSwitch, !Clear:AutoSwitch) do not start a list
                if (!str_starts_with($value, 'Option:') && !str_starts_with($value, 'Clear:')) {
                    // A record that misses its closing "^" ends with the list
                    if (null !== $transaction) {
                        $this->addTransaction($transaction, $transactionRaw, $split);
                        $transaction = null;
                        $split = null;
                    }
                    $header = $line;
                    $inTransactionList = !in_array($value, self::NON_TRANSACTION_LISTS, true);
                    $type = str_starts_with($value, 'Type:') ? Types::tryFrom(trim(substr($value, 5))) : null;
                }
            } elseif ($inTransactionList && '' !== $line) {
                if (null === $transaction) {
                    $transaction = new Transaction($type);
                    $transactionRaw = '' === $header ? '' : $header . $this->separator;
                }
                $transactionRaw .= $line . $this->separator;

                switch ($first) {
                    case DetailItems::DATE->value:
                        if ('' === $value) {
                            break;
                        }
                        if (!array_key_exists($value, $dateCache)) {
                            $dateCache[$value] = $this->parseDate($value);
                        }
                        $transaction->setDate($dateCache[$value]);
                        break;
                    case DetailItems::AMOUNT->value:
                    case DetailItems::AMOUNT_ALT->value:
                        if ('' !== $value) {
                            $transaction->setAmount($this->parseAmount($value));
                        }
                        break;
                    case DetailItems::MEMO->value:
                        $transaction->setMemo($value);
                        break;
                    case DetailItems::CLEARED_STATUS->value:
                        $transaction->setStatus(match ($value) {
                            'X', 'R' => Status::RECONCILED,
                            '*', 'c' => Status::CLEARED,
                            default => Status::NOT_CLEARED,
                        });
                        break;
                    case DetailItems::PAYEE->value:
                        $transaction->setDescription($value);
                        break;
                    case DetailItems::ADDRESS->value:
                        // One A line per address line
                        $address = $transaction->getAddress();
                        $transaction->setAddress(null === $address ? $value : $address . "\n" . $value);
                        break;
                    case DetailItems::CATEGORY->value:
                        $transaction->setCategory($value);
                        break;
                    case DetailItems::SPLIT_CATEGORY->value:
                        // Each S line starts a split
                        if (null !== $split) {
                            $this->addSplit($transaction, $split);
                        }
                        $split = ['category' => $value, 'amount' => null, 'memo' => null];
                        break;
                    case DetailItems::SPLIT_MEMO->value:
                        if (null !== $split) {
                            $split['memo'] = $value;
                        }
                        break;
                    case DetailItems::SPLIT_AMOUNT->value:
                        // Without an S line, $ is the transfer amount of an investment
                        if (null !== $split) {
                            $split['amount'] = $this->parseAmount($value);
                        }
                        break;
                    case '^':
                        $this->addTransaction($transaction, $transactionRaw, $split);
                        $transaction = null;
                        $split = null;
                        break;
                }
            }

            $line = strtok($this->separator);
        }

        // The last record can miss its closing "^"
        if (null !== $transaction) {
            $this->addTransaction($transaction, $transactionRaw, $split);
        }

        return $this->getTransactions();
    }

    /**
     * @param array{category: string, amount: ?float, memo: ?string}|null $split the split being read
     */
    private function addTransaction(Transaction $transaction, string $raw, ?array $split): void
    {
        if (null !== $split) {
            $this->addSplit($transaction, $split);
        }
        $transaction->_raw = $raw;
        $this->transactions[] = $transaction;
    }

    /**
     * @param array{category: string, amount: ?float, memo: ?string} $split
     */
    private function addSplit(Transaction $transaction, array $split): void
    {
        if (null === $split['amount']) {
            throw new ParseException(sprintf('The split "%s" has no amount ($ line).', $split['category']));
        }
        $transaction->addSplit($split['category'], $split['amount'], $split['memo']);
    }

    private function parseAmount(string $value): float
    {
        try {
            return AmountParser::parse($value, $this->decimalSeparator);
        } catch (InvalidArgumentException $e) {
            throw new ParseException($e->getMessage(), 0, $e);
        }
    }

    private function parseDate(string $value): DateTimeImmutable
    {
        // Quicken writes "25/12'23" for 2023 and pads with spaces ("1/ 1' 0")
        $normalizedDate = str_replace(["'", ' '], ['/', '0'], $value);

        foreach ($this->dateFormat->formats() as $format) {
            // "!" sets the time to midnight instead of the current time
            $date = DateTimeImmutable::createFromFormat('!' . $format, $normalizedDate);
            // A warning means an overflow ("31/02" read as 3 March): reject it instead of rolling over
            if (false !== $date && false === DateTimeImmutable::getLastErrors()) {
                return $date;
            }
        }

        throw new ParseException(sprintf('"%s" is not a valid date with %s (DateFormat::%s).', $value, DateFormat::DAY_FIRST === $this->dateFormat ? 'the day first' : 'the month first', $this->dateFormat->name));
    }
}
