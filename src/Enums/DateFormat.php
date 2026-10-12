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

namespace Silarhi\Qif\Enums;

/**
 * Order of the day and month in the slash-separated dates of a QIF file (D lines).
 * ISO dates (2023-12-25, 23-12-25) are read whatever the order.
 */
enum DateFormat
{
    /** 25/12/2023, 25/12/23, 25/12'23 */
    case DayFirst;

    /** 12/25/2023, 12/25/23, 12/25'23 (US versions of Quicken) */
    case MonthFirst;

    /**
     * Formats to try in order: two-digit years first, as "Y" also matches a two-digit year.
     *
     * @return list<string>
     */
    public function formats(): array
    {
        return match ($this) {
            self::DayFirst => ['d/m/y', 'y-m-d', 'd/m/Y', 'Y-m-d'],
            self::MonthFirst => ['m/d/y', 'y-m-d', 'm/d/Y', 'Y-m-d'],
        };
    }

    /**
     * Format of the dates the writer outputs, with four-digit years.
     */
    public function writeFormat(): string
    {
        return match ($this) {
            self::DayFirst => 'd/m/Y',
            self::MonthFirst => 'm/d/Y',
        };
    }
}
