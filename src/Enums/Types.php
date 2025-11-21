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
 * Enum Types
 *
 * @author MimoGraphix <mimographix@gmail.com>
 */
enum Types: string
{
    /** Cash Flow: Cash Account */
    case CASH = 'Cash';

    /** Cash Flow: Checking & Savings Account */
    case BANK = 'Bank';

    /** Cash Flow: Credit Card Account */
    case CCARD = 'CCard';

    /** Investing: Investment Account */
    case INVST = 'Invst';

    /** Property & Debt: Asset */
    case OTHA = 'Oth A';

    /** Property & Debt: Liability */
    case OTHL = 'Oth L';

    /** Invoice (Quicken for Business only) */
    case Invoice = 'Invoice';
}
