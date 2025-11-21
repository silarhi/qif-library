<?php

declare(strict_types=1);

/*
 * This file is part of the CFONB Parser package.
 *
 * (c) Mário Čechovič <mimographix@gmail.com>
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace MimoGraphix\QIF;

use Carbon\Carbon;
use MimoGraphix\QIF\Enums\DetailItems;
use MimoGraphix\QIF\Enums\Status;
use MimoGraphix\QIF\Enums\Types;
use RuntimeException;

/**
 * Class Parser
 *
 * @author MimoGraphix <mimographix@gmail.com>
 */
class Parser
{
    /**
     * @var Transaction[]
     */
    private array $transactions = [];

    public function __construct(private readonly string $fileContent, private readonly string $separator = "\r\n")
    {
    }

    public static function parseFile(string $filePath): self
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new RuntimeException("Failed to read file: {$filePath}");
        }
        $parser = new self($content);
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
     * @return Transaction[]
     */
    public function parse(): array
    {
        $cleanContent = preg_replace("/\xEF\xBB\xBF/", '', $this->fileContent);
        if (null === $cleanContent) {
            throw new RuntimeException('Failed to clean file content');
        }
        $line = strtok($cleanContent, $this->separator);

        $lastType = null;
        $transaction = new Transaction($lastType);
        $transactionRaw = '';
        while (false !== $line) {
            $line = trim($line);
            $transactionRaw .= $line . $this->separator;

            $first = substr($line, 0, 1);
            $line = substr($line, 1);
            switch ($first) {
                case '!':   // Type
                    $lastTypeString = trim(str_replace('Type:', '', $line));
                    $lastType = Types::tryFrom($lastTypeString);
                    $transaction = new Transaction($lastType);
                    $transactionRaw = '!Type:' . $lastTypeString;
                    break;
                case DetailItems::D->value:
                    $transaction->setDate(Carbon::parse(str_replace("'", '.', $line)));
                    break;
                case DetailItems::T->value:
                case DetailItems::U->value:
                    $transaction->setAmount((float) str_replace(',', '', $line));
                    break;
                case DetailItems::M->value:
                    $transaction->setMemo($line);
                    break;
                case DetailItems::C->value:
                    $transaction->setStatus(match ($line) {
                        'X', 'R' => Status::RECONCILED,
                        '*', 'c' => Status::CLEARED,
                        default => Status::NOT_CLEARED,
                    });
                    break;
                case DetailItems::P->value:
                    $transaction->setDescription($line);
                    break;
                case DetailItems::A->value:
                    $transaction->setAddress($line);
                    break;
                case DetailItems::O->value:
                    break;
                case DetailItems::L->value:
                    $transaction->setCategory($line);
                    break;
                case '^':
                    $transaction->_raw = $transactionRaw;
                    $this->transactions[] = $transaction;
                    $transaction = new Transaction($lastType);
                    $transactionRaw = '!Type:' . (null !== $lastType ? $lastType->value : '');
                    break;
            }

            $line = strtok($this->separator);
        }

        return $this->getTransactions();
    }
}
