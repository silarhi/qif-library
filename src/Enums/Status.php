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

namespace MimoGraphix\QIF\Enums;

/**
 * Enum Status
 *
 * Cleared status values for QIF transactions
 *
 * @author MimoGraphix <mimographix@gmail.com>
 */
enum Status: string
{
    /** Not cleared */
    case NOT_CLEARED = '';

    /** Cleared */
    case CLEARED = 'c';

    /** Reconciled */
    case RECONCILED = 'X';
}
