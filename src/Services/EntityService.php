<?php

namespace Seyls\Accounting\Services;

use Seyls\Accounting\Models\Entity;

interface EntityService
{
    /**
     * Creates accounting entity.
     * @return Entity $accounting entity
     */
    public function createEntity($name) : Entity ;

    /**
     * Return the accounting entity.
     * @param $id
     * @return Entity
     */
    public function getEntity() : Entity;
}