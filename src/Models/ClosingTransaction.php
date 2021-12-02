<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2021, Germany
 * @license   MIT
 */

namespace Seyls\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Seyls\Accounting\Traits\Recycling;
use Seyls\Accounting\Traits\Segregating;
use Seyls\Accounting\Traits\ModelTablePrefix;

use Seyls\Accounting\Interfaces\Recyclable;
use Seyls\Accounting\Interfaces\Segregatable;

/**
 * Class ClosingTransactions
 *
 * @package Ekmungai\Eloquent-IFRS
 *
 * @property Entity $entity
 * @property collection $transactions
 * @property ReportingPeriod $reportingPeriod
 * @property Carbon $destroyed_at
 * @property Carbon $deleted_at
 */
class ClosingTransaction extends Model implements Segregatable, Recyclable
{
    use Segregating;
    use SoftDeletes;
    use Recycling;
    use ModelTablePrefix;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'reporting_period_id',
        'transaction_id',
        'currency_id',
        'entity_id',
    ];

    /**
     * Instance Identifier.
     *
     * @return string
     */
    public function toString($type = false)
    {
        $classname = explode('\\', self::class);
        $instanceName = $this->reportingPeriod->calendar_year . ' Forex Translation Transaction ' . $this->transaction->toString();
        return $type ? array_pop($classname) . ': ' . $instanceName : $instanceName;
    }

    /**
     * Model's Parent Entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Closing Transaction's Transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Closing Transaction's Currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Closing Transaction's Reporting Period.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function reportingPeriod()
    {
        return $this->belongsTo(ReportingPeriod::class);
    }

    /**
     * ClosingRate attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object)$this->attributes;
    }
}
