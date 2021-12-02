<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace Seyls\Accounting\Transactions;

use Seyls\Accounting\Interfaces\Assignable;

use Seyls\Accounting\Interfaces\Sells;

use Seyls\Accounting\Traits\Assigning;
use Seyls\Accounting\Traits\Selling;

use Seyls\Accounting\Models\Transaction;

class CreditNote extends Transaction implements Sells, Assignable
{
    use Selling;
    use Assigning;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CN;

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
