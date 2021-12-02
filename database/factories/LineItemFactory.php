<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

use Seyls\Accounting\Models\LineItem;
use Seyls\Accounting\Models\Account;
use Seyls\Accounting\Models\Transaction;

$factory->define(
    LineItem::class,
    function (Faker $faker) {
        return [
            'transaction_id' => factory(Transaction::class)->create()->id,
            'account_id' => factory(Account::class)->create([
                'category_id' => null
            ])->id,
            'narration' => $faker->sentence,
            'quantity' => $faker->randomNumber(),
            'amount' => $faker->randomFloat(2, 0, 200),
        ];
    }
);
