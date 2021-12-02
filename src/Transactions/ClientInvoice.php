<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace Seyls\Accounting\Transactions;


use Seyls\Accounting\Interfaces\Clearable;
use Seyls\Accounting\Interfaces\Sells;

use Seyls\Accounting\Traits\Selling;
use Seyls\Accounting\Traits\Clearing;

use Seyls\Accounting\Models\Transaction;

class ClientInvoice extends Transaction implements Sells, Clearable
{
    use Selling;
    use Clearing;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::IN;

    /**
     * Construct new ClientInvoice
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $attributes['credited'] = false;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }
}
