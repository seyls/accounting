<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace Seyls\Accounting\Interfaces;

/**
 *
 * @author emung
 */
interface Buys
{
    /**
     * Validate Buying Transaction Main Account.
     *
     * @return void
     */
    public function save(): bool;

    /**
     * Validate Buying Transaction LineItems.
     *
     * @return void
     */
    public function post(): void;
}
