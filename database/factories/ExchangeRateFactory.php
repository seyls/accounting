<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Faker\Generator as Faker;
use Carbon\Carbon;

use Seyls\Accounting\Models\ExchangeRate;
use Seyls\Accounting\Models\Currency;

$factory->define(
    ExchangeRate::class,
    function (Faker $faker) {
        return [
            'valid_from' => $faker->dateTimeThisMonth(),
            'valid_to' => Carbon::now(),
            'currency_id' => factory(Currency::class)->create()->id,
            'rate' => $faker->randomFloat(2, 1, 5),
        ];
    }
);
