<?php

namespace App\Data\IdentityMaps;

use App\Data\User;
use App\Singleton;

/**
 * @method static UserIdentityMap getInstance()
 */
class UserIdentityMap extends Singleton
{
    private $memory = [];

    /**
     * @param int $id
     * @return User|null
     */
    public function find(int $id)
    {
        if (isset($this->memory[$id])) {
            return $this->memory[$id];
        }

        return null;
    }

    /**
     * @param User $user
     */
    public function add(User $user)
    {
        $memory[$user->getId()] = $user;
    }

    /**
     * @param User $user
     */
    public function remove(User $user)
    {
        $id = $user->getId();

        if (isset($this->memory[$id])) {
            unset($this->memory[$id]);
        }
    }
}
