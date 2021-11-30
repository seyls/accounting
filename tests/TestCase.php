<?php

namespace Seyls\Accounting\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Faker\Factory as Faker;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
    }
}