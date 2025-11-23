<?php

declare(strict_types=1);

/*
 * This file is part of the QIF Library package.
 *
 * (c) Mário Čechovič <mimographix@gmail.com>
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace MimoGraphix\QIF\Enums;

/**
 * Enum DetailItems
 *
 * @author MimoGraphix <mimographix@gmail.com>
 */
enum DetailItems: string
{
    // All
    /** Date. Leading zeroes on month and day can be skipped. Year can be either 4 digits or 2 digits or '6 (=2006). All D25 December 2006 */
    case D = 'D';

    /** Amount of the item. For payments, a leading minus sign is required. For deposits, either no sign or a leading plus sign is accepted. Do not include currency symbols ($, £, ¥, etc.). Comma separators between thousands are allowed. All T-1,234.50 */
    case T = 'T';

    /** Seems identical to T field (amount of item.) Both T and U are present in QIF files exported from Quicken 2015. All U-1,234.50 */
    case U = 'U';

    /** Memo—any text you want to record about the item. All Mgasoline for my car */
    case M = 'M';

    /** Cleared status. Values are blank (not cleared), "*" or "c" (cleared) and "X" or "R" (reconciled). All CR */
    case C = 'C';

    // Banking, Splits, Investment
    /** Number of the check. Can also be "Deposit", "Transfer", "Print", "ATM", "EFT". Banking, Splits N1001 */
    case N = 'N';

    // Banking, Splits
    /** Address of Payee. Up to 5 address lines are allowed. A 6th address line is a message that prints on the check. 1st line is normally the same as the Payee line—the name of the Payee. Banking, Splits A101 Main St. */
    case A = 'A';

    /** Category or Transfer and (optionally) Class. The literal values are those defined in the Quicken Category list. SubCategories can be indicated by a colon (":") followed by the subcategory literal. If the Quicken file uses Classes, this can be indicated by a slash ("/") followed by the class literal. For Investments, MiscIncX or MiscExpX actions, Category/class or transfer/class. (40 characters maximum) Banking, Splits LFuel:car */
    case L = 'L';

    // Banking, Investment
    /** Payee. Or a description for deposits, transfers, etc. Banking, Investment PStandard Oil, Inc. */
    case P = 'P';

    // Banking
    /** Flag this transaction as a reimbursable business expense. Banking F??? */
    case F = 'F';

    // Investment, Splits
    /** Amount for this split of the item. Same format as T field. Splits $1,000.50 */
    case AMNT = '$';

    // Split
    /** Split category. Same format as L (Categorization) field. (40 characters maximum) Splits Sgas from Esso */
    case S = 'S';

    /** Split memo—any text to go with this split item. Splits Ework trips */
    case E = 'E';

    /** Percent. Optional—used if splits are done by percentage. Splits %50 */
    case PERC = '%';

    // Investment
    /** Security name. Investment YIDS Federal Income */
    case Y = 'Y';

    /** Price. Investment I5.125 */
    case I = 'I';

    /** Quantity of shares (or split ratio, if Action is StkSplit). Investment Q4,896.201 */
    case Q = 'Q';

    /** Commission cost (generally found in stock trades) Investment O14.95 */
    case O = 'O';

    // Categories
    /** Budgeted amount - may be repeated many times for monthly budgets. Categories B85.00 */
    case B = 'B';

    // Invoices
    /** Extended data for Quicken Business. Followed by a second character subcode (see below) followed by content data. Invoices XI3 */
    case X = 'X';

    /** Ship-to address Invoices XAATTN: Receiving */
    case XA = 'XA';

    /** Invoice transaction type: 1 for invoice, 3 for payment Invoices XI1 */
    case XI = 'XI';

    /** Invoice due date Invoices XE6/17' 2 */
    case XE = 'XE';

    /** Tax account Invoices XC[*Sales Tax*] */
    case XC = 'XC';

    /** Tax rate Invoices XR7.70 */
    case XR = 'XR';

    /** Tax amount Invoices XT15.40 */
    case XT = 'XT';

    /** Line item description Invoices XSRed shoes */
    case XS = 'XS';

    /** Line item category name Invoices XNSHOES */
    case XN = 'XN';

    /** Line item quantity Invoices X#1 */
    case X_HASH = 'X#';

    /** Line item price per unit (multiply by X# for line item amount) Invoices X$150.00 */
    case X_AMNT = 'X$';

    /** Line item taxable flag Invoices XFT */
    case XF = 'XF';
}
