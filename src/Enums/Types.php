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
    case Cash = 'Cash';

    /** Cash Flow: Checking & Savings Account */
    case Bank = 'Bank';

    /** Cash Flow: Credit Card Account */
    case CreditCard = 'CCard';

    /** Investing: Investment Account */
    case Investment = 'Invst';

    /** Property & Debt: Asset */
    case OtherAsset = 'Oth A';

    /** Property & Debt: Liability */
    case OtherLiability = 'Oth L';

    /** Invoice (Quicken for Business only) */
    case Invoice = 'Invoice';
}
