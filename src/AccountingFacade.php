<?php

namespace Seyls\Accounting;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Seyls\Accounting\Skeleton\SkeletonClass
 */
class AccountingFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'accounting';
    }
}
