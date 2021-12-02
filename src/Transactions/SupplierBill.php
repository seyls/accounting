<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace Seyls\Accounting\Transactions;

use Seyls\Accounting\Interfaces\Buys;
use Seyls\Accounting\Interfaces\Clearable;

use Seyls\Accounting\Traits\Buying;
use Seyls\Accounting\Traits\Clearing;

use Seyls\Accounting\Models\Transaction;

class SupplierBill extends Transaction implements Buys, Clearable
{
    use Buying;
    use Clearing;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::BL;

    /**
     * Construct new ContraEntry
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $attributes['credited'] = true;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }
}
