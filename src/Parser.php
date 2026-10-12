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
use function count;

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
        private readonly DateFormat $dateFormat = DateFormat::DayFirst,
        private readonly ?DecimalSeparator $decimalSeparator = null,
    ) {
    }

    /**
     * @param DecimalSeparator|null $decimalSeparator null to detect it from each amount, rejecting ambiguous ones ("1,234")
     *
     * @throws ParseException when a value cannot be read without guessing
     */
    public static function parseFile(string $filePath, DateFormat $dateFormat = DateFormat::DayFirst, ?DecimalSeparator $decimalSeparator = null): self
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
     * When it throws, getTransactions() returns the transactions read before the error.
     *
     * @return Transaction[]
     *
     * @throws ParseException when a value cannot be read without guessing
     */
    public function parse(): array
    {
        $content = preg_replace("/\xEF\xBB\xBF/", '', $this->fileContent);
        if (null === $content) {
            throw new RuntimeException('Failed to clean file content');
        }

        $this->transactions = [];
        $type = null;
        $header = '';
        $inTransactionList = true;
        // Lines of the record being read, null between records
        $transactionRaw = null;
        // Null until the record has a value: a record without any (a stray "^") is not a transaction
        $transaction = null;
        /** @var array{category: string, amount: ?float, memo: ?string, lineIndex: int}|null $split */
        $split = null;
        /** @var array<string, DateTimeImmutable> $dateCache */
        $dateCache = [];
        $lineBreak = $this->detectLineBreak($content);
        // Index of the line among the non-empty ones: lineNumber() turns it into a line number when it throws
        $lineIndex = -1;
        $line = strtok($content, $this->separator);
        while (false !== $line) {
            ++$lineIndex;
            $line = trim($line);
            $first = substr($line, 0, 1);
            $value = substr($line, 1);

            if ('!' === $first) {
                // Options (!Option:AutoSwitch, !Clear:AutoSwitch) do not start a list
                if (!str_starts_with($value, 'Option:') && !str_starts_with($value, 'Clear:')) {
                    // A record that misses its closing "^" ends with the list
                    if (null !== $transaction) {
                        $this->addTransaction($transaction, (string) $transactionRaw, $split, $content);
                    }
                    $transactionRaw = null;
                    $transaction = null;
                    $split = null;
                    $header = $line . $lineBreak;
                    $inTransactionList = !in_array($value, self::NON_TRANSACTION_LISTS, true);
                    $type = str_starts_with($value, 'Type:') ? Types::tryFrom(trim(substr($value, 5))) : null;
                }
            } elseif ($inTransactionList && '' !== $line) {
                $transactionRaw ??= $header;
                $transactionRaw .= $line . $lineBreak;

                if ('^' === $first) {
                    if (null !== $transaction) {
                        $this->addTransaction($transaction, $transactionRaw, $split, $content);
                    }
                    $transactionRaw = null;
                    $transaction = null;
                    $split = null;
                } elseif ('' !== $value || DetailItems::SplitCategory->value === $first) {
                    // An empty field is skipped like a missing one, but an S line always starts a split: the $ and E
                    // lines that follow it belong to that split
                    $transaction ??= new Transaction($type);

                    try {
                        switch ($first) {
                            case DetailItems::Date->value:
                                if (!array_key_exists($value, $dateCache)) {
                                    $dateCache[$value] = $this->parseDate($value);
                                }
                                $transaction->setDate($dateCache[$value]);
                                break;
                            case DetailItems::Amount->value:
                            case DetailItems::AmountAlt->value:
                                $transaction->setAmount(AmountParser::parse($value, $this->decimalSeparator));
                                break;
                            case DetailItems::Memo->value:
                                $transaction->setMemo($value);
                                break;
                            case DetailItems::ClearedStatus->value:
                                $transaction->setStatus(match ($value) {
                                    'X', 'R' => Status::Reconciled,
                                    '*', 'c' => Status::Cleared,
                                    default => Status::NotCleared,
                                });
                                break;
                            case DetailItems::Payee->value:
                                $transaction->setDescription($value);
                                break;
                            case DetailItems::Address->value:
                                // One A line per address line
                                $address = $transaction->getAddress();
                                $transaction->setAddress(null === $address ? $value : $address . "\n" . $value);
                                break;
                            case DetailItems::Category->value:
                                $transaction->setCategory($value);
                                break;
                            case DetailItems::SplitCategory->value:
                                if (null !== $split) {
                                    $this->addSplit($transaction, $split, $content);
                                }
                                $split = ['category' => $value, 'amount' => null, 'memo' => null, 'lineIndex' => $lineIndex];
                                break;
                            case DetailItems::SplitMemo->value:
                                // Without an S line, E and $ belong to the transfer of an investment
                                if (null !== $split) {
                                    $split['memo'] = $value;
                                }
                                break;
                            case DetailItems::SplitAmount->value:
                                if (null !== $split) {
                                    $split['amount'] = AmountParser::parse($value, $this->decimalSeparator);
                                }
                                break;
                        }
                    } catch (InvalidArgumentException $e) {
                        throw new ParseException($e->getMessage(), $this->lineNumber($content, $lineIndex), count($this->transactions), $e);
                    }
                }
            }

            $line = strtok($this->separator);
        }

        // The last record can miss its closing "^"
        if (null !== $transaction) {
            $this->addTransaction($transaction, (string) $transactionRaw, $split, $content);
        }

        return $this->getTransactions();
    }

    /**
     * The first line break of the content, kept in $_raw.
     */
    private function detectLineBreak(string $content): string
    {
        $position = strcspn($content, $this->separator);
        $lineBreak = substr($content, $position, 2);

        return match (true) {
            "\r\n" === $lineBreak && str_contains($this->separator, "\n") => "\r\n",
            '' === $lineBreak => "\n",
            default => $lineBreak[0],
        };
    }

    /**
     * The number, from 1, of the non-empty line at this index: the empty lines strtok() skipped are counted again.
     */
    private function lineNumber(string $content, int $lineIndex): int
    {
        $lineNumber = 1;
        $offset = 0;
        for ($index = 0;; ++$index) {
            $breaksLength = strspn($content, $this->separator, $offset);
            // "\r\n" ends one line, any other separator character ends a line on its own
            $lineNumber += $breaksLength - substr_count(substr($content, $offset, $breaksLength), "\r\n");
            if ($index === $lineIndex) {
                return $lineNumber;
            }
            $offset += $breaksLength;
            $offset += strcspn($content, $this->separator, $offset);
        }
    }

    /**
     * @param array{category: string, amount: ?float, memo: ?string, lineIndex: int}|null $split the split being read
     */
    private function addTransaction(Transaction $transaction, string $raw, ?array $split, string $content): void
    {
        if (null !== $split) {
            $this->addSplit($transaction, $split, $content);
        }
        $transaction->_raw = $raw;
        $this->transactions[] = $transaction;
    }

    /**
     * @param array{category: string, amount: ?float, memo: ?string, lineIndex: int} $split
     */
    private function addSplit(Transaction $transaction, array $split, string $content): void
    {
        if (null === $split['amount']) {
            throw new ParseException(sprintf('The split "%s" has no amount ($ line).', $split['category']), $this->lineNumber($content, $split['lineIndex']), count($this->transactions));
        }
        $transaction->addSplit($split['category'], $split['amount'], $split['memo']);
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

        throw new InvalidArgumentException(sprintf('"%s" is not a valid date with %s (DateFormat::%s).', $value, DateFormat::DayFirst === $this->dateFormat ? 'the day first' : 'the month first', $this->dateFormat->name));
    }
}
