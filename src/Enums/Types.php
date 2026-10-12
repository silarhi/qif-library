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

enum Types: string
{
    /** Cash Flow: Cash Account */
    case CASH = 'Cash';

    /** Cash Flow: Checking & Savings Account */
    case BANK = 'Bank';

    /** Cash Flow: Credit Card Account */
    case CREDIT_CARD = 'CCard';

    /** Investing: Investment Account */
    case INVESTMENT = 'Invst';

    /** Property & Debt: Asset */
    case OTHER_ASSET = 'Oth A';

    /** Property & Debt: Liability */
    case OTHER_LIABILITY = 'Oth L';

    /** Invoice (Quicken for Business only) */
    case INVOICE = 'Invoice';
}
