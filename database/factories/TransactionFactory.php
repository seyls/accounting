<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Carbon\Carbon;
use Faker\Generator as Faker;

use Seyls\Accounting\Models\Transaction;
use Seyls\Accounting\Models\Account;
use Seyls\Accounting\Models\Currency;
use Seyls\Accounting\Models\ExchangeRate;

$factory->define(
    Transaction::class,
    function (Faker $faker) {
        return [
            'exchange_rate_id' => factory(ExchangeRate::class)->create()->id,
            'currency_id' => factory(Currency::class)->create()->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'transaction_date' => Carbon::now(),
            'transaction_no' => $faker->word,
            'transaction_type' => $faker->randomElement(array_keys(config('accounting')['transactions'])),
            'reference' => $faker->word,
            'narration' => $faker->sentence,
            'credited' =>  true,
        ];
    }
);
