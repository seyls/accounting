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

use Seyls\Accounting\Traits\Buying;

use Seyls\Accounting\Models\Account;
use Seyls\Accounting\Models\Transaction;

use Seyls\Accounting\Exceptions\MainAccount;

class CashPurchase extends Transaction implements Buys
{
    use Buying;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CP;

    /**
     * Construct new CashPurchase
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $attributes['credited'] = true;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }

    /**
     * Validate CashPurchase Main Account
     */
    public function save(array $options = []): bool
    {
        if (is_null($this->account) || $this->account->account_type != Account::BANK) {
            throw new MainAccount(self::PREFIX, Account::BANK);
        }

        return parent::save();
    }
}
