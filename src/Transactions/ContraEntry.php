<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace Seyls\Accounting\Transactions;

use Seyls\Accounting\Models\Account;
use Seyls\Accounting\Models\LineItem;
use Seyls\Accounting\Models\Transaction;

use Seyls\Accounting\Exceptions\LineItemAccount;

use Seyls\Accounting\Exceptions\MainAccount;
use Seyls\Accounting\Exceptions\VatCharge;

class ContraEntry extends Transaction {
    
    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::CE;

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

    /**
     * Validate ContraEntry Main Account
     */
    public function save(array $options = []): bool
    {
        if (is_null($this->account) || $this->account->account_type != Account::BANK) {
            throw new MainAccount(self::PREFIX, Account::BANK);
        }

        return parent::save();
    }

    /**
     * Validate ContraEntry LineItem
     */
    public function addLineItem(LineItem $lineItem): bool
    {
        if ($lineItem->account->account_type != Account::BANK) {
            throw new LineItemAccount(self::PREFIX, [Account::BANK]);
        }

        if ($lineItem->vat['total'] > 0) {
            throw new VatCharge(self::PREFIX);
        }

        return parent::addLineItem($lineItem);
    }
}
