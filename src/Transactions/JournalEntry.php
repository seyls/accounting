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
use Seyls\Accounting\Interfaces\Clearable;

use Seyls\Accounting\Models\LineItem;

use Seyls\Accounting\Traits\Assigning;
use Seyls\Accounting\Traits\Clearing;

use Seyls\Accounting\Models\Transaction;

use Seyls\Accounting\Exceptions\InvalidVatRate;
use Seyls\Accounting\Exceptions\MissingMainAccountAmount;
use Seyls\Accounting\Exceptions\MultipleVatError;

/**
 * Class JournalEntry
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property ExchangeRate $exchangeRate
 * @property Account $account
 * @property Currency $currency
 * @property Carbon $date
 * @property string $reference
 * @property string $transaction_no
 * @property string $transaction_type
 * @property string $narration
 * @property bool $credited
 * @property float $amount
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */

class JournalEntry extends Transaction implements Assignable, Clearable
{
    use Assigning;
    use Clearing;

    /**
     * Transaction Number prefix
     *
     * @var string
     */
    const PREFIX = Transaction::JN;

    /**
     * Construct new JournalEntry
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        if (!isset($attributes['credited'])) {
            $attributes['credited'] = true;
        }
        $attributes['transaction_type'] = self::PREFIX;

        parent::__construct($attributes);
    }

    /**
     * Add LineItem to Transaction LineItems.
     *
     * @param LineItem $lineItem
     */
    public function addLineItem(LineItem $lineItem): bool
    {
        if ($this->compound && count($lineItem->vat) > 1) {
            throw new MultipleVatError('Compound Journal Entries cannot have Vat');
        }

        $success = parent::addLineItem($lineItem);
        
        if($success && $this->compound){
            parent::addCompoundEntry(['id' => $lineItem->account_id, 'amount' => $lineItem->amount * $lineItem->quantity], $lineItem->credited);
        }
        return $success;
    }

    /**
     * Relate LineItems to Transaction.
     */
    public function save(array $options = []): bool
    {

        if($this->compound && (is_null($this->main_account_amount) || $this->main_account_amount == 0)){
            throw new MissingMainAccountAmount();
        }

        if($this->compound){
            parent::addCompoundEntry([
                    'id' => $this->account_id, 
                    'amount' => $this->main_account_amount
                ], 
                $this->credited
            );
        }
        
        return parent::save();
    }
}
