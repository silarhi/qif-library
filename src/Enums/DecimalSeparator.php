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
 * Decimal separator of the amounts of a QIF file. The other character is the thousands separator.
 */
enum DecimalSeparator: string
{
    /** 1,234.50 */
    case POINT = '.';

    /** 1.234,50 */
    case COMMA = ',';

    public function thousandsSeparator(): string
    {
        return match ($this) {
            self::POINT => ',',
            self::COMMA => '.',
        };
    }
}
