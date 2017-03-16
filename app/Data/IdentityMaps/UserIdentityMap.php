<?php

namespace App\Data\IdentityMaps;

use App\Data\User;
use App\Singleton;

/**
 * @method static UserIdentityMap getInstance()
 */
class UserIdentityMap extends Singleton {

    private $memory = [];

    /**
     * Obtain a user from the Identity Map
     * 
     * @param int $id
     * @return User|null
     */
    public function get(int $id) {
        if (isset($this->memory[$id])) {
            return $this->memory[$id];
        }

        return null;
    }

    /**
     * Add a user to the Identity Map
     * 
     * @param User $user
     */
    public function add(User $user) {
        $memory[$user->getId()] = $user;
    }

    /**
     * Remove a user from the Identity Map
     *
     * @param User $user
     */
    public function delete(User $user) {
        $id = $user->getId();

        if (isset($this->memory[$id])) {
            unset($this->memory[$id]);
        }
    }

}
