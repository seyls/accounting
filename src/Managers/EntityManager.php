<?php

namespace Seyls\Accounting\Managers;

use Seyls\Accounting\Models\Currency;
use Seyls\Accounting\Models\Entity;
use Seyls\Accounting\Services\EntityService;

class EntityManager implements EntityService
{
    protected Entity $entity;

    public function __construct(Entity $entity = null)
    {
        if(!is_null($entity)){
            $this->entity = $entity;
        }
    }

    /**
     * Create entity
     *
     * @param $name
     * @return Entity
     */
    public function createEntity($name) : Entity
    {
        $entity = Entity::create([
           'name' => $name
        ]);

        return $entity;
    }

    public function getEntity() : Entity
    {
        return $this->entity;
    }

    public function updateCurrency(Currency $currency)
    {
        $this->entity->currency_id = $currency->id;
        $this->entity->save();
    }
}