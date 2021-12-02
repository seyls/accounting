<?php

/**
 * @var \Illuminate\Database\Eloquent\Factory $factory
 */

use Faker\Generator as Faker;

use Seyls\Accounting\Models\Entity;

$factory->define(
    Entity::class,
    function (Faker $faker) {
        return [
            'name' => $faker->company,
            'multi_currency' => $faker->boolean(),
        ];
    }
);
