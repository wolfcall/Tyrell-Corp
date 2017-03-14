<?php

namespace App\Data\UoWs;

use App\Data\Mappers\UserMapper;
use App\Data\User;
use App\Singleton;

/**
 * @ignore Unused
 *
 * @method static UserUoW getInstance()
 */
class UserUoW extends Singleton {

    private $newList = [];
    private $changedList = [];
    private $deletedList = [];

    /**
     * @var UserMapper
     */
    private $mapper;

    protected function __construct() {
        parent::__construct();

        $this->mapper = UserMapper::getInstance();
    }

    public function registerNew(User $user) {
        $this->newList[] = $user;
    }

    public function registerDirty(User $user) {
        $this->changedList[] = $user;
    }

    public function registerDeleted(User $user) {
        $this->deletedList[] = $user;
    }

    public function commit() {
        $this->mapper->addMany($this->newList);
        $this->mapper->updateMany($this->changedList);
        $this->mapper->deleteMany($this->deletedList);

        // empty the lists after the commit
        $this->newList = [];
        $this->changedList = [];
        $this->deletedList = [];
    }

}
