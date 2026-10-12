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
 * Cleared status values for QIF transactions
 */
enum Status: string
{
    /** Not cleared */
    case NotCleared = '';

    /** Cleared */
    case Cleared = 'c';

    /** Reconciled */
    case Reconciled = 'X';
}
