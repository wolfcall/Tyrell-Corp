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
    public function get(int $id)
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
     * @ignore Unused
     *
     * @param User $user
     */
    public function delete(User $user)
    {
        $id = $user->getId();

        if (isset($this->memory[$id])) {
            unset($this->memory[$id]);
        }
    }
}
