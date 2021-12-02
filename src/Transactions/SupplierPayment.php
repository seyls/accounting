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
use Seyls\Accounting\Interfaces\Fetchable;

use Seyls\Accounting\Traits\Assigning;
use Seyls\Accounting\Traits\Fetching;

use Seyls\Accounting\Models\Account;
use Seyls\Accounting\Models\LineItem;
use Seyls\Accounting\Models\Transaction;

use Seyls\Accounting\Exceptions\LineItemAccount;
use Seyls\Accounting\Exceptions\MainAccount;
use Seyls\Accounting\Exceptions\VatCharge;

class SupplierPayment extends Transaction implements Assignable
{
    use Assigning;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::PY;

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
     * Validate SupplierPayment Main Account
     */
    public function save(array $options = []): bool
    {
        if (is_null($this->account) || $this->account->account_type != Account::PAYABLE) {
            throw new MainAccount(self::PREFIX, Account::PAYABLE);
        }

        return parent::save();
    }

    /**
     * Validate SupplierPayment LineItems
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
