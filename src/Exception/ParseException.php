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

namespace Silarhi\Qif\Exception;

use RuntimeException;

use function sprintf;

use Throwable;

/**
 * Thrown when a QIF file contains a value that cannot be read without guessing (invalid date, ambiguous amount...).
 */
class ParseException extends RuntimeException
{
    /**
     * @param string $reason      what is wrong with the value, without its location
     * @param int    $lineNumber  the line of the value, from 1
     * @param int    $recordIndex the index the transaction would have had in Parser::getTransactions(), from 0
     */
    public function __construct(
        private readonly string $reason,
        private readonly int $lineNumber,
        private readonly int $recordIndex,
        ?Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Line %d (record index %d): %s', $lineNumber, $recordIndex, $reason), 0, $previous);
    }

    /**
     * What is wrong with the value, without its location.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * The line of the value, from 1. A split without amount is reported on its S line.
     */
    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    /**
     * The index the transaction would have had in Parser::getTransactions(), from 0: the number of transactions read
     * before it.
     */
    public function getRecordIndex(): int
    {
        return $this->recordIndex;
    }
}
