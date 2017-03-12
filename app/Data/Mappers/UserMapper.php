<?php

namespace App\Data\Mappers;

use App\Data\IdentityMaps\UserIdentityMap;
use App\Data\TDGs\UserTDG;
use App\Data\UoWs\UserUoW;
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
     * Handles the creation of a new object of type User
     *
     * @ignore Unused
     *
     * @param int $id
     * @param string $name
     * @param string $password
     * @return User
     */
    public function create(int $id, string $name, string $password): User {
        $user = new User($id, $name, $password);

        //Add the new Client to the list of existing objects in Live memory
        $this->identityMap->add($user);

        //Add to UoW registry so that we create it in the DB once the user is ready to commit everything.
        UserUoW::getInstance()->registerNew($user);

        return $user;
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
     * @ignore Unused
     *
     * @param int $id
     * @param string $name
     */
    public function set(int $id, string $name) {
        // First we fetch the client || We could have passed the Client as a Param. But this assumes you might not have
        // access to the instance of the desired object.
        $user = $this->find($id);

        // Mutator fuction to SET the new Amount.
        $user->setName($name);

        // We've modified something in the object so we Register the instance as Dirty in the UoW.
        UserUoW::getInstance()->registerDirty($user);
    }

    /**
     * Used to update that a user has attemped to make a reservation
     *
     * @param int $id
     * @param int $status
     */
    public function setAttempt($userId, $status) {
        $this->tdg->setAttempt($userId, $status);
    }

    /**
     * Used to check that a user has attemped to make a reservation
     *
     * @param int $id
     */
    public function getAttempt($userId) {
        return $this->tdg->getAttempt($userId);
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

    /**
     * @ignore Unused
     *
     * @param int $id
     */
    public function delete(int $id) {
        //Fire we fetch the client by checking the identity map
        $user = $this->identityMap->get($id);

        // If the identity map returned the object, then remove it from the IdentityMap
        if ($user !== null) {
            $this->identityMap->delete($user);
        }

        // We want to delete this object from out DB, so we simply register it as Deleted in the UoW
        UserUoW::getInstance()->registerDeleted($user);
    }

    /**
     * @ignore Unused
     *
     * Finalize changes
     */
    public function done() {
        UserUoW::getInstance()->commit();
    }

    /**
     * Pass the list of Users to add to DB to the TDG
     *
     * @ignore Unused
     *
     * @param array $newList
     */
    public function addMany(array $newList) {
        $this->tdg->addMany($newList);
    }

    /**
     * Pass the list of Users to update in the DB to the TDG
     *
     * @ignore Unused
     *
     * @param array $updateList
     */
    public function updateMany(array $updateList) {
        $this->tdg->updateMany($updateList);
    }

    /**
     * Pass the list of Users to remove from DB to the TDG
     *
     * @ignore Unused
     *
     * @param array $deleteList
     */
    public function deleteMany(array $deleteList) {
        $this->tdg->deleteMany($deleteList);
    }

}
