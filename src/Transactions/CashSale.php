<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace Seyls\Accounting\Transactions;

use Seyls\Accounting\Interfaces\Sells;

use Seyls\Accounting\Traits\Selling;

use Seyls\Accounting\Models\Account;
use Seyls\Accounting\Models\Transaction;

use Seyls\Accounting\Exceptions\MainAccount;

class CashSale extends Transaction implements Sells
{
    use Selling;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CS;

    /**
     * Construct new CashSale
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $attributes['credited'] = false;
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }

    /**
     * Validate CashSale Main Account
     */
    public function save(array $options = []): bool
    {
        if (is_null($this->account) || $this->account->account_type != Account::BANK) {
            throw new MainAccount(self::PREFIX, Account::BANK);
        }

        return parent::save();
    }
}
