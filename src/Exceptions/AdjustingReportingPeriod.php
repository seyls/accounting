<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace Seyls\Accounting\Exceptions;

use Seyls\Accounting\Models\ReportingPeriod;
use Seyls\Accounting\Models\Transaction;

class AdjustingReportingPeriod extends IFRSException
{
    /**
     * Adjusting Reporting Period Exception
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = null, int $code = null)
    {
        $type = Transaction::getType(Transaction::JN);
        $error = "Only " . $type . " Transactions can be posted to a reporting period whose status is " . ReportingPeriod::ADJUSTING;

        parent::__construct($error . $message, $code);
    }
}
