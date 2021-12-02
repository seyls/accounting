<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Carbon\Carbon;
use Faker\Generator as Faker;

use Seyls\Accounting\Transactions\JournalEntry;
use Seyls\Accounting\Models\Transaction;
use Seyls\Accounting\Models\Account;

$factory->define(
    JournalEntry::class,
    function (Faker $faker) {
        return [
            'account_id' => factory(Account::class)->create()->id,
            'date' => Carbon::now(),
            'narration' => $faker->word,
            'transaction_type' => Transaction::JN,
            'amount' => $faker->randomFloat(2),
        ];
    }
);
