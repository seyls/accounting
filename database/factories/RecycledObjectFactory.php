<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Faker\Generator as Faker;

use Seyls\Accounting\Models\RecycledObject;
use Seyls\Accounting\User;

$factory->define(
    RecycledObject::class,
    function (Faker $faker) {
        return [
            'user_id' => factory(User::class)->create()->id,
            'recyclable_id' => factory(User::class)->create()->id,
            'recyclable_type' => User::class,
        ];
    }
);
