<?php

namespace App\Data\Mappers;

use App\Data\IdentityMaps\UserIdentityMap;
use App\Data\TDGs\UserTDG;
use App\Data\User;
use App\Singleton;

/**
 * @method static UserMapper getInstance()
 */
class UserMapper extends Singleton {

    /**
     * @var UserTDG
     */
    private $tdg;

    /**
     * @var UserIdentityMap
     */
    private $identityMap;

    /**
     * UserMapper constructor.
     */
    protected function __construct() {
        parent::__construct();

        $this->tdg = UserTDG::getInstance();
        $this->identityMap = UserIdentityMap::getInstance();
    }

    /**
     * Fetch message for retrieving a User with the given ID
     *
     * @param int $id
     * @return User
     */
    public function find(int $id): User {
        $user = $this->identityMap->get($id);
        $result = null;

        // If Identity Map doesn't have it then use TDG.
        if ($user === null) {
            $result = $this->tdg->find($id);
        }

        // If TDG doesn't have it then it doens't exist.
        if ($result !== null) {
            //We got the client from the TDG who got it from the DB and now the mapper must add it to the ClientIdentityMap
            $user = new User((int) $result[0], (string) $result[1], (string) $result[2], (double) $result[3]);
            $this->identityMap->add($user);
        }

        return $user;
    }

    /**
     * Returns the check to see if a student is part of Capstone or not
     *
     * @param int $userId
     * @return int
     */
    public function capstone(int $userId): int {
        return $this->tdg->capstone($userId);
    }

}
