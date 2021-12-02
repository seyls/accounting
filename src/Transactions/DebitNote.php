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
use Seyls\Accounting\Interfaces\Buys;

use Seyls\Accounting\Traits\Assigning;
use Seyls\Accounting\Traits\Buying;

use Seyls\Accounting\Models\Transaction;

class DebitNote extends Transaction implements Buys, Assignable
{
    use Buying;
    use Assigning;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::DN;

    /**
     * Construct new ContraEntry
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
